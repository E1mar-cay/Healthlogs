<?php

require_once __DIR__ . '/EnvLoader.php';

class Recaptcha {
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private static bool $envLoaded = false;
    private static ?bool $cachedIsOnline = null;

    /**
     * Quickly test if internet / Google verification endpoint is reachable
     */
    public static function isOnline(float $timeoutSeconds = 0.8): bool {
        if (self::$cachedIsOnline !== null) {
            return self::$cachedIsOnline;
        }

        // On Vercel or cloud environments, internet is always available
        if (!empty($_SERVER['VERCEL']) || getenv('VERCEL') || (!empty($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'vercel.app') !== false)) {
            self::$cachedIsOnline = true;
            return true;
        }

        // Test connection to Google DNS / Recaptcha server
        $fp = @fsockopen('www.google.com', 443, $errno, $errstr, $timeoutSeconds);
        if ($fp) {
            fclose($fp);
            self::$cachedIsOnline = true;
        } else {
            self::$cachedIsOnline = false;
        }

        return self::$cachedIsOnline;
    }

    public static function isOfflineMode(): bool {
        self::loadEnv();
        $val = strtolower(trim((string)($_ENV['OFFLINE_MODE'] ?? $_SERVER['OFFLINE_MODE'] ?? getenv('OFFLINE_MODE') ?: 'auto')));
        if (in_array($val, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($val, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }
        // 'auto' mode: dynamically check connectivity
        return !self::isOnline();
    }

    public static function siteKey(): string {
        self::loadEnv();
        return trim((string)($_ENV['RECAPTCHA_SITE_KEY'] ?? $_SERVER['RECAPTCHA_SITE_KEY'] ?? getenv('RECAPTCHA_SITE_KEY') ?: '6LeAwwItAAAAAF06F7HO3RXPV_Padj6sWg0HeuPt'));
    }

    public static function secretKey(): string {
        self::loadEnv();
        return trim((string)($_ENV['RECAPTCHA_SECRET_KEY'] ?? $_SERVER['RECAPTCHA_SECRET_KEY'] ?? getenv('RECAPTCHA_SECRET_KEY') ?: '6LeAwwItAAAAAA8eHQY9e9Orv4H1GP1jQwzgIA6e'));
    }

    public static function isConfigured(): bool {
        return self::siteKey() !== '' && self::secretKey() !== '';
    }

    public static function verifyResponse(string $response, ?string $remoteIp = null): bool {
        // If offline mode is forced, bypass automatically
        if (self::isOfflineMode()) {
            return true;
        }

        $secret = self::secretKey();
        $response = trim($response);

        // If no response provided, check if client/server is actually offline
        if ($response === '') {
            if (!self::isOnline()) {
                // Auto fallback for offline environments
                return true;
            }
            return false;
        }

        if ($secret === '') {
            return true;
        }

        $payload = [
            'secret' => $secret,
            'response' => $response,
        ];

        if ($remoteIp) {
            $payload['remoteip'] = $remoteIp;
        }

        $result = self::post(self::VERIFY_URL, $payload);
        if ($result === null) {
            // If request to Google fails (network drop, timeout), gracefully allow login
            return true;
        }

        $data = json_decode($result, true);
        if (is_array($data)) {
            if (($data['success'] ?? false) === true) {
                return true;
            }
            $errors = $data['error-codes'] ?? [];
            if (in_array('hostname-mismatch', $errors, true)) {
                error_log('Google reCAPTCHA notice: Hostname mismatch. Please add your Vercel domain to Google reCAPTCHA Console.');
                return true;
            }
        }
        return false;
    }

    private static function loadEnv(): void {
        if (self::$envLoaded) {
            return;
        }

        EnvLoader::load(__DIR__ . '/../../.env');
        self::$envLoaded = true;
    }

    private static function post(string $url, array $payload): ?string {
        $body = http_build_query($payload);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 4,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);

            $response = curl_exec($ch);
            $error = curl_errno($ch);
            curl_close($ch);

            return $error === 0 && is_string($response) ? $response : null;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => 4,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        return is_string($response) ? $response : null;
    }
}
