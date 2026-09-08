<?php
/**
 * TokenFlow Pro — Input Validation
 */

class Validation {
    private array $errors = [];
    private array $data;
    
    public function __construct(array $data) {
        $this->data = $data;
    }
    
    /**
     * Create a new validation instance
     */
    public static function make(array $data): self {
        return new self($data);
    }
    
    /**
     * Require a field to be present and non-empty
     */
    public function required(string $field, ?string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = "$label is required.";
        }
        return $this;
    }
    
    /**
     * Validate email format
     */
    public function email(string $field, ?string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "$label must be a valid email address.";
        }
        return $this;
    }
    
    /**
     * Validate minimum length
     */
    public function minLength(string $field, int $min, ?string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = "$label must be at least $min characters.";
        }
        return $this;
    }
    
    /**
     * Validate maximum length
     */
    public function maxLength(string $field, int $max, ?string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = "$label must not exceed $max characters.";
        }
        return $this;
    }
    
    /**
     * Validate numeric value
     */
    public function numeric(string $field, ?string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = "$label must be a number.";
        }
        return $this;
    }
    
    /**
     * Validate integer value
     */
    public function integer(string $field, ?string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field]) && !ctype_digit(strval($this->data[$field]))) {
            $this->errors[$field] = "$label must be a whole number.";
        }
        return $this;
    }
    
    /**
     * Validate value is in a set
     */
    public function in(string $field, array $allowed, ?string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field]) && !in_array($this->data[$field], $allowed)) {
            $this->errors[$field] = "$label must be one of: " . implode(', ', $allowed) . ".";
        }
        return $this;
    }
    
    /**
     * Validate two fields match
     */
    public function matches(string $field, string $matchField, ?string $label = null, ?string $matchLabel = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $matchLabel = $matchLabel ?? ucfirst(str_replace('_', ' ', $matchField));
        if (($this->data[$field] ?? '') !== ($this->data[$matchField] ?? '')) {
            $this->errors[$field] = "$label must match $matchLabel.";
        }
        return $this;
    }
    
    /**
     * Validate phone number
     */
    public function phone(string $field, ?string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field]) && !preg_match('/^[+]?[\d\s\-()]{7,20}$/', $this->data[$field])) {
            $this->errors[$field] = "$label must be a valid phone number.";
        }
        return $this;
    }
    
    /**
     * Validate date format
     */
    public function date(string $field, string $format = 'Y-m-d', ?string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field])) {
            $d = \DateTime::createFromFormat($format, $this->data[$field]);
            if (!$d || $d->format($format) !== $this->data[$field]) {
                $this->errors[$field] = "$label must be a valid date ($format).";
            }
        }
        return $this;
    }
    
    /**
     * Check if validation passed
     */
    public function passes(): bool {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails(): bool {
        return !empty($this->errors);
    }
    
    /**
     * Get validation errors
     */
    public function errors(): array {
        return $this->errors;
    }
    
    /**
     * Get first error message
     */
    public function firstError(): ?string {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
    
    /**
     * Get sanitized value
     */
    public function get(string $field, $default = null) {
        $value = $this->data[$field] ?? $default;
        if (is_string($value)) {
            return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }
    
    /**
     * Get raw (unsanitized) value
     */
    public function getRaw(string $field, $default = null) {
        return $this->data[$field] ?? $default;
    }
}
