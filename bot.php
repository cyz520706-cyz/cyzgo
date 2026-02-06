<?php
// ==============================
// Telegram Bot Webhook 修复版
// 解决405 Method Not Allowed错误
// ==============================

// 1. 允许所有请求方法（解决405错误）
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: *');
    header('Content-Length: 0');
    header('Content-Type: text/plain');
    exit(200);
}

// 2. 设置响应头
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400');

// 3. 验证Token（从环境变量或配置文件读取）
$BOT_TOKEN = '8345582227:AAFFozVMJsNEHPOcXddO0id1L4c_KKxxJsI'; // 替换为你的真实Token
if (empty($BOT_TOKEN)) {
    http_response_code(500);
    echo json_encode(['error' => 'BOT_TOKEN not configured']);
    exit;
}

// 4. 记录日志（用于调试）
$log_data = [
    'time' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'query' => $_SERVER['QUERY_STRING'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
];

// 5. 处理GET请求（直接访问测试）
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && empty(file_get_contents('php://input'))) {
    echo json_encode([
        'status' => 'Telegram Bot is running',
        'platform' => 'chinashop.de5.net',
        'token_exists' => !empty($BOT_TOKEN),
        'webhook_info' => true,
        'log' => $log_data,
        'bot_test' => 'Send /start to test',
        'webhook_url' => 'https://api.telegram.org/bot' . substr($BOT_TOKEN, 0, 10) . '***/getWebhookInfo',
    ], JSON_PRETTY_PRINT);
    exit;
}

// 6. 处理Telegram Webhook POST请求
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (empty($input) || empty($update)) {
    // 不是Telegram的有效请求
    http_response_code(200);
    echo 'OK';
    exit;
}

// 7. 记录Telegram消息
$log_data['update_id'] = $update['update_id'] ?? 'none';
$log_data['message'] = $update['message']['text'] ?? 'no text';
$log_data['chat_id'] = $update['message']['chat']['id'] ?? 'none';

// 保存日志
file_put_contents('telegram_webhook.log', json_encode($log_data, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);

// 8. 处理机器人逻辑
if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $text = $update['message']['text'] ?? '';
    $first_name = $update['message']['chat']['first_name'] ?? '朋友';
    
    // 响应消息
    $response_text = match (true) {
        str_starts_with($text, '/start') => "🎉 欢迎 $first_name！\n\n" .
                                          "我是中蒙代购机器人\n\n" .
                                          "📦 请发送商品链接或图片询价\n" .
                                          "💬 客服在线时间：9:00-22:00",
        
        str_starts_with($text, '/help') => "🆘 帮助信息\n\n" .
                                          "1. 直接发送链接\n" .
                                          "2. 描述商品信息\n" .
                                          "3. 联系我们：@客服用户名",
        
        str_starts_with($text, '/ping') => "🏓 Pong!\n" .
                                          "服务器正常\n" .
                                          "时间：" . date('Y-m-d H:i:s'),
        
        empty($text) => "请发送文字、链接或图片",
        
        default => "📦 收到询价：\n\n" . 
                   htmlspecialchars(substr($text, 0, 200)) . "\n\n" .
                   "✅ 已收到，客服稍后回复您"
    };
    
    // 9. 发送消息回Telegram
    $api_url = "https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage";
    $post_data = [
        'chat_id' => $chat_id,
        'text' => $response_text,
        'parse_mode' => 'HTML',
    ];
    
    // 使用cURL发送
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post_data),
        CURLOPT_TIME
