<?php
/**
 * Simple Admin Panel to View Form Submissions
 * For development use only
 */

// Basic security - change password for production
$password = 'WebSolutions2024!';
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['password']) && $_POST['password'] === $password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Admin Login</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
                input { width: 100%; padding: 10px; margin: 10px 0; }
                button { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; }
            </style>
        </head>
        <body>
            <h2>Admin Login</h2>
            <form method="post">
                <input type="password" name="password" placeholder="Enter password" required>
                <button type="submit">Login</button>
            </form>
            <p><small>Contact administrator for access credentials</small></p>
        </body>
        </html>
        <?php
        exit;
    }
}

// Read and parse log file
$logFile = '../php/logs/app.log';
$submissions = [];

if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES);
    
    foreach ($lines as $line) {
        if (strpos($line, 'Contact form submitted successfully') !== false) {
            // Parse the log line
            preg_match('/\[(.*?)\].*?\[(.*?)\].*?Context: (.*)/', $line, $matches);
            
            if (count($matches) >= 4) {
                $context = json_decode($matches[3], true);
                
                // Handle both old and new log formats
                if (isset($context['form_data'])) {
                    // New format with full form data
                    $formData = $context['form_data'];
                    $submissions[] = [
                        'timestamp' => $matches[1],
                        'ip' => $matches[2],
                        'firstName' => $formData['firstName'] ?? 'N/A',
                        'lastName' => $formData['lastName'] ?? 'N/A',
                        'email' => $formData['email'] ?? 'N/A',
                        'phone' => $formData['phone'] ?? '',
                        'subject' => $formData['subject'] ?? 'N/A',
                        'message' => $formData['message'] ?? 'N/A',
                        'newsletter' => $formData['newsletter'] ?? 'No',
                        'submission_id' => $context['submission_id'] ?? 'N/A'
                    ];
                } else {
                    // Old format - limited data
                    $submissions[] = [
                        'timestamp' => $matches[1],
                        'ip' => $matches[2],
                        'firstName' => 'N/A',
                        'lastName' => 'N/A',
                        'email' => $context['email'] ?? 'N/A',
                        'phone' => '',
                        'subject' => $context['subject'] ?? 'N/A',
                        'message' => 'N/A (old log format)',
                        'newsletter' => 'N/A',
                        'submission_id' => $context['submission_id'] ?? 'N/A'
                    ];
                }
            }
        }
    }
}

// Reverse to show newest first
$submissions = array_reverse($submissions);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Form Submissions - Admin Panel</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5; 
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        h1 { 
            color: #333; 
            border-bottom: 2px solid #007cba; 
            padding-bottom: 10px; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th, td { 
            padding: 8px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
            vertical-align: top;
        }
        th { 
            background: #007cba; 
            color: white; 
            font-size: 12px;
        }
        tr:hover { 
            background: #f9f9f9; 
        }
        .message-cell {
            max-width: 200px;
            position: relative;
        }
        .message-preview {
            font-size: 12px;
            line-height: 1.4;
        }
        .message-full {
            background: #f8f9fa;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
            font-size: 12px;
            line-height: 1.4;
            max-height: 200px;
            overflow-y: auto;
        }
        .view-full-btn, .reply-btn {
            background: #007cba;
            color: white;
            border: none;
            padding: 4px 8px;
            font-size: 10px;
            border-radius: 3px;
            cursor: pointer;
            margin-top: 5px;
        }
        .reply-btn {
            background: #28a745;
        }
        .view-full-btn:hover, .reply-btn:hover {
            opacity: 0.8;
        }
        td {
            font-size: 12px;
        }
        td a {
            color: #007cba;
            text-decoration: none;
        }
        td a:hover {
            text-decoration: underline;
        }
        .no-data { 
            text-align: center; 
            color: #666; 
            font-style: italic; 
            padding: 40px; 
        }
        .logout { 
            float: right; 
            background: #dc3545; 
            color: white; 
            padding: 8px 16px; 
            text-decoration: none; 
            border-radius: 4px; 
        }
        .refresh { 
            background: #28a745; 
            color: white; 
            padding: 8px 16px; 
            text-decoration: none; 
            border-radius: 4px; 
            margin-right: 10px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            Form Submissions 
            <a href="?refresh=1" class="refresh">Refresh</a>
            <a href="?logout=1" class="logout">Logout</a>
        </h1>
        
        <?php if (isset($_GET['logout'])): ?>
            <?php session_destroy(); header('Location: ' . $_SERVER['PHP_SELF']); exit; ?>
        <?php endif; ?>
        
        <p><strong>Total Submissions:</strong> <?= count($submissions) ?></p>
        
        <?php if (empty($submissions)): ?>
            <div class="no-data">
                No form submissions found yet.<br>
                Submit the contact form to see entries here.
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Newsletter</th>
                        <th>IP</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $index => $submission): ?>
                        <tr>
                            <td><?= htmlspecialchars($submission['timestamp']) ?></td>
                            <td><?= htmlspecialchars($submission['firstName'] . ' ' . $submission['lastName']) ?></td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($submission['email']) ?>">
                                    <?= htmlspecialchars($submission['email']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($submission['phone'] ?: '-') ?></td>
                            <td><?= htmlspecialchars(ucfirst($submission['subject'])) ?></td>
                            <td class="message-cell">
                                <div class="message-preview">
                                    <?= htmlspecialchars(substr($submission['message'], 0, 100)) ?>
                                    <?php if (strlen($submission['message']) > 100): ?>...<?php endif; ?>
                                </div>
                                <button class="view-full-btn" onclick="toggleMessage(<?= $index ?>)">View Full</button>
                                <div class="message-full" id="message-<?= $index ?>" style="display: none;">
                                    <?= nl2br(htmlspecialchars($submission['message'])) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($submission['newsletter']) ?></td>
                            <td><?= htmlspecialchars($submission['ip']) ?></td>
                            <td>
                                <button class="reply-btn" onclick="replyTo('<?= htmlspecialchars($submission['email']) ?>', '<?= htmlspecialchars($submission['firstName'] . ' ' . $submission['lastName']) ?>')">Reply</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        <p><small>
            <strong>WebSolutions Admin Panel</strong> - Secure form submission management system with real-time monitoring and email integration.
        </small></p>
    </div>

    <script>
        function toggleMessage(index) {
            const messageDiv = document.getElementById('message-' + index);
            const button = event.target;
            
            if (messageDiv.style.display === 'none') {
                messageDiv.style.display = 'block';
                button.textContent = 'Hide';
            } else {
                messageDiv.style.display = 'none';
                button.textContent = 'View Full';
            }
        }
        
        function replyTo(email, name) {
            const subject = encodeURIComponent('Re: Your inquiry');
            const body = encodeURIComponent('Dear ' + name + ',\n\nThank you for contacting us.\n\n');
            const mailtoLink = 'mailto:' + email + '?subject=' + subject + '&body=' + body;
            window.open(mailtoLink);
        }
        
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>