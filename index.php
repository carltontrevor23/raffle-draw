<?php
/**
 * Customer Appreciation Month Raffle Draw System
 * Main Entry Point
 * 
 * Phase 1: Project Setup & System Verification
 */

// Include MySQL Database Configuration
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Appreciation Month | Raffle Draw System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Libre+Franklin:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="templatemo_610_aurum_gold/templatemo-aurum-gold.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/raffle-theme.css">
</head>
<body class="raffle-app">

    <nav class="nav" id="navbar">
        <div class="container">
            <div class="nav-inner">
                <a href="index.php" class="logo">Customer <span>Appreciation</span></a>
                <ul class="nav-links">
                    <li><a href="#setup-guide">Setup Guide</a></li>
                    <li><a href="#modules">System Overview</a></li>
                </ul>
                <div class="nav-cta">
                    <a href="admin/login.php" class="btn btn-outline">Admin Login</a>
                    <a href="register.php" class="btn btn-primary">Enter the Raffle</a>
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
            <li><a href="#setup-guide">Setup Guide</a></li>
            <li><a href="#modules">System Overview</a></li>
        </ul>
        <div class="mobile-menu-cta">
            <a href="admin/login.php" class="btn btn-outline">Admin Login</a>
            <a href="register.php" class="btn btn-primary">Enter the Raffle</a>
        </div>
    </aside>

    <!-- Hero Banner -->
    <section class="hero">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="hero-badge"><span class="dot"></span>Customer Appreciation Month</div>
                    <h1 class="hero-title">A little gratitude.<br><span class="gold">A chance to win.</span></h1>
                    <p class="hero-desc">Join the customer appreciation raffle. Register for your ticket, then return to see whether your entry has been selected.</p>
                    <div class="hero-actions">
                        <a href="register.php" class="btn btn-primary">Register for the Draw</a>
                        <a href="#modules" class="btn btn-outline">Explore the Raffle</a>
                    </div>
                </div>
                <div class="hero-visual" aria-hidden="true">
                    <div class="raffle-hero-card">
                        <div class="raffle-hero-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
                                <path d="M4 7.5A2.5 2.5 0 0 0 6.5 5h11A2.5 2.5 0 0 0 20 7.5v2a2.5 2.5 0 0 0 0 5v2a2.5 2.5 0 0 0-2.5 2.5h-11A2.5 2.5 0 0 0 4 16.5v-2a2.5 2.5 0 0 0 0-5z"/>
                                <path d="M12 8v1m0 3v1m0 3v1"/>
                            </svg>
                        </div>
                        <span class="raffle-eyebrow">Your moment to win</span>
                        <h2>One entry.<br>One celebration.</h2>
                        <p>Every eligible customer receives a unique raffle ticket.</p>
                        <div class="raffle-card-rule"></div>
                        <div class="raffle-card-note">Your next step starts here</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Environment & Connection Status Alert -->
        <?php if ($db_connected): ?>
            <div class="status-box success">
                <span style="font-size: 1.5rem;"></span>
                <div>
                    <strong>Database Connected Successfully!</strong>
                    <p>PDO connection established to database: <code><?php echo htmlspecialchars($db_name); ?></code> on <code><?php echo htmlspecialchars($host); ?></code>.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="status-box danger">
                <span style="font-size: 1.5rem;"></span>
                <div>
                    <strong>Database Connection Pending or Failed:</strong>
                    <p><?php echo htmlspecialchars($db_error); ?></p>
                    <p style="margin-top: 0.5rem; font-size: 0.875rem;">
                        <strong>Next Step:</strong> Ensure MySQL is started in XAMPP and import <code>sql/schema.sql</code> into phpMyAdmin.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- System Diagnostic Cards -->
        <section class="raffle-section">
            <div class="raffle-section-header">
                <div class="section-label">System Status</div>
                <h2>Everything you need to know</h2>
                <p>Check the raffle system and its connection before getting started.</p>
            </div>
            <div class="grid-cards">
                <div class="card">
                    <h3 class="card-title"> PHP Runtime</h3>
                    <div class="card-body">
                        <p>Version: <strong><?php echo PHP_VERSION; ?></strong></p>
                        <p>Driver: <strong>PHP Data Objects (PDO)</strong></p>
                        <p style="margin-top: 0.5rem;"><span class="badge badge-success">Operational</span></p>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title"> MySQL Database</h3>
                    <div class="card-body">
                        <p>Target DB: <strong><?php echo htmlspecialchars($db_name); ?></strong></p>
                        <p>Host: <strong><?php echo htmlspecialchars($host); ?></strong></p>
                        <p style="margin-top: 0.5rem;">
                            <?php if ($db_connected): ?>
                                <span class="badge badge-success">Connected via PDO</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Not Connected</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title">⚡ Client-side JavaScript</h3>
                    <div class="card-body">
                        <p>Type: <strong>Vanilla JavaScript (ES6)</strong></p>
                        <p>Status: <span id="js-status-badge" class="badge badge-danger">Initializing...</span></p>
                        <div style="margin-top: 0.75rem;">
                            <button id="btn-test-js" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
                                Test JS Interactivity
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main System Areas -->
        <section id="modules" class="raffle-section">
            <div class="raffle-section-header">
                <div class="section-label">How it works</div>
                <h2>One simple raffle journey</h2>
            </div>
            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                Customers register for one ticket, while administrators monitor entries, select winners, and review the winners list.
            </p>
            <div class="grid-cards">
                <div class="card">
                    <h3 class="card-title">Customer Registration</h3>
                    <div class="card-body">
                        <p>Customer registration and ticket number assignment for customer appreciation month participants.</p>
                        <p style="margin-top: 0.75rem;">
                            <span class="badge badge-success">Implemented</span>
                            <a href="register.php" style="margin-left: 0.5rem; font-weight: 600; font-size: 0.85rem; color: var(--gold-light);">Test Registration &rarr;</a>
                        </p>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title"> Admin Dashboard</h3>
                    <div class="card-body">
                        <p>View total entries, eligible entries, previous winners, and the current draw-pool status.</p>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title">🎲 Winner Selection</h3>
                    <div class="card-body">
                        <p>The PHP draw endpoint selects one valid entry and records the winner; JavaScript presents the result.</p>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title"> Winners List</h3>
                    <div class="card-body">
                        <p>Review selected winners, their raffle tickets, and the time each winner was recorded.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- XAMPP Setup Instructions -->
        <section id="setup-guide" class="raffle-section">
            <div class="raffle-section-header">
                <div class="section-label">Getting started</div>
                <h2>Local setup guide</h2>
            </div>
            <div class="card" style="margin-top: 1rem;">
                <ol style="margin-left: 1.25rem; line-height: 1.8;">
                    <li>Open your <strong>XAMPP Control Panel</strong> and start both <strong>Apache</strong> and <strong>MySQL</strong>.</li>
                    <li>Open your browser and navigate to <strong><code>http://localhost/phpmyadmin</code></strong>.</li>
                    <li>Click on the <strong>Import</strong> tab at the top.</li>
                    <li>Browse and select the SQL file from this project:
                        <div class="code-block">raffle-draw/sql/schema.sql</div>
                    </li>
                    <li>Click <strong>Import</strong> (or <strong>Go</strong>) to automatically create the <code>raffle_db</code> database, tables, and sample data.</li>
                    <li>Refresh this page in your browser — the MySQL status badge above will turn green!</li>
                </ol>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Customer Appreciation Month &bull; Raffle Draw System</p>
            <p style="font-size: 0.8rem; margin-top: 0.25rem;">A celebration of the customers who make us.</p>
        </div>
    </footer>

    <!-- Custom Vanilla JavaScript -->
    <script src="js/main.js"></script>
</body>
</html>
