<?php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => '测试成功',
    'data' => [
        ['time' => '12:00:00', 'content' => '测试消息1'],
        ['time' => '12:01:00', 'content' => '测试消息2']
    ],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
EOF
echo "✅ 创建了测试端点"
