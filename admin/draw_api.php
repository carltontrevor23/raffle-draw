<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

// Session Authentication Guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Please log in as an administrator.'
    ]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only POST is accepted.'
    ]);
    exit;
}

// Database Connection
require_once __DIR__ . '/../config/db.php';

if (!$db_connected || !$pdo) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection unavailable. Ensure MySQL is running in XAMPP.'
    ]);
    exit;
}

try {
    //Retrieve ONE random eligible entry from MySQL. Only entries with entry_status = 'valid' are eligible
    $selectStmt = $pdo->prepare("
        SELECT 
            e.id AS entry_id, 
            e.customer_id, 
            c.name AS customer_name, 
            c.phone AS customer_phone, 
            c.email AS customer_email
        FROM entries e
        INNER JOIN customers c ON e.customer_id = c.id
        WHERE e.entry_status = 'valid'
        ORDER BY RAND()
        LIMIT 1
    ");
    $selectStmt->execute();
    $selectedEntry = $selectStmt->fetch();

    if (!$selectedEntry) {
        // Handle scenario where no eligible entries exist
        echo json_encode([
            'success' => false,
            'message' => 'There are no eligible raffle entries available to draw from. All participants have already won or no tickets exist.'
        ]);
        exit;
    }

    // Record winner & mark entry status
    $pdo->beginTransaction();

    // Select a prize for the admin draw from configured active winning prizes
    $prizeStmt = $pdo->query("SELECT id, name FROM prizes WHERE is_win = 1 ORDER BY RAND() LIMIT 1");
    $prize = $prizeStmt->fetch();
    $prizeId = $prize ? (int)$prize['id'] : 5;
    $prizeName = $prize ? $prize['name'] : 'Customer Appreciation Prize';

    $claimCode = 'CLM-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2)));

    // Record winner in winners table with prize and claim information
    $insertWinnerStmt = $pdo->prepare('INSERT INTO winners (entry_id, customer_id, prize_id, claim_code, claim_status, email_status, draw_date) VALUES (?, ?, ?, ?, "unclaimed", "pending", NOW())');
    $insertWinnerStmt->execute([$selectedEntry['entry_id'], $selectedEntry['customer_id'], $prizeId, $claimCode]);
    $winnerId = (int) $pdo->lastInsertId();

    // Update entry_status to 'won' so this entry cannot be selected again
    $updateEntryStmt = $pdo->prepare("UPDATE entries SET entry_status = 'won' WHERE id = ?");
    $updateEntryStmt->execute([$selectedEntry['entry_id']]);

    $pdo->commit();

    // Query updated counts
    $totalEntries    = (int) $pdo->query('SELECT COUNT(*) FROM entries')->fetchColumn();
    $eligibleEntries = (int) $pdo->query("SELECT COUNT(*) FROM entries WHERE entry_status = 'valid'")->fetchColumn();
    $totalWinners    = (int) $pdo->query('SELECT COUNT(*) FROM winners')->fetchColumn();

    $ticketCode = 'CAM-' . str_pad($selectedEntry['entry_id'], 5, '0', STR_PAD_LEFT);
    $formattedDate = date('F j, Y - g:i A');

    // Return authoritatively determined winner to the frontend
    echo json_encode([
        'success' => true,
        'winner' => [
            'winner_id'   => $winnerId,
            'entry_id'    => (int) $selectedEntry['entry_id'],
            'ticket_code' => $ticketCode,
            'name'        => $selectedEntry['customer_name'],
            'prize_name'  => $prizeName,
            'claim_code'  => $claimCode,
            'phone'       => $selectedEntry['customer_phone'],
            'email'       => $selectedEntry['customer_email'],
            'draw_date'   => $formattedDate,
            'raw_date'    => date('M j, Y - g:i A')
        ],
        'stats' => [
            'total'    => $totalEntries,
            'eligible' => $eligibleEntries,
            'winners'  => $totalWinners
        ]
    ]);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Raffle draw database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'The draw could not be completed because of a database error. Please check the server log.'
    ]);
    exit;
}
