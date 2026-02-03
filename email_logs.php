<?php
/**
 * Email Log Viewer
 * COSMIC SURGICALS - View email sending history
 */

require_once __DIR__ . '/includes/auth.php';

// Check login (only admin can view logs)
if (!isLoggedIn()) {
    header('Location: /xamp-cosmic/modules/auth/login.php');
    exit;
}

$logFile = __DIR__ . '/logs/email.log';
$logs = [];

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    $lines = array_filter(explode("\n", $content));
    $logs = array_reverse($lines); // Most recent first
}

// Clear logs action
if (isset($_POST['clear_logs']) && $_SESSION['user_role'] === 'admin') {
    file_put_contents($logFile, '');
    header('Location: /xamp-cosmic/email_logs.php?cleared=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Logs - COSMIC SURGICALS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: white; margin-bottom: 20px; }
        .nav { margin-bottom: 20px; }
        .nav a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            margin-right: 10px;
        }
        .nav a:hover { background: rgba(255,255,255,0.2); }
        .card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .card-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            padding: 18px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h2 { font-size: 18px; }
        .card-body { padding: 20px; max-height: 600px; overflow-y: auto; }
        .log-entry {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            font-family: 'Consolas', monospace;
            font-size: 13px;
            line-height: 1.5;
        }
        .log-entry:last-child { border-bottom: none; }
        .log-entry:hover { background: #f8f9fa; }
        .log-entry.sent { border-left: 4px solid #10b981; }
        .log-entry.failed { border-left: 4px solid #ef4444; }
        .status-sent { color: #10b981; font-weight: 600; }
        .status-failed { color: #ef4444; font-weight: 600; }
        .timestamp { color: #6b7280; }
        .invoice-num { color: #1e3c72; font-weight: 600; }
        .empty { text-align: center; padding: 40px; color: #666; }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-refresh { background: #10b981; color: white; }
        .btn-refresh:hover { background: #059669; }
        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #d1fae5;
            color: #065f46;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            flex: 1;
            text-align: center;
        }
        .stat-box h3 { font-size: 28px; color: #1e3c72; }
        .stat-box p { color: #666; font-size: 13px; margin-top: 5px; }
        .stat-box.success h3 { color: #10b981; }
        .stat-box.failed h3 { color: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="/xamp-cosmic/modules/invoices/index.php">← Back to Invoices</a>
            <a href="/xamp-cosmic/modules/invoices/create.php">Create Invoice</a>
            <a href="/xamp-cosmic/test_email.php">Test Email</a>
        </div>

        <h1>Email Logs</h1>

        <?php if (isset($_GET['cleared'])): ?>
            <div class="alert">Logs cleared successfully!</div>
        <?php endif; ?>

        <?php
        // Calculate stats
        $totalSent = 0;
        $totalFailed = 0;
        foreach ($logs as $log) {
            if (strpos($log, 'Status: SENT') !== false) $totalSent++;
            if (strpos($log, 'Status: FAILED') !== false) $totalFailed++;
        }
        ?>

        <div class="stats">
            <div class="stat-box success">
                <h3><?php echo $totalSent; ?></h3>
                <p>Emails Sent</p>
            </div>
            <div class="stat-box failed">
                <h3><?php echo $totalFailed; ?></h3>
                <p>Failed</p>
            </div>
            <div class="stat-box">
                <h3><?php echo count($logs); ?></h3>
                <p>Total Attempts</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Email History (Most Recent First)</h2>
                <div>
                    <a href="/xamp-cosmic/email_logs.php" class="btn btn-refresh">Refresh</a>
                    <?php if ($_SESSION['user_role'] === 'admin' && count($logs) > 0): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Clear all logs?');">
                        <button type="submit" name="clear_logs" class="btn btn-danger">Clear Logs</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <div class="empty">
                        <p>No email logs yet.</p>
                        <p style="margin-top: 10px; font-size: 14px;">Logs will appear here when invoices are created and emails are sent.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $isSent = strpos($log, 'Status: SENT') !== false;
                        $isFailed = strpos($log, 'Status: FAILED') !== false;
                        $class = $isSent ? 'sent' : ($isFailed ? 'failed' : '');

                        // Format log entry
                        $formatted = $log;
                        $formatted = preg_replace('/\[(.*?)\]/', '<span class="timestamp">[$1]</span>', $formatted, 1);
                        $formatted = preg_replace('/Invoice: ([\w\-]+)/', 'Invoice: <span class="invoice-num">$1</span>', $formatted);
                        $formatted = str_replace('Status: SENT', 'Status: <span class="status-sent">SENT</span>', $formatted);
                        $formatted = str_replace('Status: FAILED', 'Status: <span class="status-failed">FAILED</span>', $formatted);
                        ?>
                        <div class="log-entry <?php echo $class; ?>">
                            <?php echo $formatted; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
