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
            <button class="btn btn-primary" onclick="loadLogs()">
                <span>🔄</span> 刷新日志
            </button>
            <button class="btn btn-success" onclick="testConnection()">
                <span>🌐</span> 测试连接
            </button>
            <button class="btn btn-danger" onclick="clearLogs()">
                <span>🗑️</span> 清空日志
            </button>
        </div>
        
        <div class="logs-container" id="logs">
            <div class="loading">
                <div class="loading-spinner"></div>
                <p>正在加载日志中...</p>
            </div>
        </div>
    </div>
    
    <script>
    async function loadLogs() {
        const logsDiv = document.getElementById('logs');
        logsDiv.innerHTML = `
            <div class="loading">
                <div class="loading-spinner"></div>
                <p>正在加载...</p>
            </div>
        `;
        
        try {
            // 直接使用当前页面的 AJAX 端点
            const response = await fetch('admin.php?ajax=1');
            const data = await response.json();
            
            if (data.success) {
                // 更新统计信息
                document.getElementById('totalLogs').textContent = data.total || 0;
                let todayCount = 0;
                
                if (data.logs && data.logs.length > 0) {
                    let html = '';
                    data.logs.forEach(log => {
                        // 解析日志行
                        const timeMatch = log.match(/^\[([^\]]+)\]/);
                        const time = timeMatch ? timeMatch[1] : '';
                        const content = timeMatch ? log.slice(timeMatch[0].length).trim() : log;
                        
                        // 分类消息
                        let messageClass = 'log-entry';
                        if (content.includes('用户') && !content.includes('机器人')) {
                            messageClass += ' user-message';
                        } else if (content.includes('机器人') || content.includes('收到')) {
                            messageClass += ' bot-message';
                        } else if (content.includes('http') || content.includes('链接')) {
                            messageClass += ' url-message';
                        }
                        
                        // 检查是否是今天的消息
                        const today = new Date().toISOString().split('T')[0];
                        if (time.includes(today)) {
                            todayCount++;
                        }
                        
                        // 添加链接高亮
                        let formattedContent = content;
                        const urlMatch = content.match(/(https?:\/\/[^\s]+)/g);
                        if (urlMatch) {
                            urlMatch.forEach(url => {
                                formattedContent = formattedContent.replace(url, 
                                    `<a href="${url}" target="_blank" class="url">${url}</a>`);
                            });
                        }
                        
                        html += `
                            <div class="${messageClass}">
                                <div class="log-time">${time || '无时间戳'}</div>
                                <div class="log-content">${formattedContent}</div>
                            </div>
                        `;
                    });
                    
                    document.getElementById('todayLogs').textContent = todayCount;
                    logsDiv.innerHTML = html;
                    
                    // 滚动到底部
                    setTimeout(() => {
                        logsDiv.scrollTop = logsDiv.scrollHeight;
                    }, 100);
                } else {
                    logsDiv.innerHTML = '<div class="empty"><p>暂无日志记录</p></div>';
                }
            } else {
                logsDiv.innerHTML = `
                    <div class="empty">
                        <p>加载失败: ${data.error || '未知错误'}</p>
                        <button onclick="loadLogs()" class="btn btn-primary" style="margin-top: 10px;">重试</button>
                    </div>
                `;
            }
        } catch (error) {
            logsDiv.innerHTML = `
                <div class="empty">
                    <p>网络错误: ${error.message}</p>
                    <button onclick="loadLogs()" class="btn btn-primary" style="margin-top: 10px;">重试</button>
                </div>
            `;
        }
    }
    
    async function testConnection() {
        try {
            const response = await fetch('test_json.php');
            const data = await response.json();
            if (data.success) {
                alert('✅ 连接正常！服务器时间：' + data.timestamp);
            }
        } catch (error) {
            alert('❌ 连接失败：' + error.message);
        }
    }
    
    function clearLogs() {
        if (confirm('确定要清空所有日志吗？此操作不可撤销！')) {
            window.location.href = 'admin.php?action=clear';
        }
    }
    
    // 页面加载时自动加载
    document.addEventListener('DOMContentLoaded', loadLogs);
    
    // 每30秒自动刷新
    setInterval(loadLogs, 30000);
    </script>
</body>
</html>
EOF
echo "✅ admin.php 已重建"
