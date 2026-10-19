<?php
/**
 * Customer Appreciation Month | Overview & Winners Showcase
 * Cornerstone Asset Managers
 */

require_once __DIR__ . '/config/db.php';

$publicWinners = [];
$activePrizes  = [];

if ($db_connected && $pdo) {
    try {
        // Fetch confirmed public winners with privacy-safe joins
        $stmt = $pdo->query("
            SELECT
                w.id AS winner_id,
                w.draw_date,
                c.name AS customer_name,
                COALESCE(p.name, 'Customer Appreciation Prize') AS prize_name,
                p.segment_label AS prize_segment_label
            FROM winners w
            LEFT JOIN entries e ON w.entry_id = e.id
            LEFT JOIN customers c ON (w.customer_id = c.id OR e.customer_id = c.id)
            LEFT JOIN prizes p ON w.prize_id = p.id
            WHERE c.name IS NOT NULL
            ORDER BY w.draw_date DESC
            LIMIT 12
        ");
        $publicWinners = $stmt->fetchAll();

        // Fetch active prize list for promotional showcase (exclude Try Again)
        $pStmt = $pdo->query("
            SELECT name, segment_label, icon, is_win
            FROM prizes
            WHERE is_active = 1 AND is_win = 1
            ORDER BY sort_order ASC
        ");
        $activePrizes = $pStmt->fetchAll();

    } catch (PDOException $e) {
        error_log('Error querying public winners: ' . $e->getMessage());
    }
}


/**
 * Format winner's name safely for public display without exposing personal details.
 * Example: "Alice Johnson" -> "Alice J."
 */
function formatPublicWinnerName(?string $fullName): string {
    if (!$fullName) {
        return 'Valued Client';
    }
    $parts = preg_split('/\s+/', trim($fullName));
    if (count($parts) === 1) {
        return htmlspecialchars($parts[0]);
    }
    $firstName = $parts[0];
    $lastName = end($parts);
    $lastInitial = mb_substr($lastName, 0, 1) . '.';
    return htmlspecialchars($firstName . ' ' . $lastInitial);
}

function getPrizeImagePath(?string $segmentLabel, ?string $prizeName): ?string {
    $prizeImages = [
        'iphone 18 pro max' => 'images/iphone-18-pro-max.jpg',
        'macbook neo' => 'images/macbook-neo.jpg',
        'weekend getaway' => 'images/weekend-getaway-for-2.jpg',
        'weekend getaway for two at marriott kampala' => 'images/weekend-getaway-for-2.jpg',
        'airfryer' => 'images/airfryer.jpg',
        'ugx 50k voucher' => 'images/shopping-voucher.jpg',
        'shopping voucher worth ugx 50,000' => 'images/shopping-voucher.jpg',
        'vacuum cup' => 'images/vacuum-cup.jpg',
        'cornerstone vacuum cup' => 'images/vacuum-cup.jpg',
        'umbrella' => 'images/umbrella.jpg',
        'cornerstone umbrella' => 'images/umbrella.jpg',
        'powerbank' => 'images/magsafe-powerbank.jpg',
        'cornerstone magsafe powerbank' => 'images/magsafe-powerbank.jpg',
    ];

    foreach ([$segmentLabel, $prizeName] as $label) {
        $key = strtolower(trim($label ?? ''));
        if (isset($prizeImages[$key])) {
            return $prizeImages[$key];
        }
    }

    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Appreciation Month | Cornerstone Asset Managers</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Libre+Franklin:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=2.1">
    <link rel="stylesheet" href="css/raffle-theme.css?v=2.1">
</head>
<body class="raffle-app">

    <nav class="nav" id="navbar">
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
                    <li><a href="index.php" class="active">Overview</a></li>
                    <li><a href="#prizes">Prizes</a></li>
                    <li><a href="#winners">Winners</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="raffle.php">Spin Wheel</a></li>
                </ul>
                <div class="nav-cta">
                    <a href="raffle.php" class="btn btn-primary"> Spin Wheel</a>
                    <a href="register.php" class="btn btn-outline">Get Ticket</a>
                    <a href="admin/login.php" class="btn btn-outline" style="padding: 7px 12px; font-size: 11px;">Admin</a>
                </div>
                <button class="mobile-menu-btn" id="mobileMenuBtn" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </nav>
    <div class="mobile-menu-overlay" id="mobileMenuOverlay" style="display: none;"></div>
    <aside class="mobile-menu" id="mobileMenu" aria-hidden="true" style="display: none;">
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
            <li><a href="index.php" class="active">Overview</a></li>
            <li><a href="#prizes">Prizes</a></li>
            <li><a href="#winners">Winners</a></li>
            <li><a href="register.php">Register</a></li>
            <li><a href="raffle.php">Spin Wheel</a></li>
        </ul>
        <div class="mobile-menu-cta">
            <a href="raffle.php" class="btn btn-primary">Spin the Wheel</a>
            <a href="register.php" class="btn btn-outline">Register for Free</a>
            <a href="admin/login.php" class="btn btn-outline">Admin Portal</a>
        </div>
    </aside>

    <!-- Hero Banner -->
    <section class="hero">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="hero-badge"><span class="dot"></span>Customer Appreciation Month</div>
                    <h1 class="hero-title">A celebration of gratitude.<br><span class="gold">Your chance to win.</span></h1>
                    <p class="hero-desc">
                        As part of Customer Appreciation Month, Cornerstone Asset Managers invites every valued client to register and take <strong>up to 3 spins</strong> on our interactive raffle wheel.
                    </p>
                    <div class="hero-actions">
                        <a href="raffle.php" class="btn btn-primary" style="background: linear-gradient(135deg, #d4af37, #f3e5ab); color: #000; font-weight: 700;">
                            Spin the Raffle Wheel
                        </a>
                        <a href="register.php" class="btn btn-outline">Register for Ticket</a>
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
                        <span class="raffle-eyebrow">3 Chances · Instant Celebration</span>
                        <h2>Interactive<br>Raffle Wheel.</h2>
                        <p>Register, receive your ticket code, and spin for premium prizes.</p>
                        <div class="raffle-card-rule"></div>
                        <div class="raffle-card-note">Cornerstone Asset Managers</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">

            <!-- Prizes Showcase Section -->
            <section id="prizes" style="margin-bottom: 50px;">
                <div style="text-align: center; max-width: 650px; margin: 0 auto 30px auto;">
                    <div class="section-label">Featured Rewards</div>
                    <h2 style="font-size: 2.1rem; font-family: 'Cormorant Garamond', Georgia, serif; margin: 0 0 8px 0;">
                        Celebrate With Premium Rewards
                    </h2>
                    <p style="color: var(--text-muted); font-size: 15px;">
                        Each eligible customer receives up to 3 spin attempts to win one of these exciting prizes:
                    </p>
                </div>

                <div class="winners-grid" style="grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));">
                    <?php foreach ($activePrizes as $prize): ?>
                        <div class="winner-tile">
                            <div class="winner-tile-icon prize-tile-icon">
                                <?php $prizeImage = getPrizeImagePath($prize['segment_label'] ?? null, $prize['name'] ?? null); ?>
                                <?php if ($prizeImage !== null): ?>
                                    <img src="<?php echo htmlspecialchars($prizeImage); ?>"
                                         alt="<?php echo htmlspecialchars($prize['name']); ?>"
                                         class="prize-tile-img">
                                <?php else: ?>
                                    <?php echo htmlspecialchars($prize['icon']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="winner-tile-info">
                                <div class="winner-tile-name"><?php echo htmlspecialchars($prize['name']); ?></div>
                                <div class="winner-tile-prize">Cornerstone Appreciation Reward</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Confirmed Public Winners Showcase Section -->
            <section id="winners" class="public-winners-section">
                <div style="text-align: center; max-width: 650px; margin: 0 auto 30px auto;">
                    <div class="section-label">Wall of Winners</div>
                    <h2 style="font-size: 2.1rem; font-family: 'Cormorant Garamond', Georgia, serif; margin: 0 0 8px 0;">
                        Recent Confirmed Winners
                    </h2>
                    <p style="color: var(--text-muted); font-size: 15px;">
                        Congratulations to our clients who have already won prizes in the Customer Appreciation Month raffle draw:
                    </p>
                </div>

                <?php if (empty($publicWinners)): ?>
                    <div style="text-align: center; padding: 36px 20px; background: #161411; border: 1px dashed var(--border-color); border-radius: 8px;">
                        <p style="color: var(--text-muted); margin: 0;">
                            No confirmed winners yet today. Be the first to spin the wheel and claim your prize!
                        </p>
                        <a href="raffle.php" class="btn btn-primary" style="margin-top: 14px;">
                            Spin the Wheel Now
                        </a>
                    </div>
                <?php else: ?>
                    <div class="winners-grid">
                        <?php foreach ($publicWinners as $winner): ?>
                            <div class="winner-tile">
                                <div class="winner-tile-icon prize-tile-icon">
                                    <?php $prizeImage = getPrizeImagePath($winner['prize_segment_label'] ?? null, $winner['prize_name'] ?? null); ?>
                                    <?php if ($prizeImage !== null): ?>
                                        <img src="<?php echo htmlspecialchars($prizeImage); ?>"
                                             alt="<?php echo htmlspecialchars($winner['prize_name']); ?>"
                                             class="prize-tile-img">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($winner['prize_icon']); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="winner-tile-info">
                                    <div class="winner-tile-name"><?php echo formatPublicWinnerName($winner['customer_name']); ?></div>
                                    <div class="winner-tile-prize"><?php echo htmlspecialchars($winner['prize_name']); ?></div>
                                    <div class="winner-tile-date">
                                        Won on <?php echo date('M j, Y', strtotime($winner['draw_date'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div style="text-align: center; margin-top: 36px;">
                    <a href="raffle.php" class="btn btn-primary">
                        🎡 Check Your Ticket & Spin
                    </a>
                </div>
            </section>

        </div>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Cornerstone Asset Managers &bull; Customer Appreciation Month</p>
            <p style="font-size: 0.8rem; margin-top: 0.25rem;">Dedicated to growing and protecting your wealth with distinction.</p>
        </div>
    </footer>

    <!-- Custom Vanilla JavaScript -->
    <script src="js/main.js"></script>
</body>
</html>
