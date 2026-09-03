<?php
/**
 * SMS Helper for HealthLogs
 * Supports Android Phone Gateway (Zero API Fee) & Semaphore API
 */

class SmsHelper {
    private string $provider;
    private string $gatewayUrl;
    private string $apiKey;
    private string $senderName;
    private bool $enabled;

    public function __construct() {
        $this->provider = strtolower(getenv('SMS_PROVIDER') ?: 'android_gateway');
        $this->gatewayUrl = getenv('SMS_GATEWAY_URL') ?: 'http://192.168.1.100:8080/send';
        $this->apiKey = getenv('SMS_GATEWAY_API_KEY') ?: (getenv('SEMAPHORE_API_KEY') ?: '');
        $this->senderName = getenv('SEMAPHORE_SENDER_NAME') ?: 'HealthLogs';
        $this->enabled = getenv('SMS_ENABLED') !== 'false';
    }

    /**
     * Normalize Philippine phone number to international (+63) or local (09) format
     */
    private function formatPhoneNumber(string $number): string {
        $clean = preg_replace('/[^0-9]/', '', $number);
        if (str_starts_with($clean, '63') && strlen($clean) === 12) {
            return '0' . substr($clean, 2);
        }
        if (str_starts_with($clean, '9') && strlen($clean) === 10) {
            return '0' . $clean;
        }
        return $clean;
    }

    /**
     * Send SMS Reminder to a patient
     */
    public function sendReminder(array $reminder, array $patient): bool {
        if (!$this->enabled) {
            error_log("SMS sending is disabled in .env");
            return false;
        }

        $phone = $this->formatPhoneNumber($patient['contact_no'] ?? '');
        if (empty($phone)) {
            error_log("Patient has no valid mobile contact number");
            return false;
        }

        $patientName = ($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? '');
        $dueDate = date('M d, Y', strtotime($reminder['due_date'] ?? 'now'));
        $type = ucfirst($reminder['reminder_type'] ?? 'Health');

        // Formulate SMS message text
        $message = "HealthLogs Reminder for {$patientName}:\n";
        $message .= "Your {$type} appointment/schedule is set for {$dueDate}.\n";
        if (!empty($reminder['message'])) {
            $message .= "Note: " . $reminder['message'] . "\n";
        }
        $message .= "Please visit the Barangay Health Center. Thank you!";

        return $this->sendSms($phone, $message);
    }

    /**
     * Send raw SMS message via selected provider
     */
    public function sendSms(string $to, string $message): bool {
        if ($this->provider === 'android_gateway') {
            return $this->sendViaAndroidGateway($to, $message);
        } elseif ($this->provider === 'semaphore') {
            return $this->sendViaSemaphore($to, $message);
        }

        error_log("Unsupported SMS provider: " . $this->provider);
        return false;
    }

    /**
     * Option #2: Send SMS via Android Gateway App (Zero API Fee)
     */
    private function sendViaAndroidGateway(string $to, string $message): bool {
        $payload = json_encode([
            'number' => $to,
            'message' => $message,
            'api_key' => $this->apiKey,
        ]);

        $ch = curl_init($this->gatewayUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("Android Gateway cURL Error: " . $curlError);
            return false;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log("Android Gateway HTTP Failure Code: {$httpCode}, Response: {$response}");
        return false;
    }

    /**
     * Option #1: Send SMS via Semaphore API
     */
    private function sendViaSemaphore(string $to, string $message): bool {
        $params = [
            'apikey' => $this->apiKey,
            'number' => $to,
            'message' => $message,
            'sendername' => $this->senderName
        ];

        $ch = curl_init('https://api.semaphore.co/api/v4/messages');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode >= 200 && $httpCode < 300);
    }
}
