<?php
// admin.php - 完整版本包含对话和订单管理

// 开启错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. 常量定义
if (!defined('LOG_DB_PATH')) {
    define('LOG_DB_PATH', '/tmp/logs.db');
}
if (!defined('DB_PATH')) {
    define('DB_PATH', '/tmp/admin.db');
}

// 2. 创建必要目录
$needDirs = [dirname(LOG_DB_PATH), dirname(DB_PATH)];
foreach ($needDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// 3. Session 配置
session_start();
if (!session_id()) {
    session_regenerate_id(true);
}

// 4. LogDB类定义
class LogDB {
    private static $instance = null;
    private $db;

    private function __construct() {
        $this->initDB();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initDB(): void {
        $this->db = new SQLite3(LOG_DB_PATH);
        
        // 创建日志表
        $this->db->exec('CREATE TABLE IF NOT EXISTS logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            timestamp TEXT NOT NULL DEFAULT (datetime(\'now\')),
            level TEXT NOT NULL,
            message TEXT NOT NULL,
            context TEXT
        )');

        // 创建对话表
        $this->db->exec('CREATE TABLE IF NOT EXISTS conversations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT NOT NULL,
            user_id TEXT,
            message TEXT NOT NULL,
            sender TEXT NOT NULL CHECK (sender IN (\'user\', \'bot\')),
            timestamp TEXT NOT NULL DEFAULT (datetime(\'now\')),
            metadata TEXT
        )');

        // 创建订单表
        $this->db->exec('CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_number TEXT NOT NULL UNIQUE,
            customer_name TEXT NOT NULL,
            customer_phone TEXT,
            product_name TEXT NOT NULL,
            quantity INTEGER NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            status TEXT NOT NULL DEFAULT \'pending\' CHECK (status IN (\'pending\', \'processing\', \'completed\', \'cancelled\')),
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            updated_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            notes TEXT
        )');

        $this->createSampleData();
    }

    private function createSampleData(): void {
        // 检查是否已有数据
        $count = $this->db->querySingle('SELECT COUNT(*) FROM conversations');
        if ($count == 0) {
            // 添加示例对话数据
            $stmt = $this->db->prepare('INSERT INTO conversations (session_id, user_id, message, sender, metadata) VALUES (?, ?, ?, ?, ?)');
            
            $sampleConversations = [
                ['sess_001', 'user_001', '你好，我想了解一下你们的产品', 'user', json_encode(['ip' => '192.168.1.100'])],
                ['sess_001', 'user_001', '您好！很高兴为您服务。我们有多款优质产品，您想了解哪一类呢？', 'bot', json_encode(['response_time' => '1.2s'])],
                ['sess_001', 'user_001', '我想买一台笔记本电脑', 'user', json_encode(['budget' => '5000-8000'])],
                ['sess_001', 'user_001', '好的，我推荐几款适合的型号。您的预算大概是多少呢？', 'bot', json_encode(['category' => 'recommendation'])],
                ['sess_002', 'user_002', '你们的客服电话是多少？', 'user', json_encode(['type' => 'inquiry'])],
                ['sess_002', 'user_002', '我们的客服热线是400-888-8888，工作时间是周一至周五9:00-18:00', 'bot', json_encode(['type' => 'contact_info'])]
            ];

            foreach ($sampleConversations as $conv) {
                $stmt->reset();
                $stmt->bindValue(1, $conv[0], SQLITE3_TEXT);
                $stmt->bindValue(2, $conv[1], SQLITE3_TEXT);
                $stmt->bindValue(3, $conv[2], SQLITE3_TEXT);
                $stmt->bindValue(4, $conv[3], SQLITE3_TEXT);
                $stmt->bindValue(5, $conv[4], SQLITE3_TEXT);
                $stmt->execute();
            }
        }

        // 添加示例订单数据
        $orderCount = $this->db->querySingle('SELECT COUNT(*) FROM orders');
        if ($orderCount == 0) {
            $stmt = $this->db->prepare('INSERT INTO orders (order_number, customer_name, customer_phone, product_name, quantity, price, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            
            $sampleOrders = [
                ['ORD-2024-001', '张三', '13800138001', 'MacBook Pro 14寸', 1, 15999.00, 'pending', '客户要求加急发货'],
                ['ORD-2024-002', '李四', '13800138002', 'iPhone 15 Pro', 2, 8999.00, 'processing', '已确认库存'],
                ['ORD-2024-003', '王五', '13800138003', 'iPad Air', 1, 4399.00, 'completed', '已发货，快递单号SF123456789'],
                ['ORD-2024-004', '赵六', '13800138004', 'AirPods Pro', 1, 1999.00, 'cancelled', '客户主动取消'],
                ['ORD-2024-005', '钱七', '13800138005', 'Apple Watch Series 9', 1, 2999.00, 'pending', '等待付款确认']
            ];

            foreach ($sampleOrders as $order) {
                $stmt->reset();
                $stmt->bindValue(1, $order[0], SQLITE3_TEXT);
                $stmt->bindValue(2, $order[1], SQLITE3_TEXT);
                $stmt->bindValue(3, $order[2], SQLITE3_TEXT);
                $stmt->bindValue(4, $order[3], SQLITE3_TEXT);
                $stmt->bindValue(5, $order[4], SQLITE3_INTEGER);
                $stmt->bindValue(6, $order[5], SQLITE3_FLOAT);
                $stmt->bindValue(7, $order[6], SQLITE3_TEXT);
                $stmt->bindValue(8, $order[7], SQLITE3_TEXT);
                $stmt->execute();
            }
        }
    }

    public function write(string $level, string $message, array $context = []): void {
        $stmt = $this->db->prepare('INSERT INTO logs (level, message, context) VALUES (?, ?, ?)');
        $stmt->bindValue(1, $level, SQLITE3_TEXT);
        $stmt->bindValue(2, $message, SQLITE3_TEXT);
        $stmt->bindValue(3, json_encode($context, JSON_UNESCAPED_UNICODE), SQLITE3_TEXT);
        $stmt->execute();
    }

    public function query(string $sql, array $params = []): SQLite3Result|false {
        if (empty($params)) {
            return $this->db->query($sql);
        }
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param, SQLITE3_TEXT);
        }
        
        return $stmt->execute();
    }

    public function exec(string $sql): bool {
        return $this->db->exec($sql);
    }

    public function close(): void {
        if ($this->db) {
            $this->db->close();
        }
    }
}

// 5. checkSecurity函数
function checkSecurity(): void {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['login_token'] ?? '';
        $pwd   = $_POST['password']   ?? '';
        
        if ($token === 'valid' && $pwd === 'admin123') {
            $_SESSION['admin_logged_in'] = true;
            LogDB::getInstance()->write('INFO', 'Admin login successful', ['ip'=>$_SERVER['REMOTE_ADDR']??'unknown']);
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            showLoginForm('密码错误，请重试');
            exit;
        }
    }

    showLoginForm();
    exit;
}

// 6. 登录表单函数
function showLoginForm(string $error = ''): void {
    $title = "Admin Panel – Login";
    $hint  = "默认密码：admin123（仅供测试）";
    
    echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' . htmlspecialchars($title) . '</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
 body{margin:0;font-family:"Microsoft YaHei", sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);height:100vh;display:flex;align-items:center;justify-content:center;}
 .card{background:#fff;padding:40px;border-radius:15px;box-shadow:0 20px 40px rgba(0,0,0,.15);min-width:360px;text-align:center;}
 h1{color:#2d3748;margin-bottom:30px;}
 input[type=password]{width:100%;padding:14px;border:2px solid #e2e8f0;border-radius:8px;font-size:16px;margin:12px 0;}
 button{width:100%;padding:14px;background:#667eea;color:#fff;border:none;border-radius:8px;font-size:16px;cursor:pointer;margin-top:10px;}
 button:hover{background:#5a67d8;}
 .hint{color:#718096;font-size:14px;margin-top:20px;}
 .error{color:#e53e3e;margin-top:10px;font-weight:bold;}
</style>
</head>
<body>
<div class="card">
<h1><i class="fas fa-shield-alt"></i> 管理员登录</h1>';
    if (!empty($error)) {
        echo '<div class="error"><i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($error) . '</div>';
    }
    echo '<form method="POST">
<input type="hidden" name="login_token" value="valid">
<input type="password" name="password" placeholder="请输入管理员密码" required>
<button type="submit"><i class="fas fa-sign-in-alt"></i> 登录</button>
</form>
<div class="hint">' . htmlspecialchars($hint) . '</div>
</div>
</body>
</html>';
}

// 7. 获取当前页面
$currentPage = $_GET['page'] ?? 'dashboard';

// 8. 执行安全检查
checkSecurity();

// 9. 管理后台页面
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Panel – Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
 body{margin:0;font-family:"Microsoft YaHei",sans-serif;background:#f7fafc;}
 .sidebar{position:fixed;left:0;top:0;width:250px;height:100vh;background:#2d3748;color:#fff;padding:20px;overflow-y:auto;}
 .main-content{margin-left:250px;padding:0;}
 header{background:#2d3748;color:#fff;padding:20px 40px;margin-left:250px;}
 .content{padding:30px 40px;}
 .card{background:#fff;border-radius:8px;padding:20px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.08);}
 h2{margin-top:0;}
 .nav-item{padding:12px 15px;margin:5px 0;border-radius:8px;cursor:pointer;color:#e2e8f0;text-decoration:none;display:block;transition:all 0.3s;}
 .nav-item:hover, .nav-item.active{background:#4a5568;color:#fff;}
 .status{color:#38a169;font-weight:bold;}
 .status.processing{color:#d69e2e;}
 .status.completed{color:#38a169;}
 .status.cancelled{color:#e53e3e;}
 .status.pending{color:#d69e2e;}
 table{width:100%;border-collapse:collapse;}
 th,td{border-bottom:1px solid #e2e8f0;padding:12px;text-align:left;vertical-align:top;}
 tr:hover{background:#f9fafb;}
 .badge{padding:4px 8px;border-radius:12px;font-size:12px;font-weight:bold;}
 .badge.user{background:#ebf8ff;color:#2b6cb0;}
 .badge.bot{background:#f0fff4;color:#2f855a;}
 .conversation{max-height:400px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;padding:15px;background:#f7fafc;}
 .message{margin-bottom:15px;padding:10px;border-radius:8px;max-width:70%;}
 .message.user{background:#ebf8ff;margin-left:auto;}
 .message.bot{background:#fff;border:1px solid #e2e8f0;}
 .message-meta{font-size:12px;color:#718096;margin-bottom:5px;}
 .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:20px;}
 .stat-card{background:#fff;border-radius:8px;padding:20px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.08);}
 .stat-number{font-size:2em;font-weight:bold;color:#2d3748;}
 .stat-label{color:#718096;margin-top:5px;}
</style>
</head>
<body>
<div class="sidebar">
    <h3><i class="fas fa-cogs"></i> 管理后台</h3>
    <nav>
        <a href="?page=dashboard" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> 仪表板
        </a>
        <a href="?page=conversations" class="nav-item <?php echo $currentPage === 'conversations' ? 'active' : ''; ?>">
            <i class="fas fa-comments"></i> 对话记录
        </a>
        <a href="?page=orders" class="nav-item <?php echo $currentPage === 'orders' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i> 订单管理
        </a>
        <a href="?page=logs" class="nav-item <?php echo $currentPage === 'logs' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i> 系统日志
        </a>
    </nav>
</div>

<?php
$logDB = LogDB::getInstance();

// 页面内容
if ($currentPage === 'dashboard') {
    // 获取统计数据
    $convCount = $logDB->querySingle('SELECT COUNT(DISTINCT session_id) FROM conversations');
    $orderCount = $logDB->querySingle('SELECT COUNT(*) FROM orders');
    $pendingOrders = $logDB->querySingle('SELECT COUNT(*) FROM orders WHERE status = "pending"');
    $completedOrders = $logDB->querySingle('SELECT COUNT(*) FROM orders WHERE status = "completed"');
    
    echo '<header><h1><i class="fas fa-tachometer-alt"></i> 仪表板</h1></header>';
    echo '<div class="content">';
    echo '<div class="stats">
        <div class="stat-card">
            <div class="stat-number">' . $convCount . '</div>
            <div class="stat-label">总会话数</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">' . $orderCount . '</div>
            <div class="stat-label">总订单数</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">' . $pendingOrders . '</div>
            <div class="stat-label">待处理订单</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">' . $completedOrders . '</div>
            <div class="stat-label">已完成订单</div>
        </div>
    </div>';

    echo '<div class="card">
        <h2>最近对话（最新5条）</h2>';
    $res = $logDB->query('SELECT * FROM conversations ORDER BY timestamp DESC LIMIT 5');
    echo '<div class="conversation">';
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $metadata = json_decode($row['metadata'], true);
        echo '<div class="message ' . $row['sender'] . '">
            <div class="message-meta">' . $row['sender'] . ' • ' . $row['timestamp'] . ' • ' . $row['session_id'] . '</div>
            <div>' . htmlspecialchars($row['message']) . '</div>
        </div>';
    }
    echo '</div></div>';

    echo '<div class="card">
        <h2>最新订单（最新5条）</h2>
        <table>
            <thead><tr><th>订单号</th><th>客户</th><th>产品</th><th>金额</th><th>状态</th><th>创建时间</th></tr></thead>
            <tbody>';
    $orderRes = $logDB->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 5');
    while ($order = $orderRes->fetchArray(SQLITE3_ASSOC)) {
        echo '<tr>
            <td>' . htmlspecialchars($order['order_number']) . '</td>
            <td>' . htmlspecialchars($order['customer_name']) . '</td>
            <td>' . htmlspecialchars($order['product_name']) . '</td>
            <td>￥' . number_format($order['price'], 2) . '</td>
            <td><span class="status ' . $order['status'] . '">' . $order['status'] . '</span></td>
            <td>' . htmlspecialchars($order['created_at']) . '</td>
        </tr>';
    }
    echo '</tbody></table></div>';

} elseif ($currentPage === 'conversations') {
    echo '<header><h1><i class="fas fa-comments"></i> 对话记录</h1></header>';
    echo '<div class="content">';
    
    // 获取所有会话
    $sessions = $logDB->query('SELECT DISTINCT session_id, MAX(timestamp) as last_activity FROM conversations GROUP BY session_id ORDER BY last_activity DESC');
    
    echo '<div class="card">
        <h2>会话列表 (' . $logDB->querySingle('SELECT COUNT(DISTINCT session_id) FROM conversations') . ' 个会话)</h2>';
    
    $sessionNum = 1;
    while ($session = $sessions->fetchArray(SQLITE3_ASSOC)) {
        $convRes = $logDB->query('SELECT * FROM conversations WHERE session_id = ? ORDER BY timestamp', [$session['session_id']]);
        $messageCount = $logDB->querySingle('SELECT COUNT(*) FROM conversations WHERE session_id = ?', [$session['session_id']]);
        
        echo '<div style="border:1px solid #e2e8f0;border-radius:8px;margin-bottom:15px;padding:15px;">
            <h4>会话 #' . $sessionNum . ' • ' . $session['session_id'] . ' • ' . $messageCount . ' 条消息 • 最后活动: ' . $session['last_activity'] . '</h4>
            <div class="conversation">';
        
        while ($msg = $convRes->fetchArray(SQLITE3_ASSOC)) {
            echo '<div class="message ' . $msg['sender'] . '">
                <div class="message-meta">' . $msg['sender'] . ' • ' . $msg['timestamp'] . '</div>
                <div>' . htmlspecialchars($msg['message']) . '</div>
            </div>';
        }
        
        echo '</div></div>';
        $sessionNum++;
    }
    echo '</div>';

} elseif ($currentPage === 'orders') {
    echo '<header><h1><i class="fas fa-shopping-cart"></i> 订单管理</h1></header>';
    echo '<div class="content">';
    
    echo '<div class="card">
        <h2>所有订单 (' . $logDB->querySingle('SELECT COUNT(*) FROM orders') . ' 个订单)</h2>
        <table>
            <thead><tr>
                <th>订单号</th><th>客户信息</th><th>产品</th><th>数量</th><th>金额</th><th>状态</th><th>创建时间</th><th>备注</th>
            </tr></thead><tbody>';
    
    $orderRes = $logDB->query('SELECT * FROM orders ORDER BY created_at DESC');
    while ($order = $orderRes->fetchArray(SQLITE3_ASSOC)) {
        echo '<tr>
            <td><strong>' . htmlspecialchars($order['order_number']) . '</strong></td>
            <td>' . htmlspecialchars($order['customer_name']) . '<br><small>' . htmlspecialchars($order['customer_phone']) . '</small></td>
            <td>' . htmlspecialchars($order['product_name']) . '</td>
            <td>' . $order['quantity'] . '</td>
            <td><strong>￥' . number_format($order['price'] * $order['quantity'], 2) . '</strong></td>
            <td><span class="status ' . $order['status'] . '">' . $order['status'] . '</span></td>
            <td>' . htmlspecialchars($order['created_at']) . '</td>
            <td>' . htmlspecialchars($order['notes']) . '</td>
        </tr>';
    }
    echo '</tbody></table></div>';

} elseif ($currentPage === 'logs') {
    echo '<header><h1><i class="fas fa-list"></i> 系统日志</h1></header>';
    echo '<div class="content">';
    
    echo '<div class="card">
        <h2>系统日志（最近50条）</h2>
        <table>
            <thead><tr><th>ID</th><th>时间</th><th>级别</th><th>信息</th><th>上下文</th></tr></thead>
            <tbody>';
    
    $res = $logDB->query('SELECT * FROM logs ORDER BY id DESC LIMIT 50');
    $count = 0;
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $count++;
        echo '<tr>
            <td>' . $row['id'] . '</td>
            <td>' . htmlspecialchars($row['timestamp']) . '</td>
            <td><span class="badge ' . strtolower($row['level']) . '">' . $row['level'] . '</span></td>
            <td>' . htmlspecialchars($row['message']) . '</td>
            <td><small>' . htmlspecialchars($row['context']) . '</small></td>
        </tr>';
    }
    if ($count === 0) {
        echo '<tr><td colspan="5">暂无日志数据</td></tr>';
    }
    echo '</tbody></table></div>';
}

echo '</div>';
?>

</body>
</html>
