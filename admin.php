<?php
// admin.php - 对话管理面板
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>中蒙代购机器人 - 对话管理</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .log-entry { 
            background: #f5f5f5; 
            margin: 10px 0; 
            padding: 10px; 
            border-radius: 5px;
            border-left: 4px solid #2196F3;
        }
        .user-info { color: #2196F3; font-weight: bold; }
        .message { margin: 5px 0; }
        .timestamp { color: #666; font-size: 12px; }
        .action-bar { margin: 20px 0; }
        button { padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #1976D2; }
    </style>
</head>
<body>
    <h1>🤖 中蒙代购机器人对话日志</h1>
    
    <div class="action-bar">
        <button onclick="location.reload()">🔄 刷新日志</button>
        <button onclick="clearLogs()">🗑️ 清空日志</button>
        <button onclick="downloadLogs()">📥 下载日志</button>
        <button onclick="location.href='https://dashboard.render.com/cyzgo/logs'" target="_blank">📊 Render实时日志</button>
    </div>
    
    <div id="log-container">
        <h3>最近对话记录：</h3>
        <?php
        $log_file = 'telegram_webhook.log';
        if (file_exists($log_file)) {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES);
            $lines = array_reverse($lines); // 最新的在前面
            $count = 0;
            
            foreach ($lines as $line) {
                if ($count >= 50) break; // 只显示最近50条
                
                echo "<div class='log-entry'>";
                echo "<div class='timestamp'>" . substr($line, 0, 19) . "</div>";
                
                // 高亮用户信息
                if (strpos($line, '用户ID:') !== false) {
                    echo "<div class='user-info'>" . 
                         str_replace(
                             ['用户ID:', '用户名:', '姓名:', '消息:'], 
                             ['👤 用户ID:', '@', '👤 姓名:', '💬 消息:'], 
                             $line
                         ) . 
                         "</div>";
                } else {
                    echo "<div class='message'>" . htmlspecialchars($line) . "</div>";
                }
                
                echo "</div>";
                $count++;
            }
        } else {
            echo "<p>暂无对话记录</p>";
        }
        ?>
    </div>
    
    <script>
        function clearLogs() {
            if (confirm('确定要清空所有对话记录吗？')) {
                fetch('?action=clear')
                    .then(response => response.text())
                    .then(() => location.reload());
            }
        }
        
        function downloadLogs() {
            window.open('?action=download', '_blank');
        }
        
        // 每30秒自动刷新
        setInterval(() => {
            fetch('?action=checkUpdate')
                .then(response => response.json())
                .then(data => {
                    if (data.updated) {
                        location.reload();
                    }
                });
        }, 30000);
    </script>
    
    <?php
    // 处理操作
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'clear':
                file_put_contents($log_file, '');
                echo "日志已清空";
                exit;
                
            case 'download':
                header('Content-Type: text/plain');
                header('Content-Disposition: attachment; filename="telegram_dialogs_' . date('Ymd') . '.log"');
                readfile($log_file);
                exit;
                
            case 'checkUpdate':
                $last_modified = file_exists($log_file) ? filemtime($log_file) : 0;
                echo json_encode(['updated' => (time() - $last_modified < 10)]);
                exit;
        }
    }
    ?>
    
    <hr>
    <p><strong>统计信息：</strong></p>
    <?php
    if (file_exists($log_file)) {
        $content = file_get_contents($log_file);
        $total_lines = substr_count($content, "\n");
        $user_count = count(array_unique(preg_match_all('/用户ID: (\d+)/', $content, $matches) ? $matches[1] : []));
        
        echo "<p>📊 总对话数: " . $total_lines . " 条</p>";
        echo "<p>👥 总用户数: " . $user_count . " 人</p>";
        echo "<p>⏰ 日志最后更新: " . date('Y-m-d H:i:s', filemtime($log_file)) . "</p>";
    }
    ?>
</body>
</html>
<!-- 在admin.php中添加 -->
<div class="tab" id="users-tab">
    <h2><i class="fas fa-users"></i> 用户列表</h2>
    
    <div class="user-grid">
        <?php
        $log_file = 'telegram_webhook.log';
        $users = [];
        
        if (file_exists($log_file)) {
            $content = file_get_contents($log_file);
            preg_match_all('/用户ID: (\d+)/', $content, $matches);
            $unique_users = array_unique($matches[1]);
            
            foreach ($unique_users as $user_id) {
                // 模拟用户信息（实际中可以从消息中提取）
                echo '
                <div class="user-card">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="user-info">
                        <h3>用户 #' . substr($user_id, -4) . '</h3>
                        <p><small>ID: ' . $user_id . '</small></p>
                        <p><i class="fas fa-comment"></i> 已发送消息</p>
                        <a href="?user_id=' . $user_id . '" class="btn-small">查看对话</a>
                    </div>
                </div>';
            }
        }
        ?>
    </div>
</div>

<style>
.user-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 20px;
}
.user-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 15px;
    transition: transform 0.3s;
}
.user-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
.user-avatar {
    font-size: 48px;
    color: #667eea;
    text-align: center;
    margin-bottom: 10px;
}
.btn-small {
    display: inline-block;
    padding: 5px 10px;
    background: #667eea;
    color: white;
    border-radius: 4px;
    text-decoration: none;
    font-size: 12px;
}
</style>
