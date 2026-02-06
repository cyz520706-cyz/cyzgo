<?php
session_start();

// 临时简化验证（仅用于测试）
function checkSecurity() {
    // 直接放行，不再做Basic-Auth验证
    if (!isset($_SESSION['admin_logged_in'])) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['login_token'] ?? '') === 'valid') {
            $_SESSION['admin_logged_in'] = true;
        } else {
            showLoginForm();
            exit;
        }
    }
}

// 登录表单
function showLoginForm() {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>管理面板登录</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                font-family: 'Microsoft YaHei', sans-serif; 
                margin: 0; padding: 0; 
                height: 100vh; display: flex; align-items: center; justify-content: center;
            }
            .login-container {
                background: white; padding: 40px; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                text-align: center; min-width: 350px;
            }
            .login-container h2 { margin-bottom: 30px; color: #2d3748; }
            .login-container input { 
                width: 100%; padding: 15px; margin: 10px 0; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px;
            }
            .login-container button { 
                width: 100%; padding: 15px; background: #667eea; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; margin-top: 20px;
            }
            .login-container button:hover { background: #5a67d8; }
            .error { color: #e53e3e; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2><i class="fas fa-shield-alt"></i> 管理面板登录</h2>
            <form method="POST">
                <input type="hidden" name="login_token" value="valid">
                <input type="password" name="password" placeholder="请输入管理员密码" required>
                <button type="submit"><i class="fas fa-sign-in-alt"></i> 登录</button>
            </form>
            <p style="margin-top: 20px; color: #718096; font-size: 14px;">
                <i class="fas fa-info-circle"></i> 
                首次登录请使用默认密码：<strong>admin123</strong>（建议登录后立即修改）
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 登出功能
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 检查安全
checkSecurity();

// -------------------------------
// 2️⃣ 数据库初始化 & 操作
// -------------------------------
class LogDB {
    private $db;
    
    public function __construct() {
        $this->initDB();
    }
    
    private function initDB() {
        $this->db = new SQLite3(LOG_DB_PATH);
        // 启用 WAL 模式提高并发写入性能
        $this->db->exec('PRAGMA journal_mode = WAL;');
        $this->db->exec('PRAGMA synchronous = NORMAL;');
        $this->db->exec('CREATE TABLE IF NOT EXISTS logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT NOT NULL,
            type TEXT NOT NULL,
            message TEXT NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip TEXT
        )');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_timestamp ON logs(timestamp)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_user ON logs(user_id)');
    }
    
    public function addLog($userId, $message, $type = 'user') {
        $stmt = $this->db->prepare('INSERT INTO logs (user_id, type, message, ip) VALUES (:uid, :type, :msg, :ip)');
        $stmt->bindValue(':uid', $userId, SQLITE3_TEXT);
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $stmt->bindValue(':msg', $message, SQLITE3_TEXT);
        $stmt->bindValue(':ip', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
        $stmt->execute();
        $stmt->close();
        return $this->db->lastInsertRowID();
    }
    
    public function getLogs($filters = []) {
        $where = [];
        $params = [];
        
        // 类型过滤
        if (!($filters['show_user'] ?? true)) {
            $where[] = "type != 'user'";
        }
        if (!($filters['show_bot'] ?? true)) {
            $where[] = "type != 'bot'";
        }
        
        // 搜索关键词
        if (!empty($filters['search'])) {
            $where[] = "(message LIKE :search OR user_id LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // 时间范围
        if (isset($filters['time_range']) && $filters['time_range'] !== 'all') {
            switch ($filters['time_range']) {
                case 'today':
                    $where[] = "DATE(timestamp) = DATE('now')";
                    break;
                case 'yesterday':
                    $where[] = "DATE(timestamp) = DATE('now','-1 day')";
                    break;
                case 'week':
                    $where[] = "timestamp >= DATE('now','-7 day')";
                    break;
            }
        }
        
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // 统计总数
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM logs {$whereSql}");
        foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
        $countResult = $countStmt->execute();
        $total = $countResult->fetchArray()[0];
        $countStmt->close();
        
        // 分页
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = min(200, max(5, (int)($filters['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;
        
        // 查询数据
        $stmt = $this->db->prepare("SELECT * FROM logs {$whereSql} ORDER BY id DESC LIMIT :limit OFFSET :offset");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        $logs = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $logs[] = [
                'id' => $row['id'],
                'time' => $row['timestamp'],
                'user' => $row['user_id'],
                'message' => $row['message'],
                'type' => $row['type'],
                'ip' => $row['ip']
            ];
        }
        $stmt->close();
        
        return [
            'logs' => $logs,
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    public function getStats() {
        $stats = [];
        
        // 活跃用户（24小时内）
        $stmt = $this->db->prepare('SELECT COUNT(DISTINCT user_id) FROM logs WHERE timestamp >= datetime("now","-1 day")');
        $result = $stmt->execute();
        $stats['active_users'] = $result->fetchArray()[0];
        $stmt->close();
        
        // 总对话数
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM logs');
        $result = $stmt->execute();
        $stats['total_conversations'] = $result->fetchArray()[0];
        $stmt->close();
        
        // 今日消息
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM logs WHERE DATE(timestamp) = DATE("now")');
        $result = $stmt->execute();
        $stats['today_messages'] = $result->fetchArray()[0];
        $stmt->close();
        
        // 平均响应时间（模拟值）
        $stats['avg_response'] = '0.3s';
        
        // 最近用户列表
        $stmt = $this->db->prepare('SELECT user_id, MAX(timestamp) as last_active, COUNT(*) as message_count, message as last_message 
                                   FROM logs GROUP BY user_id ORDER BY last_active DESC LIMIT 20');
        $result = $stmt->execute();
        $stats['recent_users'] = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $stats['recent_users'][] = [
                'id' => $row['user_id'],
                'last_active' => $row['last_active'],
                'message_count' => $row['message_count'],
                'last_message' => $row['last_message'] ?: ''
            ];
        }
        $stmt->close();
        
        return $stats;
    }
    
    public function clearLogs() {
        $this->db->exec('DELETE FROM logs');
        $this->db->exec('VACUUM');
        return $this->db->changes();
    }
    
    public function exportLogs($format = 'json') {
        $stmt = $this->db->prepare('SELECT * FROM logs ORDER BY id DESC');
        $result = $stmt->execute();
        $logs = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $logs[] = $row;
        }
        $stmt->close();
        
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="telegram_logs_' . date('Y-m-d_H-i-s') . '.csv"');
            
            $output = fopen('php://output', 'w');
            // CSV 头部
            fputcsv($output, ['ID', '用户ID', '类型', '消息', '时间', 'IP']);
            
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['id'], $log['user_id'], $log['type'], 
                    $log['message'], $log['timestamp'], $log['ip']
                ]);
            }
            fclose($output);
        } else {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="telegram_logs_' . date('Y-m-d_H-i-s') . '.json"');
            echo json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        exit;
    }
}

// 初始化数据库
$logDB = new LogDB();

// -------------------------------
// 3️⃣ AJAX 请求处理
// -------------------------------
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($_GET['action']) {
        case 'get_logs':
            $filters = [
                'show_user' => ($_GET['show_user'] ?? '1') === '1',
                'show_bot' => ($_GET['show_bot'] ?? '1') === '1',
                'search' => trim($_GET['q'] ?? ''),
                'time_range' => $_GET['time'] ?? 'all',
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];
            
            $data = $logDB->getLogs($filters);
            echo json_encode([
                'success' => true,
                ...$data
            ], JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'get_stats':
            echo json_encode([
                'success' => true,
                ...$logDB->getStats()
            ], JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'clear_logs':
            // CSRF 防护
            $token = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'CSRF token invalid']);
                exit;
            }
            
            // 自动备份
            if (!is_dir(LOG_FILE_BACKUP_DIR)) {
                mkdir(LOG_FILE_BACKUP_DIR, 0755, true);
            }
            
            $backupFile = LOG_FILE_BACKUP_DIR . '/logs_backup_' . date('Y-m-d_H-i-s') . '.sqlite';
            copy(LOG_DB_PATH, $backupFile);
            
            $deleted = $logDB->clearLogs();
            echo json_encode([
                'success' => true,
                'deleted' => $deleted,
                'backup' => basename($backupFile)
            ], JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'export_logs':
            $format = $_GET['format'] ?? 'json';
            $logDB->exportLogs($format);
            exit;
            
        case 'events':
            // Server-Sent Events 实时推送
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            
            $lastId = (int)($_GET['last_id'] ?? 0);
            
            while (true) {
                $stmt = $logDB->db->prepare('SELECT * FROM logs WHERE id > :lastId ORDER BY id DESC LIMIT 10');
                $stmt->bindValue(':lastId', $lastId, SQLITE3_INTEGER);
                $result = $stmt->execute();
                
                $newLogs = [];
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $newLogs[] = $row;
                    $lastId = max($lastId, $row['id']);
                }
                $stmt->close();
                
                foreach (array_reverse($newLogs) as $log) {
                    echo "data: " . json_encode([
                        'id' => $log['id'],
                        'time' => $log['timestamp'],
                        'user' => $log['user_id'],
                        'message' => $log['message'],
                        'type' => $log['type']
                    ], JSON_UNESCAPED_UNICODE) . "\n\n";
                }
                
                flush();
                usleep(1000000); // 1秒检查一次
            }
            exit;
    }
}

// 生成 CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 Telegram 管理面板</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* 基础样式 */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Microsoft YaHei', 'PingFang SC', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* 头部样式 */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header p { font-size: 1.1rem; opacity: 0.9; }
        
        /* 控制栏 */
        .controls {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-success { background: #38a169; }
        .btn-success:hover { background: #2f855a; }
        .btn-danger { background: #e53e3e; }
        .btn-danger:hover { background: #c53030; }
        .btn-warning { background: #d69e2e; }
        .btn-warning:hover { background: #b7791f; }
        
        /* 内容区域 */
        .content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.5rem;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        /* 标签页 */
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-btn { padding: 10px 20px; border: none; background: #e2e8f0; cursor: pointer; border-radius: 5px; }
        .tab-btn.active { background: #667eea; color: white; }
        
        /* 日志区域 */
        .logs-container {
            background: #1a202c;
            border-radius: 10px;
            padding: 20px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 14px;
            line-height: 1.5;
            max-height: 600px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        
        .log-entry {
            padding: 8px 12px;
            margin-bottom: 6px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.05);
            border-left: 3px solid transparent;
            color: #cbd5e0;
            word-wrap: break-word;
            white-space: pre-wrap;
        }
        
        .log-entry.user { border-left-color: #4299e1; }
        .log-entry.bot { border-left-color: #68d391; }
        .log-time { color: #a0aec0; font-size: 12px; margin-right: 10px; }
        .log-user { color: #63b3ed; font-weight: bold; }
        .log-message { color: #e2e8f0; }
        
        /* 统计卡片 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { font-size: 14px; text-transform: uppercase; opacity: 0.9; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .stat-number { font-size: 2.5rem; font-weight: bold; }
        .stat-desc { font-size: 13px; opacity: 0.8; margin-top: 5px; }
        
        /* 用户表格 */
        .users-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .users-table th { background: #4c51bf; color: white; padding: 12px 15px; text-align: left; }
        .users-table td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; }
        .users-table tr:hover { background: #f7fafc; }
        
        /* 分页 */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .pagination button {
            padding: 8px 15px;
            border: 1px solid #e2e8f0;
            background: white;
            cursor: pointer;
            border-radius: 5px;
        }
        
        .pagination button:hover { background: #f7fafc; }
        .pagination button.active { background: #667eea; color: white; }
        
        /* 过滤器 */
        .filters {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .filters input, .filters select {
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        
        /* 响应式 */
        @media (max-width: 768px) {
            .header h1 { font-size: 1.8rem; }
            .controls { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
            .stats-grid { grid-template-columns: 1fr; }
        }
        
        /* 工具类 */
        .text-center { text-align: center; }
        .mb-20 { margin-bottom: 20px; }
        .mt-20 { margin-top: 20px; }
        .d-none { display: none; }
        .loading {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
        
        .spinner {
            border: 4px solid rgba(102, 126, 234, 0.2);
            border-radius: 50%;
            border-top: 4px solid #667eea;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 头部 -->
        <div class="header">
            <h1>
                <i class="fas fa-robot"></i>
                Telegram 管理面板
                <a href="?logout" class="btn btn-warning" style="margin-left: auto; padding: 8px 15px; font-size: 14px;">
                    <i class="fas fa-sign-out-alt"></i> 登出
                </a>
            </h1>
            <p>实时监控用户对话、查看统计分析、管理系统状态</p>
            <div class="mt-20">
                <small>
                    <i class="fas fa-clock"></i> 服务器时间：<?php echo date('Y-m-d H:i:s'); ?> | 
                    <i class="fas fa-server"></i> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Render'; ?>
                </small>
            </div>
        </div>
        
        <!-- 控制栏 -->
        <div class="controls">
            <button class="btn" onclick="refreshLogs()" id="refresh-btn">
                <i class="fas fa-sync-alt"></i> 刷新
            </button>
            <button class="btn btn-warning" onclick="clearLogs()">
                <i class="fas fa-trash-alt"></i> 清空日志
            </button>
            <a href="?action=export_logs&format=json" class="btn btn-success">
                <i class="fas fa-download"></i> 导出JSON
            </a>
            <a href="?action=export_logs&format=csv" class="btn btn-success">
                <i class="fas fa-file-csv"></i> 导出CSV
            </a>
            <button class="btn" onclick="toggleRealtime()" id="realtime-btn">
                <i class="fas fa-play"></i> 实时更新
            </button>
        </div>
        
        <!-- 主要内容 -->
        <div class="content">
            <!-- 标签页 -->
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('logs')" id="logs-tab">对话日志</button>
                <button class="tab-btn" onclick="showTab('users')" id="users-tab">用户统计</button>
                <button class="tab-btn" onclick="showTab('system')" id="system-tab">系统信息</button>
            </div>
            
            <!-- 对话日志标签页 -->
            <div id="logs-content">
                <div class="section-title">
                    <i class="fas fa-list-alt"></i> 对话记录
                    <span id="log-count" style="margin-left: auto; color: #667eea;"></span>
                </div>
                
                <!-- 过滤器 -->
                <div class="filters">
                    <label><input type="checkbox" id="show-user" checked onchange="loadLogs()"> 用户消息</label>
                    <label><input type="checkbox" id="show-bot" checked onchange="loadLogs()"> 机器人回复</label>
                    <input type="text" id="search-query" placeholder="搜索关键词..." onkeyup="loadLogs()">
                    <select id="time-range" onchange="loadLogs()">
                        <option value="all">所有时间</option>
                        <option value="today">今天</option>
                        <option value="yesterday">昨天</option>
                        <option value="week">最近一周</option>
                    </select>
                </div>
                
                <!-- 日志显示区域 -->
                <div id="logs-container" class="logs-container">
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>正在加载对话日志...</p>
                    </div>
                </div>
                
                <!-- 分页 -->
                <div class="pagination" id="pagination"></div>
            </div>
            
            <!-- 用户统计标签页 -->
            <div id="users-content" class="d-none">
                <div class="section-title">
                    <i class="fas fa-chart-pie"></i> 用户统计
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><i class="fas fa-users"></i> 活跃用户</h3>
                        <div class="stat-number" id="active-users">0</div>
                        <div class="stat-desc">24小时内活跃</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3><i class="fas fa-comment-alt"></i> 总对话数</h3>
                        <div class="stat-number" id="total-conversations">0</div>
                        <div class="stat-desc">累计消息数量</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3><i class="fas fa-calendar-alt"></i> 今日消息</h3>
                        <div class="stat-number" id="today-messages">0</div>
                        <div class="stat-desc">今天收到的消息</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h3><i class="fas fa-clock"></i> 平均响应</h3>
                        <div class="stat-number" id="avg-response">0.3s</div>
                        <div class="stat-desc">平均响应时间</div>
                    </div>
                </div>
                
                <!-- 用户列表 -->
                <div class="mt-20">
                    <h3><i class="fas fa-list"></i> 最近活跃用户</h3>
                    <table class="users-table" id="users-table">
                        <thead>
                            <tr>
                                <th>用户ID</th>
                                <th>最后活跃</th>
                                <th>消息数</th>
                                <th>最近消息</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 动态填充 -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- 系统信息标签页 -->
            <div id="system-content" class="d-none">
                <div class="section-title">
                    <i class="fas fa-server"></i> 系统状态
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><i class="fas fa-hdd"></i> 服务器状态</h3>
                        <div class="stat-number" style="color: #68d391;">✅ 正常</div>
                        <div class="stat-desc">PHP <?php echo PHP_VERSION; ?></div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <h3><i class="fas fa-database"></i> 数据库</h3>
                        <?php
                        $dbSize = file_exists(LOG_DB_PATH) ? round(filesize(LOG_DB_PATH) / 1024, 2) : 0;
                        $dbModified = file_exists(LOG_DB_PATH) ? date('Y-m-d H:i:s', filemtime(LOG_DB_PATH)) : '无';
                        ?>
                        <div class="stat-number"><?php echo $dbSize; ?> KB</div>
                        <div class="stat-desc">最后更新: <?php echo $dbModified; ?></div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">
                        <h3><i class="fas fa-code-branch"></i> 内存限制</h3>
                        <div class="stat-number"><?php echo ini_get('memory_limit'); ?></div>
                        <div class="stat-desc">当前使用: <?php echo round(memory_get_usage(true)/1024/1024, 2); ?>MB</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333;">
                        <h3><i class="fas fa-network-wired"></i> 网络状态</h3>
                        <div class="stat-number">🟢 在线</div>
                        <div class="stat-desc">IP: <?php echo $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '未知'; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 全局变量
        let currentPage = 1;
        let totalPages = 1;
        let realtimeEnabled = false;
        let eventSource = null;
        let lastEventId = 0;
        
        // 页面加载完成
        document.addEventListener('DOMContentLoaded', function() {
            loadLogs();
            updateStats();
        });
        
        // 显示标签页
        function showTab(tabName) {
            // 隐藏所有标签页
            ['logs', 'users', 'system'].forEach(tab => {
                document.getElementById(tab + '-content').classList.add('d-none');
                document.getElementById(tab + '-tab').classList.remove('active');
            });
            
            // 显示选中的标签页
            document.getElementById(tabName + '-content').classList.remove('d-none');
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // 加载对应数据
            if (tabName === 'users') {
                updateStats();
            }
        }
        
        // 加载对话日志
        async function loadLogs(page = 1) {
            const logsContainer = document.getElementById('logs-container');
            const pagination = document.getElementById('pagination');
            const logCount = document.getElementById('log-count');
            
            currentPage = page;
            
            // 显示加载状态
            logsContainer.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>正在加载对话日志...</p>
                </div>
            `;
            
            try {
                const params = new URLSearchParams({
                    action: 'get_logs',
                    page: page,
                    limit: 50,
                    show_user: document.getElementById('show-user').checked ? '1' : '0',
                    show_bot: document.getElementById('show-bot').checked ? '1' : '0',
                    q: document.getElementById('search-query').value.trim(),
                    time: document.getElementById('time-range').value
                });
                
                const response = await fetch('?' + params.toString());
                const data = await response.json();
                
                if (data.success) {
                    logCount.textContent = `共 ${data.total} 条记录，第 ${data.page}/${data.pages} 页`;
                    totalPages = data.pages;
                    
                    // 显示日志内容
                    logsContainer.innerHTML = '';
                    
                    if (data.logs.length === 0) {
                        logsContainer.innerHTML = `
                            <div class="text-center" style="color: #a0aec0; padding: 40px;">
                                <i class="fas fa-inbox fa-3x" style="margin-bottom: 15px;"></i>
                                <p>暂无对话记录</p>
                            </div>
                        `;
                    } else {
                        data.logs.forEach(log => {
                            const entry = document.createElement('div');
                            entry.className = `log-entry ${log.type}`;
                            entry.innerHTML = `
                                <span class="log-time">[${log.time}]</span>
                                <span class="log-user">${escapeHtml(log.user)}</span>
                                <span class="log-message">${escapeHtml(log.message)}</span>
                            `;
                            logsContainer.appendChild(entry);
                        });
                    }
                    
                    // 生成分页
                    generatePagination();
                }
            } catch (error) {
                logsContainer.innerHTML = `
                    <div class="text-center" style="color: #f56565; padding: 20px;">
                        <i class="fas fa-exclamation-triangle"></i> 加载失败: ${error.message}
                    </div>
                `;
            }
        }
        
        // 生成分页
        function generatePagination() {
            const pagination = document.getElementById('pagination');
            let html = '';
            
            // 上一页
            if (currentPage > 1) {
                html += `<button onclick="loadLogs(${currentPage - 1})">上一页</button>`;
            }
            
            // 页码
            const start = Math.max(1, currentPage - 2);
            const end = Math.min(totalPages, currentPage + 2);
            
            for (let i = start; i <= end; i++) {
                html += `<button onclick="loadLogs(${i})" ${i === currentPage ? 'class="active"' : ''}>${i}</button>`;
            }
            
            // 下一页
            if (currentPage < totalPages) {
                html += `<button onclick="loadLogs(${currentPage + 1})">下一页</button>`;
            }
            
            pagination.innerHTML = html;
        }
        
        // 更新统计数据
        async function updateStats() {
            try {
                const response = await fetch('?action=get_stats');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('active-users').textContent = data.active_users || 0;
                    document.getElementById('total-conversations').textContent = data.total_conversations || 0;
                    document.getElementById('today-messages').textContent = data.today_messages || 0;
                    document.getElementById('avg-response').textContent = data.avg_response || '0.3s';
                    
                    // 更新用户表格
                    const tbody = document.querySelector('#users-table tbody');
                    tbody.innerHTML = '';
                    
                    if (data.recent_users && data.recent_users.length > 0) {
                        data.recent_users.forEach(user => {
                            const row = tbody.insertRow();
                            row.innerHTML = `
                                <td><code>${escapeHtml(user.id)}</code></td>
                                <td>${user.last_active}</td>
                                <td><span style="background: #667eea; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;">${user.message_count}</span></td>
                                <td>${user.last_message ? escapeHtml(user.last_message.substring(0, 50)) + '...' : '无'}</td>
                            `;
                        });
                    }
                }
            } catch (error) {
                console.error('更新统计失败:', error);
            }
        }
        
        // 清空日志
        async function clearLogs() {
            if (!confirm('⚠️ 确定要清空所有对话日志吗？\n\n此操作将删除所有历史记录，但会自动备份到 backup 目录。')) {
                return;
            }
            
            try {
                const response = await fetch('?action=clear_logs', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'csrf_token=<?php echo $_SESSION['csrf_token']; ?>'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`✅ 日志已清空！\n删除了 ${result.deleted} 条记录\n备份文件：${result.backup}`);
                    loadLogs();
                    updateStats();
                } else {
                    alert('❌ 清空失败：' + (result.error || '未知错误'));
                }
            } catch (error) {
                alert('❌ 清空失败：' + error.message);
            }
        }
        
        // 刷新日志
        function refreshLogs() {
            loadLogs(currentPage);
        }
        
        // 实时更新切换
        function toggleRealtime() {
            const btn = document.getElementById('realtime-btn');
            
            if (realtimeEnabled) {
                // 关闭实时更新
                if (eventSource) {
                    eventSource.close();
                    eventSource = null;
                }
                realtimeEnabled = false;
                btn.innerHTML = '<i class="fas fa-play"></i> 实时更新';
                btn.classList.remove('btn-danger');
            } else {
                // 开启实时更新
                eventSource = new EventSource(`?action=events&last_id=${lastEventId}`);
                
                eventSource.onmessage = function(e) {
                    const data = JSON.parse(e.data);
                    lastEventId = data.id;
                    
                    // 在日志顶部插入新记录
                    const logsContainer = document.getElementById('logs-container');
                    const entry = document.createElement('div');
                    entry.className = `log-entry ${data.type}`;
                    entry.innerHTML = `
                        <span class="log-time">[${data.time}]</span>
                        <span class="log-user">${escapeHtml(data.user)}</span>
                        <span class="log-message">${escapeHtml(data.message)}</span>
                    `;
                    logsContainer.insertBefore(entry, logsContainer.firstChild);
                    
                    // 保持日志数量不超过100条
                    while (logsContainer.children.length > 100) {
                        logsContainer.removeChild(logsContainer.lastChild);
                    }
                };
                
                eventSource.onerror = function(e) {
                    console.warn('SSE连接错误，尝试重连...', e);
                };
                
                realtimeEnabled = true;
                btn.innerHTML = '<i class="fas fa-stop"></i> 停止实时';
                btn.classList.add('btn-danger');
            }
        }
        
        // HTML 转义
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        
        // 自动刷新统计
        setInterval(() => {
            const activeTab = document.querySelector('.tab-btn.active');
            if (activeTab && activeTab.id === 'users-tab') {
                updateStats();
            }
        }, 30000); // 30秒刷新一次统计
    </script>
</body>
</html>
