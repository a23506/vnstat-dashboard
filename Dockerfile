# Dockerfile
FROM php:8.2-apache-bookworm

# 安装 vnstat（来自 Debian bookworm 仓库）
RUN apt-get update \
 && apt-get install -y --no-install-recommends vnstat tzdata ca-certificates curl \
 && rm -rf /var/lib/apt/lists/*

# 可选：常用 Apache 模块
RUN a2enmod expires headers rewrite

# 拷贝应用到 Web 根目录
COPY app/ /var/www/html/

# 权限
RUN chown -R www-data:www-data /var/www/html

# 健康检查（首页能否正常响应）
HEALTHCHECK --interval=30s --timeout=5s --retries=5 CMD curl -fsS http://localhost/ || exit 1
