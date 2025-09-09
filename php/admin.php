<?php
/**
 * Simple Admin Interface for Contact Submissions
 * Basic interface to view and manage contact form submissions
 */

// Define application constant
define('WEBSOLUTIONS_APP', true);

// Include required files
require_once 'config.php';
require_once 'database.php';

// Set security headers
setSecurityHeaders();

// Simple authentication (in production, use proper authentication)
session_start();
$adminPassword = 'admin123'; // Change this in production!

if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['password']) && $_POST['password'] === $adminPassword) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        showLoginForm();
        exit;
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle status updates
if (isset($_POST['update_status']) && isset($_POST['submission_id']) && isset($_POST['new_status'])) {
    $submission = new ContactSubmission();
    $submission->updateStatus($_POST['submission_id'], $_POST['new_status']);
    header('Location: admin.php?updated=1');
    exit;
}

// Get submissions
$submission = new ContactSubmission();
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$status = isset($_GET['status']) ? $_GET['status'] : null;

$submissions = $submission->getAll($limit, $offset, $status);
$stats = $submission->getStats();

function showLoginForm() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - WebSolutions</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 50px; }
            .login-form { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .form-group { margin-bottom: 20px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
            button { background: #2563eb; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #1d4ed8; }
        </style>
    </head>
    <body>
        <div class="login-form">
            <h2>Admin Login</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Submissions Admin - WebSolutions</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #2563eb; }
        .stat-label { color: #666; margin-top: 5px; }
        .filters { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .submissions { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: bold; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; }
        .status-new { background: #e3f2fd; color: #1976d2; }
        .status-read { background: #f3e5f5; color: #7b1fa2; }
        .status-replied { background: #e8f5e8; color: #388e3c; }
        .status-archived { background: #fafafa; color: #616161; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8em; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .message-preview { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pagination { text-align: center; margin-top: 20px; }
        .pagination a { display: inline-block; padding: 8px 12px; margin: 0 4px; background: white; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; }
        .pagination a:hover { background: #f5f5f5; }
        .pagination .current { background: #2563eb; color: white; border-color: #2563eb; }
        .logout { float: right; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 4px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Contact Submissions Admin</h1>
            <a href="?logout=1" class="btn btn-secondary logout">Logout</a>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert">Submission status updated successfully!</div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-label">Total Submissions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['recent'] ?? 0; ?></div>
                <div class="stat-label">Last 30 Days</div>
            </div>
            <?php if (isset($stats['by_status'])): ?>
                <?php foreach ($stats['by_status'] as $statusStat): ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $statusStat['count']; ?></div>
                        <div class="stat-label"><?php echo ucfirst($statusStat['status']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="filters">
            <strong>Filter by Status:</strong>
            <a href="admin.php" class="btn <?php echo !$status ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
            <a href="admin.php?status=new" class="btn <?php echo $status === 'new' ? 'btn-primary' : 'btn-secondary'; ?>">New</a>
            <a href="admin.php?status=read" class="btn <?php echo $status === 'read' ? 'btn-primary' : 'btn-secondary'; ?>">Read</a>
            <a href="admin.php?status=replied" class="btn <?php echo $status === 'replied' ? 'btn-primary' : 'btn-secondary'; ?>">Replied</a>
            <a href="admin.php?status=archived" class="btn <?php echo $status === 'archived' ? 'btn-primary' : 'btn-secondary'; ?>">Archived</a>
        </div>

        <div class="submissions">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                                No submissions found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td>#<?php echo $sub['id']; ?></td>
                                <td><?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']); ?></td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($sub['email']); ?>">
                                        <?php echo htmlspecialchars($sub['email']); ?>
                                    </a>
                                </td>
                                <td><?php echo ucfirst($sub['subject']); ?></td>
                                <td>
                                    <div class="message-preview" title="<?php echo htmlspecialchars($sub['message']); ?>">
                                        <?php echo htmlspecialchars($sub['message']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status status-<?php echo $sub['status']; ?>">
                                        <?php echo ucfirst($sub['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($sub['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                        <select name="new_status" onchange="this.form.submit()">
                                            <option value="">Change Status</option>
                                            <option value="new" <?php echo $sub['status'] === 'new' ? 'disabled' : ''; ?>>New</option>
                                            <option value="read" <?php echo $sub['status'] === 'read' ? 'disabled' : ''; ?>>Read</option>
                                            <option value="replied" <?php echo $sub['status'] === 'replied' ? 'disabled' : ''; ?>>Replied</option>
                                            <option value="archived" <?php echo $sub['status'] === 'archived' ? 'disabled' : ''; ?>>Archived</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $status ? '&status=' . $status : ''; ?>">&laquo; Previous</a>
            <?php endif; ?>
            
            <span class="current"><?php echo $page; ?></span>
            
            <?php if (count($submissions) === $limit): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $status ? '&status=' . $status : ''; ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>