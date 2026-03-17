<?php
/**
 * Input Validation Helper Functions
 * 
 * Provides common validation methods for user inputs
 */

if (!function_exists('validate_required')) {
    /**
     * Check if a value is not empty
     */
    function validate_required($value): bool {
        if (is_string($value)) {
            return trim($value) !== '';
        }
        return !empty($value);
    }
}

if (!function_exists('validate_email')) {
    /**
     * Validate email address
     */
    function validate_email(?string $email): bool {
        if (empty($email)) {
            return true; // Allow empty (use validate_required separately if needed)
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validate_phone')) {
    /**
     * Validate Philippine phone number (10-11 digits)
     */
    function validate_phone(?string $phone): bool {
        if (empty($phone)) {
            return true; // Allow empty
        }
        // Remove common separators
        $cleaned = preg_replace('/[\s\-\(\)]+/', '', $phone);
        // Check if it's 10-11 digits (with optional +63 prefix)
        return preg_match('/^(\+63|0)?[0-9]{10}$/', $cleaned) === 1;
    }
}

if (!function_exists('validate_date')) {
    /**
     * Validate date in Y-m-d format
     */
    function validate_date(?string $date): bool {
        if (empty($date)) {
            return true; // Allow empty
        }
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}

if (!function_exists('validate_datetime')) {
    /**
     * Validate datetime in Y-m-d H:i:s format
     */
    function validate_datetime(?string $datetime): bool {
        if (empty($datetime)) {
            return true; // Allow empty
        }
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        return $d && $d->format('Y-m-d H:i:s') === $datetime;
    }
}

if (!function_exists('validate_min_length')) {
    /**
     * Check minimum string length
     */
    function validate_min_length(?string $value, int $min): bool {
        if (empty($value)) {
            return true; // Allow empty
        }
        return mb_strlen($value) >= $min;
    }
}

if (!function_exists('validate_max_length')) {
    /**
     * Check maximum string length
     */
    function validate_max_length(?string $value, int $max): bool {
        if (empty($value)) {
            return true; // Allow empty
        }
        return mb_strlen($value) <= $max;
    }
}

if (!function_exists('validate_numeric')) {
    /**
     * Check if value is numeric
     */
    function validate_numeric($value): bool {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return true; // Allow empty
        }
        return is_numeric($value);
    }
}

if (!function_exists('validate_integer')) {
    /**
     * Check if value is an integer
     */
    function validate_integer($value): bool {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return true; // Allow empty
        }
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
}

if (!function_exists('validate_positive')) {
    /**
     * Check if numeric value is positive
     */
    function validate_positive($value): bool {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return true; // Allow empty
        }
        return is_numeric($value) && $value > 0;
    }
}

if (!function_exists('validate_in_array')) {
    /**
     * Check if value exists in array
     */
    function validate_in_array($value, array $allowed): bool {
        if (empty($value)) {
            return true; // Allow empty
        }
        return in_array($value, $allowed, true);
    }
}

if (!function_exists('validate_alpha')) {
    /**
     * Check if value contains only letters
     */
    function validate_alpha(?string $value): bool {
        if (empty($value)) {
            return true; // Allow empty
        }
        return preg_match('/^[a-zA-Z\s]+$/', $value) === 1;
    }
}

if (!function_exists('validate_alphanumeric')) {
    /**
     * Check if value contains only letters and numbers
     */
    function validate_alphanumeric(?string $value): bool {
        if (empty($value)) {
            return true; // Allow empty
        }
        return preg_match('/^[a-zA-Z0-9\s]+$/', $value) === 1;
    }
}

if (!function_exists('validate_url')) {
    /**
     * Validate URL
     */
    function validate_url(?string $url): bool {
        if (empty($url)) {
            return true; // Allow empty
        }
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('validate_blood_type')) {
    /**
     * Validate blood type
     */
    function validate_blood_type(?string $bloodType): bool {
        if (empty($bloodType)) {
            return true; // Allow empty
        }
        $valid = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'A', 'B', 'AB', 'O'];
        return in_array(strtoupper($bloodType), $valid, true);
    }
}

if (!function_exists('validate_sex')) {
    /**
     * Validate sex/gender
     */
    function validate_sex(?string $sex): bool {
        if (empty($sex)) {
            return false; // Required field
        }
        return in_array(strtolower($sex), ['male', 'female'], true);
    }
}

if (!function_exists('sanitize_string')) {
    /**
     * Sanitize string input
     */
    function sanitize_string(?string $value): ?string {
        if ($value === null) {
            return null;
        }
        return trim(strip_tags($value));
    }
}

if (!function_exists('sanitize_email')) {
    /**
     * Sanitize email input
     */
    function sanitize_email(?string $email): ?string {
        if ($email === null) {
            return null;
        }
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('sanitize_phone')) {
    /**
     * Sanitize phone number (remove non-numeric except +)
     */
    function sanitize_phone(?string $phone): ?string {
        if ($phone === null) {
            return null;
        }
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}

if (!function_exists('sanitize_integer')) {
    /**
     * Sanitize and convert to integer
     */
    function sanitize_integer($value): ?int {
        if ($value === null || $value === '') {
            return null;
        }
        return (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
}

if (!function_exists('sanitize_float')) {
    /**
     * Sanitize and convert to float
     */
    function sanitize_float($value): ?float {
        if ($value === null || $value === '') {
            return null;
        }
        return (float)filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}

/**
 * Validator class for complex validation scenarios
 */
class Validator {
    private array $errors = [];
    private array $data = [];
    private array $rules = [];

    public function __construct(array $data, array $rules = []) {
        $this->data = $data;
        $this->rules = $rules;
        
        if (!empty($rules)) {
            $this->validateAll();
        }
    }
    
    /**
     * Validate all fields with their rules
     */
    private function validateAll(): void {
        foreach ($this->rules as $field => $fieldRules) {
            $this->validateField($field, $fieldRules);
        }
    }
    
    /**
     * Validate a single field
     */
    private function validateField(string $field, string $rulesString): void {
        $value = $this->data[$field] ?? null;
        $rules = explode('|', $rulesString);
        
        foreach ($rules as $rule) {
            $this->applyRule($field, $value, $rule);
        }
    }
    
    /**
     * Apply a single validation rule
     */
    private function applyRule(string $field, $value, string $rule): void {
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $parameter = $parts[1] ?? null;
        
        $isValid = true;
        $message = '';
        
        switch ($ruleName) {
            case 'required':
                $isValid = validate_required($value);
                $message = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
                break;
                
            case 'email':
                if (!empty($value)) {
                    $isValid = validate_email($value);
                    $message = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid email address.';
                }
                break;
                
            case 'min':
                if (!empty($value)) {
                    $isValid = validate_min_length($value, (int)$parameter);
                    $message = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$parameter} characters.";
                }
                break;
                
            case 'max':
                if (!empty($value)) {
                    $isValid = validate_max_length($value, (int)$parameter);
                    $message = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$parameter} characters.";
                }
                break;
                
            case 'numeric':
                if (!empty($value)) {
                    $isValid = validate_numeric($value);
                    $message = ucfirst(str_replace('_', ' ', $field)) . ' must be a number.';
                }
                break;
                
            case 'username':
                if (!empty($value)) {
                    $isValid = preg_match('/^[a-zA-Z0-9_]+$/', $value);
                    $message = ucfirst(str_replace('_', ' ', $field)) . ' can only contain letters, numbers, and underscores.';
                }
                break;
                
            case 'same':
                $otherField = $parameter;
                $otherValue = $this->data[$otherField] ?? null;
                $isValid = $value === $otherValue;
                $message = ucfirst(str_replace('_', ' ', $field)) . ' must match ' . str_replace('_', ' ', $otherField) . '.';
                break;
        }
        
        if (!$isValid) {
            $this->addError($field, $message);
        }
    }
    
    /**
     * Add an error message
     */
    public function addError(string $field, string $message): void {
        $this->errors[$field][] = $message;
    }
    
    /**
     * Check if there are any errors
     */
    public function hasErrors(string $field = null): bool {
        if ($field) {
            return isset($this->errors[$field]);
        }
        return !empty($this->errors);
    }
    
    /**
     * Get all errors
     */
    public function getErrors(): array {
        return $this->errors;
    }
    
    /**
     * Get errors for a specific field
     */
    public function getFieldErrors(string $field): array {
        return $this->errors[$field] ?? [];
    }
}
