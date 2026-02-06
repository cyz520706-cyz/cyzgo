<?php
// 强制设置 JSON 头，避免任何 HTML 输出
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
// 关闭错误输出到页面
ini_set('display_errors', 0);
error_reporting(0);
$log_file = 'telegram_webhook.log';
// 确保不会输出任何 HTML 或错误
ob_start();
register_shutdown_function(function() {
    $output = ob_get_contents();
    ob_end_clean();
    
    // 如果输出不是 JSON，记录错误但不显示
    if ($output && !preg_match('/^\s*\{/', $output)) {
        error_log("Non-JSON output detected: " . substr($output, 0, 200));
    }
});
try {
    $response = [
        'success' => false,
        'logs' => [],
        'total' => 0,
        'today' => 0,
        'users' => 0,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if (file_exists($log_file)) {
        $lines = @file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            $lines = [];
        }
        
        $response['total'] = count($lines);
        $response['today'] = 0;
        $userSet = [];
        $recent_logs = array_slice(array_reverse($lines), 0, 50);
        
        foreach ($recent_logs as $line) {
            if (preg_match('/^\[([^\]]+)\]\s*(.+)$/', $line, $matches)) {
                $time = $matches[1];
                $content = trim($matches[2]);
                
                // 提取用户信息
                if (preg_match('/用户[:\s]*([^,\s\(\)]+)/', $content, $userMatch)) {
                    $userSet[$userMatch[1]] = true;
                }
                
                // 检查是否是今天的消息
                if (strpos($time, date('Y-m-d')) === 0) {
                    $response['today']++;
                }
                
                $response['logs'][] = [
                    'time' => $time,
                    'content' => $content
                ];
            } else if (trim($line) !== '') {
                // 没有时间戳的行
                $response['logs'][] = [
                    'time' => date('Y-m-d H:i:s'),
                    'content' => trim($line)
                ];
            }
        }
        
        $response['users'] = count($userSet);
        $response['success'] = true;
    } else {
        $response['error'] = '日志文件不存在';
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '服务器错误',
        'message' => $e->getMessage()
    ]);
}
?>
EOF
echo "✅ get_logs.php 已修复"
