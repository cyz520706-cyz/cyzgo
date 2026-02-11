<?php
// ==============================
// Telegram Bot Webhook 专业版
// 修复：第113行未闭合的"["
// ==============================

// 1. 允许所有请求方法
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: *');
    header('Content-Length: 0');
    header('Content-Type: text/plain');
    exit(200);
}

// 2. 显示错误（便于调试）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 3. 设置响应头
header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 4. 你的Bot Token（请修改这里！）
$BOT_TOKEN = '';
// 例如：$BOT_TOKEN = '1234567890:ABCdefGHijklmnopQRSTUVwxyz';

if (empty($BOT_TOKEN)) {
    http_response_code(500);
    echo 'BOT_TOKEN not configured';
    exit;
}

// 5. 记录每次访问
$log_entry = date('Y-m-d H:i:s') . " | " . 
             ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . " | " . 
             ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";

file_put_contents('access.log', $log_entry, FILE_APPEND);

// 6. 处理GET请求（直接测试访问）
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && empty(file_get_contents('php://input'))) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'Telegram Bot is running',
        'bot' => substr($BOT_TOKEN, 0, 10) . '***',
        'last_error' => 'none',
        'webhook_info' => 'https://api.telegram.org/bot' . substr($BOT_TOKEN, 0, 10) . '***/getWebhookInfo',
        'set_webhook_url' => 'https://api.telegram.org/bot' . $BOT_TOKEN . '/setWebhook?url=https://chinashop.de5.net/bot.php',
        'test_bot' => 'Send /start to ' . substr($BOT_TOKEN, 0, 10) . '***'
    ], JSON_PRETTY_PRINT);
    exit;
}

// 7. 处理Telegram Webhook请求
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (empty($input)) {
    // 空请求，直接返回OK（重要！）
    http_response_code(200);
    echo 'OK';
    exit;
}

// 8. 记录Telegram消息
file_put_contents('telegram.log', $input . "\n\n", FILE_APPEND);

// 9. 处理消息（这是你之前出错的地方 - 第113行）
if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $text = $update['message']['text'] ?? '';
    $first_name = $update['message']['chat']['first_name'] ?? '朋友';
    
    // 修复：使用条件判断代替match（兼容性更好）
    if (str_starts_with($text, '/start')) {
        $response_text = "🎉 欢迎 $first_name！\n\n" .
                        "我是中蒙代购机器人\n\n" .
                        "📦 请发送商品链接或图片询价\n" .
                        "💬 客服在线时间：9:00-22:00\n\n" .
                        "试试命令：\n" .
                        "/help - 帮助信息\n" .
                        "/ping - 测试机器人";
    } elseif (str_starts_with($text, '/help')) {
        $response_text = "🆘 帮助信息\n\n" .
                        "1. 直接发送淘宝/京东链接\n" .
                        "2. 描述商品信息（尺寸/颜色）\n" .
                        "3. 发送图片参考\n\n" .
                        "📞 联系我们：@客服账号\n" .
                        "⏰ 工作时间：每天9:00-22:00";
    } elseif (str_starts_with($text, '/ping')) {
        $response_text = "🏓 Pong！\n" .
                        "服务器正常\n" .
                        "北京时间：" . date('Y-m-d H:i:s');
    } elseif (empty($text)) {
        $response_text = "🤖 请发送文字消息、商品链接或图片";
    } else {
        $response_text = "📦 收到询价：\n\n" . 
                        htmlspecialchars(substr($text, 0, 200)) . "\n\n" .
                        "✅ 已收到，客服稍后回复您\n" .
                        "⏰ 预计回复时间：24小时内";
    }
    
    // 10. 发送回复给Telegram
    $api_url = "https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage";
    $post_data = [
        'chat_id' => $chat_id,
        'text' => $response_text,
        'parse_mode' => 'HTML'
    ];
    
    // 使用curl发送
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post_data),
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // 记录发送结果
    $send_log = date('Y-m-d H:i:s') . " | Chat: $chat_id | HTTP: $http_code\n";
    file_put_contents('send.log', $send_log, FILE_APPEND);
    
    // 记录详细结果（调试用）
    if ($result) {
        file_put_contents('telegram_response.log', $result . "\n\n", FILE_APPEND);
    }
}

// 11. 必须返回OK给Telegram
http_response_code(200);
echo 'OK';
?>
