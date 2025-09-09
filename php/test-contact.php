<?php
/**
 * Simple Contact Form Test
 * Minimal version to test form submission without database
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set content type for JSON response
header('Content-Type: application/json');

// Set security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }
    
    // Check if request is AJAX
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    if (!$isAjax) {
        throw new Exception('Invalid request', 400);
    }
    
    // Basic validation
    $errors = [];
    $data = [];
    
    // Required fields
    $requiredFields = ['firstName', 'lastName', 'email', 'subject', 'message'];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = ucfirst($field) . ' is required';
        } else {
            $data[$field] = trim(htmlspecialchars($_POST[$field], ENT_QUOTES, 'UTF-8'));
        }
    }
    
    // Email validation
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    // If validation passes
    if (empty($errors)) {
        // Simple email sending (without database)
        $to = 'info@websolutions.com'; // Change this to your email
        $subject = 'New Contact Form Submission - ' . ucfirst($data['subject']);
        $message = "
        New contact form submission:
        
        Name: {$data['firstName']} {$data['lastName']}
        Email: {$data['email']}
        Subject: {$data['subject']}
        Message: {$data['message']}
        
        Submitted: " . date('Y-m-d H:i:s') . "
        IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown');
        
        $headers = [
            'From: noreply@websolutions.com',
            'Reply-To: ' . $data['email'],
            'Content-Type: text/plain; charset=UTF-8'
        ];
        
        // Try to send email (this might fail on local development)
        $emailSent = @mail($to, $subject, $message, implode("\r\n", $headers));
        
        // Always return success for testing (even if email fails)
        $response = [
            'success' => true,
            'message' => 'Thank you! Your message has been received.',
            'email_sent' => $emailSent,
            'timestamp' => date('c')
        ];
        
    } else {
        $response = [
            'success' => false,
            'message' => 'Please correct the errors in your form.',
            'errors' => $errors,
            'timestamp' => date('c')
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'timestamp' => date('c')
    ];
    
    echo json_encode($response);
}
?>