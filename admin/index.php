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
    <!-- Vanilla CSS -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <!-- Header Navigation -->
    <header class="main-header">
        <div class="container header-content">
            <a href="index.php" class="brand-title">
                🎁 Customer Appreciation Month
                <span class="brand-badge">Admin Dashboard</span>
            </a>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span class="user-pill">
                    👤 Logged in as: <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></strong>
                </span>
                <a href="../index.php" class="btn btn-secondary btn-sm" target="_blank">View Public Site</a>
                <a href="logout.php" class="btn btn-secondary btn-sm" style="color: var(--danger-color); font-weight: 600;">
                    Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Banner -->
    <section class="hero" style="padding: 2.25rem 0;">
        <div class="container">
            <h1 style="font-size: 2rem;">Raffle Management Dashboard</h1>
            <p>Monitor participant entries, review draw eligibility, and conduct the Customer Appreciation Month raffle.</p>
        </div>
    </section>

    <!-- Main Container -->
    <main class="main-content container">

        <!-- Database Error Alert if applicable -->
        <?php if (!empty($dbFetchError)): ?>
            <div class="status-box danger" style="margin-bottom: 1.5rem;">
                <span style="font-size: 1.5rem;">⚠️</span>
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
                <span class="stat-value"><?php echo number_format($totalEntries); ?></span>
                <span class="stat-desc">Total raffle tickets created</span>
            </div>

            <!-- Eligible Entries Card -->
            <div class="stat-card stat-green">
                <span class="stat-label">Eligible Entries</span>
                <span class="stat-value"><?php echo number_format($eligibleEntries); ?></span>
                <span class="stat-desc">Valid & ready for draw</span>
            </div>

            <!-- Winners Selected Card -->
            <div class="stat-card stat-amber">
                <span class="stat-label">Winners Selected</span>
                <span class="stat-value"><?php echo number_format($totalWinners); ?></span>
                <span class="stat-desc">Awarded raffle winners</span>
            </div>

            <!-- Draw Pool Status Card -->
            <div class="stat-card stat-purple">
                <span class="stat-label">Draw Pool Status</span>
                <span class="stat-value" style="font-size: 1.5rem; padding-top: 0.35rem;">
                    <?php if ($eligibleEntries > 0): ?>
                        <span class="badge badge-success">Ready (<?php echo $eligibleEntries; ?>)</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Empty Pool</span>
                    <?php endif; ?>
                </span>
                <span class="stat-desc">Eligible participants status</span>
            </div>
        </section>

        <!-- Live Raffle Draw Action Panel -->
        <section class="draw-action-panel">
            <div>
                <h2 style="font-size: 1.35rem; color: #1e3a8a; margin-bottom: 0.25rem;">
                    🎲 Live Raffle Draw Stage
                </h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">
                    Ready to pick a winner? Currently <strong><?php echo $eligibleEntries; ?></strong> eligible entry tickets are in the drawing pool.
                </p>
            </div>
            <div>
                <!-- Draw Winner Button (Demonstration Phase: Triggers preview notification) -->
                <button type="button" id="btn-draw-winner" class="draw-btn">
                    ✨ DRAW WINNER
                </button>
            </div>
        </section>

        <!-- Temporary Feature Notice (Hidden by default, shown when DRAW WINNER clicked) -->
        <div id="draw-preview-box" class="status-box warning" style="display: none; margin-bottom: 2rem;">
            <span style="font-size: 1.5rem;">🎉</span>
            <div>
                <strong>Winner Selection Engine (Phase 6 Preview):</strong>
                <p style="margin-top: 0.25rem;">
                    The <strong>"DRAW WINNER"</strong> action button is connected and ready. The automated randomized selection algorithm, animation stage, and database status update will be implemented in the next phase!
                </p>
                <p style="margin-top: 0.35rem; font-size: 0.85rem;">
                    Current pool: <strong><?php echo $eligibleEntries; ?></strong> eligible ticket(s) waiting.
                </p>
            </div>
        </div>

        <!-- Previous Winners Table Section -->
        <section>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <h2>🏆 Previous Winners</h2>
                <span class="badge badge-info"><?php echo count($winnersList); ?> Recorded Winner(s)</span>
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
                    <tbody>
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
