<?php
/**
 * Configuration File
 * Contains application settings and constants
 */

// Prevent direct access
if (!defined('WEBSOLUTIONS_APP')) {
    die('Direct access not permitted');
}

// Application Settings
define('APP_NAME', 'WebSolutions');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // development, staging, production

// Email Configuration
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls'); // tls, ssl, or empty for no encryption

// Contact Form Settings
define('CONTACT_EMAIL_TO', 'your-email@gmail.com'); // Change this to your actual email
define('CONTACT_EMAIL_FROM', 'noreply@localhost');
define('CONTACT_EMAIL_SUBJECT', 'New Contact Form Submission');
define('CONTACT_EMAIL_REPLY_TO', true); // Use sender's email as reply-to

// Security Settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour in seconds
define('MAX_FORM_SUBMISSIONS_PER_HOUR', 5);
define('HONEYPOT_FIELD_NAME', 'website'); // Hidden field to catch bots

// Rate Limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_MAX_ATTEMPTS', 5);
define('RATE_LIMIT_TIME_WINDOW', 3600); // 1 hour in seconds

// File Upload Settings (if needed in future)
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Database Configuration (optional - for bonus feature)
define('DB_ENABLED', false); // Set to true if you want to use database
define('DB_HOST', 'localhost');
define('DB_NAME', 'websolutions');
define('DB_USER', 'websolutions_user');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Logging Settings
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_FILE', __DIR__ . '/logs/app.log');

// Timezone
date_default_timezone_set('America/New_York');

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);

// Security Headers Function
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // XSS Protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (adjust as needed)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; img-src 'self' data:;");
}

// Utility Functions
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function getClientIP() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    return $input;
}

function logMessage($level, $message, $context = []) {
    if (!LOG_ENABLED) return;
    
    $logLevels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
    $currentLevel = $logLevels[LOG_LEVEL] ?? 1;
    $messageLevel = $logLevels[$level] ?? 1;
    
    if ($messageLevel < $currentLevel) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    
    $logEntry = "[{$timestamp}] [{$level}] [{$ip}] {$message}{$contextStr}" . PHP_EOL;
    
    // Ensure log directory exists
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    // Try to write to log file, fail silently if not possible
    @file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>