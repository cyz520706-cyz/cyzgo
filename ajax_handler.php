# 创建专用的ajax_handler.php
cat > ajax_handler.php << 'EOF'
<?php
// 只处理AJAX请求
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'ok', 'message' => 'Hello from API']);
?>
EOF

echo "✅ ajax_handler.php 已创建"
echo "测试: https://cyzgo.onrender.com/ajax_handler.php"
