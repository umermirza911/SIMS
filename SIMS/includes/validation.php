<?php
/**
 * Input Validation and Sanitization Module
 * Defensive coding practices for input handling
 */

/**
 * Sanitize string - prevent XSS
 * @param string $input
 * @return string
 */
function sanitizeString($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate and sanitize email
 * @param string $email
 * @return string|false
 */
function validateEmail($email)
{
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

/**
 * Validate integer
 * @param mixed $value
 * @return int|false
 */
function validateInt($value)
{
    return filter_var($value, FILTER_VALIDATE_INT);
}

/**
 * Validate positive integer
 * @param mixed $value
 * @return int|false
 */
function validatePositiveInt($value)
{
    $int = filter_var($value, FILTER_VALIDATE_INT);
    return ($int !== false && $int > 0) ? $int : false;
}

/**
 * Validate date format (YYYY-MM-DD)
 * @param string $date
 * @return bool
 */
function validateDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Validate time format (HH:MM:SS or HH:MM)
 * @param string $time
 * @return bool
 */
function validateTime($time)
{
    return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $time);
}

/**
 * Validate required field
 * @param mixed $value
 * @return bool
 */
function isRequired($value)
{
    if (is_string($value)) {
        return !empty(trim($value));
    }
    return !empty($value);
}

/**
 * Validate string length
 * @param string $value
 * @param int $min
 * @param int $max
 * @return bool
 */
function validateLength($value, $min, $max)
{
    $length = strlen($value);
    return $length >= $min && $length <= $max;
}

/**
 * Validate password strength
 * @param string $password
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePassword($password)
{
    $errors = [];

    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long';
    }

    // Additional password complexity rules (optional)
    // Uncomment to enforce stronger passwords
    /*
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    */

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Hash password
 * @param string $password
 * @return string
 */
function hashPassword($password)
{
    return password_hash($password, PASSWORD_ALGO);
}

/**
 * Sanitize array of values
 * @param array $array
 * @return array
 */
function sanitizeArray($array)
{
    return array_map('sanitizeString', $array);
}

/**
 * Validate enum value
 * @param mixed $value
 * @param array $allowed_values
 * @return bool
 */
function validateEnum($value, $allowed_values)
{
    return in_array($value, $allowed_values, true);
}

/**
 * Validate and sanitize form input
 * @param array $rules Format: ['field_name' => ['type' => 'string', 'required' => true, ...]]
 * @param array $data Input data (usually $_POST)
 * @return array ['valid' => bool, 'data' => array, 'errors' => array]
 */
function validateForm($rules, $data)
{
    $validated = [];
    $errors = [];

    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;

        // Check if required
        if (isset($rule['required']) && $rule['required'] && !isRequired($value)) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            continue;
        }

        // Skip validation if empty and not required
        if (empty($value) && (!isset($rule['required']) || !$rule['required'])) {
            $validated[$field] = null;
            continue;
        }

        // Validate based on type
        switch ($rule['type']) {
            case 'email':
                $clean = validateEmail($value);
                if ($clean === false) {
                    $errors[$field] = 'Invalid email address';
                } else {
                    $validated[$field] = $clean;
                }
                break;

            case 'int':
                $clean = validateInt($value);
                if ($clean === false) {
                    $errors[$field] = 'Must be a valid number';
                } else {
                    $validated[$field] = $clean;
                }
                break;

            case 'positive_int':
                $clean = validatePositiveInt($value);
                if ($clean === false) {
                    $errors[$field] = 'Must be a positive number';
                } else {
                    $validated[$field] = $clean;
                }
                break;

            case 'date':
                if (!validateDate($value)) {
                    $errors[$field] = 'Invalid date format';
                } else {
                    $validated[$field] = $value;
                }
                break;

            case 'time':
                if (!validateTime($value)) {
                    $errors[$field] = 'Invalid time format';
                } else {
                    $validated[$field] = $value;
                }
                break;

            case 'enum':
                if (!validateEnum($value, $rule['values'])) {
                    $errors[$field] = 'Invalid value selected';
                } else {
                    $validated[$field] = $value;
                }
                break;

            case 'string':
            default:
                $clean = sanitizeString($value);

                // Check length if specified
                if (isset($rule['min']) || isset($rule['max'])) {
                    $min = $rule['min'] ?? 0;
                    $max = $rule['max'] ?? PHP_INT_MAX;
                    if (!validateLength($clean, $min, $max)) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) .
                            " must be between $min and $max characters";
                    }
                }

                $validated[$field] = $clean;
                break;
        }
    }

    return [
        'valid' => empty($errors),
        'data' => $validated,
        'errors' => $errors
    ];
}
