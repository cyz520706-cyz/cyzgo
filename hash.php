<?php
echo "用户名: admin\n";
echo "密码哈希: " . password_hash('admin123', PASSWORD_DEFAULT) . "\n";
echo "请把上面的哈希复制到 admin.php 的 \$valid_users 数组中\n";
