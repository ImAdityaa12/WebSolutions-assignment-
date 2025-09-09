<?php
/**
 * Debug Contact Form Handler
 * Simple version to identify the 500 error
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set content type
header('Content-Type: application/json');

// Log all activity
$debug_log = [];
$debug_log[] = 'Script started at ' . date('Y-m-d H:i:s');

try {
    $debug_log[] = 'Request method: ' . $_SERVER['REQUEST_METHOD'];
    
    // Check if it's POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }
    
    $debug_log[] = 'POST data received: ' . json_encode(array_keys($_POST));
    
    // Check AJAX
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    $debug_log[] = 'Is AJAX: ' . ($isAjax ? 'Yes' : 'No');
    
    if (!$isAjax) {
        throw new Exception('Invalid request', 400);
    }
    
    // Try to include config
    $debug_log[] = 'Including config.php';
    
    // Define the constant first
    define('WEBSOLUTIONS_APP', true);
    
    if (file_exists('config.php')) {
        require_once 'config.php';
        $debug_log[] = 'Config loaded successfully';
    } else {
        $debug_log[] = 'Config file not found';
        throw new Exception('Configuration error', 500);
    }
    
    // Basic validation
    $errors = [];
    $requiredFields = ['firstName', 'lastName', 'email', 'subject', 'message'];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = ucfirst($field) . ' is required';
        }
    }
    
    $debug_log[] = 'Validation completed. Errors: ' . count($errors);
    
    if (empty($errors)) {
        $debug_log[] = 'No validation errors, processing form';
        
        // Simple success response
        $response = [
            'success' => true,
            'message' => 'Form processed successfully (debug mode)',
            'debug' => $debug_log,
            'timestamp' => date('c')
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Validation errors found',
            'errors' => $errors,
            'debug' => $debug_log,
            'timestamp' => date('c')
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $debug_log[] = 'Exception caught: ' . $e->getMessage();
    $debug_log[] = 'Exception code: ' . $e->getCode();
    
    http_response_code($e->getCode() ?: 500);
    
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => $debug_log,
        'error_code' => $e->getCode(),
        'timestamp' => date('c')
    ];
    
    echo json_encode($response);
} catch (Error $e) {
    $debug_log[] = 'Fatal error: ' . $e->getMessage();
    
    http_response_code(500);
    
    $response = [
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage(),
        'debug' => $debug_log,
        'timestamp' => date('c')
    ];
    
    echo json_encode($response);
}
?>