# ---- Composer builder ----
FROM composer:2 AS vendor
WORKDIR /app

# 1) 先拷贝依赖声明（没有 lock 也能过）
COPY app/composer.* ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

# 2) 再拷贝源码并优化自动加载
COPY app/ ./
RUN composer dump-autoload --optimize

# ---- Runtime image ----
FROM php:8.2-apache-bookworm

# 安装运行时依赖（含 vnstat）
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends vnstat tzdata ca-certificates curl; \
    rm -rf /var/lib/apt/lists/*

# 可选的 Apache 模块
RUN a2enmod expires headers rewrite

# 拷贝构建产物（含 vendor/）
COPY --from=vendor /app/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# 健康检查
HEALTHCHECK --interval=30s --timeout=5s --retries=5 CMD curl -fsS http://localhost/ || exit 1
