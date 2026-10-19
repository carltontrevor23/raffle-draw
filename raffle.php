<?php
/**
 * Customer Interactive Spinning Wheel Portal
 * Cornerstone Asset Managers - Customer Appreciation Month Raffle
 */

session_start();

require_once __DIR__ . '/config/db.php';

// Handle Exit / Ticket Reset
if (isset($_GET['action']) && $_GET['action'] === 'exit') {
    unset($_SESSION['customer_id'], $_SESSION['customer_entry_id'], $_SESSION['customer_ticket']);
    header('Location: raffle.php');
    exit;
}

$authError = '';
$ticketInput = trim($_GET['ticket'] ?? ($_POST['ticket_code'] ?? ''));

// Handle Direct Ticket Authentication or Submission
if ($ticketInput !== '') {
    if (!$db_connected || !$pdo) {
        $authError = 'Database is currently unreachable. Please ensure MySQL is running in XAMPP.';
    } else {
        try {
            // Normalize ticket code: strip spaces, allow CAM-00008, CAM00008, 8, or phone number
            $rawTicket = trim($ticketInput);
            $cleanCode = preg_replace('/^CAM[-_]?/i', '', $rawTicket);
            $numericId = ctype_digit($cleanCode) ? (int)$cleanCode : 0;

            // Search by entry ID or customer phone number
            $stmt = $pdo->prepare("
                SELECT 
                    e.id AS entry_id,
                    e.entry_status,
                    c.id AS customer_id,
                    c.name AS customer_name,
                    c.phone AS customer_phone,
                    c.email AS customer_email
                FROM entries e
                INNER JOIN customers c ON e.customer_id = c.id
                WHERE e.id = ? 
                   OR c.phone = ? 
                   OR c.email = ?
                LIMIT 1
            ");
            $stmt->execute([$numericId, $rawTicket, $rawTicket]);
            $foundCustomer = $stmt->fetch();

            if ($foundCustomer) {
                $_SESSION['customer_id']       = (int) $foundCustomer['customer_id'];
                $_SESSION['customer_entry_id'] = (int) $foundCustomer['entry_id'];
                $_SESSION['customer_ticket']   = 'CAM-' . str_pad($foundCustomer['entry_id'], 5, '0', STR_PAD_LEFT);
                header('Location: raffle.php');
                exit;
            } else {
                $authError = 'No raffle registration found for ticket or phone number "' . htmlspecialchars($ticketInput) . '". Please check your ticket number or register below.';
            }
        } catch (PDOException $e) {
            $authError = 'Database error during lookup: ' . $e->getMessage();
        }
    }
}

// Check if customer is currently logged in
$customerId = $_SESSION['customer_id'] ?? null;
$entryId    = $_SESSION['customer_entry_id'] ?? null;

$customerData   = null;
$customerSpins  = [];
$customerWinner = null;
$prizesList     = [];

if ($customerId && $entryId && $db_connected && $pdo) {
    try {
        // 1. Fetch customer details
        $stmt = $pdo->prepare("
            SELECT 
                e.id AS entry_id,
                e.entry_status,
                c.id AS customer_id,
                c.name AS customer_name,
                c.phone AS customer_phone,
                c.email AS customer_email
            FROM entries e
            INNER JOIN customers c ON e.customer_id = c.id
            WHERE e.id = ? AND c.id = ?
            LIMIT 1
        ");
        $stmt->execute([$entryId, $customerId]);
        $customerData = $stmt->fetch();

        if ($customerData) {
            // 2. Fetch customer spin history
            $spinsStmt = $pdo->prepare("
                SELECT s.*, p.name AS prize_name, p.segment_label, p.icon, p.is_win
                FROM spins s
                INNER JOIN prizes p ON s.prize_id = p.id
                WHERE s.customer_id = ?
                ORDER BY s.spin_number ASC
            ");
            $spinsStmt->execute([$customerId]);
            $customerSpins = $spinsStmt->fetchAll();

            // 3. Fetch winner record if won
            $winStmt = $pdo->prepare("
                SELECT w.*, p.name AS prize_name, p.segment_label, p.icon
                FROM winners w
                INNER JOIN prizes p ON w.prize_id = p.id
                WHERE w.customer_id = ? OR w.entry_id = ?
                LIMIT 1
            ");
            $winStmt->execute([$customerId, $entryId]);
            $customerWinner = $winStmt->fetch();

            // 4. Fetch active prizes for wheel rendering
            $prizesStmt = $pdo->query("
                SELECT id, name, segment_label, probability, is_win, color, text_color, icon, sort_order
                FROM prizes
                WHERE is_active = 1
                ORDER BY sort_order ASC
            ");
            $prizesList = $prizesStmt->fetchAll();
        } else {
            // Invalid session
            unset($_SESSION['customer_id'], $_SESSION['customer_entry_id'], $_SESSION['customer_ticket']);
            $customerId = null;
        }
    } catch (PDOException $e) {
        $authError = 'Database error loading raffle data: ' . $e->getMessage();
    }
}

$spinsUsed = count($customerSpins);
$hasWon    = (bool) $customerWinner;
$spinsLeft = $hasWon ? 0 : max(0, 3 - $spinsUsed);
$ticketCode = $customerData ? ('CAM-' . str_pad($customerData['entry_id'], 5, '0', STR_PAD_LEFT)) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Raffle Wheel | Cornerstone Asset Managers</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Libre+Franklin:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=2.2">
    <link rel="stylesheet" href="css/raffle-theme.css?v=2.2">
</head>
<body class="raffle-app raffle-wheel-page">

    <!-- Top Navigation Bar -->
    <nav class="nav" id="navbar" aria-label="Main navigation">
        <div class="container">
            <div class="nav-inner">
                <a href="index.php" class="logo logo-brand" aria-label="Cornerstone Asset Managers - Customer Appreciation">
                    <span class="logo-badge">
                        <img src="images/cornerstone-logo.png" alt="Cornerstone Asset Managers" class="brand-logo-img">
                    </span>
                    <span class="logo-divider" aria-hidden="true"></span>
                    <span class="logo-text">
                        <span class="campaign-main">Customer Appreciation</span>
                        <span class="campaign-sub">Raffle Draw System</span>
                    </span>
                </a>
                <ul class="nav-links">
                    <li><a href="index.php">Overview</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="raffle.php" class="active" aria-current="page">Spin Wheel</a></li>
                </ul>
                <div class="nav-cta">
                    <?php if ($customerData): ?>
                        <span class="user-pill" style="font-family: monospace;"><?php echo htmlspecialchars($ticketCode); ?></span>
                        <a href="raffle.php?action=exit" class="btn btn-outline" style="padding: 7px 14px; font-size: 11px;">Change Ticket</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">Get a Ticket</a>
                        <a href="admin/login.php" class="btn btn-outline">Admin</a>
                    <?php endif; ?>
                </div>
                <button class="mobile-menu-btn" id="mobileMenuBtn" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </nav>
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    <aside class="mobile-menu" id="mobileMenu" aria-hidden="true">
        <button class="mobile-menu-close" id="mobileMenuClose" type="button" aria-label="Close menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        <div class="mobile-menu-brand">
            <span class="logo-badge">
                <img src="images/cornerstone-logo.png" alt="Cornerstone Asset Managers" class="brand-logo-img">
            </span>
        </div>
        <ul class="mobile-nav-links">
            <li><a href="index.php">Overview</a></li>
            <li><a href="register.php">Register</a></li>
            <li><a href="raffle.php" class="active" aria-current="page">Spin Wheel</a></li>
        </ul>
        <div class="mobile-menu-cta">
            <?php if ($customerData): ?>
                <a href="raffle.php?action=exit" class="btn btn-outline">Exit Ticket (<?php echo htmlspecialchars($ticketCode); ?>)</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary">Register for Free</a>
                <a href="admin/login.php" class="btn btn-outline">Admin Login</a>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Main Container -->
    <main class="main-content raffle-main-content">
        <div class="container">

            <?php if (!$customerData): ?>
                <!-- Ticket Entry Card for Unauthenticated Customers -->
                <section class="raffle-entry-section" aria-labelledby="raffle-entry-heading">
                <div class="contact-form form-card raffle-form-card raffle-login-card">
                    <div class="section-label">Raffle Entrance</div>
                    <h2 id="raffle-entry-heading">Enter Your Raffle Ticket</h2>
                    <p class="raffle-form-intro">
                        Please enter your official Customer Appreciation ticket code or registered phone number to access your 3 raffle spins.
                    </p>

                    <?php if (!empty($authError)): ?>
                        <div class="status-box danger" style="margin-bottom: 1.25rem;">
                            <span></span>
                            <div><?php echo htmlspecialchars($authError); ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="raffle.php">
                        <div class="form-group">
                            <label for="ticket_code" class="form-label">
                                Ticket Code or Phone Number <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="ticket_code" 
                                name="ticket_code" 
                                class="form-input" 
                                placeholder="e.g. CAM-00008 or 0704240379" 
                                value="<?php echo htmlspecialchars($ticketInput); ?>"
                                required
                                autofocus
                            >
                            <div class="form-helper">
                                Provided upon registration (e.g. <code>CAM-00008</code> or numeric ticket number).
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem;">
                            <button type="submit" class="btn btn-primary btn-block">
                                 Access My Raffle Spins
                            </button>
                        </div>

                        <div style="text-align: center; margin-top: 1.5rem; font-size: 13px; color: var(--text-muted);">
                            Don't have a ticket yet? <a href="register.php" style="color: var(--gold-light); text-decoration: underline;">Register here for free</a>
                        </div>
                    </form>
                </div>
                </section>

            <?php else: ?>
                <!-- Authenticated Customer Raffle Stage -->
                <section class="raffle-session" aria-labelledby="raffle-welcome-heading">

                <!-- Header Banner -->
                <header class="raffle-session-header">
                    <div class="hero-badge"><span class="dot"></span>Customer Appreciation Month</div>
                    <h1 id="raffle-welcome-heading">
                        Welcome, <span class="gold"><?php echo htmlspecialchars($customerData['customer_name']); ?></span>
                    </h1>
                    <p>
                        Each valued client receives up to <strong>3 raffle spins</strong>. Spin the wheel below to celebrate with Cornerstone Asset Managers!
                    </p>
                </header>

                <!-- Attempts Progress Tracker -->
                <div class="attempts-tracker">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <?php 
                            $spinRecord = null;
                            foreach ($customerSpins as $s) {
                                if ((int)$s['spin_number'] === $i) {
                                    $spinRecord = $s;
                                    break;
                                }
                            }
                            $isAttemptWon  = $spinRecord && (int)$spinRecord['is_win'] === 1;
                            $isAttemptTry  = $spinRecord && (int)$spinRecord['is_win'] === 0;
                            $isCurrent     = !$hasWon && ($spinsUsed + 1 === $i);
                            
                            $pillClass = 'attempt-pill';
                            if ($isAttemptWon) {
                                $pillClass .= ' won';
                            } elseif ($isAttemptTry) {
                                $pillClass .= ' tryagain';
                            } elseif ($isCurrent) {
                                $pillClass .= ' active';
                            }
                        ?>
                        <div class="<?php echo $pillClass; ?>" id="pill-attempt-<?php echo $i; ?>">
                            <span class="attempt-num">Attempt <?php echo $i; ?> of 3</span>
                            <span class="attempt-val" id="pill-text-<?php echo $i; ?>">
                                <?php 
                                    if ($isAttemptWon) {
                                        echo htmlspecialchars($spinRecord['segment_label'] ?: $spinRecord['prize_name']);
                                    } elseif ($isAttemptTry) {
                                        echo 'Try Again';
                                    } elseif ($isCurrent) {
                                        echo 'Ready to Spin';
                                    } else {
                                        echo '— Unused —';
                                    }
                                ?>
                            </span>
                        </div>
                    <?php endfor; ?>
                </div>

                <!-- Live Winner Announcement Card (if already won or when won) -->
                <div id="winner-announcement-card" class="winner-card" style="<?php echo $customerWinner ? 'display: block;' : 'display: none;'; ?>">
                    <span class="prize-tag">Official Raffle Prize Winner</span>
                    <h2 class="prize-headline" id="win-headline">
                        Congratulations! You Won <?php echo $customerWinner ? htmlspecialchars($customerWinner['prize_name']) : ''; ?>!
                    </h2>
                    <p style="color: var(--text-muted); font-size: 15px; margin-bottom: 1rem;">
                        Thank you for your business and partnership with Cornerstone Asset Managers.
                    </p>

                    <div class="claim-code-box">
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--gold-light); letter-spacing: 0.1em; margin-bottom: 4px;">
                            OFFICIAL VERIFICATION CLAIM CODE
                        </div>
                        <div class="claim-code-val" id="win-claim-code">
                            <?php echo $customerWinner ? htmlspecialchars($customerWinner['claim_code']) : ''; ?>
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                            Ticket: <strong><?php echo htmlspecialchars($ticketCode); ?></strong> &bull; Won on <span id="win-date"><?php echo $customerWinner ? htmlspecialchars(date('M j, Y', strtotime($customerWinner['draw_date']))) : ''; ?></span>
                        </div>
                    </div>

                    <div style="max-width: 580px; margin: 18px auto 0 auto; background: rgba(0,0,0,0.3); border-radius: 8px; padding: 14px 18px; text-align: left; font-size: 13px; color: #c4bea8;">
                        <strong style="color: var(--gold-light); display: block; margin-bottom: 6px;">Next Steps to Claim Your Prize:</strong>
                        1. A confirmation notice has been prepared for your email (<strong><?php echo htmlspecialchars($customerData['customer_email']); ?></strong>).<br>
                        2. Present this verification code and a valid photo ID at the Cornerstone Asset Managers reception desk.<br>
                        3. All prizes must be verified and collected within 30 days.
                    </div>
                </div>

                <!-- Try Again Alert Banner -->
                <div id="try-again-banner" class="status-box warning" style="display: none; margin: 1rem 0;">
                    <span>🔄</span>
                    <div id="try-again-banner-text">
                        Try Again! You have attempts remaining. Spin the wheel again!
                    </div>
                </div>

                <!-- Main Interactive Wheel Grid -->
                <div class="wheel-layout">
                    
                    <!-- Left: Wheel Stage -->
                    <div class="wheel-stage">
                        <div class="wheel-wrapper">
                            <canvas id="raffle-wheel-canvas" width="480" height="480"></canvas>
                        </div>

                        <!-- Spin Button -->
                        <div style="text-align: center; margin-top: 8px;">
                            <button 
                                type="button" 
                                id="btn-spin-wheel" 
                                class="btn-spin <?php echo ($spinsLeft > 0 && !$hasWon) ? 'pulse' : ''; ?>"
                                <?php echo ($spinsLeft === 0 || $hasWon) ? 'disabled' : ''; ?>
                            >
                                <span id="btn-spin-label">
                                    <?php 
                                        if ($hasWon) {
                                            echo 'PRIZE AWARDED';
                                        } elseif ($spinsLeft === 0) {
                                            echo 'SPINS COMPLETED';
                                        } else {
                                            echo 'SPIN THE WHEEL';
                                        }
                                    ?>
                                </span>
                            </button>
                        </div>

                        <!-- Status Notice -->
                        <div id="spin-status-msg" style="margin-top: 14px; font-size: 13px; color: var(--text-muted); text-align: center; min-height: 20px;">
                            <?php 
                                if ($hasWon) {
                                    echo "You have won a prize! Your participation in the raffle is complete.";
                                } elseif ($spinsLeft === 0) {
                                    echo "All 3 attempts have been used. Thank you for celebrating with us!";
                                } else {
                                    echo "You have <strong id='spins-left-display' style='color: var(--gold-light);'>{$spinsLeft}</strong> of 3 spin attempt" . ($spinsLeft === 1 ? '' : 's') . " remaining.";
                                }
                            ?>
                        </div>
                    </div>

                    <!-- Right: Client Ticket & Spin History Sidebar -->
                    <div class="customer-sidebar-card">
                        <h3>Your Ticket Details</h3>
                        
                        <div class="client-meta-row">
                            <span class="client-meta-label">Ticket Code:</span>
                            <span class="client-meta-value" style="font-family: monospace; color: var(--gold-light);">
                                <?php echo htmlspecialchars($ticketCode); ?>
                            </span>
                        </div>
                        <div class="client-meta-row">
                            <span class="client-meta-label">Customer Name:</span>
                            <span class="client-meta-value"><?php echo htmlspecialchars($customerData['customer_name']); ?></span>
                        </div>
                        <div class="client-meta-row">
                            <span class="client-meta-label">Phone:</span>
                            <span class="client-meta-value"><?php echo htmlspecialchars($customerData['customer_phone']); ?></span>
                        </div>
                        <div class="client-meta-row">
                            <span class="client-meta-label">Email:</span>
                            <span class="client-meta-value" style="font-size: 12px; word-break: break-all;"><?php echo htmlspecialchars($customerData['customer_email']); ?></span>
                        </div>
                        <div class="client-meta-row">
                            <span class="client-meta-label">Spins Remaining:</span>
                            <span class="client-meta-value" id="sidebar-spins-left" style="color: var(--gold-light);">
                                <?php echo $spinsLeft; ?> / 3
                            </span>
                        </div>
                        <div class="client-meta-row">
                            <span class="client-meta-label">Ticket Status:</span>
                            <span class="client-meta-value">
                                <?php if ($hasWon): ?>
                                    <span class="badge badge-success">Winner</span>
                                <?php elseif ($spinsLeft === 0): ?>
                                    <span class="badge badge-secondary">Exhausted</span>
                                <?php else: ?>
                                    <span class="badge badge-info">Active (Eligible)</span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <!-- Spin History Table -->
                        <h4 style="margin: 24px 0 12px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--gold-light);">
                            Spin History
                        </h4>
                        
                        <div id="spin-history-container">
                            <?php if (empty($customerSpins)): ?>
                                <p id="spin-history-empty" style="font-size: 12px; color: var(--text-muted); font-style: italic;">
                                    No spins recorded yet. Hit the spin button to begin!
                                </p>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: 8px;" id="spin-history-list">
                                    <?php foreach ($customerSpins as $spin): ?>
                                        <div style="background: #100f0d; border: 1px solid var(--border-color); border-radius: 6px; padding: 8px 12px; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <strong>Attempt #<?php echo (int)$spin['spin_number']; ?>:</strong>
                                                <span><?php echo (int)$spin['is_win'] === 1 ? (htmlspecialchars($spin['prize_name'])) : '🔄 Try Again'; ?></span>
                                            </div>
                                            <span style="color: var(--text-muted); font-size: 10px;">
                                                <?php echo date('g:i A', strtotime($spin['spun_at'])); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); text-align: center;">
                            <a href="raffle.php?action=exit" class="btn btn-outline btn-block" style="font-size: 12px; padding: 9px 16px;">
                                Sign Out 
                            </a>
                        </div>
                    </div>

                </div>

                </section>
            <?php endif; ?>

        </div>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Cornerstone Asset Managers &bull; Customer Appreciation Month</p>
            <p style="font-size: 0.8rem; margin-top: 0.25rem;">A celebration of the clients who make our journey possible.</p>
        </div>
    </footer>

    <!-- Wheel and App Scripts -->
    <script src="js/wheel.js"></script>
    <script src="js/main.js"></script>

    <?php if ($customerData && !empty($prizesList)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const prizes = <?php echo json_encode($prizesList); ?>;
            let spinsLeft = <?php echo (int)$spinsLeft; ?>;
            let hasWon = <?php echo $hasWon ? 'true' : 'false'; ?>;

            const wheelCanvas = document.getElementById('raffle-wheel-canvas');
            const spinBtn = document.getElementById('btn-spin-wheel');
            const spinBtnLabel = document.getElementById('btn-spin-label');
            const spinStatusMsg = document.getElementById('spin-status-msg');
            const spinsLeftDisplay = document.getElementById('spins-left-display');
            const sidebarSpinsLeft = document.getElementById('sidebar-spins-left');
            const winnerCard = document.getElementById('winner-announcement-card');
            const tryAgainBanner = document.getElementById('try-again-banner');
            const tryAgainBannerText = document.getElementById('try-again-banner-text');

            if (!wheelCanvas || !spinBtn) return;

            // Initialize Canvas Wheel with configured prizes
            const wheel = new RaffleWheel('raffle-wheel-canvas', {
                segments: prizes
            });

            const updateTrackerPill = (spinNumber, isWin, prizeName) => {
                const pill = document.getElementById(`pill-attempt-${spinNumber}`);
                const pillText = document.getElementById(`pill-text-${spinNumber}`);
                if (!pill || !pillText) return;

                pill.classList.remove('active');
                if (isWin) {
                    pill.classList.add('won');
                    pillText.textContent = `${prizeName}`;
                } else {
                    pill.classList.add('tryagain');
                    pillText.textContent = '🔄 Try Again';
                }

                // If spins remain and not won, mark next pill active
                if (!isWin && spinNumber < 3) {
                    const nextPill = document.getElementById(`pill-attempt-${spinNumber + 1}`);
                    const nextText = document.getElementById(`pill-text-${spinNumber + 1}`);
                    if (nextPill && nextText) {
                        nextPill.classList.add('active');
                        nextText.textContent = '✨ Ready to Spin';
                    }
                }
            };

            const appendHistoryRow = (spinNumber, isWin, prizeName) => {
                const emptyMsg = document.getElementById('spin-history-empty');
                if (emptyMsg) emptyMsg.remove();

                let list = document.getElementById('spin-history-list');
                if (!list) {
                    list = document.createElement('div');
                    list.id = 'spin-history-list';
                    list.style.cssText = 'display: flex; flex-direction: column; gap: 8px;';
                    document.getElementById('spin-history-container').appendChild(list);
                }

                const item = document.createElement('div');
                item.style.cssText = 'background: #100f0d; border: 1px solid var(--border-color); border-radius: 6px; padding: 8px 12px; font-size: 12px; display: flex; justify-content: space-between; align-items: center;';
                item.innerHTML = `
                    <div>
                        <strong>Attempt #${spinNumber}:</strong>
                        <span>${isWin ? (prizeName) : '🔄 Try Again'}</span>
                    </div>
                    <span style="color: var(--text-muted); font-size: 10px;">Just now</span>
                `;
                list.prepend(item);
            };

            spinBtn.addEventListener('click', async () => {
                if (spinBtn.disabled || hasWon || spinsLeft <= 0) return;

                // Disable spin button during communication & animation
                spinBtn.disabled = true;
                spinBtn.classList.remove('pulse');
                spinBtnLabel.textContent = 'SPINNING...';
                spinStatusMsg.textContent = 'Contacting server to determine outcome...';
                tryAgainBanner.style.display = 'none';

                try {
                    // Call backend spin engine
                    const response = await fetch('api/spin.php', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });

                    let data;
                    try {
                        data = await response.json();
                    } catch {
                        throw new Error('Server returned an unreadable response. Please try again.');
                    }

                    if (!response.ok || !data || !data.success) {
                        const errMsg = (data && data.message) ? data.message : 'Spin attempt could not be processed.';
                        throw new Error(errMsg);
                    }

                    spinStatusMsg.textContent = 'Spinning the wheel... Best of luck!';

                    // Server returned authoritative prize outcome
                    const targetSegmentIndex = data.prize.segment_index;

                    // Animate canvas wheel to target segment
                    wheel.spinTo(targetSegmentIndex, () => {
                        // Animation completed
                        spinsLeft = data.spins_remaining;
                        hasWon = data.is_win;

                        // Update attempts pills & history
                        updateTrackerPill(data.spin_number, data.is_win, data.prize.segment_label || data.prize.name);
                        appendHistoryRow(data.spin_number, data.is_win, data.prize.name);

                        // Update counters
                        if (sidebarSpinsLeft) {
                            sidebarSpinsLeft.textContent = `${spinsLeft} / 3`;
                        }
                        if (spinsLeftDisplay) {
                            spinsLeftDisplay.textContent = spinsLeft;
                        }

                        if (data.is_win) {
                            // WINNER CELEBRATION!
                            spinBtnLabel.textContent = 'PRIZE AWARDED';
                            spinBtn.disabled = true;
                            spinStatusMsg.textContent = `Congratulations! You have won the ${data.prize.name}!`;

                            document.getElementById('win-headline').textContent = `Congratulations! You Won ${data.prize.name}!`;
                            document.getElementById('win-claim-code').textContent = data.claim_code;
                            document.getElementById('win-date').textContent = data.draw_date;
                            winnerCard.style.display = 'block';
                            winnerCard.scrollIntoView({ behavior: 'smooth', block: 'center' });

                        } else {
                            // TRY AGAIN
                            if (spinsLeft > 0) {
                                spinBtn.disabled = false;
                                spinBtn.classList.add('pulse');
                                spinBtnLabel.textContent = 'SPIN AGAIN';
                                spinStatusMsg.textContent = `You got Try Again! You have ${spinsLeft} attempt${spinsLeft === 1 ? '' : 's'} remaining.`;
                                
                                tryAgainBannerText.textContent = `Try Again! You have ${spinsLeft} spin attempt${spinsLeft === 1 ? '' : 's'} left. Give it another shot!`;
                                tryAgainBanner.style.display = 'flex';
                            } else {
                                spinBtn.disabled = true;
                                spinBtnLabel.textContent = 'SPINS COMPLETED';
                                spinStatusMsg.textContent = 'All 3 attempts have been used. Thank you for celebrating with Cornerstone Asset Managers!';
                                
                                tryAgainBannerText.textContent = 'You have used all 3 spin attempts for Customer Appreciation Month. Thank you for participating!';
                                tryAgainBanner.style.display = 'flex';
                            }
                        }
                    });

                } catch (error) {
                    spinBtn.disabled = false;
                    spinBtn.classList.add('pulse');
                    spinBtnLabel.textContent = 'SPIN THE WHEEL';
                    spinStatusMsg.textContent = error.message || 'An error occurred. Please try again.';
                    alert(error.message || 'Spin failed. Please try again.');
                }
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>
