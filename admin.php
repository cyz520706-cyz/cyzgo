<?php
$log_file = 'telegram_webhook.log';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🤖 中蒙代购机器人管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* 移动端优化 */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 100%;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        .logo {
            font-size: 3em;
            margin-bottom: 10px;
        }
        h1 {
            color: #333;
            font-size: 1.8em;
            margin-bottom: 5px;
        }
        .subtitle {
            color: #666;
            font-size: 1em;
        }
        .stats-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        .controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 20px 0;
            justify-content: center;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 50px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            min-width: 120px;
            justify-content: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .btn-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .btn:active {
            transform: translateY(-1px);
        }
        .logs-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        .log-message {
            background: white;
            margin: 15px 0;
            padding: 18px;
            border-radius: 12px;
            border-left: 5px solid #667eea;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .log-time {
            color: #f5576c;
            font-size: 0.85em;
            font-weight: bold;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .log-content {
            color: #333;
            line-height: 1.6;
            word-break: break-word;
        }
        .user-message {
            border-left-color: #4CAF50;
        }
        .bot-message {
            border-left-color: #2196F3;
        }
        .url-message {
            border-left-color: #FF9800;
        }
        .url-link {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
        .url-link:hover {
            text-decoration: underline;
        }
        .empty-logs {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .loading {
            text-align: center;
            padding: 30px;
        }
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .container { padding: 15px; }
            .btn { min-width: 100%; margin: 5px 0; }
            .controls { flex-direction: column; }
            .stat-number { font-size: 1.5em; }
        }
        .json-toggle {
            background: #f1f1f1;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            margin-top: 10px;
            cursor: pointer;
            font-size: 0.8em;
            color: #666;
        }
        .json-content {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-family: 'Consolas', monospace;
            font-size: 0.9em;
            overflow-x: auto;
            display: none;
        }
        .json-content.show {
            display: block;
        }
        .json-key { color: #9cdcfe; }
        .json-string { color: #ce9178; }
        .json-number { color: #b5cea8; }
        .json-boolean { color: #569cd6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🤖</div>
            <h1>中蒙代购机器人管理</h1>
            <div class="subtitle">实时监控与管理系统</div>
        </div>
        
        <div class="stats-card">
            <div class="stat-item">
                <span class="stat-number" id="online-status">🟢</span>
                <span class="stat-label">在线状态</span>
            </div>
            <div class="stat-item">
                <span class="stat-number" id="total-logs">0</span>
                <span class="stat-label">日志数量</span>
            </div>
            <div class="stat-item">
                <span class="stat-number" id="today-logs">0</span>
                <span class="stat-label">今日消息</span>
            </div>
            <div class="stat-item">
                <span class="stat-number" id="active-users">0</span>
                <span class="stat-label">活跃用户</span>
            </div>
        </div>
        
        <div class="controls">
            <button class="btn btn-primary" onclick="loadLogs()">
                <span>🔄</span>刷新日志
            </button>
            <button class="btn btn-success" onclick="testWebhook()">
                <span>📡</span>测试Webhook
            </button>
            <button class="btn btn-danger" onclick="clearLogs()">
                <span>🗑️</span>清空日志
            </button>
            <button class="btn btn-primary" onclick="exportLogs()">
                <span>📥</span>导出日志
            </button>
        </div>
        
        <div class="logs-container" id="logsContainer">
            <div class="loading">
                <div class="loading-spinner"></div>
                <p>正在加载日志...</p>
            </div>
        </div>
    </div>
    
    <script>
    async function loadLogs() {
        const container = document.getElementById('logsContainer');
        container.innerHTML = '<div class="loading"><div class="loading-spinner"></div><p>正在加载...</p></div>';
        
        try {
            const response = await fetch('get_logs.php');
            const data = await response.json();
            
            // 更新统计信息
            document.getElementById('total-logs').textContent = data.total || 0;
            document.getElementById('today-logs').textContent = data.today || 0;
            document.getElementById('active-users').textContent = data.users || 0;
            
            if (data.success && data.logs.length > 0) {
                let html = '';
                data.logs.forEach((log, index) => {
                    let messageClass = 'log-message';
                    let icon = '💬';
                    let content = log.content;
                    
                    // 根据内容类型添加不同样式
                    if (content.includes('用户 @') || content.includes('用户:')) {
                        messageClass += ' user-message';
                        icon = '👤';
                    } else if (content.includes('机器人') || content.includes('收到')) {
                        messageClass += ' bot-message';
                        icon = '🤖';
                    } else if (content.includes('http') || content.includes('链接')) {
                        messageClass += ' url-message';
                        icon = '🔗';
                        // 提取URL并创建链接
                        const urlMatch = content.match(/(https?:\/\/[^\s]+)/);
                        if (urlMatch) {
                            content = content.replace(urlMatch[0], 
                                `<a href="${urlMatch[0]}" target="_blank" class="url-link">${urlMatch[0]}</a>`);
                        }
                    }
                    
                    // 检查是否是JSON数据
                    const jsonMatch = content.match(/(\{[^}]+\})/);
                    const hasJson = jsonMatch && content.includes('{') && content.includes('}');
                    
                    html += `
                    <div class="${messageClass}">
                        <div class="log-time">
                            <span>${icon}</span>
                            ${log.time}
                        </div>
                        <div class="log-content">${content}</div>
                        ${hasJson ? `
                        <button class="json-toggle" onclick="toggleJson(this)">显示JSON详情</button>
                        <div class="json-content">${formatJson(jsonMatch[0])}</div>
                        ` : ''}
                    </div>`;
                });
                
                container.innerHTML = html;
                
                // 滚动到底部
                setTimeout(() => {
                    container.scrollTop = container.scrollHeight;
                }, 100);
            } else {
                container.innerHTML = '<div class="empty-logs"><p>暂无日志记录</p></div>';
            }
        } catch (error) {
            container.innerHTML = `<div class="empty-logs"><p>加载失败: ${error.message}</p></div>`;
        }
    }
    
    function formatJson(jsonString) {
        try {
            const json = JSON.parse(jsonString);
            const prettyJson = JSON.stringify(json, null, 2);
            
            // 语法高亮
            return prettyJson
                .replace(/(".*?":)/g, '<span class="json-key">$1</span>')
                .replace(/"(.*?)"/g, '<span class="json-string">"$1"</span>')
                .replace(/\b(true|false)\b/g, '<span class="json-boolean">$1</span>')
                .replace(/\b(null)\b/g, '<span class="json-boolean">null</span>')
                .replace(/\b(\d+)\b/g, '<span class="json-number">$1</span>')
                .replace(/\n/g, '<br>')
                .replace(/ /g, '&nbsp;');
        } catch (e) {
            return jsonString.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
    }
    
    function toggleJson(button) {
        const jsonContent = button.nextElementSibling;
        const isVisible = jsonContent.classList.contains('show');
        
        if (isVisible) {
            jsonContent.classList.remove('show');
            button.textContent = '显示JSON详情';
        } else {
            jsonContent.classList.add('show');
            button.textContent = '隐藏JSON详情';
        }
    }
    
    async function clearLogs() {
        if (confirm('确定要清空所有日志吗？此操作不可撤销！')) {
            try {
                const response = await fetch('clear_logs.php');
                const result = await response.text();
                alert(result);
                loadLogs();
            } catch (error) {
                alert('清空失败: ' + error.message);
            }
        }
    }
    
    async function testWebhook() {
        try {
            const response = await fetch('webhook.php');
            const text = await response.text();
            
            if (text.includes('Webhook') || text.includes('运行中')) {
                alert('✅ Webhook运行正常！');
            } else {
                alert('⚠️ Webhook可能有问题');
            }
        } catch (error) {
            alert('❌ 测试失败: ' + error.message);
        }
    }
    
    function exportLogs() {
        alert('导出功能开发中...\n\n当前日志文件: telegram_webhook.log\n请通过FTP下载该文件。');
    }
    
    // 页面加载时自动加载
    document.addEventListener('DOMContentLoaded', () => {
        loadLogs();
        // 每10秒自动刷新
        setInterval(loadLogs, 10000);
    });
    </script>
</body>
</html>
EOF

# 创建get_logs.php
cat > get_logs.php << 'EOF'
<?php
header('Content-Type: application/json');
$log_file = 'telegram_webhook.log';

$response = [
    'success' => false,
    'logs' => [],
    'total' => 0,
    'today' => 0,
    'users' => 0
];

if (file_exists($log_file)) {
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    // 统计信息
    $response['total'] = count($lines);
    $response['today'] = 0;
    $userSet = [];
    
    // 解析最近50条日志
    $recent_logs = array_slice(array_reverse($lines), 0, 50);
    
    foreach ($recent_logs as $line) {
        // 解析时间戳和内容
        if (preg_match('/^\[([^\]]+)\]\s*(.+)$/', $line, $matches)) {
            $time = $matches[1];
            $content = $matches[2];
            
            // 提取用户ID
            if (preg_match('/用户\s+@?([^\s\(]+)/', $content, $userMatch)) {
                $userSet[$userMatch[1]] = true;
            }
            
            // 检查是否是今天的消息
            $today = date('Y-m-d');
            if (strpos($time, $today) === 0) {
                $response['today']++;
            }
            
            $response['logs'][] = [
                'time' => $time,
                'content' => trim($content)
            ];
        }
    }
    
    $response['users'] = count($userSet);
    $response['success'] = true;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
EOF

# 创建clear_logs.php
cat > clear_logs.php << 'EOF'
<?php
$log_file = 'telegram_webhook.log';

if (file_exists($log_file)) {
    // 只清空，不删除文件
    file_put_contents($log_file, date('[Y-m-d H:i:s]') . " 日志已清空\n");
    echo '日志已清空';
} else {
    echo '日志文件不存在';
}
?>
EOF
