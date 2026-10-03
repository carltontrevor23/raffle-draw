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
    <!-- Custom Vanilla CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Main Navigation Header -->
    <header class="main-header">
        <div class="container header-content">
            <a href="index.php" class="brand-title">
                🎁 Customer Appreciation Month
                <span class="brand-badge">Raffle Draw</span>
            </a>
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php" class="active">Overview</a></li>
                    <li><a href="register.php">Register for Raffle</a></li>
                    <li><a href="#setup-guide">Setup Guide</a></li>
                    <li><a href="#modules">Planned Modules</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Banner -->
    <section class="hero">
        <div class="container">
            <h1>Customer Appreciation Month</h1>
            <p>Welcome to the official Raffle Draw System setup. Built exclusively with vanilla HTML, CSS, JavaScript, PHP, and MySQL.</p>
            <div style="margin-top: 1.25rem;">
                <a href="register.php" class="btn btn-secondary" style="font-weight: 700; color: #1e3a8a;">
                    🎟️ Open Customer Registration Page
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content container">

        <!-- Environment & Connection Status Alert -->
        <?php if ($db_connected): ?>
            <div class="status-box success">
                <span style="font-size: 1.5rem;">✅</span>
                <div>
                    <strong>Database Connected Successfully!</strong>
                    <p>PDO connection established to database: <code><?php echo htmlspecialchars($db_name); ?></code> on <code><?php echo htmlspecialchars($host); ?></code>.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="status-box danger">
                <span style="font-size: 1.5rem;">⚠️</span>
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
        <section>
            <h2>System Environment Status</h2>
            <div class="grid-cards">
                <div class="card">
                    <h3 class="card-title">🐘 PHP Runtime</h3>
                    <div class="card-body">
                        <p>Version: <strong><?php echo PHP_VERSION; ?></strong></p>
                        <p>Driver: <strong>PHP Data Objects (PDO)</strong></p>
                        <p style="margin-top: 0.5rem;"><span class="badge badge-success">Operational</span></p>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title">🗄️ MySQL Database</h3>
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

        <!-- Planned Project Modules (Overview for Supervisor) -->
        <section id="modules" style="margin-top: 2.5rem;">
            <h2>Project Architecture & Planned Phases</h2>
            <p style="color: var(--text-muted); margin-bottom: 1rem;">
                The raffle draw system is structured into straightforward, modular components:
            </p>
            <div class="grid-cards">
                <div class="card">
                    <h3 class="card-title">🎟️ 1. Customer Participants</h3>
                    <div class="card-body">
                        <p>Customer registration and ticket number assignment for customer appreciation month participants.</p>
                        <p style="margin-top: 0.75rem;">
                            <span class="badge badge-success">Implemented</span>
                            <a href="register.php" style="margin-left: 0.5rem; font-weight: 600; font-size: 0.85rem; color: var(--primary-color);">Test Registration &rarr;</a>
                        </p>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title">🏆 2. Prize Management</h3>
                    <div class="card-body">
                        <p>Catalog of available prizes, descriptions, and allocation limits configured for the draw.</p>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title">🎲 3. Raffle Draw Stage</h3>
                    <div class="card-body">
                        <p>Exciting, randomized drawing interface to select eligible winners fairly using vanilla JS and PHP.</p>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title">📜 4. Winners Ledger</h3>
                    <div class="card-body">
                        <p>Auditable log of all winning customers, associated prizes, and recorded draw timestamps.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- XAMPP Setup Instructions -->
        <section id="setup-guide" style="margin-top: 2.5rem;">
            <h2>XAMPP Quick Setup Guide</h2>
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
            <p>&copy; <?php echo date('Y'); ?> Customer Appreciation Month &bull; Raffle Draw Demonstration System</p>
            <p style="font-size: 0.8rem; margin-top: 0.25rem;">Built with Vanilla HTML5, CSS3, JavaScript, PHP (PDO), & MySQL</p>
        </div>
    </footer>

    <!-- Custom Vanilla JavaScript -->
    <script src="js/main.js"></script>
</body>
</html>
