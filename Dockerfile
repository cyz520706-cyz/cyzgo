# Dockerfile
FROM php:8.2-apache

# 安装必要扩展
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    curl \
    && docker-php-ext-install zip pdo_mysql

# 启用Apache重写模块
RUN a2enmod rewrite headers

# 复制网站文件
COPY . /var/www/html/

# 设置工作目录
WORKDIR /var/www/html

# 设置文件权限
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 暴露端口
EXPOSE 80

# 启动Apache
CMD ["apache2-foreground"]
