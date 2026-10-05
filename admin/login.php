<?php
/**
 * Customer Appreciation Month Raffle Draw System
 * Admin Authentication - Login Page (Phase 5)
 */

session_start();

// If already logged in, redirect straight to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$username = '';
$error = '';
$loggedOutMessage = '';

if (isset($_GET['logged_out']) && $_GET['logged_out'] === '1') {
    $loggedOutMessage = 'You have been successfully logged out.';
}

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Demonstration Credentials (Beginner-friendly & simple to explain)
    $DEMO_USER = 'admin';
    $DEMO_PASS = 'admin123';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } elseif ($username === $DEMO_USER && $password === $DEMO_PASS) {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        // Store session authentication state
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username']  = $DEMO_USER;
        $_SESSION['admin_login_at']  = time();

        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password. Check demo credentials below.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Customer Appreciation Month Raffle</title>
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
                <a href="../index.php" class="logo">Customer <span>Appreciation</span></a>
                <ul class="nav-links">
                    <li><a href="../index.php">Public Site</a></li>
                    <li><a href="../register.php">Raffle Entry</a></li>
                    <li><a href="login.php" class="active" aria-current="page">Admin Login</a></li>
                </ul>
                <div class="nav-cta">
                    <a href="../register.php" class="btn btn-outline">Customer Entry</a>
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
            <li><a href="../index.php">Public Site</a></li>
            <li><a href="../register.php">Raffle Entry</a></li>
            <li><a href="login.php" class="active" aria-current="page">Admin Login</a></li>
        </ul>
        <div class="mobile-menu-cta">
            <a href="../register.php" class="btn btn-outline">Customer Entry</a>
        </div>
    </aside>

    <section class="hero">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="hero-badge"><span class="dot"></span>Administrator access</div>
                    <h1 class="hero-title">The draw,<br><span class="gold">in good hands.</span></h1>
                    <p class="hero-desc">Sign in to review eligible entries, conduct the raffle and keep track of every selected winner.</p>
                </div>
                <div class="hero-visual" aria-hidden="true">
                    <div class="raffle-hero-card">
                        <div class="raffle-hero-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
                                <path d="M12 3 4.5 6v5.2c0 4.8 3.2 8.1 7.5 9.8 4.3-1.7 7.5-5 7.5-9.8V6z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                        </div>
                        <span class="raffle-eyebrow">Administrator portal</span>
                        <h2>Fair draws.<br>Clear records.</h2>
                        <p>Access is reserved for the raffle administrator.</p>
                        <div class="raffle-card-rule"></div>
                        <div class="raffle-card-note">Secure dashboard access</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <main class="main-content">

        <div class="contact-form form-card raffle-login-card">
            <div class="section-label">Admin portal</div>
            <h2>Administrator sign-in</h2>
            <p class="raffle-form-intro">
                Enter administrator credentials to access the raffle dashboard.
            </p>

            <!-- Logout Notice -->
            <?php if (!empty($loggedOutMessage)): ?>
                <div class="status-box success" style="margin-bottom: 1.25rem;">
                    <span>ℹ️</span>
                    <div><?php echo htmlspecialchars($loggedOutMessage); ?></div>
                </div>
            <?php endif; ?>

            <!-- Error Notice -->
            <?php if (!empty($error)): ?>
                <div class="status-box danger" style="margin-bottom: 1.25rem;">
                    <span></span>
                    <div><strong>Login Error:</strong> <?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        placeholder="e.g. admin"
                        value="<?php echo htmlspecialchars($username); ?>" 
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="••••••••" 
                        required
                    >
                </div>

                <div style="margin-top: 1.75rem;">
                    <button type="submit" class="btn btn-primary btn-block">
                        Sign In to Dashboard
                    </button>
                </div>
            </form>

            <!-- Demonstration Credentials Helper Box -->
            <div class="demo-credentials">
                <strong>Demonstration credentials</strong>
                <div>Username: <code>admin</code></div>
                <div>Password: <code>admin123</code></div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Customer Appreciation Month &bull; Admin Portal</p>
        </div>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html>
