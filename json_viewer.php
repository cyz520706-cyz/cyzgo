<?php
header('Content-Type: application/json');
$log_file = 'telegram_webhook.log';

$action = $_GET['action'] ?? 'latest';

if ($action === 'clear') {
    if (file_exists($log_file)) {
        file_put_contents($log_file, '');
        echo json_encode(['success' => true, 'message' => '日志已清空']);
    }
    exit;
}

// 获取日志并尝试解析为JSON
$result = ['success' => true, 'messages' => []];
if (file_exists($log_file)) {
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    // 只取最后20条
    $recent = array_slice($lines, -20);
    
    foreach ($recent as $line) {
        // 提取可能的JSON部分
        preg_match('/\[(.*?)\]\s*(.*)/', $line, $matches);
        $time = $matches[1] ?? '';
        $content = $matches[2] ?? $line;
        
        $entry = ['time' => $time];
        
        // 尝试解析为JSON
        $jsonStart = strpos($content, '{');
        if ($jsonStart !== false) {
            $jsonStr = substr($content, $jsonStart);
            // 尝试解析
            @$json = json_decode($jsonStr, true);
            if ($json) {
                $entry['type'] = 'json';
                $entry['data'] = $json;
                $entry['raw'] = $jsonStr;
            } else {
                $entry['type'] = 'text';
                $entry['text'] = $content;
            }
        } else {
            // 检查是否是URL消息
            if (strpos($content, 'https://') !== false || strpos($content, 'http://') !== false) {
                $entry['type'] = 'url';
                $entry['url'] = $content;
            } else if (strpos($content, '/') === 0) {
                $entry['type'] = 'command';
                $entry['command'] = $content;
            } else {
                $entry['type'] = 'text';
                $entry['text'] = $content;
            }
        }
        
        $result['messages'][] = $entry;
    }
    
    $result['total'] = count($lines);
    $result['recent'] = count($recent);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
EOF

# 创建简洁的查看界面
cat > view.php << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>📊 机器人消息</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        .message { background: white; border-radius: 8px; padding: 15px; margin: 10px 0; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .message-time { color: #666; font-size: 12px; margin-bottom: 5px; }
        .message-content { font-family: monospace; margin: 5px 0; }
        .message-url { color: #0066cc; text-decoration: none; }
        .message-command { color: #cc3300; font-weight: bold; }
        .json-block { background: #f8f8f8; border: 1px solid #ddd; border-radius: 5px; padding: 10px; margin: 5px 0; overflow-x: auto; }
        .json-key { color: #0074d9; }
        .json-string { color: #2ecc40; }
    </style>
</head>
<body>
    <h1>🤖 机器人消息列表</h1>
    <div id="messages">正在加载...</div>
    
    <script>
    async function loadMessages() {
        const response = await fetch('json_viewer.php');
        const data = await response.json();
        
        if (data.success) {
            let html = '';
            data.messages.forEach((msg, i) => {
                html += `<div class="message">
                    <div class="message-time">${i+1}. ${msg.time}</div>`;
                
                if (msg.type === 'json') {
                    html += `<div class="json-block">${formatJson(msg.data)}</div>`;
                } else if (msg.type === 'url') {
                    html += `<div class="message-content">
                        <a href="${msg.url}" class="message-url">${msg.url}</a>
                    </div>`;
                } else if (msg.type === 'command') {
                    html += `<div class="message-content message-command">${msg.command}</div>`;
                } else {
                    html += `<div class="message-content">${msg.text || '--'}</div>`;
                }
                
                html += '</div>';
            });
            
            document.getElementById('messages').innerHTML = html || '<p>暂无消息</p>';
        }
    }
    
    function formatJson(obj) {
        const jsonStr = JSON.stringify(obj, null, 2);
        return jsonStr.replace(/(".*?":)/g, '<span class="json-key">$1</span>')
                     .replace(/(".*?")/g, '<span class="json-string">$1</span>')
                     .replace(/\n/g, '<br>')
                     .replace(/ /g, '&nbsp;');
    }
    
    setInterval(loadMessages, 3000);
    loadMessages();
    </script>
</body>
</html>
EOF

echo "✅ 创建了JSON解析页面"
