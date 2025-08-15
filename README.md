# 什么是 vnstat-dashboard？

**vnstat-dashboard** 是一个使用 PHP 与 Bootstrap 编写的自适应 Web 界面，改编自 bjd 的 *vnstat-php-frontend*。它用于展示 **vnStat**（支持 2.x 版本）提供的网络流量统计，包含：

- 小时级统计图表（使用 Google Charts）
- 天、月统计总览
- “Top 10” 高流量日统计
- 自动填充的网络接口选择

## 使用 Docker 运行

### 构建镜像

`$ docker build . -t amarston/vnstat-dashboard:latest`

### 推送镜像

`$ docker push amarston/vnstat-dashboard:latest`

### 启动容器

`$ docker run --name vnstat-dashboard -p 80:80 -v /usr/bin/vnstat:/usr/bin/vnstat -v /var/lib/vnstat:/var/lib/vnstat -d amarston/vnstat-dashboard:latest`

### 停止容器

`$ docker stop vnstat-dashboard`

## 本地直接运行（非 Docker）

### 运行步骤

```
$ cp -rp app/ /var/www/html/vnstat/
$ cd /var/www/html/vnstat/
$ composer install
```

## 许可证

Copyright (C) 2019 Alexander Marston (alexander.marston@gmail.com)

本程序以 **GNU General Public License v3**（或更高版本）条款发布，你可以在遵循许可证的前提下再发布与/或修改本程序。

本程序以“按现状”提供，不提供任何担保；包括但不限于适销性或特定用途适用性的默示担保。详情请参阅 GPLv3。

你应该已经随程序一同收到一份 GNU 通用公共许可证的副本；如未收到，请参见 http://www.gnu.org/licenses/。

## 关于

一个用于展示 vnStat（支持 2.x）的网络流量统计图形化 Web 界面。

https://alexandermarston.github.io/vnstat-dashboard/
