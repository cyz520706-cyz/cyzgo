cd /var/www/html

cat > admin.php << 'EOF'
<?php
// admin.php - 完整可用的管理面板
header('Content-Type: text/html; charset=utf-8');
ob_start();

// ==================== AJAX处理器 ====================
if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
    $action = $_REQUEST['action'] ?? 'get_logs';
    $log_file = 'telegram_webhook.log';
    
    // 设置JSON响应头
    header('Content-Type: application/json; charset=utf-8');
    
    switch($action) {
        case 'get_logs':
            $logs = [];
            if (file_exists($log_file)) {
                $content = file_get_contents($log_file);
                $lines = array_filter(explode("\n", trim($content)));
                
                foreach ($lines as $line) {
                    if (empty(trim($line))) continue;
                    
                    // 简单解析日志行
                    $type = (strpos($line, '用户消息') !== false) ? 'user' : 'bot';
                    preg_match('/\[(.*?)\]/', $line, $time_match);
                    $time = $time_match[1] ?? date('H:i:s');
                    
                    // 解码Unicode
                    $message = $line;
                    $message = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function($match) {
                        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
                    }, $message);
                    
                    $logs[] = [
                        'time' => $time,
                        'message' => htmlspecialchars($message),
                        'type' => $type
                    ];
                }
                
                // 只取最后50条并反转（最新的在前）
                $logs = array_slice(array_reverse($logs), 0, 50);
            }
            
            echo json_encode([
                'success' => true,
                'total' => count($logs),
                'logs' => $logs
            ], JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'clear_logs':
            if (file_exists($log_file)) {
                file_put_contents($log_file, '');
            }
            echo json_encode(['success' => true]);
            exit;
            
        case 'get_stats':
            $stats = ['total' => 0, 'today' => 0, 'latest' => ''];
            if (file_exists($log_file)) {
                $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $stats['total'] = count($lines);
                
                // 统计今天的消息
                $today = date('Y-m-d');
                foreach ($lines as $line) {
                    if (strpos($line, "[$today") === 0) {
                        $stats['today']++;
                    }
                }
                
                // 获取最新一条
                if (!empty($lines)) {
                    $stats['latest'] = end($lines);
                }
            }
            echo json_encode(['success' => true, 'stats' => $stats], JSON_UNESCAPED_UNICODE);
            exit;
            
        default:
            echo json_encode(['success' => false, 'error' => '未知操作']);
            exit;
    }
}

// ==================== HTML界面 ====================
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 中蒙代购机器人 - 管理面板</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Microsoft YaHei", sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; margin-bottom: 20px; text-align: center; }
        .header h1 { font-size: 2.2rem; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 15px; }
        
        .controls { display: flex; gap: 15px; padding: 20px; background: white; border-radius: 10px; margin-bottom: 20px; flex-wrap: wrap; justify-content: center; }
        .btn { padding: 12px 24px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn:hover { background: #5a67d8; transform: translateY(-2px); transition: all 0.3s; }
        .btn-danger { background: #e53e3e; }
        .btn-danger:hover { background: #c53030; }
        .btn-success { background: #38a169; }
        .btn-success:hover { background: #2f855a; }
        
        .content { background: white; border-radius: 10px; padding: 25px; margin-bottom: 20px; }
        .section-title { font-size: 1.4rem; margin-bottom: 15px; color: #2d3748; display: flex; align-items: center; gap: 10px; }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-number { font-size: 2rem; font-weight: bold; }
        .stat-label { font-size: 14px; opacity: 0.9; }
        
        .logs-container { background: #1a202c; border-radius: 10px; padding: 20px; color: white; font-family: monospace; max-height: 500px; overflow-y: auto; margin-top: 15px; }
        .log-entry { padding: 10px; margin-bottom: 8px; border-left: 4px solid #667eea; background: rgba(255,255,255,0.05); word-break: break-word; }
        .log-entry.user { border-left-color: #4299e1; }
        .log-entry.bot { border-left-color: #48bb78; }
        .log-time { color: #a0aec0; font-size: 12px; margin-right: 10px; }
        
        .loading { text-align: center; padding: 30px; }
        .spinner { border: 4px solid rgba(0,0,0,0.1); border-radius: 50%; border-top: 4px solid #667eea; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 15px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .footer { text-align: center; padding: 20px; color: #718096; }
        
        @media (max-width: 768px) {
            .header h1 { font-size: 1.6rem; }
            .controls { flex-direction: column; }
            .btn { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-robot"></i> 中蒙代购机器人管理面板</h1>
            <p>实时监控对话日志，管理机器人状态</p>
        </div>
        
        <div class="controls">
            <button class="btn" onclick="loadLogs()" id="refreshBtn">
                <i class="fas fa-sync-alt"></i> 刷新日志
            </button>
            <button class="btn btn-danger" onclick="clearLogs()">
                <i class="fas fa-trash-alt"></i> 清空日志
            </button>
            <button class="btn btn-success" onclick="loadStats()">
                <i class="fas fa-chart-bar"></i> 更新统计
            </button>
            <a href="webhook.php" class="btn">
                <i class="fas fa-home"></i> 返回主页
            </a>
        </div>
        
        <div class="content">
            <div class="section-title"><i class="fas fa-chart-line"></i> 统计数据</div>
            <div class="stats" id="statsContainer">
                <div class="stat-card">
                    <div class="stat-number" id="totalMessages">0</div>
                    <div class="stat-label">总消息数</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="stat-number" id="todayMessages">0</div>
                    <div class="stat-label">今日消息</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="stat-number" id="logSize">0 KB</div>
                    <div class="stat-label">日志大小</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: #333;">
                    <div class="stat-number" id="lastUpdate"><?php echo date('H:i'); ?></div>
                    <div class="stat-label">最后更新</div>
                </div>
            </div>
            
            <div class="section-title"><i class="fas fa-comments"></i> 对话日志</div>
            <div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
                <label><input type="checkbox" id="showUser" checked onchange="loadLogs()"> 用户消息</label>
                <label><input type="checkbox" id="showBot" checked onchange="loadLogs()"> 机器人回复</label>
                <input type="text" id="searchInput" placeholder="搜索消息..." style="flex-grow: 1; padding: 8px; border: 1px solid #e2e8f0; border-radius: 5px;" onkeyup="loadLogs()">
            </div>
            
            <div id="logsContainer" class="logs-container">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>正在加载日志...</p>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 15px; color: #718096; font-size: 14px;">
                <i class="fas fa-info-circle"></i> 自动刷新: <span id="countdown">30</span>秒
            </div>
        </div>
        
        <div class="footer">
            <p><i class="fas fa-server"></i> Apache/PHP | <i class="fas fa-clock"></i> <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>

    <script>
        let autoRefreshTimer = 30;
        let countdownInterval;
        
        // 页面加载完成
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadLogs();
            startAutoRefresh();
        });
        
        // 加载日志
        async function loadLogs() {
            const container = document.getElementById('logsContainer');
            const refreshBtn = document.getElementById('refreshBtn');
            
            container.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>正在加载日志...</p>
                </div>
            `;
            
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 加载中...';
            
            try {
                const response = await fetch('admin.php?ajax=1&action=get_logs');
                const data = await response.json();
                
                if (data.success) {
                    const showUser = document.getElementById('showUser').checked;
                    const showBot = document.getElementById('showBot').checked;
                    const search = document.getElementById('searchInput').value.toLowerCase();
                    
                    container.innerHTML = '';
                    
                    if (data.logs.length === 0) {
                        container.innerHTML = `
                            <div class="log-entry" style="text-align: center; border-color: #d69e2e;">
                                <i class="fas fa-inbox fa-2x" style="color: #d69e2e;"></i>
                                <p style="margin-top: 10px; color: #a0aec0;">暂无对话记录</p>
                            </div>
                        `;
                    } else {
                        data.logs.forEach(log => {
                            // 过滤
                            if ((log.type === 'user' && !showUser) || (log.type === 'bot' && !showBot)) {
                                return;
                            }
                            
                            // 搜索过滤
                            if (search && !log.message.toLowerCase().includes(search)) {
                                return;
                            }
                            
                            const entry = document.createElement('div');
                            entry.className = `log-entry ${log.type}`;
                            entry.innerHTML = `
                                <span class="log-time">[${log.time}]</span>
                                ${escapeHtml(log.message)}
                            `;
                            container.appendChild(entry);
                        });
                    }
                } else {
                    showError('加载失败: ' + (data.error || '未知错误'));
                }
            } catch (error) {
                showError('网络错误: ' + error.message);
            } finally {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> 刷新日志';
                resetAutoRefresh();
            }
        }
        
        // 加载统计
        async function loadStats() {
            try {
                const response = await fetch('admin.php?ajax=1&action=get_stats');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('totalMessages').textContent = data.stats.total;
                    document.getElementById('todayMessages').textContent = data.stats.today;
                    
                    // 获取日志文件大小
                    fetch('admin.php?ajax=1&action=get_logs')
                        .then(res => res.json())
                        .then(logData => {
                            const sizeKB = (JSON.stringify(logData).length / 1024).toFixed(2);
                            document.getElementById('logSize').textContent = sizeKB + ' KB';
                        });
                    
                    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('zh-CN', {hour: '2-digit', minute:'2-digit'});
                }
            } catch (error) {
                console.error('统计加载失败:', error);
            }
        }
        
        // 清空日志
        async function clearLogs() {
            if (!confirm('⚠️ 确定要清空所有日志吗？此操作不可恢复！')) {
                return;
            }
            
            try {
                const response = await fetch('admin.php?ajax=1&action=clear_logs');
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ 日志已清空');
                    loadLogs();
                    loadStats();
                } else {
                    alert('❌ 清空失败');
                }
            } catch (error) {
                alert('❌ 网络错误: ' + error.message);
            }
        }
        
        // 自动刷新
        function startAutoRefresh() {
            updateCountdown();
            countdownInterval = setInterval(updateCountdown, 1000);
        }
        
        function updateCountdown() {
            const countdownEl = document.getElementById('countdown');
            countdownEl.textContent = autoRefreshTimer;
            
            if (autoRefreshTimer <= 0) {
                loadLogs();
                loadStats();
                autoRefreshTimer = 30;
            } else {
                autoRefreshTimer--;
            }
        }
        
        function resetAutoRefresh() {
            autoRefreshTimer = 30;
            document.getElementById('countdown').textContent = '30';
        }
        
        // 显示错误
        function showError(message) {
            const container = document.getElementById('logsContainer');
            container.innerHTML = `
                <div class="log-entry" style="border-color: #e53e3e; background: rgba(229, 62, 62, 0.1);">
                    <i class="fas fa-exclamation-triangle"></i> ${message}
                </div>
            `;
        }
        
        // HTML转义
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
<?php
ob_end_flush();
?>
EOF

echo "✅ admin.php 已创建！"
echo "请立即访问: https://cyzgo.onrender.com/admin.php"
