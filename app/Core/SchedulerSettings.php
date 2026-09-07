<?php

if (!ini_get('date.timezone') || ini_get('date.timezone') === 'UTC') {
    date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Manila');
}

class SchedulerSettings {
    private static string $configFile = __DIR__ . '/../../storage/scheduler_settings.json';

    /**
     * Get all scheduler settings
     */
    public static function getSettings(): array {
        if (file_exists(self::$configFile)) {
            $content = file_get_contents(self::$configFile);
            $data = json_decode($content, true);
            if (is_array($data)) {
                return $data;
            }
        }

        // Fallback to env or default 08:00
        $envTime = getenv('REMINDER_SCHEDULED_TIME');
        return [
            'scheduled_time' => (!empty($envTime) && preg_match('/^\d{2}:\d{2}$/', $envTime)) ? $envTime : '08:00',
            'last_run_time' => null,
            'last_run_date' => null
        ];
    }

    /**
     * Get the 24-hour scheduled time (e.g., '08:00' or '14:30')
     */
    public static function getScheduledTime(): string {
        $settings = self::getSettings();
        $time = $settings['scheduled_time'] ?? '08:00';
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) ? $time : '08:00';
    }

    /**
     * Get formatted human-readable time (e.g., '8:00 AM' or '2:30 PM')
     */
    public static function getFormattedTime(): string {
        $time = self::getScheduledTime();
        return date('g:i A', strtotime("2000-01-01 {$time}"));
    }

    /**
     * Set and save the scheduled time
     */
    public static function setScheduledTime(string $time): bool {
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
            return false;
        }

        $dir = dirname(self::$configFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $settings = self::getSettings();
        $settings['scheduled_time'] = $time;
        $saved = file_put_contents(self::$configFile, json_encode($settings, JSON_PRETTY_PRINT)) !== false;

        // Also update or append REMINDER_SCHEDULED_TIME in .env
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            if (strpos($envContent, 'REMINDER_SCHEDULED_TIME=') !== false) {
                $envContent = preg_replace('/REMINDER_SCHEDULED_TIME=.*$/m', "REMINDER_SCHEDULED_TIME={$time}", $envContent);
            } else {
                $envContent .= "\nREMINDER_SCHEDULED_TIME={$time}\n";
            }
            @file_put_contents($envFile, $envContent);
        }

        return $saved;
    }

    /**
     * Record execution timestamp
     */
    public static function markRunToday(): void {
        $dir = dirname(self::$configFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $settings = self::getSettings();
        $settings['last_run_date'] = date('Y-m-d');
        $settings['last_run_time'] = date('Y-m-d H:i:s');
        @file_put_contents(self::$configFile, json_encode($settings, JSON_PRETTY_PRINT));
    }

    /**
     * Get last run timestamp string
     */
    public static function getLastRunTime(): ?string {
        $settings = self::getSettings();
        return $settings['last_run_time'] ?? null;
    }
}
