<?php
/**
 * Contact Form Handler
 * Processes contact form submissions with validation and security measures
 */

// Define application constant
define('WEBSOLUTIONS_APP', true);

// Include configuration
require_once 'config.php';

// Only include database if enabled
if (defined('DB_ENABLED') && DB_ENABLED) {
    try {
        require_once 'database.php';
    } catch (Exception $e) {
        logMessage('WARNING', 'Database not available: ' . $e->getMessage());
    }
}

// Set security headers
setSecurityHeaders();

// Set content type for JSON response
header('Content-Type: application/json');

class ContactFormHandler {
    private $errors = [];
    private $data = [];
    private $rateLimitFile;
    
    public function __construct() {
        $this->rateLimitFile = __DIR__ . '/rate_limit.json';
    }
    
    /**
     * Main handler method
     */
    public function handle() {
        try {
            // Only allow POST requests
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check if request is AJAX
            if (!isAjaxRequest()) {
                throw new Exception('Invalid request', 400);
            }
            
            // Check rate limiting
            if (!$this->checkRateLimit()) {
                throw new Exception('Too many requests. Please try again later.', 429);
            }
            
            // Validate and sanitize input
            $this->validateInput();
            
            // Check for honeypot (bot detection)
            if (!$this->checkHoneypot()) {
                throw new Exception('Spam detected', 400);
            }
            
            // If validation passes, process the form
            if (empty($this->errors)) {
                $this->processForm();
                $this->sendResponse(true, 'Thank you! Your message has been sent successfully.');
            } else {
                $this->sendResponse(false, 'Please correct the errors in your form.', $this->errors);
            }
            
        } catch (Exception $e) {
            // Log the error
            logMessage('ERROR', 'Contact form error: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'ip' => getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'post_data' => array_keys($_POST)
            ]);
            
            // Send user-friendly error message
            $userMessage = 'There was an error processing your request. Please try again later.';
            $httpCode = $e->getCode() ?: 500;
            
            // In development, show actual error
            if (defined('APP_ENV') && APP_ENV === 'development') {
                $userMessage = $e->getMessage();
            }
            
            $this->sendResponse(false, $userMessage, [], $httpCode);
        }
    }
    
    /**
     * Validate and sanitize form input
     */
    private function validateInput() {
        // Required fields
        $requiredFields = ['firstName', 'lastName', 'email', 'subject', 'message'];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $this->errors[$field] = ucfirst($field) . ' is required';
            } else {
                $this->data[$field] = sanitizeInput($_POST[$field]);
            }
        }
        
        // Optional fields
        $optionalFields = ['phone', 'newsletter'];
        
        foreach ($optionalFields as $field) {
            if (isset($_POST[$field])) {
                $this->data[$field] = sanitizeInput($_POST[$field]);
            }
        }
        
        // Specific validations
        if (!empty($this->data['firstName'])) {
            if (!$this->validateName($this->data['firstName'])) {
                $this->errors['firstName'] = 'First name contains invalid characters';
            }
        }
        
        if (!empty($this->data['lastName'])) {
            if (!$this->validateName($this->data['lastName'])) {
                $this->errors['lastName'] = 'Last name contains invalid characters';
            }
        }
        
        if (!empty($this->data['email'])) {
            if (!$this->validateEmail($this->data['email'])) {
                $this->errors['email'] = 'Please enter a valid email address';
            }
        }
        
        if (!empty($this->data['phone'])) {
            if (!$this->validatePhone($this->data['phone'])) {
                $this->errors['phone'] = 'Please enter a valid phone number';
            }
        }
        
        if (!empty($this->data['message'])) {
            if (strlen($this->data['message']) < 10) {
                $this->errors['message'] = 'Message must be at least 10 characters long';
            } elseif (strlen($this->data['message']) > 1000) {
                $this->errors['message'] = 'Message must be no more than 1000 characters';
            }
        }
        
        // Validate subject
        if (!empty($this->data['subject'])) {
            $validSubjects = ['general', 'quote', 'support', 'partnership', 'other'];
            if (!in_array($this->data['subject'], $validSubjects)) {
                $this->errors['subject'] = 'Please select a valid subject';
            }
        }
    }
    
    /**
     * Validate name format
     */
    private function validateName($name) {
        return preg_match('/^[a-zA-Z\s\'-]+$/', $name) && strlen($name) >= 2 && strlen($name) <= 50;
    }
    
    /**
     * Validate email format
     */
    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number format
     */
    private function validatePhone($phone) {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        return preg_match('/^(\+?1?)?[\d]{10,15}$/', $cleaned);
    }
    
    /**
     * Check honeypot field for bot detection
     */
    private function checkHoneypot() {
        // If honeypot field is filled, it's likely a bot
        return empty($_POST[HONEYPOT_FIELD_NAME]);
    }
    
    /**
     * Check rate limiting
     */
    private function checkRateLimit() {
        if (!RATE_LIMIT_ENABLED) {
            return true;
        }
        
        $ip = getClientIP();
        $currentTime = time();
        
        // Load existing rate limit data
        $rateLimitData = [];
        if (file_exists($this->rateLimitFile)) {
            $rateLimitData = json_decode(file_get_contents($this->rateLimitFile), true) ?: [];
        }
        
        // Clean old entries
        $rateLimitData = array_filter($rateLimitData, function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < RATE_LIMIT_TIME_WINDOW;
        });
        
        // Check current IP attempts
        $ipAttempts = array_filter($rateLimitData, function($timestamp, $attemptIp) use ($ip) {
            return $attemptIp === $ip;
        }, ARRAY_FILTER_USE_BOTH);
        
        if (count($ipAttempts) >= RATE_LIMIT_MAX_ATTEMPTS) {
            return false;
        }
        
        // Add current attempt
        $rateLimitData[$ip . '_' . $currentTime] = $currentTime;
        
        // Save rate limit data
        file_put_contents($this->rateLimitFile, json_encode($rateLimitData), LOCK_EX);
        
        return true;
    }
    
    /**
     * Process the form (send email, save to database, etc.)
     */
    private function processForm() {
        // Save to database (bonus feature) - only if enabled
        $submissionId = null;
        if (defined('DB_ENABLED') && DB_ENABLED) {
            $submissionId = $this->saveToDatabase();
        }
        
        // Send email notification
        $this->sendEmailNotification($submissionId);
        
        // Log successful submission with full form data
        logMessage('INFO', 'Contact form submitted successfully', [
            'submission_id' => $submissionId ?: 'N/A (DB disabled)',
            'form_data' => [
                'firstName' => $this->data['firstName'],
                'lastName' => $this->data['lastName'],
                'email' => $this->data['email'],
                'phone' => $this->data['phone'] ?? '',
                'subject' => $this->data['subject'],
                'message' => $this->data['message'],
                'newsletter' => isset($this->data['newsletter']) ? 'Yes' : 'No'
            ],
            'ip' => getClientIP()
        ]);
    }
    
    /**
     * Save form data to database
     */
    private function saveToDatabase() {
        // Skip database operations if not enabled
        if (!defined('DB_ENABLED') || !DB_ENABLED) {
            logMessage('INFO', 'Database disabled, skipping save');
            return null;
        }
        
        try {
            // Check if database classes exist
            if (!class_exists('ContactSubmission')) {
                logMessage('WARNING', 'ContactSubmission class not found, skipping database save');
                return null;
            }
            
            $submission = new ContactSubmission();
            
            // Set submission data
            $submission->firstName = $this->data['firstName'];
            $submission->lastName = $this->data['lastName'];
            $submission->email = $this->data['email'];
            $submission->phone = $this->data['phone'] ?? null;
            $submission->subject = $this->data['subject'];
            $submission->message = $this->data['message'];
            $submission->newsletterSubscription = isset($this->data['newsletter']);
            
            // Sanitize data
            $submission->sanitize();
            
            // Validate data
            $errors = $submission->validate();
            if (!empty($errors)) {
                logMessage('WARNING', 'Database validation failed', ['errors' => $errors]);
                return null; // Continue without database save
            }
            
            // Save to database
            if ($submission->save()) {
                return $submission->id;
            }
            
            return null;
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to save to database: ' . $e->getMessage());
            // Don't throw exception - continue with email sending
            return null;
        }
    }
    
    /**
     * Send email notification
     */
    private function sendEmailNotification($submissionId = null) {
        // Skip email sending in development mode
        if (defined('APP_ENV') && APP_ENV === 'development') {
            logMessage('INFO', 'Email sending skipped in development mode', [
                'to' => CONTACT_EMAIL_TO,
                'subject' => CONTACT_EMAIL_SUBJECT . ' - ' . ucfirst($this->data['subject']),
                'submission_id' => $submissionId
            ]);
            return;
        }
        
        $to = CONTACT_EMAIL_TO;
        $subject = CONTACT_EMAIL_SUBJECT . ' - ' . ucfirst($this->data['subject']);
        $from = CONTACT_EMAIL_FROM;
        $replyTo = CONTACT_EMAIL_REPLY_TO ? $this->data['email'] : $from;
        
        // Create email body
        $body = $this->createEmailBody($submissionId);
        
        // Email headers
        $headers = [
            'From: ' . $from,
            'Reply-To: ' . $replyTo,
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: 3'
        ];
        
        // Send email
        $success = mail($to, $subject, $body, implode("\r\n", $headers));
        
        if (!$success) {
            logMessage('ERROR', 'Failed to send email notification', [
                'to' => $to,
                'subject' => $subject
            ]);
            throw new Exception('Failed to send email. Please try again later.');
        }
    }
    
    /**
     * Create email body HTML
     */
    private function createEmailBody($submissionId = null) {
        $subjectLabels = [
            'general' => 'General Inquiry',
            'quote' => 'Request Quote',
            'support' => 'Technical Support',
            'partnership' => 'Partnership',
            'other' => 'Other'
        ];
        
        $subjectLabel = $subjectLabels[$this->data['subject']] ?? 'Unknown';
        $newsletter = isset($this->data['newsletter']) ? 'Yes' : 'No';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>New Contact Form Submission</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #2563eb; }
                .value { margin-top: 5px; }
                .footer { background: #6c757d; color: white; padding: 15px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>New Contact Form Submission</h1>
                </div>
                <div class="content">
                    <div class="field">
                        <div class="label">Name:</div>
                        <div class="value">' . htmlspecialchars($this->data['firstName'] . ' ' . $this->data['lastName']) . '</div>
                    </div>
                    <div class="field">
                        <div class="label">Email:</div>
                        <div class="value">' . htmlspecialchars($this->data['email']) . '</div>
                    </div>';
        
        if (!empty($this->data['phone'])) {
            $html .= '
                    <div class="field">
                        <div class="label">Phone:</div>
                        <div class="value">' . htmlspecialchars($this->data['phone']) . '</div>
                    </div>';
        }
        
        $html .= '
                    <div class="field">
                        <div class="label">Subject:</div>
                        <div class="value">' . htmlspecialchars($subjectLabel) . '</div>
                    </div>
                    <div class="field">
                        <div class="label">Message:</div>
                        <div class="value">' . nl2br(htmlspecialchars($this->data['message'])) . '</div>
                    </div>
                    <div class="field">
                        <div class="label">Newsletter Subscription:</div>
                        <div class="value">' . $newsletter . '</div>
                    </div>
                    <div class="field">
                        <div class="label">Submitted:</div>
                        <div class="value">' . date('Y-m-d H:i:s') . '</div>
                    </div>
                    <div class="field">
                        <div class="label">IP Address:</div>
                        <div class="value">' . getClientIP() . '</div>
                    </div>';
        
        if ($submissionId) {
            $html .= '
                    <div class="field">
                        <div class="label">Submission ID:</div>
                        <div class="value">#' . $submissionId . '</div>
                    </div>';
        }
        
        $html .= '
                </div>
                <div class="footer">
                    <p>This email was sent from the ' . APP_NAME . ' contact form.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Send JSON response
     */
    private function sendResponse($success, $message, $errors = [], $httpCode = 200) {
        http_response_code($httpCode);
        
        $response = [
            'success' => $success,
            'message' => $message,
            'timestamp' => date('c')
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response);
        exit;
    }
}

// Handle the request
$handler = new ContactFormHandler();
$handler->handle();
?>