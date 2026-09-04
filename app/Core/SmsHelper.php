<?php
/**
 * SMS Helper for HealthLogs
 * Supports TextBee Gateway (app.textbee.dev), Android Phone Gateway & Semaphore API
 */

class SmsHelper {
    private string $provider;
    private string $gatewayUrl;
    private string $apiKey;
    private string $textbeeApiKey;
    private string $textbeeDeviceId;
    private string $senderName;
    private bool $enabled;

    public function __construct() {
        $this->provider = strtolower(getenv('SMS_PROVIDER') ?: 'textbee');
        $this->gatewayUrl = getenv('SMS_GATEWAY_URL') ?: 'http://192.168.1.100:8080/send';
        $this->apiKey = getenv('SMS_GATEWAY_API_KEY') ?: (getenv('SEMAPHORE_API_KEY') ?: '');
        $this->textbeeApiKey = getenv('TEXTBEE_API_KEY') ?: (getenv('SMS_GATEWAY_API_KEY') ?: '');
        $this->textbeeDeviceId = getenv('TEXTBEE_DEVICE_ID') ?: '';
        $this->senderName = getenv('SEMAPHORE_SENDER_NAME') ?: 'HealthLogs';
        $this->enabled = getenv('SMS_ENABLED') !== 'false';
    }

    /**
     * Normalize Philippine phone number to international (+63) or local (09) format based on provider
     */
    private function formatPhoneNumber(string $number): string {
        $clean = preg_replace('/[^0-9+]/', '', $number);
        if (str_starts_with($clean, '+')) {
            return $clean;
        }
        $digits = preg_replace('/[^0-9]/', '', $number);
        if ($this->provider === 'textbee') {
            if (str_starts_with($digits, '63') && strlen($digits) === 12) {
                return '+' . $digits;
            }
            if (str_starts_with($digits, '09') && strlen($digits) === 11) {
                return '+63' . substr($digits, 1);
            }
            if (str_starts_with($digits, '9') && strlen($digits) === 10) {
                return '+63' . $digits;
            }
            return '+' . $digits;
        } else {
            if (str_starts_with($digits, '63') && strlen($digits) === 12) {
                return '0' . substr($digits, 2);
            }
            if (str_starts_with($digits, '9') && strlen($digits) === 10) {
                return '0' . $digits;
            }
            return $digits;
        }
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
        $formattedTo = $this->formatPhoneNumber($to);

        if ($this->provider === 'textbee') {
            return $this->sendViaTextbee($formattedTo, $message);
        } elseif ($this->provider === 'android_gateway') {
            return $this->sendViaAndroidGateway($formattedTo, $message);
        } elseif ($this->provider === 'semaphore') {
            return $this->sendViaSemaphore($formattedTo, $message);
        }

        error_log("Unsupported SMS provider: " . $this->provider);
        return false;
    }

    /**
     * Option #1: Send SMS via TextBee API (https://app.textbee.dev)
     */
    private function sendViaTextbee(string $to, string $message): bool {
        if (empty($this->textbeeApiKey)) {
            error_log("TextBee Error: TEXTBEE_API_KEY is not set in .env");
            return false;
        }

        $url = 'https://api.textbee.dev/api/v1/gateway/send-sms';
        $payloadData = [
            'recipients' => [$to],
            'message' => $message
        ];

        if (!empty($this->textbeeDeviceId)) {
            $payloadData['deviceId'] = $this->textbeeDeviceId;
        }

        $payload = json_encode($payloadData);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->textbeeApiKey,
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("TextBee cURL Error: " . $curlError);
            return false;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log("TextBee HTTP Failure Code: {$httpCode}, Response: {$response}");
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
     * Option #3: Send SMS via Semaphore API
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

