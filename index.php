<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>🐫 中蒙代购Telegram机器人</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { 
            font-family: 'Arial', sans-serif; 
            max-width: 1000px; 
            margin: 0 auto; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
        }
        .container {
            background: rgba(255,255,255,0.95);
            padding: 30px;
            border-radius: 15px;
            color: #333;
            margin-top: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .header {
            text-align: center;
            padding: 20px;
        }
        .logo {
            font-size: 3em;
            margin-bottom: 10px;
        }
        .status-card {
            background: #f8f9fa;
            border-left: 5px solid #28a745;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .status-error {
            border-left: 5px solid #dc3545;
        }
        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Monaco', 'Courier New', monospace;
            margin: 15px 0;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            margin: 10px 5px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🐫</div>
        <h1>中蒙代购 Telegram 机器人</h1>
        <p>Webhook 服务状态</p>
    </div>
    
    <div class="container">
        <h2>📊 服务器信息</h2>
        
        <div class="status-card">
            <h3>✅ 基础状态</h3>
            <ul>
                <li>🌐 服务器: <strong><?php echo $_SERVER['HTTP_HOST']; ?></strong></li>
                <li>⏰ 时间: <strong><?php echo date('Y-m-d H:i:s'); ?></strong></li>
                <li>🔧 PHP版本: <strong><?php echo phpversion(); ?></strong></li>
                <li>📁 根目录: <strong><?php echo __DIR__; ?></strong></li>
            </ul>
        </div>
        
        <div class="status-card">
            <h3>🤖 Telegram 配置</h3>
            <?php
            $bot_token = getenv('BOT_TOKEN');
            if ($bot_token) {
                echo '<p class="success">✅ Bot Token 已设置 ('.substr($bot_token, 0, 10).'...)</p>';
            } else {
                echo '<p class="error">❌ Bot Token 未设置 - 请在Render.com设置环境变量</p>';
            }
            ?>
            <p>📍 Webhook URL: <code>https://<?php echo $_SERVER['HTTP_HOST']; ?>/webhook.php</code></p>
        </div>
        
        <h2>🔧 测试命令</h2>
        
        <div class="code-block">
# 测试 Webhook (POST 请求)
curl -X POST "https://<?php echo $_SERVER['HTTP_HOST']; ?>/webhook.php" \
  -H "Content-Type: application/json" \
  -d '{"message":{"chat":{"id":123456},"text":"/start"}}'
        </div>
        
        <div class="code-block">
# 设置 Webhook
https://api.telegram.org/bot8345582227:AAFFozVMJsNEHPOcXddO0id1L4c_KKxxJsI/setWebhook?url=https://<?php echo $_SERVER['HTTP_HOST']; ?>/webhook.php
        </div>
        
        <h2>📁 目录文件</h2>
        <div class="code-block">
<?php
$files = scandir('.');
echo "当前目录下的文件：\n";
foreach($files as $file) {
    if($file !== '.' && $file !== '..') {
        echo "- " . $file . "\n";
    }
}
?>
        </div>
        
        <h2>🔗 快速链接</h2>
        <p>
            <a href="/webhook.php" class="btn" target="_blank">测试 Webhook</a>
            <a href="https://api.telegram.org/bot<?php echo $bot_token; ?>/getWebhookInfo" class="btn" target="_blank">查看Webhook状态</a>
        </p>
    </div>
    
    <div style="text-align: center; margin-top: 30px; color: white; opacity: 0.7;">
        <p>中蒙代购项目 © 2024 | Render.com 部署 | cyzgo.com</p>
        <p>状态: <span class="success">●</span> 运行中</p>
    </div>
</body>
</html>
