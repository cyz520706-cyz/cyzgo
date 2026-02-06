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
            if (preg_match('/^$$([^$$]+)\]\s*(.+)$/', $line, $matches)) {
                $time = $matches[1];
                $content = trim($matches[2]);
                
                // 提取用户信息
                if (preg_match('/用户[:\s]*([^,\s$$]+)/', $content, $userMatch)) {
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

# 3. 创建简单的测试端点
cat > test_json.php << 'EOF'
<?php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => '测试成功',
    'data' => [
        ['time' => '12:00:00', 'content' => '测试消息1'],
        ['time' => '12:01:00', 'content' => '测试消息2']
    ],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
EOF

echo "✅ 创建了测试端点"

# 4. 简化 admin.php 不再依赖外部 API
cat > admin.php << 'EOF'
<?php
$log_file = 'telegram_webhook.log';

// 如果是 AJAX 请求，返回 JSON
if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    
    header('Content-Type: application/json');
    $response = ['success' => false, 'logs' => []];
    
    if (file_exists($log_file)) {
        $lines = @file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $response['total'] = count($lines);
        
        $recent = array_slice(array_reverse($lines), 0, 50);
        foreach ($recent as $line) {
            $response['logs'][] = htmlspecialchars($line);
        }
        
        $response['success'] = true;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 如果是清除日志请求
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    if (file_exists($log_file)) {
        file_put_contents($log_file, date('[Y-m-d H:i:s]') . " 日志已清空\n");
    }
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📱 机器人管理面板</title>
    <style>
        :root {
            --primary: #667eea;
            --danger: #f56565;
            --success: #48bb78;
            --dark: #2d3748;
            --light: #f7fafc;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary) 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 100%;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, #764ba2 100%);
            color: white;
            padding: 30px 25px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .header h1 .emoji {
            font-size: 40px;
        }
        
        .status {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .status-item {
            text-align: center;
            padding: 15px 25px;
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        
        .status-number {
            font-size: 32px;
            font-weight: bold;
            display: block;
        }
        
        .status-label {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .controls {
            padding: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            min-width: 160px;
            justify-content: center;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .logs-container {
            padding: 25px;
            max-height: 500px;
            overflow-y: auto;
            background: var(--light);
        }
        
        .log-entry {
            background: white;
            margin-bottom: 15px;
            padding: 20px;
            border-radius: 15px;
            border-left: 5px solid var(--primary);
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .log-time {
            color: var(--primary);
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 15px;
        }
        
        .log-content {
            color: var(--dark);
            line-height: 1.6;
            word-break: break-word;
            font-size: 16px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .empty {
            text-align: center;
            padding: 40px;
            color: #a0aec0;
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .btn { min-width: 100%; margin: 5px 0; }
            .controls { flex-direction: column; }
            .status { gap: 15px; }
            .status-item { padding: 10px 15px; }
        }
        
        .user-message { border-left-color: var(--success); }
        .bot-message { border-left-color: var(--primary); }
        .url-message { border-left-color: #ed8936; }
        
        .url {
            color: var(--primary);
            text-decoration: none;
            font-weight: bold;
        }
        
        .url:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <span class="emoji">🤖</span>
                中蒙代购机器人管理系统
            </h1>
            <p style="opacity: 0.9; margin-top: 5px;">实时监控与管理</p>
            
            <div class="status">
                <div class="status-item">
                    <span class="status-number" id="totalLogs">0</span>
                    <span class="status-label">总日志数</span>
                </div>
                <div class="status-item">
                    <span class="status-number">🟢</span>
                    <span class="status-label">在线状态</span>
                </div>
                <div class="status-item">
                    <span class="status-number" id="todayLogs">0</span>
                    <span class="status-label">今日消息</span>
                </div>
            </div>
        </div>
        
        <div class="controls">
            <button
