cat > admin_logs.php << 'EOF'
<?php
header('Content-Type: application/json');
$log_file = 'telegram_webhook.log';
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    if (file_exists($log_file)) {
        file_put_contents($log_file, '');
        echo json_encode(['success' => true, 'message' => '日志已清空']);
    } else {
        echo json_encode(['success' => false, 'error' => '日志文件不存在']);
    }
    exit;
}
$logs = [];
if (file_exists($log_file)) {
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    // 只取最后100条
    $lines = array_slice($lines, -100);
    
    foreach ($lines as $line) {
        preg_match('/\[(.*?)\]/', $line, $matches);
        $time = $matches[1] ?? '';
        $message = substr($line, strlen($time) + 2); // 移除时间戳
        
        $logs[] = [
            'time' => htmlspecialchars($time),
            'message' => htmlspecialchars($message)
        ];
    }
}
echo json_encode([
    'success' => true,
    'total' => count($logs),
    'logs' => array_reverse($logs) // 最新的在前面
]);
?>
EOF
echo "✅ 所有文件已创建完成！"
echo "
