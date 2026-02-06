<?php
// bot.php - Telegram 网页机器人（简单版）
// 把这个文件上传到你的网站：chinashop.de5.net/bot.php

$BOT_TOKEN = '8345582227:AAFFozVMJsNEHPOcXddO0id1L4c_KKxxJsI'; // 替换为你的 Token
$admin_id = 6530121748; // 你的 Telegram 用户 ID，从 @userinfobot 获取

// 获取 Telegram 消息
$update = json_decode(file_get_contents('php://input'), true);

if (!$update) {
    // 如果是 GET 请求，显示说明
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo "这是 Telegram 机器人接口。请通过 Telegram 访问 @chinashop888_bot";
        exit;
    }
    exit;
}

// 处理消息
$chat_id = $update['message']['chat']['id'] ?? null;
$text = $update['message']['text'] ?? '';
$username = $update['message']['chat']['username'] ?? '用户';

if ($text) {
    if ($text === '/start' || $text === '/start@chinashop888_bot') {
        sendMessage($chat_id, "欢迎使用中蒙代购专线！

🤖 *TG 机器人下单系统*

请选择操作：
1️⃣ 发送商品链接或描述下单
2️⃣ 发送 /order 开始新订单
3️⃣ 发送 /help 查看帮助

蒙古文: Монголд богино хугацаанд Хятадын бүтээгдэхүүн хүргэж өгнө

客服联系: @chinashop_support");
    } elseif ($text === '/order') {
        sendMessage($chat_id, "请发送您想购买的商品：
1. 拼多多/淘宝链接
2. 或直接描述商品");
        
        // 记录用户状态
        file_put_contents("user_{$chat_id}.json", json_encode(['state' => 'awaiting_product']));
    } elseif (strpos($text, 'https://') !== false || strpos($text, 'http://') !== false) {
        // 用户发送了链接
        sendMessage($chat_id, "✅ 已收到商品链接！

请补充信息：
1. 数量：
2. 颜色/规格：
3. 您的收货地址（蒙古）：

回复格式示例：
数量：1个
颜色：黑色
地址：乌兰巴托，巴彦高勒区");

        // 更新状态并保存链接
        file_put_contents("user_{$chat_id}.json", json_encode([
            'state' => 'awaiting_details',
            'product_link' => $text
        ]));
        
        // 通知管理员
        sendMessage($admin_id, "🆕 新订单意向
用户: @{$username}
商品链接: {$text}");
    } else {
        // 检查用户状态
        $user_file = "user_{$chat_id}.json";
        if (file_exists($user_file)) {
            $user_data = json_decode(file_get_contents($user_file), true);
            
            if ($user_data['state'] === 'awaiting_details') {
                // 用户补充了订单详情
                sendMessage($chat_id, "📦 订单已创建！
我们将在5分钟内核实商品价格和运费。

订单状态将在这里更新，请保持关注。
有任何问题请联系 @chinashop_support");
                
                // 通知管理员完整订单
                $product_link = $user_data['product_link'] ?? '无链接';
                sendMessage($admin_id, "📋 *新订单详情*
用户: @{$username}
商品: {$product_link}
补充信息: {$text}
订单ID: ORD" . time());
                
                // 删除用户状态文件
                unlink($user_file);
            }
        } else {
            // 默认回复
            sendMessage($chat_id, "您好！我是中蒙代购机器人。
请发送：
1. 商品链接（淘宝/拼多多）
2. 或商品描述
3. 或使用 /order 开始下单");
        }
    }
}

// 发送消息函数
function sendMessage($chat_id, $text) {
    global $BOT_TOKEN;
    $url = "https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    file_get_contents($url, false, $context);
}
