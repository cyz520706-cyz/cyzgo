<?php
$log_file = 'telegram_webhook.log';
?>
<!DOCTYPE html>
<html>
<head><title>日志查看</title></head>
<body style="font-family:monospace; background:#000; color:#0f0;">
<h1 style="color:#0ff;">📜 机器人日志</h1>
<pre style="background:#111; padding:10px; border:1px solid #333;">
<?php
if (file_exists($log_file)) {
    readfile($log_file);
} else {
    echo '日志文件不存在';
}
?>
</pre>
<p><a href="javascript:location.reload()" style="color:#0ff;">刷新</a> | 
<a href="clear_logs.php" style="color:#f00;" onclick="return confirm('清空日志？')">清空</a></p>
</body>
</html>
EOF

echo "✅ 最简日志查看器: simple_log_viewer.php"
echo "立即访问: https://cyzgo.onrender.com/simple_log_viewer.php"
