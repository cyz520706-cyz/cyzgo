# 创建一个专门处理AJAX请求的独立文件
cat > api.php << 'EOF'
<?php
// api.php - 纯JSON响应API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

$action = $_GET['action'] ?? $_POST['action'] ?? 'test';
$log_file = 'telegram_webhook.log';

switch($action) {
    case 'test':
        echo json_encode([
            'success' => true,
            'message' => 'API服务正常',
            'timestamp' => time(),
            'time' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'get_logs':
        $logs = [];
        if (file_exists($log_file)) {
            $content = file_get_contents($log_file);
            if (!empty($content)) {
                $lines = array_filter(explode("\n", trim($content)));
                foreach ($lines as $line) {
                    if (trim($line) === '') continue;
                    
                    preg_match('/\[(.*?)\]/', $line, $time_match);
                    $time = $time_match[1] ?? date('H:i:s');
                    $type = (strpos($line, '用户消息') !== false || strpos($line, '用户ID:') !== false) ? 'user' : 'bot';
                    
                    // 解码Unicode
                    $message = $line;
                    $message = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function($match) {
                        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
                    }, $message);
                    
                    $logs[] = [
                        'time' => $time,
                        'message' => $message,
                        'type' => $type
                    ];
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'total' => count($logs),
            'logs' => array_reverse(array_slice($logs, -50)) // 最新的50条
        ], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'get_stats':
        $stats = [
            'total_messages' => 0,
            'today_messages' => 0,
            'log_exists' => false,
            'log_size' => 0
        ];
        
        if (file_exists($log_file)) {
            $stats['log_exists'] = true;
            $stats['log_size'] = filesize($log_file);
            
            $content = file_get_contents($log_file);
            if ($content) {
                $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $stats['total_messages'] = count($lines);
                
                $today = date('Y-m-d');
                foreach ($lines as $line) {
                    if (strpos($line, "[$today") === 0) {
                        $stats['today_messages']++;
                    }
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'server_time' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'clear_logs':
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
        }
        echo json_encode(['success' => true, 'message' => '日志已清空']);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => '未知操作']);
}
?>
EOF

echo "✅ api.php 已创建"
echo "测试API: https://cyzgo.onrender.com/api.php?action=test"
