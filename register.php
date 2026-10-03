<?php
/**
 * Customer Appreciation Month Raffle Draw System
 * Customer Registration Page (Phase 3)
 */

require_once __DIR__ . '/config/db.php';

$name = '';
$phone = '';
$email = '';
$errors = [];
$registrationSuccess = false;
$entryInfo = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    // Server-side Validation
    if ($name === '') {
        $errors[] = 'Full name is required.';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Full name must be at least 2 characters long.';
    }

    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    } elseif (strlen($phone) < 7) {
        $errors[] = 'Please enter a valid phone number (at least 7 digits).';
    }

    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Database Processing
    if (empty($errors)) {
        if (!$db_connected || !$pdo) {
            $errors[] = 'Database is currently unreachable. Please make sure MySQL is running in XAMPP.';
        } else {
            try {
                // Check if email already registered
                $checkStmt = $pdo->prepare('SELECT id FROM customers WHERE email = ? LIMIT 1');
                $checkStmt->execute([$email]);
                $existingCustomer = $checkStmt->fetch();

                if ($existingCustomer) {
                    $errors[] = 'The email address (' . htmlspecialchars($email) . ') has already been registered for this raffle!';
                } else {
                    // Use a transaction to ensure both customer and entry are saved together
                    $pdo->beginTransaction();

                    // 1. Insert customer record
                    $insertCustomer = $pdo->prepare('INSERT INTO customers (name, phone, email) VALUES (?, ?, ?)');
                    $insertCustomer->execute([$name, $phone, $email]);
                    $customerId = (int) $pdo->lastInsertId();

                    // 2. Create the raffle entry
                    $insertEntry = $pdo->prepare('INSERT INTO entries (customer_id, entry_status) VALUES (?, ?)');
                    $insertEntry->execute([$customerId, 'valid']);
                    $entryId = (int) $pdo->lastInsertId();

                    $pdo->commit();

                    $registrationSuccess = true;
                    $entryInfo = [
                        'entry_id'    => $entryId,
                        'ticket_code' => 'CAM-' . str_pad($entryId, 5, '0', STR_PAD_LEFT),
                        'name'        => $name,
                        'email'       => $email,
                        'phone'       => $phone,
                        'date'        => date('F j, Y - g:i A')
                    ];

                    // Clear form inputs
                    $name  = '';
                    $phone = '';
                    $email = '';
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                // MySQL error 23000 handles duplicate key violation
                if ($e->getCode() == 23000) {
                    $errors[] = 'Duplicate entry detected: You are already registered for this raffle.';
                } else {
                    $errors[] = 'Database error during registration: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for Raffle | Customer Appreciation Month</title>
    <!-- Vanilla CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Header Navigation -->
    <header class="main-header">
        <div class="container header-content">
            <a href="index.php" class="brand-title">
                🎁 Customer Appreciation Month
                <span class="brand-badge">Raffle Draw</span>
            </a>
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php">Overview</a></li>
                    <li><a href="register.php" class="active">Register for Raffle</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Banner -->
    <section class="hero">
        <div class="container">
            <h1>Customer Raffle Entry</h1>
            <p>Celebrate Customer Appreciation Month with us! Register below to get your official raffle entry ticket.</p>
        </div>
    </section>

    <!-- Main Container -->
    <main class="main-content container">

        <?php if ($registrationSuccess && $entryInfo): ?>
            <!-- Success Confirmation & Ticket Card -->
            <div class="ticket-card">
                <div class="ticket-header">
                    <span class="ticket-badge">Official Raffle Entry</span>
                    <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem; color: var(--success-color);">
                        🎉 Registration Successful!
                    </h2>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">
                        You have been officially entered into the Customer Appreciation Month Raffle Draw.
                    </p>
                    <div style="margin-top: 1rem;">
                        <span style="font-size: 0.85rem; color: var(--text-muted); display: block;">YOUR TICKET CODE</span>
                        <span class="ticket-number"><?php echo htmlspecialchars($entryInfo['ticket_code']); ?></span>
                    </div>
                </div>

                <div class="ticket-details">
                    <div class="ticket-row">
                        <span class="ticket-label">Entry ID:</span>
                        <span class="ticket-value">#<?php echo htmlspecialchars($entryInfo['entry_id']); ?></span>
                    </div>
                    <div class="ticket-row">
                        <span class="ticket-label">Full Name:</span>
                        <span class="ticket-value"><?php echo htmlspecialchars($entryInfo['name']); ?></span>
                    </div>
                    <div class="ticket-row">
                        <span class="ticket-label">Email:</span>
                        <span class="ticket-value"><?php echo htmlspecialchars($entryInfo['email']); ?></span>
                    </div>
                    <div class="ticket-row">
                        <span class="ticket-label">Phone:</span>
                        <span class="ticket-value"><?php echo htmlspecialchars($entryInfo['phone']); ?></span>
                    </div>
                    <div class="ticket-row">
                        <span class="ticket-label">Status:</span>
                        <span class="ticket-value"><span class="badge badge-success">Valid Entry</span></span>
                    </div>
                    <div class="ticket-row">
                        <span class="ticket-label">Registered At:</span>
                        <span class="ticket-value"><?php echo htmlspecialchars($entryInfo['date']); ?></span>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="register.php" class="btn btn-primary">Register Another Customer</a>
                    <a href="index.php" class="btn btn-secondary">Return to Overview</a>
                </div>
            </div>

        <?php else: ?>

            <!-- Registration Form Card -->
            <div class="form-card">
                <h2 style="font-size: 1.4rem; margin-bottom: 0.5rem; text-align: center;">Enter the Raffle</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; margin-bottom: 1.5rem;">
                    Please fill out your details below. Each customer is eligible for one raffle entry.
                </p>

                <!-- Server-side Error Messages Alert -->
                <?php if (!empty($errors)): ?>
                    <div class="status-box danger" style="margin-bottom: 1.25rem;">
                        <span style="font-size: 1.3rem;">⚠️</span>
                        <div>
                            <strong>Please correct the following:</strong>
                            <ul style="margin-left: 1.25rem; margin-top: 0.25rem; font-size: 0.9rem;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Customer Registration Form -->
                <form id="raffle-registration-form" method="POST" action="register.php" novalidate>
                    
                    <!-- Full Name Field -->
                    <div class="form-group">
                        <label for="name" class="form-label">
                            Full Name <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-input" 
                            placeholder="e.g. Jane Doe"
                            value="<?php echo htmlspecialchars($name); ?>"
                            autocomplete="name"
                        >
                        <div class="field-error" id="error-name">Please enter your full name (at least 2 letters).</div>
                    </div>

                    <!-- Phone Number Field -->
                    <div class="form-group">
                        <label for="phone" class="form-label">
                            Phone Number <span class="required">*</span>
                        </label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            class="form-input" 
                            placeholder="e.g. 555-0199 or 08012345678"
                            value="<?php echo htmlspecialchars($phone); ?>"
                            autocomplete="tel"
                        >
                        <div class="field-error" id="error-phone">Please enter a valid phone number (at least 7 digits).</div>
                    </div>

                    <!-- Email Address Field -->
                    <div class="form-group">
                        <label for="email" class="form-label">
                            Email Address <span class="required">*</span>
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="e.g. jane.doe@example.com"
                            value="<?php echo htmlspecialchars($email); ?>"
                            autocomplete="email"
                        >
                        <div class="field-error" id="error-email">Please enter a valid email address.</div>
                        <div class="form-helper">This will be used to notify you if you are selected in the draw.</div>
                    </div>

                    <!-- Submit Button -->
                    <div style="margin-top: 1.75rem;">
                        <button type="submit" id="btn-submit" class="btn btn-primary btn-block">
                            🎟️ Register & Get Raffle Ticket
                        </button>
                    </div>

                </form>
            </div>

        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Customer Appreciation Month &bull; Raffle Draw System</p>
        </div>
    </footer>

    <!-- Vanilla JavaScript for Client-side Validation -->
    <script src="js/main.js"></script>
</body>
</html>
