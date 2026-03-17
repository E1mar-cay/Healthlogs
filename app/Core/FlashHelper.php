<?php
/**
 * Flash Messages and Validation Error Display Helper
 */

if (!function_exists('display_flash_messages')) {
    /**
     * Display flash messages (success, error, info, warning)
     */
    function display_flash_messages(): void {
        $types = [
            'success_message' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'text' => 'text-green-800', 'icon' => '✓'],
            'error_message' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-800', 'icon' => '✕'],
            'info_message' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-800', 'icon' => 'ℹ'],
            'warning_message' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'text' => 'text-yellow-800', 'icon' => '⚠'],
        ];

        foreach ($types as $key => $style) {
            if (isset($_SESSION[$key])) {
                echo '<div class="mb-4 p-4 rounded-lg border ' . $style['bg'] . ' ' . $style['border'] . ' ' . $style['text'] . '">';
                echo '<span class="font-bold mr-2">' . $style['icon'] . '</span>';
                echo h($_SESSION[$key]);
                echo '</div>';
                unset($_SESSION[$key]);
            }
        }
    }
}

if (!function_exists('display_validation_errors')) {
    /**
     * Display validation errors
     */
    function display_validation_errors(): void {
        if (isset($_SESSION['validation_errors']) && !empty($_SESSION['validation_errors'])) {
            echo '<div class="mb-4 p-4 rounded-lg border bg-red-50 border-red-200 text-red-800">';
            echo '<div class="font-bold mb-2">Please fix the following errors:</div>';
            echo '<ul class="list-disc list-inside space-y-1">';
            foreach ($_SESSION['validation_errors'] as $error) {
                echo '<li>' . h($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
            unset($_SESSION['validation_errors']);
        }
    }
}

if (!function_exists('old')) {
    /**
     * Get old input value (for repopulating forms after validation error)
     */
    function old(string $key, $default = '') {
        $value = $_SESSION['old_input'][$key] ?? $default;
        
        // Clear old input after first use
        if (isset($_SESSION['old_input'])) {
            static $cleared = false;
            if (!$cleared) {
                register_shutdown_function(function() {
                    unset($_SESSION['old_input']);
                });
                $cleared = true;
            }
        }
        
        return $value;
    }
}

if (!function_exists('has_error')) {
    /**
     * Check if a field has validation error
     */
    function has_error(string $field): bool {
        if (!isset($_SESSION['validation_errors'])) {
            return false;
        }
        
        foreach ($_SESSION['validation_errors'] as $error) {
            if (stripos($error, $field) !== false) {
                return true;
            }
        }
        
        return false;
    }
}

if (!function_exists('error_class')) {
    /**
     * Return error CSS class if field has error
     */
    function error_class(string $field, string $errorClass = 'border-red-500'): string {
        return has_error($field) ? $errorClass : '';
    }
}
