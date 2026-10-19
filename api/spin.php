<?php
/**
 * Server-Authoritative Spin Engine API
 * Cornerstone Asset Managers - Customer Appreciation Month Raffle
 *
 * Handles probability distribution, attempt tracking, race-condition prevention,
 * outcome persistence, and winner records.
 */

session_start();

header('Content-Type: application/json; charset=utf-8');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error'   => 'method_not_allowed',
        'message' => 'Method not allowed. Only POST is accepted.'
    ]);
    exit;
}

// Ensure customer is authenticated via ticket
$customerId = $_SESSION['customer_id'] ?? null;
$entryId    = $_SESSION['customer_entry_id'] ?? null;

if (!$customerId || !$entryId) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'unauthenticated',
        'message' => 'Please enter your raffle ticket number to participate.'
    ]);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/mailer.php';

if (!$db_connected || !$pdo) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'database_unavailable',
        'message' => 'The system is temporarily unable to reach the database. Please try again shortly.'
    ]);
    exit;
}

try {
    // Start atomic transaction
    $pdo->beginTransaction();

    // 1. Lock customer entry to prevent concurrent race conditions
    $entryStmt = $pdo->prepare("
        SELECT 
            e.id AS entry_id,
            e.customer_id,
            e.entry_status,
            c.name AS customer_name,
            c.phone AS customer_phone,
            c.email AS customer_email
        FROM entries e
        INNER JOIN customers c ON e.customer_id = c.id
        WHERE e.id = ? AND e.customer_id = ?
        FOR UPDATE
    ");
    $entryStmt->execute([$entryId, $customerId]);
    $entry = $entryStmt->fetch();

    if (!$entry) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error'   => 'entry_not_found',
            'message' => 'Raffle ticket could not be found.'
        ]);
        exit;
    }

    if ($entry['entry_status'] === 'disqualified') {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error'   => 'disqualified',
            'message' => 'This raffle entry is not eligible for participation.'
        ]);
        exit;
    }

    // 2. Check if customer already won a prize (rule: once won, no further spins permitted)
    $winnerStmt = $pdo->prepare("
        SELECT w.id, w.claim_code, w.draw_date, p.name AS prize_name 
        FROM winners w
        LEFT JOIN prizes p ON w.prize_id = p.id
        WHERE w.customer_id = ? OR w.entry_id = ?
        LIMIT 1
    ");
    $winnerStmt->execute([$customerId, $entryId]);
    $existingWin = $winnerStmt->fetch();

    if ($existingWin || $entry['entry_status'] === 'won') {
        $pdo->rollBack();
        $prizeName = $existingWin['prize_name'] ?? 'a prize';
        echo json_encode([
            'success' => false,
            'error'   => 'already_won',
            'message' => "You have already won {$prizeName}! Each customer may win at most one prize.",
            'winner_info' => [
                'prize_name' => $prizeName,
                'claim_code' => $existingWin['claim_code'] ?? '',
                'draw_date'  => $existingWin['draw_date'] ?? ''
            ]
        ]);
        exit;
    }

    // 3. Count existing spin attempts
    $spinsStmt = $pdo->prepare("SELECT COUNT(*) FROM spins WHERE customer_id = ? FOR UPDATE");
    $spinsStmt->execute([$customerId]);
    $currentSpinsCount = (int) $spinsStmt->fetchColumn();

    if ($currentSpinsCount >= 3) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error'   => 'spins_exhausted',
            'message' => 'You have used all 3 of your raffle spins for Customer Appreciation Month. Thank you for participating!',
            'spins_used' => 3,
            'spins_remaining' => 0
        ]);
        exit;
    }

    $nextSpinNumber = $currentSpinsCount + 1; // 1, 2, or 3

    // 4. Load configured active prizes and validate probability distribution
    $prizesStmt = $pdo->query("
        SELECT id, name, segment_label, probability, is_win, color, text_color, icon, sort_order 
        FROM prizes 
        WHERE is_active = 1 
        ORDER BY sort_order ASC
    ");
    $prizes = $prizesStmt->fetchAll();

    if (empty($prizes)) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'missing_prizes',
            'message' => 'Raffle prize configuration is currently unavailable.'
        ]);
        exit;
    }

    // Validate that probabilities sum to 100.0% (+- 0.05 margin of precision)
    $totalProbability = 0.0;
    foreach ($prizes as $p) {
        $totalProbability += (float) $p['probability'];
    }

    if (abs($totalProbability - 100.0) > 0.1) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'invalid_probabilities',
            'message' => "System error: Configured prize probabilities sum to {$totalProbability}%, but must sum to 100%."
        ]);
        exit;
    }

    // 5. Server-side authoritative outcome selection
    // Uses high-precision random number (1 to 1,000,000 / 10,000 = 0.0001% to 100.0000%)
    $randVal = random_int(1, 1000000) / 10000.0;
    $cumulative = 0.0;
    $selectedPrize = null;
    $selectedSegmentIndex = 0;

    foreach ($prizes as $index => $prize) {
        $cumulative += (float) $prize['probability'];
        if ($randVal <= $cumulative) {
            $selectedPrize = $prize;
            $selectedSegmentIndex = $index;
            break;
        }
    }

    // Fallback safety to last segment if floating point edge
    if (!$selectedPrize) {
        $selectedPrize = end($prizes);
        $selectedSegmentIndex = count($prizes) - 1;
    }

    $isWin = (int) $selectedPrize['is_win'] === 1;
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? null;

    // 6. Record spin attempt in database
    $insertSpinStmt = $pdo->prepare("
        INSERT INTO spins (customer_id, entry_id, spin_number, prize_id, is_win, spun_at, ip_address)
        VALUES (?, ?, ?, ?, ?, NOW(), ?)
    ");
    $insertSpinStmt->execute([
        $customerId,
        $entryId,
        $nextSpinNumber,
        $selectedPrize['id'],
        $isWin ? 1 : 0,
        $clientIp
    ]);

    $claimCode = null;
    $formattedDate = date('F j, Y - g:i A');
    $winnerRecord = null;
    $ticketCode = 'CAM-' . str_pad($entryId, 5, '0', STR_PAD_LEFT);

    if ($isWin) {
        // Generate unique, cryptographically random verification claim code
        $randomHex = strtoupper(bin2hex(random_bytes(4)));
        $claimCode = 'CLM-' . substr($randomHex, 0, 4) . '-' . substr($randomHex, 4, 4);

        // Record winner record
        $insertWinnerStmt = $pdo->prepare("
            INSERT INTO winners (entry_id, customer_id, prize_id, claim_code, claim_status, email_status, draw_date)
            VALUES (?, ?, ?, ?, 'unclaimed', 'pending', NOW())
        ");
        $insertWinnerStmt->execute([
            $entryId,
            $customerId,
            $selectedPrize['id'],
            $claimCode
        ]);
        $winnerId = (int) $pdo->lastInsertId();

        // Mark entry as won so customer cannot spin again
        $updateEntryStmt = $pdo->prepare("UPDATE entries SET entry_status = 'won' WHERE id = ?");
        $updateEntryStmt->execute([$entryId]);

        $winnerRecord = [
            'id'          => $winnerId,
            'claim_code'  => $claimCode,
            'ticket_code' => $ticketCode,
            'draw_date'   => $formattedDate
        ];
    }

    // Commit database transaction
    $pdo->commit();

    // 7. Dispatch Email Notification if won (post-commit to ensure winner is committed)
    $emailNotice = null;
    if ($isWin && $winnerRecord) {
        $customerData = [
            'id'    => $customerId,
            'name'  => $entry['customer_name'],
            'email' => $entry['customer_email'],
            'phone' => $entry['customer_phone']
        ];
        $emailResult = sendWinnerPrizeEmail($pdo, $customerData, $selectedPrize, $winnerRecord);
        $emailNotice = $emailResult['message'];
    }

    $spinsRemaining = $isWin ? 0 : max(0, 3 - $nextSpinNumber);

    echo json_encode([
        'success'         => true,
        'spin_number'     => $nextSpinNumber,
        'spins_remaining' => $spinsRemaining,
        'is_win'          => $isWin,
        'prize'           => [
            'id'            => (int) $selectedPrize['id'],
            'name'          => $selectedPrize['name'],
            'segment_label' => $selectedPrize['segment_label'],
            'segment_index' => $selectedSegmentIndex,
            'icon'          => $selectedPrize['icon'],
            'color'         => $selectedPrize['color'],
            'text_color'    => $selectedPrize['text_color']
        ],
        'claim_code'      => $claimCode,
        'ticket_code'     => $ticketCode,
        'draw_date'       => $formattedDate,
        'email_notice'    => $emailNotice,
        'message'         => $isWin
            ? "Congratulations! You won the {$selectedPrize['name']}!"
            : ($spinsRemaining > 0 
                ? "You got Try Again! You have {$spinsRemaining} attempt" . ($spinsRemaining === 1 ? '' : 's') . " remaining."
                : "Try Again! You have completed all 3 spin attempts for Customer Appreciation Month.")
    ]);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Spin engine database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'database_error',
        'message' => 'A database error occurred while processing your spin attempt. Please try again.'
    ]);
    exit;
} catch (Throwable $t) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Spin engine error: ' . $t->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'system_error',
        'message' => 'An unexpected error occurred while processing your spin. Please try again.'
    ]);
    exit;
}

