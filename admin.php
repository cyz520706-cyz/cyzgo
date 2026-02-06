<?php
// admin.php - 修复中文乱码的专业管理面板
header('Content-Type: text/html; charset=utf-8');
ob_start();

// 强制设置编码
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 中蒙代购机器人 - 对话管理面板</title>
    
    <!-- 引入iconfont图标 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* 基础样式 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'PingFang SC', 'Microsoft YaHei', 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* 头部样式 */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* 控制栏样式 */
        .controls {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-success {
            background: #38a169;
        }
        
        .btn-success:hover {
            background: #2f855a;
        }
        
        .btn-danger {
            background: #e53e3e;
        }
        
        .btn-danger:hover {
            background: #c53030;
        }
        
        .btn-warning {
            background: #d69e2e;
        }
        
        .btn-warning:hover {
            background: #b7791f;
        }
        
        /* 内容区域样式 */
        .content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.5rem;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        /* 日志显示样式 */
        .logs-container {
            background: #1a202c;
            border-radius: 10px;
            padding: 20px;
            font-family: 'Monaco', 'Menlo', 'Consolas', 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
            max-height: 600px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        
        .log-entry {
            padding: 8px 12px;
            margin-bottom: 6px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.05);
            border-left: 3px solid transparent;
            transition: background 0.2s;
            color: #cbd5e0;
            word-wrap: break-word;
            white-space: pre-wrap;
        }
        
        .log-entry:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .log-entry.user {
            border-left-color: #4299e1;
        }
        
        .log-entry.bot {
            border-left-color: #68d391;
        }
        
        .log-time {
            color: #a0aec0;
            font-size: 12px;
            margin-right: 10px;
        }
        
        .log-user {
            color: #63b3ed;
            font-weight: bold;
        }
        
        .log-message {
            color: #e2e8f0;
        }
        
        /* 统计卡片样式 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            font-size: 14px;
            text-transform: uppercase;
            opacity: 0.9;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .stat-desc {
            font-size: 13px;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        /* 表格样式（用户列表） */
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .users-table th {
            background: #4c51bf;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .users-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .users-table tr:hover {
            background: #f7fafc;
        }
        
        /* 加载动画 */
        .loading {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
        
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top: 4px solid #667eea;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* 底部样式 */
        .footer {
            text-align: center;
            padding: 25px;
            color: #718096;
            font-size: 14px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8rem;
            }
            
            .controls {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .logs-container {
                font-size: 13px;
                max-height: 400px;
            }
            
            .stat-number {
                font-size: 2rem;
            }
        }
        
        /* 工具类 */
        .text-center { text-align: center; }
        .mb-20 { margin-bottom: 20px; }
        .mt-20 { margin-top: 20px; }
        .d-none { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <!-- 头部 -->
        <div class="header">
            <h1>
                <i class="fas fa-robot"></i>
                中蒙代购机器人 - 对话管理面板
            </h1>
            <p>实时监控用户对话、查看统计分析、管理系统状态</p>
            <div class="mt-20">
                <small>
                    <i class="fas fa-clock"></i> 服务器时间：<?php echo date('Y-m-d H:i:s'); ?> | 
                    <i class="fas fa-server"></i> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Render'; ?>
                </small>
            </div>
        </div>
        
        <!-- 控制栏 -->
        <div class="controls">
            <button class="btn" onclick="loadLogs()" id="refresh-btn">
                <i class="fas fa-sync-alt"></i> 刷新对话日志
            </button>
            <a href="https://dashboard.render.com/" class="btn btn-success" target="_blank">
                <i class="fas fa-chart-line"></i> Render控制台
            </a>
            <button class="btn btn-warning" onclick="clearLogs()" id="clear-btn">
                <i class="fas fa-trash-alt"></i> 清空日志
            </button>
            <a href="export.php?format=json" class="btn btn-success">
                <i class="fas fa-download"></i> 导出数据
            </a>
            <a href="webhook.php" class="btn">
                <i class="fas fa-home"></i> 返回首页
            </a>
        </div>
        
        <!-- 主要内容区域 -->
        <div class="content">
            <!-- 选项卡 -->
            <div class="tabs mb-20">
                <button class="btn" onclick="showTab('logs')" id="logs-tab-btn">
                    <i class="fas fa-comments"></i> 对话日志
                </button>
                <button class="btn" onclick="showTab('users')" id="users-tab-btn">
                    <i class="fas fa-users"></i> 用户统计
                </button>
                <button class="btn" onclick="showTab('system')" id="system-tab-btn">
                    <i class="fas fa-cog"></i> 系统信息
                </button>
            </div>
            
            <!-- 对话日志标签页 -->
            <div id="logs-tab">
                <div class="section-title">
                    <i class="fas fa-list-alt"></i> 最近对话记录
                    <span id="log-count" class="btn" style="margin-left: auto; padding: 5px 10px; font-size: 14px;">
                        加载中...
                    </span>
                </div>
                
                <!-- 日志过滤选项 -->
                <div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
                    <label>
                        <input type="checkbox" id="show-user" checked onchange="loadLogs()"> 显示用户消息
                    </label>
                    <label>
                        <input type="checkbox" id="show-bot" checked onchange="loadLogs()"> 显示机器人回复
                    </label>
                    <input type="text" id="search-query" placeholder="搜索关键词..." 
                           style="padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px; flex-grow: 1;"
                           onkeyup="loadLogs()">
                    <select id="time-range" onchange="loadLogs()" style="padding: 8px; border-radius: 4px;">
                        <option value="all">所有时间</option>
                        <option value="today">今天</option>
                        <option value="yesterday">昨天</option>
                        <option value="week">最近一周</option>
                    </select>
                </div>
                
                <!-- 日志显示区域 -->
                <div id="logs-container" class="logs-container">
                    <!-- 日志内容通过JavaScript动态加载 -->
                </div>
                
                <div style="text-align: center; margin-top: 15px; color: #718096; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    正在加载对话记录，请稍候...
                    <div class="spinner mt-20" style="width: 30px; height: 30px;"></div>
                </div>
            </div>
            
            <!-- 用户统计标签页 -->
            <div id="users-tab" class="d-none">
                <div class="section-title">
                    <i class="fas fa-chart-pie"></i> 用户统计
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><i class="fas fa-users"></i> 活跃用户</h3>
                        <div class="stat-number" id="active-users">0</div>
                        <div class="stat-desc">24小时内活跃</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3><i class="fas fa-comment-alt"></i> 总对话数</h3>
                        <div class="stat-number" id="total-conversations">0</div>
                        <div class="stat-desc">累计消息数量</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3><i class="fas fa-calendar-alt"></i> 今日消息</h3>
                        <div class="stat-number" id="today-messages">0</div>
                        <div class="stat-desc">今天收到的消息</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h3><i class="fas fa-clock"></i> 平均响应</h3>
                        <div class="stat-number" id="avg-response">0.5s</div>
                        <div class="stat-desc">平均响应时间</div>
                    </div>
                </div>
                
                <!-- 用户列表表格 -->
                <div style="margin-top: 30px;">
                    <h3><i class="fas fa-list"></i> 用户列表</h3>
                    <div id="users-table-container">
                        <!-- 用户表格通过JavaScript动态加载 -->
                    </div>
                </div>
            </div>
            
            <!-- 系统信息标签页 -->
            <div id="system-tab" class="d-none">
                <div class="section-title">
                    <i class="fas fa-server"></i> 系统状态
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><i class="fas fa-hdd"></i> 服务器状态</h3>
                        <div class="stat-number" style="color: #68d391;">✅ 正常</div>
                        <div class="stat-desc">运行时间: <?php echo round((time() - $_SERVER['REQUEST_TIME'])/3600, 2); ?>小时</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <h3><i class="fas fa-file-code"></i> 日志文件</h3>
                        <?php
                        $log_file = 'telegram_webhook.log';
                        if (file_exists($log_file)) {
                            $size = filesize($log_file);
                            $mod_time = date('Y-m-d H:i:s', filemtime($log_file));
                            echo '<div class="stat-number">' . round($size/1024, 2) . ' KB</div>';
                            echo '<div class="stat-desc">最后更新: ' . $mod_time . '</div>';
                        } else {
                            echo '<div class="stat-number">0 KB</div>';
                            echo '<div class="stat-desc">日志文件不存在</div>';
                        }
                        ?>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">
                        <h3><i class="fas fa-code-branch"></i> PHP版本</h3>
                        <div class="stat-number"><?php echo PHP_VERSION; ?></div>
                        <div class="stat-desc">内存限制: <?php echo ini_get('memory_limit'); ?></div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333;">
                        <h3><i class="fas fa-network-wired"></i> 网络状态</h3>
                        <div class="stat-number">🟢 在线</div>
                        <div class="stat-desc">IP: <?php echo $_SERVER['SERVER_ADDR'] ?? '未知'; ?></div>
                    </div>
                </div>
                
                <!-- 系统信息详情 -->
                <div style="margin-top: 30px; background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h3><i class="fas fa-info-circle"></i> 系统详情</h3>
                    <pre style="background: #1a202c; color: #cbd5e0; padding: 15px; border-radius: 5px; overflow: auto; font-size: 12px;">
操作系统: <?php echo php_uname('s') . ' ' . php_uname('r'); ?>

服务器软件: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? '未知'; ?>

最大执行时间: <?php echo ini_get('max_execution_time'); ?>秒

时区设置: <?php echo date_default_timezone_get(); ?>

脚本目录: <?php echo __DIR__; ?>

请求时间: <?php echo date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']); ?>
                    </pre>
                </div>
            </div>
        </div>
        
        <!-- 底部信息 -->
        <div class="footer">
            <p>
                <i class="fas fa-copyright"></i> 2024 中蒙代购机器人 &nbsp;|&nbsp;
                <i class="fas fa-shield-alt"></i> 数据安全 &nbsp;|&nbsp;
                <i class="fas fa-heart" style="color: #e53e3e;"></i> Powered by Render
            </p>
            <p style="font-size: 12px; margin-top: 10px;">
                <i class="fas fa-clock"></i> 页面生成时间: <?php echo date('Y-m-d H:i:s'); ?> &nbsp;|&nbsp;
                <i class="fas fa-sync-alt"></i> 自动刷新: <span id="auto-refresh-countdown">30</span>秒
            </p>
        </div>
    </div>
    
    <script>
        // 全局变量
        let currentTab = 'logs';
        let autoRefreshInterval;
        let refreshCountdown = 30;
        
        // 页面加载完成
        document.addEventListener('DOMContentLoaded', function() {
            // 默认显示日志标签页
            showTab('logs');
            
            // 开始自动刷新倒计时
            startAutoRefresh();
            
            // 开始加载数据
            setTimeout(() => {
                loadLogs();
                updateStats();
            }, 500);
        });
        
        // 显示标签页
        function showTab(tabName) {
            // 隐藏所有标签页
            document.getElementById('logs-tab').style.display = 'none';
            document.getElementById('users-tab').style.display = 'none';
            document.getElementById('system-tab').style.display = 'none';
            
            // 移除所有按钮的激活样式
            document.getElementById('logs-tab-btn').classList.remove('btn-success');
            document.getElementById('users-tab-btn').classList.remove('btn-success');
            document.getElementById('system-tab-btn').classList.remove('btn-success');
            
            // 显示选中的标签页
            document.getElementById(tabName + '-tab').style.display = 'block';
            
            // 激活对应的按钮
            document.getElementById(tabName + '-tab-btn').classList.add('btn-success');
            
            // 更新当前标签页
            currentTab = tabName;
            
            // 如果是用户标签页，加载用户数据
            if (tabName === 'users') {
                loadUsersTable();
            }
        }
        
        // 加载对话日志
        async function loadLogs() {
            const logsContainer = document.getElementById('logs-container');
            const logCount = document.getElementById('log-count');
            const refreshBtn = document.getElementById('refresh-btn');
            
            // 显示加载状态
            logsContainer.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>正在加载对话日志，请稍候...</p>
                </div>
            `;
            
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 加载中...';
            
            // 获取过滤参数
            const showUser = document.getElementById('show-user').checked;
            const showBot = document.getElementById('show-bot').checked;
            const searchQuery = document.getElementById('search-query').value;
            const timeRange = document.getElementById('time-range').value;
            
            try {
                const response = await fetch(`?action=get_logs&show_user=${showUser}&show_bot=${showBot}&q=${encodeURIComponent(searchQuery)}&time=${timeRange}`);
                const data = await response.json();
                
                if (data.success) {
                    // 更新日志数量
                    logCount.textContent = `${data.total} 条记录`;
                    
                    // 显示日志内容
                    logsContainer.innerHTML = '';
                    
                    if (data.logs.length === 0) {
                        logsContainer.innerHTML = `
                            <div class="log-entry text-center">
                                <i class="fas fa-inbox fa-2x" style="color: #a0aec0; margin-bottom: 10px;"></i>
                                <p style="color: #a0aec0;">暂无对话记录</p>
                                <small>等待用户发送消息...</small>
                            </div>
                        `;
                    } else {
                        data.logs.forEach(log => {
                            const logEntry = document.createElement('div');
                            logEntry.className = `log-entry ${log.type}`;
                            logEntry.innerHTML = `
                                <span class="log-time">[${log.time}]</span>
                                ${log.user ? `<span class="log-user">${log.user}</span>` : ''}
                                <span class="log-message">${formatMessage(log.message)}</span>
                            `;
                            logsContainer.appendChild(logEntry);
                        });
                    }
                } else {
                    logsContainer.innerHTML = `
                        <div class="log-entry text-center" style="color: #f56565;">
                            <i class="fas fa-exclamation-triangle"></i> 加载失败: ${data.error}
                        </div>
                    `;
                }
            } catch (error) {
                logsContainer.innerHTML = `
                    <div class="log-entry text-center" style="color: #f56565;">
                        <i class="fas fa-times-circle"></i> 网络错误: ${error.message}
                    </div>
                `;
            } finally {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> 刷新对话日志';
            }
        }
        
        // 格式化消息内容
        function formatMessage(message) {
            if (!message) return '';
            
            // 将Unicode转义序列转换为中文
            let formatted = message;
            
            // 处理常见的Unicode转义
            formatted = formatted.replace(/\\u(\w{4})/gi, (match, grp) => {
                return String.fromCharCode(parseInt(grp, 16));
            });
            
            // 处理HTML特殊字符
            formatted = formatted
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            
            // 高亮关键词
            const searchQuery = document.getElementById('search-query').value;
            if (searchQuery) {
                const regex = new RegExp(`(${searchQuery})`, 'gi');
                formatted = formatted.replace(regex, '<mark style="background: #f6e05e; color: #1a202c; padding: 2px 4px; border-radius: 2px;">$1</mark>');
            }
            
            return formatted;
        }
        
        // 更新统计数据
        async function updateStats() {
            try {
                const response = await fetch('?action=get_stats');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('active-users').textContent = data.active_users || 0;
                    document.getElementById('total-conversations').textContent = data.total_conversations || 0;
                    document.getElementById('today-messages').textContent = data.today_messages || 0;
                }
            } catch (error) {
                console.error('更新统计失败:', error);
            }
        }
        
        // 加载用户表格
        async function loadUsersTable() {
            const container = document.getElementById('users-table-container');
            container.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>正在加载用户数据...</p>
                </div>
            `;
            
            try {
                const response = await fetch('?action=get_users');
                const data = await response.json();
                
                if (data.success && data.users.length > 0) {
                    let tableHTML = `
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>用户ID</th>
                                    <th>最后活跃</th>
                                    <th>消息数</th>
                                    <th>最近消息</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    data.users.forEach(user => {
                        tableHTML += `
                            <tr>
                                <td><code>${user.id}</code></td>
                                <td>${user.last_active}</td>
                                <td><span class="btn" style="padding: 3px 8px;">${user.message_count}</span></td>
                                <td>${user.last_message ? user.last_message.substring(0, 30) + '...' : '无'}</td>
                                <td>
                                    <button class="btn" style="padding: 5px 10px; font-size: 12px;" 
                                            onclick="viewUserLogs('${user.id}')">
                                        <i class="fas fa-search"></i> 查看
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    tableHTML += `
                            </tbody>
                        </table>
                    `;
                    
                    container.innerHTML = tableHTML;
                } else {
                    container.innerHTML = `
                        <div class="log-entry text-center">
                            <i class="fas fa-user-slash"></i>
                            <p style="color: #a0aec0; margin-top: 10px;">暂无用户数据</p>
                        </div>
                    `;
                }
            } catch (error) {
                container.innerHTML = `
                    <div class="log-entry text-center" style="color: #f56565;">
                        <i class="fas fa-times-circle"></i> 加载用户数据失败: ${error.message}
                    </div>
                `;
            }
        }
        
        // 查看特定用户日志
        function viewUserLogs(userId) {
            document.getElementById('search-query').value = `用户ID:${userId}`;
            document.getElementById('show-user').checked = true;
            document.getElementById('show-bot').checked = true;
            document.getElementById('time-range').value = 'all';
            
            showTab('logs');
            loadLogs();
        }
        
        // 清空日志
        async function clearLogs() {
            if (!confirm('⚠️ 确定要清空所有对话日志吗？\n\n此操作将删除所有历史记录，无法恢复！')) {
                return;
            }
            
            const clearBtn = document.getElementById('clear-btn');
            clearBtn.disabled = true;
            clearBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 清空中...';
            
            try {
                const response = await fetch('?action=clear_logs');
                const result = await response.text();
                
                if (result === 'success') {
                    alert('✅ 日志已成功清空！');
                    loadLogs();
                    updateStats();
                } else {
                    alert('❌ 清空失败：' + result);
                }
            } catch (error) {
                alert('❌ 清空失败：' + error.message);
            } finally {
                clearBtn.disabled = false;
                clearBtn.innerHTML = '<i class="fas fa-trash-alt"></i> 清空日志';
            }
        }
        
        // 开始自动刷新
        function startAutoRefresh() {
            const countdownElement = document.getElementById('auto-refresh-countdown');
            
            autoRefreshInterval = setInterval(() => {
                refreshCountdown--;
                countdownElement.textContent = refreshCountdown;
                
                if (refreshCountdown <= 0) {
                    // 刷新当前标签页的数据
                    if (currentTab === 'logs') {
                        loadLogs();
                    } else if (currentTab === 'users') {
                        loadUsersTable();
                        updateStats();
                    }
                    
                    // 重置倒计时
                    refreshCountdown = 30;
                }
            }, 1000);
        }
        
        // 工具函数：格式化时间戳
        function formatTimestamp(timestamp) {
            const date = new Date(timestamp * 1000);
            return date.toLocaleString('zh-CN');
        }
        
        // 工具函数：计算时间差
        function timeAgo(timestamp) {
            const now = Math.floor(Date.now() / 1000);
            const diff = now - timestamp;
            
            if (diff < 60) return '刚刚';
            if (diff < 3600) return Math.floor(diff / 60) + '分钟前';
            if (diff < 86400) return Math.floor(diff / 3600) + '小时前';
            return Math.floor(diff / 86400) + '天前';
        }
    </script>
    
    <?php
    // ==============================
    // PHP后端处理逻辑
    // ==============================
    
    // 处理所有AJAX请求
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        $log_file = 'telegram_webhook.log';
        
        // 设置JSON响应头
        header('Content-Type: application/json; charset=utf-8');
        
        switch ($action) {
            case 'get_logs':
                if (!file_exists($log_file)) {
                    echo json_encode([
                        'success' => true,
                        'total' => 0,
                        'logs' => []
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                $content = file_get_contents($log_file);
                $lines = explode("\n", trim($content));
                $filtered_logs = [];
                
                // 获取过滤参数
                $show_user = ($_GET['show_user'] ?? 'true') === 'true';
                $show_bot = ($_GET['show_bot'] ?? 'true') === 'true';
                $search_query = $_GET['q'] ?? '';
                $time_range = $_GET['time'] ?? 'all';
                
                foreach ($lines as $line) {
                    if (empty(trim($line))) continue;
                    
                    // 解析日志行（根据你的日志格式调整）
                    // 假设格式: [时间] 用户ID: xxx | 消息: xxx
                    $log_entry = parseLogLine($line);
                    
                    if (!$log_entry) continue;
                    
                    // 应用过滤器
                    if ($search_query && stripos($line, $search_query) === false) {
                        continue;
                    }
                    
                    // 时间范围过滤
                    if ($time_range !== 'all') {
                        $log_time = strtotime($log_entry['time']);
                        $now = time();
                        
                        switch ($time_range) {
                            case 'today':
                                if (date('Y-m-d', $log_time) !== date('Y-m-d')) continue 2;
                                break;
                            case 'yesterday':
                                $yesterday = date('Y-m-d', strtotime('-1 day'));
                                if (date('Y-m-d', $log_time) !== $yesterday) continue 2;
                                break;
                            case 'week':
                                $one_week_ago = strtotime('-7 days');
                                if ($log_time < $one_week_ago) continue 2;
                                break;
                        }
                    }
                    
                    // 类型过滤
                    if ($log_entry['type'] === 'user' && !$show_user) continue;
                    if ($log_entry['type'] === 'bot' && !$show_bot) continue;
                    
                    $filtered_logs[] = $log_entry;
                }
                
                // 反转，最新的在前面
                $filtered_logs = array_reverse($filtered_logs);
                $filtered_logs = array_slice($filtered_logs, 0, 100); // 只取最新100条
                
                echo json_encode([
                    'success' => true,
                    'total' => count($filtered_logs),
                    'logs' => $filtered_logs
                ], JSON_UNESCAPED_UNICODE);
                exit;
                
            case 'clear_logs':
                file_put_contents($log_file, '');
                echo 'success';
                exit;
                
            case 'get_stats':
                $stats = [
                    'active_users' => 0,
                    'total_conversations' => 0,
                    'today_messages' => 0,
                    'avg_response' => '0.5s'
                ];
                
                if (file_exists($log_file)) {
                    $content = file_get_contents($log_file);
                    $lines = explode("\n", trim($content));
                    $stats['total_conversations'] = count($lines);
                    
                    // 简单的统计逻辑（根据实际需求调整）
                    $user_ids = [];
                    $today = date('Y-m-d');
                    
                    foreach ($lines as $line) {
                        if (stripos($line, '[用户ID:') !== false) {
                            preg_match('/\[用户ID:(\d+)\]/', $line, $matches);
                            if ($matches) {
                                $user_ids[] = $matches[1];
                            }
                        }
                        
                        // 统计今天的消息
                        if (strpos($line, '[' . $today) === 0) {
                            $stats['today_messages']++;
                        }
                    }
                    
                    $stats['active_users'] = count(array_unique($user_ids));
                }
                
                echo json_encode([
                    'success' => true,
                    ...$stats
                ], JSON_UNESCAPED_UNICODE);
                exit;
                
            case 'get_users':
                $users = [];
                
                if (file_exists($log_file)) {
                    $content = file_get_contents($log_file);
                    $lines = explode("\n", trim($content));
                    
                    $user_data = [];
                    
                    foreach ($lines as $line) {
                        // 解析用户信息（根据你的日志格式调整）
                        if (preg_match('/用户ID:\s*(\d+).*?\|\s*消息:\s*(.+)/', $line, $matches)) {
                            $user_id = $matches[1];
                            $message = $matches[2];
                            
                            if (!isset($user_data[$user_id])) {
                                $user_data[$user_id] = [
                                    'count' => 0,
                                    'last_message' => '',
                                    'last_time' => ''
                                ];
                            }
                            
                            $user_data[$user_id]['count']++;
                            $user_data[$user_id]['last_message'] = $message;
                            
                            // 提取时间
                            preg_match('/\[(.*?)\]/', $line, $time_match);
                            if ($time_match) {
                                $user_data[$user_id]['last_time'] = $time_match[1];
                            }
                        }
                    }
                    
                    foreach ($user_data as $id => $data) {
                        $users[] = [
                            'id' => $id,
                            'message_count' => $data['count'],
                            'last_message' => $data['last_message'],
                            'last_active' => $data['last_time'] ?: '未知'
                        ];
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'users' => $users
                ], JSON_UNESCAPED_UNICODE);
                exit;
        }
    }
    
    // 解析日志行的辅助函数
    function parseLogLine($line) {
        // 根据你的实际日志格式调整这个函数
        // 示例日志格式: [2024-01-15 10:30:25] 用户ID: 123456789 | 消息: 你好
        
        $pattern = '/\[(.*?)\]\s*(.*?)\s*\|\s*消息:\s*(.+)/';
        if (preg_match($pattern, $line, $matches)) {
            $time = $matches[1];
            $user_info = $matches[2];
            $message = $matches[3];
            
            // 判断是用户消息还是机器人回复
            $type = (strpos($user_info, '用户ID:') !== false) ? 'user' : 'bot';
            
            return [
                'time' => $time,
                'user' => $user_info,
                'message' => $message,
                'type' => $type
            ];
        }
        
        // 如果不符合格式，返回原行
        return [
            'time' => date('H:i:s'),
            'user' => '',
            'message' => $line,
            'type' => 'bot'
        ];
    }
    
    ob_end_flush();
    ?>
</body>
</html>
