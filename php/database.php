<?php
/**
 * Database Connection and Management
 * Handles database operations for contact form submissions
 */

// Prevent direct access
if (!defined('WEBSOLUTIONS_APP')) {
    die('Direct access not permitted');
}

class Database {
    private $connection;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    
    public function __construct() {
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
    }
    
    /**
     * Create database connection
     */
    public function connect() {
        if ($this->connection !== null) {
            return $this->connection;
        }
        
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
            logMessage('INFO', 'Database connection established');
            return $this->connection;
            
        } catch (PDOException $e) {
            logMessage('ERROR', 'Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->connect();
    }
    
    /**
     * Create database tables if they don't exist
     */
    public function createTables() {
        try {
            $pdo = $this->connect();
            
            // Contact submissions table
            $contactSubmissionsSQL = "
                CREATE TABLE IF NOT EXISTS contact_submissions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    first_name VARCHAR(50) NOT NULL,
                    last_name VARCHAR(50) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    phone VARCHAR(20) NULL,
                    subject ENUM('general', 'quote', 'support', 'partnership', 'other') NOT NULL,
                    message TEXT NOT NULL,
                    newsletter_subscription BOOLEAN DEFAULT FALSE,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent TEXT NULL,
                    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_created_at (created_at),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            // Rate limiting table
            $rateLimitSQL = "
                CREATE TABLE IF NOT EXISTS rate_limits (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    attempts INT DEFAULT 1,
                    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NOT NULL,
                    INDEX idx_ip_action (ip_address, action),
                    INDEX idx_expires_at (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            // Application logs table
            $logsSQL = "
                CREATE TABLE IF NOT EXISTS application_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    level ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR') NOT NULL,
                    message TEXT NOT NULL,
                    context JSON NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_level (level),
                    INDEX idx_created_at (created_at),
                    INDEX idx_ip_address (ip_address)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $pdo->exec($contactSubmissionsSQL);
            $pdo->exec($rateLimitSQL);
            $pdo->exec($logsSQL);
            
            logMessage('INFO', 'Database tables created successfully');
            return true;
            
        } catch (PDOException $e) {
            logMessage('ERROR', 'Failed to create database tables: ' . $e->getMessage());
            throw new Exception('Failed to create database tables');
        }
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $pdo = $this->connect();
            $stmt = $pdo->query('SELECT 1');
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Close database connection
     */
    public function close() {
        $this->connection = null;
    }
}

/**
 * Contact Submission Model
 */
class ContactSubmission {
    private $db;
    private $table = 'contact_submissions';
    
    public $id;
    public $firstName;
    public $lastName;
    public $email;
    public $phone;
    public $subject;
    public $message;
    public $newsletterSubscription;
    public $ipAddress;
    public $userAgent;
    public $status;
    public $createdAt;
    public $updatedAt;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Save contact submission to database
     */
    public function save() {
        try {
            $pdo = $this->db->connect();
            
            $sql = "INSERT INTO {$this->table} 
                    (first_name, last_name, email, phone, subject, message, 
                     newsletter_subscription, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $this->firstName,
                $this->lastName,
                $this->email,
                $this->phone,
                $this->subject,
                $this->message,
                $this->newsletterSubscription ? 1 : 0,
                $this->ipAddress,
                $this->userAgent
            ]);
            
            if ($result) {
                $this->id = $pdo->lastInsertId();
                logMessage('INFO', 'Contact submission saved to database', ['id' => $this->id]);
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            logMessage('ERROR', 'Failed to save contact submission: ' . $e->getMessage());
            throw new Exception('Failed to save contact submission');
        }
    }
    
    /**
     * Validate contact submission data
     */
    public function validate() {
        $errors = [];
        
        if (empty($this->firstName) || strlen($this->firstName) < 2 || strlen($this->firstName) > 50) {
            $errors['firstName'] = 'First name must be between 2 and 50 characters';
        }
        
        if (empty($this->lastName) || strlen($this->lastName) < 2 || strlen($this->lastName) > 50) {
            $errors['lastName'] = 'Last name must be between 2 and 50 characters';
        }
        
        if (empty($this->email) || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email address is required';
        }
        
        if (!empty($this->phone) && !preg_match('/^(\+?1?)?[\d]{10,15}$/', preg_replace('/[^\d+]/', '', $this->phone))) {
            $errors['phone'] = 'Invalid phone number format';
        }
        
        if (empty($this->subject) || !in_array($this->subject, ['general', 'quote', 'support', 'partnership', 'other'])) {
            $errors['subject'] = 'Valid subject is required';
        }
        
        if (empty($this->message) || strlen($this->message) < 10 || strlen($this->message) > 1000) {
            $errors['message'] = 'Message must be between 10 and 1000 characters';
        }
        
        return $errors;
    }
    
    /**
     * Sanitize input data
     */
    public function sanitize() {
        $this->firstName = sanitizeInput($this->firstName);
        $this->lastName = sanitizeInput($this->lastName);
        $this->email = sanitizeInput($this->email);
        $this->phone = sanitizeInput($this->phone);
        $this->subject = sanitizeInput($this->subject);
        $this->message = sanitizeInput($this->message);
        $this->ipAddress = getClientIP();
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Get all contact submissions
     */
    public function getAll($limit = 50, $offset = 0, $status = null) {
        try {
            $pdo = $this->db->connect();
            
            $sql = "SELECT * FROM {$this->table}";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            logMessage('ERROR', 'Failed to retrieve contact submissions: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get contact submission by ID
     */
    public function getById($id) {
        try {
            $pdo = $this->db->connect();
            
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            logMessage('ERROR', 'Failed to retrieve contact submission: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update submission status
     */
    public function updateStatus($id, $status) {
        try {
            $pdo = $this->db->connect();
            
            $sql = "UPDATE {$this->table} SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            
            return $stmt->execute([$status, $id]);
            
        } catch (PDOException $e) {
            logMessage('ERROR', 'Failed to update submission status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get submission statistics
     */
    public function getStats() {
        try {
            $pdo = $this->db->connect();
            
            $stats = [];
            
            // Total submissions
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM {$this->table}");
            $stats['total'] = $stmt->fetch()['total'];
            
            // Submissions by status
            $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status");
            $stats['by_status'] = $stmt->fetchAll();
            
            // Submissions by subject
            $stmt = $pdo->query("SELECT subject, COUNT(*) as count FROM {$this->table} GROUP BY subject");
            $stats['by_subject'] = $stmt->fetchAll();
            
            // Recent submissions (last 30 days)
            $stmt = $pdo->query("SELECT COUNT(*) as recent FROM {$this->table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stats['recent'] = $stmt->fetch()['recent'];
            
            return $stats;
            
        } catch (PDOException $e) {
            logMessage('ERROR', 'Failed to retrieve submission statistics: ' . $e->getMessage());
            return [];
        }
    }
}

/**
 * Database initialization script
 * Run this to set up the database tables
 */
function initializeDatabase() {
    try {
        $db = new Database();
        
        // Test connection
        if (!$db->testConnection()) {
            throw new Exception('Cannot connect to database');
        }
        
        // Create tables
        $db->createTables();
        
        echo "Database initialized successfully!\n";
        return true;
        
    } catch (Exception $e) {
        echo "Database initialization failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// If this file is run directly, initialize the database
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    define('WEBSOLUTIONS_APP', true);
    require_once 'config.php';
    initializeDatabase();
}
?>