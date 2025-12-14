<?php
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/email_config.php';
require_once '/var/www/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    private function createMailer() {
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        
        return $mail;
    }
    
    public function sendEmail($toEmail, $toName, $subject, $htmlBody, $emailType, $memberId = null, $eventId = null, $paymentId = null) {
        // If email is disabled, just log it and return success
        if (!EMAIL_ENABLED) {
            $this->logEmail($memberId, $toEmail, $emailType, $subject, 'sent', 'Email disabled - logged only', $eventId, $paymentId);
            return true;
        }
        
        // Only create mailer and attempt to send if email is enabled
        try {
            $mail = $this->createMailer();
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);
            
            $mail->send();
            
            $this->logEmail($memberId, $toEmail, $emailType, $subject, 'sent', null, $eventId, $paymentId);
            return true;
            
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $this->logEmail($memberId, $toEmail, $emailType, $subject, 'failed', $errorMessage, $eventId, $paymentId);
            error_log("Email sending failed: " . $errorMessage);
            return false;
        }
    }
    
    private function logEmail($memberId, $email, $emailType, $subject, $status, $errorMessage, $eventId, $paymentId) {
        $stmt = $this->conn->prepare("
            INSERT INTO email_logs 
            (member_id, email_address, email_type, email_subject, status, error_message, event_id, payment_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssssii", $memberId, $email, $emailType, $subject, $status, $errorMessage, $eventId, $paymentId);
        $stmt->execute();
    }
    
    public function sendWelcomeEmail($memberId, $memberEmail, $memberName, $membershipType) {
        $subject = "Welcome to KNAA - Your Membership is Active!";
        
        $htmlBody = $this->getEmailTemplate([
            'title' => 'Welcome to KNAA!',
            'content' => "
                <p>Dear {$memberName},</p>
                <p>Welcome to the Kenyan Nurses Association of America! We're thrilled to have you join our community of dedicated healthcare professionals.</p>
                <p><strong>Your Membership Details:</strong></p>
                <ul>
                    <li>Member ID: {$memberId}</li>
                    <li>Membership Type: {$membershipType}</li>
                    <li>Email: {$memberEmail}</li>
                </ul>
                <p>As a KNAA member, you now have access to:</p>
                <ul>
                    <li>Professional networking opportunities</li>
                    <li>Continuing education resources</li>
                    <li>Career development support</li>
                    <li>Exclusive member events</li>
                    <li>Advocacy and mentorship programs</li>
                </ul>
                <p>Visit your member dashboard to explore all the benefits available to you.</p>
            ",
            'button_text' => 'Go to Dashboard',
            'button_link' => SITE_URL . '/src/dashboard.php'
        ]);
        
        return $this->sendEmail($memberEmail, $memberName, $subject, $htmlBody, 'welcome', $memberId);
    }
    
    public function sendEventRegistrationConfirmation($registrationId) {
        $stmt = $this->conn->prepare("
            SELECT er.*, e.event_title, e.event_date, e.event_time, 
                   e.venue_name, e.street_address, e.city, e.state, e.zip_code
            FROM event_registrations er
            JOIN events e ON er.event_id = e.event_id
            WHERE er.registration_id = ?
        ");
        $stmt->bind_param("i", $registrationId);
        $stmt->execute();
        $registration = $stmt->get_result()->fetch_assoc();
        
        if (!$registration) return false;
        
        $eventDate = date('l, F j, Y', strtotime($registration['event_date']));
        $eventTime = $registration['event_time'] ? date('g:i A', strtotime($registration['event_time'])) : 'TBD';
        $location = "{$registration['venue_name']}, {$registration['street_address']}, {$registration['city']}, {$registration['state']} {$registration['zip_code']}";
        
        $subject = "Registration Confirmed: {$registration['event_title']}";
        
        $htmlBody = $this->getEmailTemplate([
            'title' => 'Event Registration Confirmed!',
            'content' => "
                <p>Dear {$registration['first_name']} {$registration['last_name']},</p>
                <p>Your registration for <strong>{$registration['event_title']}</strong> has been confirmed!</p>
                <p><strong>Event Details:</strong></p>
                <ul>
                    <li><strong>Date:</strong> {$eventDate}</li>
                    <li><strong>Time:</strong> {$eventTime}</li>
                    <li><strong>Location:</strong> {$location}</li>
                    <li><strong>Registration Fee:</strong> $" . number_format($registration['registration_fee'], 2) . "</li>
                </ul>
                <p><strong>Payment Status:</strong> " . ucfirst($registration['payment_status']) . "</p>
                " . ($registration['payment_status'] === 'pending' ? 
                    "<p><em>Please complete your payment to secure your spot. Your registration will be confirmed once payment is received.</em></p>" : "") . "
                <p>We look forward to seeing you at the event!</p>
            ",
            'button_text' => 'View Event Details',
            'button_link' => SITE_URL . '/src/event_details.php?id=' . $registration['event_id']
        ]);
        
        return $this->sendEmail(
            $registration['email'], 
            $registration['first_name'] . ' ' . $registration['last_name'],
            $subject, 
            $htmlBody, 
            'event_confirmation',
            $registration['member_id'],
            $registration['event_id']
        );
    }
    
    public function sendPaymentReceipt($paymentId) {
        $stmt = $this->conn->prepare("
            SELECT mp.*, m.first_name, m.last_name, m.email, mt.type_name
            FROM membership_payments mp
            JOIN members m ON mp.member_id = m.member_id
            JOIN membership_types mt ON mp.membership_type_id = mt.type_id
            WHERE mp.payment_id = ?
        ");
        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        
        if (!$payment) return false;
        
        $paymentDate = date('F j, Y', strtotime($payment['payment_date']));
        $periodStart = date('F j, Y', strtotime($payment['period_start_date']));
        $periodEnd = date('F j, Y', strtotime($payment['period_end_date']));
        
        $subject = "Payment Receipt - KNAA Membership";
        
        $htmlBody = $this->getEmailTemplate([
            'title' => 'Payment Receipt',
            'content' => "
                <p>Dear {$payment['first_name']} {$payment['last_name']},</p>
                <p>Thank you for your payment. This email confirms your membership payment to the Kenyan Nurses Association of America.</p>
                <p><strong>Payment Details:</strong></p>
                <ul>
                    <li><strong>Receipt Number:</strong> #KNAA-{$payment['payment_id']}</li>
                    <li><strong>Payment Date:</strong> {$paymentDate}</li>
                    <li><strong>Amount Paid:</strong> $" . number_format($payment['amount'], 2) . "</li>
                    <li><strong>Payment Type:</strong> " . ucfirst($payment['payment_type']) . "</li>
                    <li><strong>Payment Method:</strong> " . ucfirst(str_replace('_', ' ', $payment['payment_method'])) . "</li>
                    <li><strong>Membership Type:</strong> {$payment['type_name']}</li>
                    <li><strong>Coverage Period:</strong> {$periodStart} - {$periodEnd}</li>
                </ul>
                <p>Your membership is now active and you have full access to all KNAA benefits.</p>
                <p>If you have any questions about this payment, please contact us at info@kenyannursesusa.org.</p>
            ",
            'button_text' => 'View Dashboard',
            'button_link' => SITE_URL . '/src/dashboard.php'
        ]);
        
        return $this->sendEmail(
            $payment['email'], 
            $payment['first_name'] . ' ' . $payment['last_name'],
            $subject, 
            $htmlBody, 
            'payment_receipt',
            $payment['member_id'],
            null,
            $paymentId
        );
    }
    
    public function sendMembershipExpiryReminder($memberId) {
        $stmt = $this->conn->prepare("
            SELECT m.*, mt.type_name 
            FROM members m
            JOIN membership_types mt ON m.membership_type_id = mt.type_id
            WHERE m.member_id = ?
        ");
        $stmt->bind_param("s", $memberId);
        $stmt->execute();
        $member = $stmt->get_result()->fetch_assoc();
        
        if (!$member) return false;
        
        $expiryDate = date('F j, Y', strtotime($member['membership_expiration_date']));
        $daysUntilExpiry = ceil((strtotime($member['membership_expiration_date']) - time()) / 86400);
        
        $subject = "Membership Renewal Reminder - KNAA";
        
        $htmlBody = $this->getEmailTemplate([
            'title' => 'Membership Renewal Reminder',
            'content' => "
                <p>Dear {$member['first_name']} {$member['last_name']},</p>
                <p>This is a friendly reminder that your KNAA membership will expire in <strong>{$daysUntilExpiry} days</strong> on {$expiryDate}.</p>
                <p>Don't miss out on these valuable benefits:</p>
                <ul>
                    <li>Professional networking opportunities</li>
                    <li>Access to continuing education resources</li>
                    <li>Exclusive member events and conferences</li>
                    <li>Career development support</li>
                    <li>Advocacy representation</li>
                </ul>
                <p>Renew your membership today to ensure uninterrupted access to all KNAA benefits.</p>
            ",
            'button_text' => 'Renew Membership',
            'button_link' => SITE_URL . '/payments.html'
        ]);
        
        return $this->sendEmail(
            $member['email'], 
            $member['first_name'] . ' ' . $member['last_name'],
            $subject, 
            $htmlBody, 
            'expiry_reminder',
            $memberId
        );
    }
    
    public function sendEventReminder($eventId, $daysBeforeEvent = 7) {
        $stmt = $this->conn->prepare("
            SELECT er.registration_id, er.first_name, er.last_name, er.email, er.member_id,
                   e.event_title, e.event_date, e.event_time, 
                   e.venue_name, e.street_address, e.city, e.state, e.zip_code
            FROM event_registrations er
            JOIN events e ON er.event_id = e.event_id
            WHERE er.event_id = ? AND er.payment_status = 'completed'
        ");
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($registrations)) return 0;
        
        $event = $registrations[0];
        $eventDate = date('l, F j, Y', strtotime($event['event_date']));
        $eventTime = $event['event_time'] ? date('g:i A', strtotime($event['event_time'])) : 'TBD';
        $location = "{$event['venue_name']}, {$event['street_address']}, {$event['city']}, {$event['state']} {$event['zip_code']}";
        
        $subject = "Reminder: {$event['event_title']} in {$daysBeforeEvent} Days";
        
        $sentCount = 0;
        foreach ($registrations as $registration) {
            $htmlBody = $this->getEmailTemplate([
                'title' => 'Event Reminder',
                'content' => "
                    <p>Dear {$registration['first_name']} {$registration['last_name']},</p>
                    <p>This is a reminder that <strong>{$event['event_title']}</strong> is coming up in {$daysBeforeEvent} days!</p>
                    <p><strong>Event Details:</strong></p>
                    <ul>
                        <li><strong>Date:</strong> {$eventDate}</li>
                        <li><strong>Time:</strong> {$eventTime}</li>
                        <li><strong>Location:</strong> {$location}</li>
                    </ul>
                    <p>We're excited to see you there! Please arrive 15 minutes early for check-in.</p>
                    <p>If you need directions or have any questions, feel free to contact us.</p>
                ",
                'button_text' => 'View Event Details',
                'button_link' => SITE_URL . '/src/event_details.php?id=' . $eventId
            ]);
            
            if ($this->sendEmail(
                $registration['email'], 
                $registration['first_name'] . ' ' . $registration['last_name'],
                $subject, 
                $htmlBody, 
                'event_reminder',
                $registration['member_id'],
                $eventId
            )) {
                $sentCount++;
            }
        }
        
        return $sentCount;
    }
    
    public function sendPasswordResetEmail($email, $resetToken) {
        $stmt = $this->conn->prepare("SELECT member_id, first_name, last_name FROM members WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $member = $stmt->get_result()->fetch_assoc();
        
        if (!$member) return false;
        
        $resetLink = SITE_URL . "/src/reset_password.php?token=" . urlencode($resetToken);
        
        $subject = "Password Reset Request - KNAA";
        
        $htmlBody = $this->getEmailTemplate([
            'title' => 'Password Reset Request',
            'content' => "
                <p>Dear {$member['first_name']} {$member['last_name']},</p>
                <p>We received a request to reset your password for your KNAA member account.</p>
                <p>Click the button below to reset your password. This link will expire in 1 hour.</p>
                <p>If you didn't request a password reset, please ignore this email or contact us if you have concerns.</p>
            ",
            'button_text' => 'Reset Password',
            'button_link' => $resetLink
        ]);
        
        return $this->sendEmail(
            $email, 
            $member['first_name'] . ' ' . $member['last_name'],
            $subject, 
            $htmlBody, 
            'password_reset',
            $member['member_id']
        );
    }
    
    private function getEmailTemplate($data) {
        $title = $data['title'] ?? 'KNAA Notification';
        $content = $data['content'] ?? '';
        $buttonText = $data['button_text'] ?? null;
        $buttonLink = $data['button_link'] ?? null;
        
        $buttonHtml = '';
        if ($buttonText && $buttonLink) {
            $buttonHtml = "
                <table role=\"presentation\" style=\"margin: 30px 0;\">
                    <tr>
                        <td style=\"background: #DC143C; padding: 15px 35px; border-radius: 5px; text-align: center;\">
                            <a href=\"{$buttonLink}\" style=\"color: #FFFFFF; text-decoration: none; font-weight: 600; font-size: 16px;\">{$buttonText}</a>
                        </td>
                    </tr>
                </table>
            ";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset=\"UTF-8\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <title>{$title}</title>
        </head>
        <body style=\"margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5;\">
            <table role=\"presentation\" style=\"width: 100%; border-collapse: collapse;\">
                <tr>
                    <td style=\"padding: 40px 0;\">
                        <table role=\"presentation\" style=\"width: 600px; margin: 0 auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);\">
                            <tr>
                                <td style=\"background: #FFE8E8; padding: 30px; text-align: center;\">
                                    <h1 style=\"margin: 0; color: #0052A5; font-size: 28px;\">{$title}</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style=\"padding: 40px 30px; color: #333333; line-height: 1.6;\">
                                    {$content}
                                    {$buttonHtml}
                                </td>
                            </tr>
                            <tr>
                                <td style=\"background: #000514; color: rgba(255,255,255,0.8); padding: 30px; text-align: center; font-size: 14px;\">
                                    <p style=\"margin: 0 0 10px 0;\">Kenyan Nurses Association of America</p>
                                    <p style=\"margin: 0 0 10px 0;\">Email: info@kenyannursesusa.org</p>
                                    <p style=\"margin: 0; font-size: 12px; color: rgba(255,255,255,0.6);\">&copy; 2025 KNAA. All rights reserved.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
    }
}