<?php
/**
 * Customer Appreciation Month Raffle Draw System
 * Admin Dashboard (Phase 5)
 */

session_start();

// 1. Session Authentication Guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. Database Connection
require_once __DIR__ . '/../config/db.php';

$totalEntries    = 0;
$eligibleEntries = 0;
$totalWinners    = 0;
$winnersList     = [];
$dbFetchError    = null;

// 3. Fetch Dashboard Metrics & Previous Winners
if ($db_connected && $pdo) {
    try {
        // Total entries
        $stmt = $pdo->query('SELECT COUNT(*) FROM entries');
        $totalEntries = (int) $stmt->fetchColumn();

        // Eligible entries (status = 'valid')
        $stmt = $pdo->query("SELECT COUNT(*) FROM entries WHERE entry_status = 'valid'");
        $eligibleEntries = (int) $stmt->fetchColumn();

        // Total winners
        $stmt = $pdo->query('SELECT COUNT(*) FROM winners');
        $totalWinners = (int) $stmt->fetchColumn();

        // Previous winners list with customer details
        $stmt = $pdo->query('
            SELECT 
                w.id AS winner_id,
                w.draw_date,
                e.id AS entry_id,
                c.name AS customer_name,
                c.phone AS customer_phone,
                c.email AS customer_email
            FROM winners w
            INNER JOIN entries e ON w.entry_id = e.id
            INNER JOIN customers c ON e.customer_id = c.id
            ORDER BY w.draw_date DESC
        ');
        $winnersList = $stmt->fetchAll();

    } catch (PDOException $e) {
        $dbFetchError = 'Failed to load dashboard statistics: ' . $e->getMessage();
    }
} else {
    $dbFetchError = 'Database is currently unreachable. Please make sure MySQL is running in XAMPP.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Customer Appreciation Month Raffle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Libre+Franklin:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../templatemo_610_aurum_gold/templatemo-aurum-gold.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/raffle-theme.css">
</head>
<body class="raffle-app">

    <nav class="nav" id="navbar">
        <div class="container">
            <div class="nav-inner">
                <a href="index.php" class="logo">Customer <span>Appreciation</span></a>
                <ul class="nav-links">
                    <li><a href="#dashboard" class="active" aria-current="page">Dashboard</a></li>
                    <li><a href="#winner-history">Winner History</a></li>
                </ul>
                <div class="nav-cta">
                    <span class="user-pill"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></span>
                    <a href="../index.php" class="btn btn-outline">Public Site</a>
                    <a href="logout.php" class="btn btn-primary">Log Out</a>
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
        <ul class="mobile-nav-links">
            <li><a href="#dashboard" class="active" aria-current="page">Dashboard</a></li>
            <li><a href="#winner-history">Winner History</a></li>
        </ul>
        <div class="mobile-menu-cta">
            <a href="../index.php" class="btn btn-outline">Public Site</a>
            <a href="logout.php" class="btn btn-primary">Log Out</a>
        </div>
    </aside>

    <section class="hero">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="hero-badge"><span class="dot"></span>Administrator dashboard</div>
                    <h1 class="hero-title">A fair draw.<br><span class="gold">Every time.</span></h1>
                    <p class="hero-desc">Review the entry pool, select a winner and keep the Customer Appreciation Month raffle running smoothly.</p>
                </div>
                <div class="hero-visual">
                    <div class="price-card raffle-dashboard-summary">
                        <div class="price-header">
                            <span class="price-label">Current draw pool</span>
                            <span class="price-live" id="hero-draw-pool-status"><?php echo $eligibleEntries > 0 ? 'Ready' : 'Pool empty'; ?></span>
                        </div>
                        <div class="price-main">
                            <div class="price-value" id="stat-hero-eligible"><?php echo number_format($eligibleEntries); ?></div>
                            <div class="raffle-summary-label">Eligible raffle entries</div>
                        </div>
                        <div class="raffle-card-rule"></div>
                        <div class="raffle-card-note">Each eligible entry has one chance to win</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <main class="main-content" id="dashboard">

        <!-- Database Error Alert if applicable -->
        <?php if (!empty($dbFetchError)): ?>
            <div class="status-box danger" style="margin-bottom: 1.5rem;">
                <span style="font-size: 1.5rem;"></span>
                <div>
                    <strong>System Notice:</strong>
                    <p><?php echo htmlspecialchars($dbFetchError); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Key Metrics Cards -->
        <section class="stats-grid">
            <!-- Total Entries Card -->
            <div class="stat-card">
                <span class="stat-label">Total Entries</span>
                <span class="stat-value" id="stat-total-entries"><?php echo number_format($totalEntries); ?></span>
                <span class="stat-desc">Total raffle tickets created</span>
            </div>

            <!-- Eligible Entries Card -->
            <div class="stat-card stat-green">
                <span class="stat-label">Eligible Entries</span>
                <span class="stat-value" id="stat-eligible-entries"><?php echo number_format($eligibleEntries); ?></span>
                <span class="stat-desc">Valid & ready for draw</span>
            </div>

            <!-- Winners Selected Card -->
            <div class="stat-card stat-amber">
                <span class="stat-label">Winners Selected</span>
                <span class="stat-value" id="stat-total-winners"><?php echo number_format($totalWinners); ?></span>
                <span class="stat-desc">Awarded raffle winners</span>
            </div>

            <!-- Draw Pool Status Card -->
            <div class="stat-card stat-purple">
                <span class="stat-label">Draw Pool Status</span>
                <span class="stat-value" style="font-size: 1.5rem; padding-top: 0.35rem;">
                    <?php if ($eligibleEntries > 0): ?>
                        <span class="badge badge-success" id="draw-pool-status">Ready (<?php echo $eligibleEntries; ?>)</span>
                    <?php else: ?>
                        <span class="badge badge-danger" id="draw-pool-status">Empty Pool</span>
                    <?php endif; ?>
                </span>
                <span class="stat-desc">Eligible participants status</span>
            </div>
        </section>

        <!-- Raffle Draw Action Panel -->
        <section class="draw-action-panel">
            <div>
                <h2 style="font-size: 1.35rem; margin-bottom: 0.25rem;">
                     Live Raffle Draw Stage
                </h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">
                    Ready to pick a winner? Currently <strong id="draw-eligible-count"><?php echo $eligibleEntries; ?></strong> eligible entry tickets are in the drawing pool.
                </p>
            </div>
            <div>
                <button type="button" id="btn-draw-winner" class="draw-btn" <?php echo $eligibleEntries === 0 ? 'disabled' : ''; ?>>
                    DRAW WINNER
                </button>
            </div>
        </section>

        <section id="draw-animation" class="rolling-stage" style="display: none;" aria-live="polite" aria-atomic="true">
            <div class="rolling-card">
                <span class="rolling-badge" id="draw-animation-badge">Drawing in progress</span>
                <p class="rolling-subtitle" id="draw-animation-message">The server is choosing an eligible entry.</p>
                <div class="rolling-display-box">
                    <span class="rolling-icon" id="draw-animation-icon" aria-hidden="true">🎟️</span>
                    <span class="rolling-name" id="draw-animation-name">Selecting winner...</span>
                </div>
                <div class="rolling-progress-bar" role="progressbar" aria-label="Winner reveal progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                    <div class="rolling-progress-fill" id="draw-progress-fill"></div>
                </div>
            </div>
        </section>

        <section id="draw-winner-result" class="winner-spotlight-card" style="display: none;" aria-live="polite">
            <span class="spotlight-badge"> Winner selected</span>
            <h2 style="margin-top: 1rem;">Congratulations!</h2>
            <div class="winner-ticket-banner">
                <span style="font-size: 0.85rem; opacity: 0.9;">WINNING TICKET</span>
                <span class="winner-ticket-number" id="draw-winner-ticket"></span>
            </div>
            <p id="draw-winner-name" style="font-size: 1.5rem; font-weight: 700;"></p>
            <p id="draw-winner-details" style="color: var(--text-muted); margin-top: 0.25rem;"></p>
        </section>

        <div id="draw-error" class="status-box danger" style="display: none;" role="alert"></div>

        <!-- Previous Winners Table Section -->
        <section id="winner-history" class="raffle-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <div class="raffle-section-header">
                    <div class="section-label">Draw archive</div>
                    <h2>Previous winners</h2>
                    <p>Every selected winner and ticket, recorded in one place.</p>
                </div>
                <span class="badge badge-info" id="recorded-winner-count"><?php echo count($winnersList); ?> Recorded Winner(s)</span>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Draw #</th>
                            <th>Ticket Code</th>
                            <th>Winner Name</th>
                            <th>Phone Number</th>
                            <th>Email Address</th>
                            <th>Draw Timestamp</th>
                        </tr>
                    </thead>
                    <tbody id="winners-table-body">
                        <?php if (!empty($winnersList)): ?>
                            <?php foreach ($winnersList as $index => $winner): ?>
                                <tr>
                                    <td><strong>#<?php echo (int) $winner['winner_id']; ?></strong></td>
                                    <td>
                                        <span class="badge badge-info" style="font-family: monospace;">
                                            CAM-<?php echo str_pad((int) $winner['entry_id'], 5, '0', STR_PAD_LEFT); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($winner['customer_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($winner['customer_phone']); ?></td>
                                    <td><?php echo htmlspecialchars($winner['customer_email']); ?></td>
                                    <td><?php echo htmlspecialchars(date('M j, Y - g:i A', strtotime($winner['draw_date']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎟️</div>
                                    <p><strong>No winners have been drawn yet.</strong></p>
                                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                                        Once winners are selected in the raffle draw stage, their details and draw timestamps will appear here.
                                    </p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Customer Appreciation Month &bull; Admin Management Portal</p>
        </div>
    </footer>

    <!-- Vanilla JavaScript for Draw Button Interaction -->
    <script src="../js/main.js"></script>
</body>
</html>
