#!/bin/bash
# Telegram Bot Docker部署脚本

echo "🤖 开始部署Telegram机器人..."

# 1. 检查Docker是否安装
if ! command -v docker &> /dev/null; then
    echo "❌ Docker未安装"
    echo "请先安装Docker: https://docs.docker.com/get-docker/"
    exit 1
fi

# 2. 检查BOT_TOKEN
if [ -z "$BOT_TOKEN" ]; then
    read -p "请输入Telegram Bot Token: " BOT_TOKEN
    export BOT_TOKEN=$BOT_TOKEN
fi

# 3. 构建Docker镜像
echo "🔨 构建Docker镜像..."
docker build -t telegram-bot .

# 4. 运行容器
echo "🚀 启动容器..."
docker run -d \
  --name telegram-bot \
  -p 8080:80 \
  -e BOT_TOKEN=$BOT_TOKEN \
  -v $(pwd)/logs:/var/log \
  --restart unless-stopped \
  telegram-bot

# 5. 检查容器状态
echo "📊 检查容器状态..."
docker ps | grep telegram-bot

# 6. 设置Webhook
echo "🌐 设置Telegram Webhook..."
DOMAIN="你的公网IP或域名"
if [ -z "$DOMAIN" ]; then
    read -p "请输入你的公网域名/IP: " DOMAIN
fi

WEBHOOK_URL="https://$DOMAIN:8080/bot.php"
echo "Webhook地址: $WEBHOOK_URL"

curl -X POST "https://api.telegram.org/bot$BOT_TOKEN/setWebhook" \
  -d "url=$WEBHOOK_URL&drop_pending_updates=true"

echo "✅ 部署完成！"
echo "👉 访问 http://localhost:8080/bot.php 测试"
echo "👉 查看日志: docker logs telegram-bot"
