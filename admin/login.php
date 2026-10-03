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
    <!-- Vanilla CSS -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <!-- Header Navigation -->
    <header class="main-header">
        <div class="container header-content">
            <a href="../index.php" class="brand-title">
                🎁 Customer Appreciation Month
                <span class="brand-badge">Admin Portal</span>
            </a>
            <nav>
                <ul class="nav-links">
                    <li><a href="../index.php">Public Site</a></li>
                    <li><a href="../register.php">Customer Registration</a></li>
                    <li><a href="login.php" class="active">Admin Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Banner -->
    <section class="hero">
        <div class="container">
            <h1>Staff & Admin Access</h1>
            <p>Sign in to monitor entries, manage raffle settings, and run the lucky draw.</p>
        </div>
    </section>

    <!-- Main Container -->
    <main class="main-content container">

        <div class="form-card">
            <h2 style="font-size: 1.4rem; margin-bottom: 0.5rem; text-align: center;">Administrator Sign-in</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; margin-bottom: 1.5rem;">
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
                    <span>⚠️</span>
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
                        🔐 Sign In to Dashboard
                    </button>
                </div>
            </form>

            <!-- Demonstration Credentials Helper Box -->
            <div style="margin-top: 1.75rem; background-color: var(--bg-page); border: 1px dashed var(--border-color); border-radius: var(--border-radius); padding: 1rem; font-size: 0.88rem; color: var(--text-muted);">
                <strong style="color: var(--text-main); display: block; margin-bottom: 0.25rem;">🔑 Demonstration Credentials:</strong>
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

</body>
</html>
