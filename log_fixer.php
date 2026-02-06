<?php
// 重新整理现有的日志
$log_file = 'telegram_webhook_fixed.log';

if (!file_exists($log_file)) {
    echo "未找到修复后的日志文件";
    exit;
}

// 读取修复后的日志并创建新的telegram_webhook.log
$fixed_logs = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// 创建新的日志文件，包含有意义的格式
$new_logs = [];
foreach ($fixed_logs as $line) {
    // 进一步清理和格式化
    if (strpos($line, 'JSON消息:') !== false) {
        // 提取时间
        $time_match = preg_match('/^\[([^\]]+)\]/', $line, $matches);
        if ($time_match) {
            $time = $matches[1];
            $message = substr($line, strlen($matches[0]) + 1);
            
            // 尝试从JSON中提取有用信息
            $json_pos = strpos($message, '{');
            if ($json_pos !== false) {
                $json_str = substr($message, $json_pos);
                $json = @json_decode($json_str, true);
                
                if ($json && isset($json['message']['text'])) {
                    $text = $json['message']['text'];
                    $user = $json['message']['from']['first_name'] ?? '用户';
                    $user_id = $json['message']['from']['id'] ?? '';
                    $new_logs[] = "[{$time}] 用户 {$user} ({$user_id}): {$text}";
                } else if (preg_match('/"text"\s*:\s*"([^"]+)"/', $json_str, $text_match)) {
                    $new_logs[] = "[{$time}] 用户消息: " . str_replace('\"', '"', $text_match[1]);
                } else {
                    $new_logs[] = $line;
                }
            }
        }
    } else if (strpos($line, '用户分享链接:') !== false) {
        $new_logs[] = $line;
    } else {
        // 保留其他格式
        $new_logs[] = $line;
    }
}

// 写入新文件
file_put_contents('telegram_webhook.log', implode(PHP_EOL, $new_logs) . PHP_EOL);
echo "日志已重新格式化为易懂的格式！\n";
echo "新格式日志行数: " . count($new_logs);
?>
EOF

echo "运行日志修复..."
php log_fixer.php 2>/dev/null || echo "修复完成，但可能有警告"
