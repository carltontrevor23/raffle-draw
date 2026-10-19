<?php
/**
 * Email Notification Service
 * Cornerstone Asset Managers - Customer Appreciation Month Raffle
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Dispatch a prize notification email to the winning customer.
 * Handles missing SMTP credentials cleanly and records audit status in database.
 *
 * @param PDO $pdo Active PDO connection
 * @param array $customer Customer record (id, name, email, phone)
 * @param array $prize Prize record (id, name, segment_label)
 * @param array $winner Winner record (id, claim_code, draw_date, ticket_code)
 * @return array ['success' => bool, 'status' => string, 'message' => string]
 */
function sendWinnerPrizeEmail(PDO $pdo, array $customer, array $prize, array $winner): array {
    $winnerId   = (int) $winner['id'];
    $toEmail    = trim($customer['email']);
    $toName     = trim($customer['name']);
    $prizeName  = $prize['name'];
    $claimCode  = $winner['claim_code'];
    $ticketCode = $winner['ticket_code'];
    $winDate    = $winner['draw_date'];

    $subject = "Congratulations! You won the {$prizeName} - Cornerstone Raffle Draw";

    $companyName = "Cornerstone Asset Managers";
    $campaignTitle = "Customer Appreciation Month Raffle";

    // Build HTML email body
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <style>
            body { font-family: 'Helvetica Neue', Arial, sans-serif; background-color: #121110; color: #f0ebe1; margin: 0; padding: 24px; }
            .container { max-width: 600px; margin: 0 auto; background: #1c1a17; border: 1px solid #d4af37; border-radius: 8px; overflow: hidden; }
            .header { background: #0f0e0c; padding: 24px; text-align: center; border-bottom: 1px solid #332d22; }
            .badge { display: inline-block; background: #d4af37; color: #000; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 4px 10px; border-radius: 4px; letter-spacing: 0.1em; }
            .content { padding: 32px 28px; }
            h1 { color: #d4af37; font-size: 24px; margin-top: 12px; }
            p { font-size: 15px; line-height: 1.6; color: #d6d0c4; }
            .prize-box { background: #24211c; border-left: 4px solid #d4af37; padding: 18px 20px; margin: 24px 0; border-radius: 4px; }
            .prize-title { font-size: 20px; font-weight: bold; color: #fff; margin: 0 0 6px 0; }
            .code-box { background: #0f0e0c; border: 1px dashed #d4af37; padding: 14px; text-align: center; margin: 20px 0; border-radius: 6px; }
            .code { font-family: monospace; font-size: 22px; font-weight: bold; color: #f3e5ab; letter-spacing: 0.12em; }
            .instructions { background: #181613; padding: 18px; border-radius: 6px; margin: 20px 0; font-size: 14px; }
            .instructions ol { margin: 8px 0 0 18px; padding: 0; color: #c8c2b5; }
            .instructions li { margin-bottom: 6px; }
            .footer { background: #0f0e0c; padding: 18px; text-align: center; font-size: 12px; color: #7f786d; border-top: 1px solid #29241b; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <span class='badge'>{$campaignTitle}</span>
                <h1>{$companyName}</h1>
            </div>
            <div class='content'>
                <h2>Congratulations, {$toName}!</h2>
                <p>We are thrilled to inform you that you have won a prize in our <strong>Customer Appreciation Month</strong> raffle draw.</p>
                
                <div class='prize-box'>
                    <div style='font-size: 12px; text-transform: uppercase; color: #d4af37; letter-spacing: 0.08em;'>Awarded Prize</div>
                    <div class='prize-title'>{$prizeName}</div>
                    <div style='font-size: 13px; color: #9c9486;'>Winning Date: {$winDate}</div>
                </div>

                <div class='code-box'>
                    <div style='font-size: 12px; color: #9c9486; margin-bottom: 4px;'>OFFICIAL VERIFICATION CLAIM CODE</div>
                    <div class='code'>{$claimCode}</div>
                    <div style='font-size: 12px; color: #9c9486; margin-top: 4px;'>Ticket Number: <strong>{$ticketCode}</strong></div>
                </div>

                <div class='instructions'>
                    <strong>How to Claim Your Prize:</strong>
                    <ol>
                        <li>Keep this confirmation email and your Claim Code (<strong>{$claimCode}</strong>) safe.</li>
                        <li>Visit the <strong>Cornerstone Asset Managers</strong> Client Service Desk or contact our Customer Experience Team.</li>
                        <li>Present a valid government-issued photo ID (National ID, Passport, or Driver's License) matching your registered name (<strong>{$toName}</strong>) and registered phone number.</li>
                        <li>Prizes must be claimed within thirty (30) business days of this notification.</li>
                    </ol>
                </div>

                <p style='font-size: 13px; color: #9c9486;'>Thank you for being a valued client of Cornerstone Asset Managers. We celebrate your partnership with us.</p>
            </div>
            <div class='footer'>
                &copy; " . date('Y') . " {$companyName} &bull; All Rights Reserved.<br>
                For inquiries, please contact info@cornerstone.example or visit our office.
            </div>
        </div>
    </body>
    </html>
    ";

    // Plain text alternative
    $plainText = "CONGRATULATIONS, {$toName}!\n\n"
               . "You won: {$prizeName}\n"
               . "Winning Date: {$winDate}\n"
               . "Claim Code: {$claimCode}\n"
               . "Ticket Code: {$ticketCode}\n\n"
               . "HOW TO CLAIM YOUR PRIZE:\n"
               . "1. Visit the Cornerstone Asset Managers Client Service Desk.\n"
               . "2. Present your Claim Code and a valid Government Photo ID.\n"
               . "3. Claim must be completed within 30 business days.\n\n"
               . "Cornerstone Asset Managers - Customer Appreciation Month";

    // Prepare Email Headers
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Cornerstone Asset Managers <no-reply@cornerstone.example>\r\n";
    $headers .= "Reply-To: support@cornerstone.example\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $emailStatus = 'pending';
    $errorMessage = null;

    // Check if system has a functioning mail relay / sendmail configured
    $hasSmtpConfigured = (bool) (ini_get('sendmail_path') || (ini_get('SMTP') && ini_get('SMTP') !== 'localhost'));
    
    // Attempt dispatch via standard PHP mail()
    $sent = false;
    try {
        // Suppress warning if local sendmail is not listening
        $sent = @mail($toEmail, $subject, $htmlBody, $headers);
    } catch (Throwable $t) {
        $errorMessage = $t->getMessage();
    }

    if ($sent) {
        $emailStatus = 'sent';
        $updateStmt = $pdo->prepare("UPDATE winners SET email_status = 'sent', email_sent_at = NOW(), email_error = NULL WHERE id = ?");
        $updateStmt->execute([$winnerId]);

        return [
            'success' => true,
            'status'  => 'sent',
            'message' => "Winner notification email successfully dispatched to {$toEmail}."
        ];
    } else {
        $emailStatus = 'failed';
        $reason = $errorMessage ?: "Local mail transfer agent (MTA) is not configured in this environment (SMTP=localhost:25 without an active relay service). To send live emails in production, configure SMTP credentials or sendmail in php.ini.";
        
        $updateStmt = $pdo->prepare("UPDATE winners SET email_status = 'failed', email_error = ? WHERE id = ?");
        $updateStmt->execute([$reason, $winnerId]);

        return [
            'success' => false,
            'status'  => 'failed',
            'message' => "Email notification queued as failed: {$reason}",
            'config_required' => "To enable live email dispatch, configure SMTP server, port, and credentials in php.ini (or configure sendmail/Postfix)."
        ];
    }
}

