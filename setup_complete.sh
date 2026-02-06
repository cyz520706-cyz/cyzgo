<?php
echo '<!DOCTYPE html><html><head><title>中蒙代购机器人</title>';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<style>';
echo 'body{font-family:Arial;margin:0;padding:20px;background:#f0f2f5;}';
echo '.header{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:40px 20px;text-align:center;border-radius:0 0 20px 20px;}';
echo '.container{max-width:800px;margin:20px auto;}';
echo '.card{background:white;padding:25px;border-radius:15px;margin:15px 0;box-shadow:0 5px 20px rgba(0,0,0,0.1);}';
echo '.card h3{margin-top:0;color:#333;}';
echo '.btn{display:inline-block;background:#667eea;color:white;padding:12px 25px;text-decoration:none;border-radius:50px;margin:5px;transition:0.3s;}';
echo '.btn:hover{background:#5a67d8;transform:translateY(-2px);}';
echo '</style>';
echo '</head><body>';
echo '<div class="header">';
echo '<h1 style="font-size:2.5em;">🤖 中蒙代购机器人</h1>';
echo '<p>专业跨境代购服务管理系统</p>';
echo '</div>';
echo '<div class="container">';
echo '<div class="card">';
echo '<h3>📱 控制面板</h3>';
echo '<p><a href="admin.php" class="btn">管理面板</a>';
echo '<a href="webhook.php" class="btn">Webhook状态</a>';
echo '<a href="get_logs.php" class="btn">API接口</a></p>';
echo '</div>';
echo '<div class="card">';
echo '<h3>📊 系统状态</h3>';
echo '<p>服务器时间: ' . date('Y-m-d H:i:s') . '</p>';
$log_file = 'telegram_webhook.log';
if(file_exists($log_file)){
    $size = filesize($log_file);
    $lines = @count(file($log_file, FILE_SKIP_EMPTY_LINES));
    echo "<p>日志文件: " . round($size/1024,2) . " KB ($lines 条记录)</p>";
}
echo '</div>';
echo '</div></body></html>';
?>
INDEX

echo "✅ 系统安装完成！"
echo ""
echo "🔗 请立即访问以下链接："
echo ""
echo "1. 📱 主管理页面: https://cyzgo.onrender.com/admin.php"
echo "2. 📊 简单日志查看: https://cyzgo.onrender.com/get_logs.php"
echo "3. 🌐 Webhook状态: https://cyzgo.onrender.com/webhook.php"
echo ""
echo "🎯 你的机器人正在接收消息！"
echo "📧 用户发送的链接: https://mobile.yangkeduo.com/goods2.html?ps=7rnuKWRR4q"
echo "💬 用户消息: '这个是怎么卖的'"
echo ""
echo "系统已经自动格式化日志，现在应该能正常查看！"
EOF

chmod +x setup_complete.sh
./setup_complete.sh
