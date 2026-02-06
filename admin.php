<?php
echo '<!DOCTYPE html>';
echo '<html>';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<title>管理面板</title>';
echo '<style>';
echo 'body { font-family: Arial; padding: 20px; background: #f0f2f5; }';
echo '.container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }';
echo '.log { background: #f9f9f9; padding: 12px; margin: 8px 0; border-left: 4px solid #007bff; }';
echo '.log-time { color: #666; font-size: 12px; }';
echo '.btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }';
echo '.btn:hover { background: #0056b3; }';
echo '.btn-danger { background: #dc3545; }';
echo '</style>';
echo '</head>';
echo '<body>';
echo '<div class="container">';
echo '<h1>🤖 中蒙代购机器人 - 管理面板</h1>';
// 检查日志文件
$log_file = 'telegram_webhook.log';
echo '<p><strong>日志状态:</strong> ';
if (file_exists($log_file)) {
    $size = filesize($log_file);
    $lines = count(file($log_file, FILE_SKIP_EMPTY_LINES));
    echo "存在 | 大小: " . round($size/1024, 2) . " KB | 行数: $lines";
} else {
    echo '不存在';
}
echo '</p>';
echo '<div>';
echo '<button class="btn" onclick="loadLogs()">🔄 刷新日志</button>';
echo '<button class="btn btn-danger" onclick="clearLogs()">🗑️ 清空日志</button>';
echo '<button class="btn" onclick="testAPI()">🔧 测试API</button>';
echo '</div>';
echo '<h3>📝 对话日志</h3>';
echo '<div id="logs">正在加载...</div>';
echo '</div>'; // container结束
echo '<script>';
echo 'async function loadLogs() {';
echo '  try {';
echo '    const response = await fetch("admin_logs.php");';
echo '    const data = await response.json();';
echo '    if (data.success) {';
echo '      let html = "";';
echo '      data.logs.forEach(log => {';
echo '        html += `<div class="log"><span class="log-time">[\${log.time}]</span> \${log.message}</div>`;';
echo '      });';
echo '      if (data.logs.length === 0) {';
echo '        html = "<p>暂无日志记录</p>";';
echo '      }';
echo '      document.getElementById("logs").innerHTML = html;';
echo '    }';
echo '  } catch (error) {';
echo '    document.getElementById("logs").innerHTML = "<p>错误: " + error.message + "</p>";';
echo '  }';
echo '}';
echo 'async function clearLogs() {';
echo '  if (confirm("确定要清空所有日志吗？")) {';
echo '    const response = await fetch("admin_logs.php?action=clear");';
echo '    const data = await response.json();';
echo '    alert(data.message || "已清空");';
echo '    loadLogs();';
echo '  }';
echo '}';
echo 'async function testAPI() {';
echo '  const response = await fetch("api.php");';
echo '  const data = await response.json();';
echo '  alert("API状态: " + data.status + "\\n时间: " + data.time);';
echo '}';
echo '// 页面加载时自动加载日志';
echo 'window.onload = loadLogs;';
echo '// 每30秒自动刷新';
echo 'setInterval(loadLogs, 30000);';
echo '</script>';
echo '</body>';
echo '</html>';
?>
EOF
