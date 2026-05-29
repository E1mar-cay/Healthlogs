<?php

require_once __DIR__ . '/EnvLoader.php';

class Recaptcha {
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private static bool $envLoaded = false;

    public static function siteKey(): string {
        self::loadEnv();
        return trim((string)(getenv('RECAPTCHA_SITE_KEY') ?: ''));
    }

    public static function secretKey(): string {
        self::loadEnv();
        return trim((string)(getenv('RECAPTCHA_SECRET_KEY') ?: ''));
    }

    public static function isConfigured(): bool {
        return self::siteKey() !== '' && self::secretKey() !== '';
    }

    public static function verifyResponse(string $response, ?string $remoteIp = null): bool {
        $secret = self::secretKey();
        $response = trim($response);

        if ($secret === '' || $response === '') {
            return false;
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
            return false;
        }

        $data = json_decode($result, true);
        return is_array($data) && ($data['success'] ?? false) === true;
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
                CURLOPT_TIMEOUT => 10,
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
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        return is_string($response) ? $response : null;
    }
}
