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
