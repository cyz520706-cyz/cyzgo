<?php
// bot.php - 修复版本
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 允许所有请求方法
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

// 记录日志（调试用）
file_put_contents('bot-log.txt', date('Y-m-d H:i:s') . " 收到请求\n" . print_r($_SERVER, true), FILE_APPEND);

// 获取 Telegram 的 POST 数据
$input = file_get_contents("php://input");
file_put_contents('telegram-post.txt', $input, FILE_APPEND);

if ($input) {
    // 解析 Telegram 更新
    $update = json_decode($input, true);
    
    if ($update) {
        // 处理消息
        handleUpdate($update);
    }
}

// 如果是直接访问，返回OK
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "🤖 Telegram Bot 运行中！";
    if ($input) {
        echo "<br>收到数据长度：" . strlen($input);
    }
}

/**
 * 处理 Telegram 更新
 */
function handleUpdate($update) {
    $BOT_TOKEN = '8345582227:AAFFozVMJsNEHPOcXddO0id1L4c_KKxxJsI'; // 替换为你的Token！
    
    // 记录日志
    file_put_contents('telegram-update.txt', json_encode($update, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), FILE_APPEND);
    
    // 提取消息信息
    $message = $update['message'] ?? $update['callback_query']['message'] ?? null;
    if (!$message) {
        return;
    }
    
    $chat_id = $message['chat']['id'];
    $text = $update['message']['text'] ?? $update['callback_query']['data'] ?? '';
    $first_name = $message['chat']['first_name'] ?? '客户';
    $username = $message['chat']['username'] ?? '用户';
    
    // 回复消息
    $reply = "";
    if ($text === '/start') {
        $reply = "🎉 欢迎 {$first_name}！\n\n我是中蒙代购机器人！\n请发送商品链接或描述开始下单。";
    } else {
        $reply = "📦 已收到您的查询：\n" . mb_substr($text, 0, 100) . "...\n\n我们的客服将尽快回复！";
    }
    
    // 发送到 Telegram
    $response = sendTelegramMessage($BOT_TOKEN, $chat_id, $reply);
    file_put_contents('telegram-response.txt', $response, FILE_APPEND);
}

/**
 * 发送 Telegram 消息
 */
function sendTelegramMessage($token, $chat_id, $text) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    return $result ? $result : "发送失败";
}
?>
