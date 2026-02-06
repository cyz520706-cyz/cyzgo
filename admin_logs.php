<?php
// admin_logs.php - 纯JSON输出
header('Content-Type: application/json');
$log_file = 'telegram_webhook.log';
$action = $_GET['action'] ?? 'get_logs';
if ($action === 'clear') {
    if (file_exists($log_file)) {
        file_put_contents($log_file, '');
        echo json_encode(['success' => true, 'message' => '日志已清空']);
    } else {
        echo json_encode(['success' => false, 'error' => '日志文件不存在']);
    }
    exit;
}
// 获取日志
$logs = [];
if (file_exists($log_file)) {
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach (array_slice($lines, -50) as $line) {
        // 解析时间戳
        preg_match('/\[(.*?)\]/', $line, $matches);
        $time = $matches[1] ?? '';
        $message = trim(substr($line, strlen($time) + 2));
        
        $logs[] = [
            'time' => htmlspecialchars($time),
            'message' => htmlspecialchars($message)
        ];
    }
}
echo json_encode([
    'success' => true,
    'total' => count($logs),
    'logs' => array_reverse($logs)
], JSON_UNESCAPED_UNICODE);
?>
EOF
echo "✅ admin_logs.php 已修复"
# 3. 测试文件是否正确
echo "=== 检查文件开头 ==="
head -c 100 admin_logs.php
echo ""
echo "=== 测试JSON输出 ==="
php -f admin_logs.php 2>/dev/null || curl -s "http://localhost/admin_logs.php"
