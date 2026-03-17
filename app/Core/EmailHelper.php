<?php
/**
 * Email Helper using PHPMailer
 * Handles sending email notifications for reminders
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper {
    private PHPMailer $mailer;
    private bool $enabled;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->enabled = getenv('MAIL_ENABLED') !== 'false';
        
        if ($this->enabled) {
            $this->configure();
        }
    }
    
    /**
     * Configure PHPMailer with SMTP settings
     */
    private function configure(): void {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = getenv('MAIL_USERNAME') ?: '';
            $this->mailer->Password = getenv('MAIL_PASSWORD') ?: '';
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = (int)(getenv('MAIL_PORT') ?: 587);
            
            // Default sender
            $fromEmail = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@healthlogs.local';
            $fromName = getenv('MAIL_FROM_NAME') ?: 'HealthLogs';
            $this->mailer->setFrom($fromEmail, $fromName);
            
            // Character set
            $this->mailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
        }
    }
    
    /**
     * Send reminder email
     */
    public function sendReminder(array $reminder, array $patient): bool {
        if (!$this->enabled) {
            error_log("Email sending is disabled");
            return false;
        }
        
        if (empty($patient['email'])) {
            error_log("Patient has no email address");
            return false;
        }
        
        try {
            // Reset recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipient
            $this->mailer->addAddress($patient['email'], $patient['first_name'] . ' ' . $patient['last_name']);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $this->getSubject($reminder);
            $this->mailer->Body = $this->getHtmlBody($reminder, $patient);
            $this->mailer->AltBody = $this->getTextBody($reminder, $patient);
            
            $this->mailer->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Get email subject based on reminder type
     */
    private function getSubject(array $reminder): string {
        $types = [
            'immunization' => 'Immunization Reminder',
            'prenatal' => 'Prenatal Visit Reminder',
            'postnatal' => 'Postnatal Visit Reminder',
            'tb' => 'TB Follow-up Reminder',
            'general' => 'Health Appointment Reminder'
        ];
        
        return $types[$reminder['reminder_type']] ?? 'Health Reminder';
    }
    
    /**
     * Get HTML email body
     */
    private function getHtmlBody(array $reminder, array $patient): string {
        $name = htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']);
        $message = htmlspecialchars($reminder['message']);
        $dueDate = date('F j, Y', strtotime($reminder['due_date']));
        $dayOfWeek = date('l', strtotime($reminder['due_date']));
        $type = ucfirst($reminder['reminder_type']);
        
        // Type-specific colors and icons
        $typeConfig = [
            'immunization' => ['color' => '#10b981', 'icon' => '💉', 'bg' => '#d1fae5'],
            'prenatal' => ['color' => '#ec4899', 'icon' => '🤰', 'bg' => '#fce7f3'],
            'postnatal' => ['color' => '#f59e0b', 'icon' => '👶', 'bg' => '#fef3c7'],
            'tb' => ['color' => '#ef4444', 'icon' => '🏥', 'bg' => '#fee2e2'],
            'general' => ['color' => '#0ea5a4', 'icon' => '📋', 'bg' => '#ccfbf1']
        ];
        
        $config = $typeConfig[$reminder['reminder_type']] ?? $typeConfig['general'];
        $color = $config['color'];
        $icon = $config['icon'];
        $bgColor = $config['bg'];
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #1e293b; background: #f1f5f9; }
        .email-wrapper { background: #f1f5f9; padding: 40px 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); }
        .header { background: linear-gradient(135deg, #0ea5a4 0%, #0891b2 50%, #2563eb 100%); color: white; padding: 40px 30px; text-align: center; position: relative; }
        .header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="rgba(255,255,255,0.05)"/></svg>') repeat; opacity: 0.3; }
        .header-content { position: relative; z-index: 1; }
        .logo { font-size: 36px; font-weight: 800; margin: 0; letter-spacing: -0.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .tagline { margin: 8px 0 0 0; opacity: 0.95; font-size: 15px; font-weight: 500; }
        .content { padding: 40px 30px; }
        .badge-container { margin-bottom: 24px; }
        .badge { display: inline-flex; align-items: center; gap: 8px; padding: 8px 20px; background: {$color}; color: white; border-radius: 50px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .greeting { font-size: 28px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0; }
        .message { font-size: 16px; color: #475569; margin: 0 0 30px 0; line-height: 1.7; }
        .date-card { background: {$bgColor}; border-left: 5px solid {$color}; border-radius: 12px; padding: 24px; margin: 30px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .date-label { font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .date-value { font-size: 32px; font-weight: 800; color: {$color}; margin: 4px 0; line-height: 1.2; }
        .day-value { font-size: 18px; font-weight: 600; color: #475569; margin-top: 4px; }
        .info-box { background: #f8fafc; border-radius: 12px; padding: 20px; margin: 24px 0; border: 1px solid #e2e8f0; }
        .info-box p { margin: 0; color: #475569; font-size: 15px; line-height: 1.6; }
        .cta-container { text-align: center; margin: 32px 0; }
        .cta-text { color: #64748b; font-size: 14px; margin-bottom: 12px; }
        .divider { height: 1px; background: linear-gradient(to right, transparent, #e2e8f0, transparent); margin: 32px 0; }
        .footer { background: #f8fafc; padding: 32px 30px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer-text { color: #94a3b8; font-size: 13px; line-height: 1.6; margin: 8px 0; }
        .footer-brand { color: #0ea5a4; font-weight: 700; }
        @media only screen and (max-width: 600px) {
            .email-wrapper { padding: 20px 10px; }
            .header { padding: 30px 20px; }
            .content { padding: 30px 20px; }
            .logo { font-size: 28px; }
            .greeting { font-size: 24px; }
            .date-value { font-size: 28px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="container">
            <div class="header">
                <div class="header-content">
                    <h1 class="logo">⚕️ HealthLogs</h1>
                    <p class="tagline">Barangay Health Management System</p>
                </div>
            </div>
            
            <div class="content">
                <div class="badge-container">
                    <span class="badge">{$icon} {$type} Reminder</span>
                </div>
                
                <h2 class="greeting">Hello, {$name}! 👋</h2>
                
                <p class="message">{$message}</p>
                
                <div class="date-card">
                    <div class="date-label">📅 Scheduled Appointment</div>
                    <div class="date-value">{$dueDate}</div>
                    <div class="day-value">{$dayOfWeek}</div>
                </div>
                
                <div class="info-box">
                    <p><strong>⏰ Important:</strong> Please arrive 15 minutes before your scheduled time. Bring your health records and valid ID.</p>
                </div>
                
                <div class="divider"></div>
                
                <div class="cta-container">
                    <p class="cta-text">Need to reschedule or have questions?</p>
                    <p style="color: #64748b; font-size: 14px;">📞 Visit your local Barangay Health Unit</p>
                </div>
            </div>
            
            <div class="footer">
                <p class="footer-text">This is an automated reminder from <span class="footer-brand">HealthLogs</span></p>
                <p class="footer-text">Barangay Health Management System</p>
                <p class="footer-text" style="margin-top: 16px; font-size: 12px;">📧 Please do not reply to this email</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Get plain text email body
     */
    private function getTextBody(array $reminder, array $patient): string {
        $name = $patient['first_name'] . ' ' . $patient['last_name'];
        $message = $reminder['message'];
        $dueDate = date('F j, Y', strtotime($reminder['due_date']));
        $type = ucfirst($reminder['reminder_type']);
        
        return <<<TEXT
HealthLogs - Barangay Health Unit
{$type} Reminder

Hello, {$name}!

{$message}

Scheduled Date: {$dueDate}

Please make sure to attend your appointment on the scheduled date. If you need to reschedule, please contact your Barangay Health Unit.

---
This is an automated reminder from HealthLogs - Barangay Health Management System
Please do not reply to this email.
TEXT;
    }
    
    /**
     * Test email configuration
     */
    public function testConnection(): array {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Email is disabled'];
        }
        
        try {
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();
            return ['success' => true, 'message' => 'SMTP connection successful'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

/**
 * Helper function to send reminder email
 */
function send_reminder_email(array $reminder, array $patient): bool {
    $emailHelper = new EmailHelper();
    return $emailHelper->sendReminder($reminder, $patient);
}
