<?php
/**
 * Flash Messages and Validation Error Display Helper
 */

if (!function_exists('display_flash_messages')) {
    /**
     * Display flash messages (success, error, info, warning)
     *
     * @param bool $swalToast If true, triggers SweetAlert2 toasts via inline script when Swal loads.
     * @param bool $inlineBanner Keep visible banners (in addition to toasts).
     */
    function display_flash_messages(bool $swalToast = true, bool $inlineBanner = false): void {
        $types = [
            'success_message' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'text' => 'text-green-800', 'icon' => '✓', 'swal_icon' => 'success'],
            'error_message' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-800', 'icon' => '✕', 'swal_icon' => 'error'],
            'info_message' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-800', 'icon' => 'ℹ', 'swal_icon' => 'info'],
            'warning_message' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'text' => 'text-yellow-800', 'icon' => '⚠', 'swal_icon' => 'warning'],
        ];

        $toastPayload = [];

        foreach ($types as $key => $style) {
            if (isset($_SESSION[$key])) {
                $msg = (string)$_SESSION[$key];
                if ($inlineBanner) {
                    echo '<div class="mb-4 p-4 rounded-lg border ' . $style['bg'] . ' ' . $style['border'] . ' ' . $style['text'] . '">';
                    echo '<span class="font-bold mr-2">' . $style['icon'] . '</span>';
                    echo h($msg);
                    echo '</div>';
                } else {
                    echo '<p class="sr-only" role="status">' . h($msg) . '</p>';
                }

                $toastPayload[] = [
                    'icon' => $style['swal_icon'],
                    'title' => $msg,
                ];
                unset($_SESSION[$key]);
            }
        }

        if ($swalToast && !empty($toastPayload)) {
            $json = json_encode($toastPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
            echo '<script>document.addEventListener("DOMContentLoaded",function(){if(typeof Swal==="undefined")return;var T=Swal.mixin({toast:true,position:"top-end",showConfirmButton:false,timer:4500,timerProgressBar:true});var q=' . $json . ';q.forEach(function(x){T.fire({icon:x.icon,title:x.title});});});</script>';
        }
    }
}

if (!function_exists('display_validation_errors')) {
    /**
     * Display validation errors
     *
     * @param bool $swalModal If true, opens a SweetAlert2 modal listing errors (still shows inline list).
     */
    function display_validation_errors(bool $swalModal = true): void {
        if (isset($_SESSION['validation_errors']) && !empty($_SESSION['validation_errors'])) {
            echo '<div class="mb-4 p-4 rounded-lg border bg-red-50 border-red-200 text-red-800">';
            echo '<div class="font-bold mb-2">Please fix the following errors:</div>';
            echo '<ul class="list-disc list-inside space-y-1">';
            foreach ($_SESSION['validation_errors'] as $error) {
                echo '<li>' . h($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';

            if ($swalModal) {
                $listHtml = '<ul style="text-align:left;margin:0;padding-left:1.25em">';
                foreach ($_SESSION['validation_errors'] as $error) {
                    $listHtml .= '<li>' . htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') . '</li>';
                }
                $listHtml .= '</ul>';
                $jsonHtml = json_encode($listHtml, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
                echo '<script>document.addEventListener("DOMContentLoaded",function(){if(typeof Swal==="undefined")return;Swal.fire({icon:"error",title:"Please fix the following",html:' . $jsonHtml . '});});</script>';
            }

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
