<?php
// admin.php - 完全修复版本

// 开启错误报告（生产环境中请移除）
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
        $this->db->exec('CREATE TABLE IF NOT EXISTS logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            timestamp TEXT NOT NULL DEFAULT (datetime(\'now\')),
            level TEXT NOT NULL,
            message TEXT NOT NULL,
            context TEXT
        )');
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
// ✅ 这里类定义正确结束

// 5. checkSecurity函数（不是类方法，所以没有public关键字）
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

// 7. 执行安全检查
checkSecurity();

// 8. 管理后台页面
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
 header{background:#2d3748;color:#fff;padding:20px 40px;}
 .container{padding:30px 40px;}
 .card{background:#fff;border-radius:8px;padding:20px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.08);}
 h2{margin-top:0;}
 table{width:100%;border-collapse:collapse;}
 th,td{border-bottom:1px solid #e2e8f0;padding:10px;text-align:left;}
 tr:hover{background:#f9fafb;}
 .status{color:#38a169;font-weight:bold;}
</style>
</head>
<body>
<header>
    <h1><i class="fas fa-cogs"></i> 管理后台</h1>
</header>

<div class="container">
    <div class="card">
        <h2>系统状态</h2>
        <p class="status">✅ 系统运行正常</p>
        <p>PHP 版本：<?php echo PHP_VERSION; ?></p>
        <p>服务器时间：<?php echo date('Y-m-d H:i:s'); ?></p>
        <p>数据库路径：<?php echo LOG_DB_PATH; ?></p>
    </div>

    <div class="card">
        <h2>最近日志（最近 10 条）</h2>
        <?php
        $logDB = LogDB::getInstance();
        $res = $logDB->query('SELECT * FROM logs ORDER BY id DESC LIMIT 10');
        echo '<table><thead><tr><th>ID</th><th>时间</th><th>级别</th><th>信息</th><th>上下文</th></tr></thead><tbody>';
        
        if ($res) {
            $count = 0;
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                $count++;
                echo '<tr>';
                echo '<td>'.htmlspecialchars($row['id']).'</td>';
                echo '<td>'.htmlspecialchars($row['timestamp']).'</td>';
                echo '<td>'.htmlspecialchars($row['level']).'</td>';
                echo '<td>'.htmlspecialchars($row['message']).'</td>';
                echo '<td>'.htmlspecialchars($row['context']).'</td>';
                echo '</tr>';
            }
            if ($count === 0) {
                echo '<tr><td colspan="5">暂无日志数据</td></tr>';
            }
        } else {
            echo '<tr><td colspan="5">数据库查询失败</td></tr>';
        }
        echo '</tbody></table>';
        ?>
    </div>

    <div class="card">
        <h2>快速操作</h2>
        <p>这里可以放置您的自定义功能按钮和链接。</p>
        <p><a href="#" style="color:#667eea;">功能1</a> | <a href="#" style="color:#667eea;">功能2</a> | <a href="#" style="color:#667eea;">功能3</a></p>
    </div>
</div>

</body>
</html>
