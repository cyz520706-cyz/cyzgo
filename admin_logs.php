<?php
header('Content-Type: application/json');
$log_file = 'telegram_webhook.log';

// 获取最新的日志
$logs = [];
if (file_exists($log_file)) {
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logs = array_map('htmlspecialchars', array_slice(array_reverse($lines), 0, 50));
}

echo json_encode([
    'success' => true,
    'total' => count($logs),
    'logs' => $logs
], JSON_UNESCAPED_UNICODE);
?>
EOF

# 创建简化的管理面板
cat > simple_admin.php << 'EOF'
<?php
$log_file = 'telegram_webhook.log';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🤖 机器人日志查看</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Consolas', 'Monaco', monospace; padding: 20px; background: #1a1a1a; color: #e0e0e0; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #4CAF50; margin-bottom: 20px; font-size: 24px; }
        .controls { margin-bottom: 20px; padding: 15px; background: #2d2d2d; border-radius: 5px; }
        .btn { background: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 3px; cursor: pointer; margin-right: 10px; }
        .btn:hover { background: #45a049; }
        .btn-danger { background: #f44336; }
        .btn-danger:hover { background: #da190b; }
        .stats { display: inline-block; margin-left: 20px; color: #aaa; }
        .log-container { background: #252525; border: 1px solid #333; border-radius: 5px; padding: 15px; overflow-x: auto; }
        .log-entry { padding: 12px; margin-bottom: 8px; background: #2d2d2d; border-left: 3px solid #4CAF50; font-size: 14px; line-height: 1.5; white-space: pre-wrap; word-break: break-all; }
        .log-time { color: #4CAF50; font-weight: bold; }
        .json-key { color: #87ceeb; }
        .json-string { color: #98c379; }
        .json-number { color: #d19a66; }
        .json-bool { color: #c678dd; }
        .json-null { color: #5c6370; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 中蒙代购机器人实时日志</h1>
        
        <div class="controls">
            <button class="btn" onclick="loadLogs()">🔄 刷新</button>
            <button class="btn-danger" onclick="clearLogs()">🗑️ 清空</button>
            <span class="stats">
                日志行数: <span id="logCount">0</span> |
                最后更新: <span id="lastUpdate">-</span>
            </span>
        </div>
        
        <div class="log-container" id="logs">
            正在加载日志...
        </div>
    </div>
    
    <script>
    function formatLogEntry(text) {
        // 尝试格式化为漂亮的JSON显示
        text = text.replace(/&quot;/g, '"')
                   .replace(/&lt;/g, '<')
                   .replace(/&gt;/g, '>')
                   .replace(/&amp;/g, '&');
        
        // 尝试检测是否是JSON格式
        if (text.includes('{') && text.includes('}')) {
            try {
                const jsonMatch = text.match(/\{[^}]+\}/);
                if (jsonMatch) {
                    const jsonStr = jsonMatch[0];
                    const obj = JSON.parse(jsonStr);
                    // 只格式化JSON部分
                    const prettyJson = JSON.stringify(obj, null, 2);
                    const formatted = prettyJson
                        .replace(/"([^"]+)":/g, '<span class="json-key">"$1":</span>')
                        .replace(/"([^"]+)"/g, '<span class="json-string">"$1"</span>')
                        .replace(/\b(true|false)\b/g, '<span class="json-bool">$1</span>')
                        .replace(/\b(null)\b/g, '<span class="json-null">$1</span>')
                        .replace(/\b\d+\b/g, '<span class="json-number">$&</span>');
                    
                    return text.replace(jsonStr, '<div class="json-block">' + formatted + '</div>');
                }
            } catch (e) {
                // 如果不是有效的JSON，保持原样
            }
        }
        
        // 简化替换
        return text.replace(/(".*?":)/g, '<span class="json-key">$1</span>')
                   .replace(/(".*?")/g, '<span class="json-string">$1</span>');
    }
    
    async function loadLogs() {
        try {
            const response = await fetch('admin_logs.php');
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('logCount').textContent = data.total;
                document.getElementById('lastUpdate').textContent = 
                    new Date().toLocaleTimeString();
                
                const logsDiv = document.getElementById('logs');
                if (data.logs.length > 0) {
                    let html = '';
                    data.logs.forEach(log => {
                        // 提取时间戳
                        const timeMatch = log.match(/^\[([^\]]+)\]/);
                        const time = timeMatch ? timeMatch[1] : '';
                        const message = timeMatch ? log.slice(timeMatch[0].length).trim() : log;
                        
                        html += `<div class="log-entry">
                            <span class="log-time">${time || '无时间戳'}</span>
                            ${formatLogEntry(message)}
                        </div>`;
                    });
                    logsDiv.innerHTML = html;
                    
                    // 滚动到底部
                    logsDiv.scrollTop = logsDiv.scrollHeight;
                } else {
                    logsDiv.innerHTML = '<div class="log-entry">暂无日志记录</div>';
                }
            }
        } catch (error) {
            console.error('加载失败:', error);
            document.getElementById('logs').innerHTML = 
                `<div class="log-entry">加载失败: ${error.message}</div>`;
        }
    }
    
    async function clearLogs() {
        if (confirm('确定要清空所有日志吗？')) {
            await fetch('admin_logs.php?action=clear');
            setTimeout(loadLogs, 500);
        }
    }
    
    // 自动刷新
    setInterval(loadLogs, 5000);
    loadLogs();
    </script>
</body>
</html>
EOF

# 创建清空日志的功能
cat > clear_logs.php << 'EOF'
<?php
$log_file = 'telegram_webhook.log';
if (file_exists($log_file)) {
    file_put_contents($log_file, ''); 
    echo '日志已清空';
} else {
    echo '日志文件不存在';
}
?>
EOF

echo "✅ 优化后的日志查看器已创建"

# 查看日志的最后几行
echo "=== 最近日志 ==="
tail -20 telegram_webhook.log
