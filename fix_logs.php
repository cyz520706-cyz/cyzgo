<?php
// 修复日志格式
$input_file = 'telegram_webhook.log';
$output_file = 'telegram_webhook_fixed.log';
$backup_file = 'telegram_webhook.bak';

if (file_exists($input_file)) {
    // 备份原文件
    copy($input_file, $backup_file);
    
    // 读取原文件
    $lines = file($input_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    $fixed_logs = [];
    $current_json = '';
    $in_json = false;
    $json_depth = 0;
    $current_time = '';
    
    foreach ($lines as $line) {
        // 检查是否是时间戳行
        if (preg_match('/^(\[[^\]]+\])\s*(.+)$/', $line, $matches)) {
            $time = $matches[1];
            $content = $matches[2];
            
            // 如果是开始JSON或文本
            if ($content === '{') {
                $in_json = true;
                $current_time = $time;
                $current_json = '{';
                $json_depth = 1;
            } else if ($in_json) {
                // 继续JSON结构
                $current_json .= "\n" . $content;
                
                // 统计花括号深度
                $json_depth += substr_count($content, '{');
                $json_depth -= substr_count($content, '}');
                
                // JSON结束
                if ($json_depth === 0) {
                    // 保存完整的JSON
                    $fixed_logs[] = $current_time . ' JSON消息: ' . $current_json;
                    $in_json = false;
                }
            } else if (strpos($content, 'http') !== false) {
                // URL消息
                $fixed_logs[] = $time . ' 用户分享链接: ' . str_replace('&quot;', '"', $content);
            } else {
                // 普通文本
                $fixed_logs[] = $line;
            }
        } else {
            // 无时间戳的行
            $fixed_logs[] = $line;
        }
    }
    
    // 写入修复后的日志
    file_put_contents($output_file, implode(PHP_EOL, $fixed_logs) . PHP_EOL);
    
    // 创建新的webhook.php（修复日志记录方式）
    $webhook_content = '<?php
$log_file = "telegram_webhook.log";

// 更好的日志记录函数
function log_message($message) {
    global $log_file;
    if (is_array($message)) {
        $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    file_put_contents($log_file, date("[Y-m-d H:i:s]") . " " . $message . PHP_EOL, FILE_APPEND);
}

// 处理Telegram webhook
$input = file_get_contents("php://input");

if (!empty($input)) {
    $data = json_decode($input, true);
    
    if ($data) {
        $update_id = $data["update_id"] ?? 0;
        
        // 处理不同类型的更新
        if (isset($data["message"])) {
            $message = $data["message"];
            $text = $message["text"] ?? (isset($message["caption"]) ? "图片/视频: " . $message["caption"] : "无文本");
            $user_id = $message["from"]["id"] ?? "unknown";
            $username = $message["from"]["username"] ?? $message["from"]["first_name"] ?? "用户";
            
            // 记录用户消息
            log_message("用户 @{$username} ({$user_id}): {$text}");
            
            // 如果是URL消息，提取链接
            if (isset($message["entities"])) {
                foreach ($message["entities"] as $entity) {
                    if ($entity["type"] === "url") {
                        $offset = $entity["offset"];
                        $length = $entity["length"];
                        $url = substr($text, $offset, $length);
                        log_message("检测到链接: {$url}");
                    }
                }
            }
            
            // 回复消息
            header("Content-Type: application/json");
            echo json_encode([
                "method" => "sendMessage",
                "chat_id" => $message["chat"]["id"],
                "text" => "收到您的消息: " . substr($text, 0, 50) . (strlen($text) > 50 ? "..." : ""),
                "reply_to_message_id" => $message["message_id"]
            ]);
        } elseif (isset($data["callback_query"])) {
            // 按钮回调
            $callback = $data["callback_query"];
            $data = $callback["data"] ?? "";
            $user = $callback["from"]["username"] ?? $callback["from"]["first_name"] ?? "用户";
            log_message("用户 @{$user} 点击按钮: {$data}");
            
            header("Content-Type: application/json");
            echo json_encode([
                "method" => "answerCallbackQuery",
                "callback_query_id" => $callback["id"],
                "text" => "收到"
            ]);
        }
    }
} else {
    // GET请求显示状态
    echo "<!DOCTYPE html><html><head><title>Telegram Webhook</title></head>";
    echo "<body style=\"font-family: Arial; padding: 30px;\">";
    echo "<h1>🤖 Telegram Webhook 运行中</h1>";
    echo "<p>Webhook URL: " . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "</p>";
    echo "<p>服务器时间: " . date("Y-m-d H:i:s") . "</p>";
    echo "<a href=\"simple_admin.php\">查看日志</a> | ";
    echo "<a href=\"/\">首页</a>";
    echo "</body></html>";
}
?>';
    
    file_put_contents('webhook.php', $webhook_content);
    
    echo "日志已修复！";
    echo "\n原日志行数: " . count($lines);
    echo "\n修复后行数: " . count($fixed_logs);
    echo "\n新webhook.php已创建";
} else {
    echo "日志文件不存在";
}
?>
EOF

# 运行修复
echo "正在修复日志格式..."
php fix_logs.php
echo ""
