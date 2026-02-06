<?php
// Telegram Bot Webhook for cyzgo
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 如果是GET请求，显示信息
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "🤖 中蒙代购 Telegram Bot Webhook\n";
    echo "================================\n";
    echo "✅ 状态: 运行正常\n";
    echo "⏰ 时间: " . date('Y-m-d H:i:s') . "\n";
    echo "🌐 服务器: " . $_SERVER['HTTP_HOST'] . "\n";
    echo "🔧 PHP版本: " . phpversion() . "\n\n";
    echo "📞 Telegram将发送POST请求到此URL\n";
    exit;
}

// 处理POST请求（来自Telegram）
$input = file_get_contents('php://input');
$update = json_decode($input, true);

// 记录接收到的数据
file_put_contents(__DIR__ . '/telegram_webhook.log', 
    date('Y-m-d H:i:s') . "\n" . 
    json_encode($update, JSON_PRETTY_PRINT) . "\n\n", 
    FILE_APPEND
);

// 如果有消息，处理它
if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $text = $update['message']['text'] ?? '';
    
    $response = match(true) {
        str_contains($text, '/start') => "🚀 中蒙代购机器人已启动！\n欢迎使用我们的代购服务。",
        str_contains($text, '/help') => "📚 帮助指南\n\n发送商品链接或描述进行询价",
        !empty($text) => "✅ 已收到消息：\n\"" . substr($text, 0, 100) . "\"\n\n我们的客服会尽快回复。",
        default => "请输入文字消息或使用命令。"
    };
    
    // 如果设置了BOT_TOKEN，发送回复
    $bot_token = getenv('BOT_TOKEN');
    if ($bot_token) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.telegram.org/bot{$bot_token}/sendMessage",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'chat_id' => $chat_id,
                'text' => $response
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}

// 告诉Telegram“收到消息”
http_response_code(200);
echo 'OK';
?>
