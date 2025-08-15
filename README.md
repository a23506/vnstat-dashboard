# vnstat-dashboard（改进版）

一个用于展示 **vnStat**（2.x）网络流量统计的轻量自适应 PHP 面板。本改进版的目标是让容器 **更稳、更简单、更现代**：

- ✅ **不再有 glibc 版本冲突**：在镜像内安装 `vnstat`，而不是挂载宿主的可执行文件。
- ✅ **使用当前 Debian**（Bookworm），没有失效的 APT 源。
- ✅ **两种部署方案**：A）复用宿主的 `vnstatd` 数据库；B）用 sidecar 跑 `vnstatd`。
- ✅ 提供 **Docker/Compose 示例**、**健康检查** 与 **故障排查**。

> 如果你只想快速跑起来，请直接看 [快速开始](#快速开始)。

---

## 目录

- [功能](#功能)
- [环境要求](#环境要求)
- [快速开始](#快速开始)
- [从源码构建](#从源码构建)
- [推荐部署方式](#推荐部署方式)
  - [A）复用宿主 vnstatd（挂载数据库）](#a复用宿主-vnstatd挂载数据库)
  - [B）Sidecar 运行 vnstatd（宿主无需安装）](#bsidecar-运行-vnstatd宿主无需安装)
- [Dockerfile（现代基底）](#dockerfile现代基底)
- [安全加固（可选）](#安全加固可选)
- [故障排查](#故障排查)
- [常见问题](#常见问题)
- [许可证](#许可证)

---

## 功能

- 小时、天、月度流量图表（Google Charts）
- Top 10 高流量日
- 自动识别接口列表
- 简单的 PHP/Bootstrap 前端

> 面板运行时通过 `vnstat --json` 读取数据；**必须能拿到 JSON**（数据库里要有数据）。

## 环境要求

- `/var/lib/vnstat` 中的 **vnStat 数据库**（由 `vnstatd` 生成）。
- Docker 20.10+（或 Podman）。
- 可选：时区变量 `TZ`（例如 `TZ=America/Phoenix`）。

---

## 快速开始

**使用预构建镜像（推荐）**

```bash
docker run -d --name vnstat-dashboard   -p 8686:80   -e TZ=America/Phoenix   -v /var/lib/vnstat:/var/lib/vnstat:ro   ghcr.io/<your-org-or-user>/vnstat-dashboard:latest
```

打开 http://localhost:8686

> 请确保宿主机正在运行 `vnstatd`，且 `/var/lib/vnstat` 已有数据。例如：
> ```bash
> # 宿主只需一次每接口的初始化（将 <iface> 替换为 eth0/ens3 等）
> sudo apt-get install -y vnstat
> sudo systemctl enable --now vnstat
> sudo vnstat -u -i <iface>
> vnstat -i <iface> --json | head  # 能看到 JSON 即可
> ```

---

## 从源码构建

```bash
# 构建
docker build -t vnstat-dashboard:local .

# 运行
docker run -d --name vnstat-dashboard   -p 8686:80   -e TZ=America/Phoenix   -v /var/lib/vnstat:/var/lib/vnstat:ro   vnstat-dashboard:local
```

---

## 推荐部署方式

### A）复用宿主 vnstatd（挂载数据库）

- `vnstatd` 继续在**宿主机**运行；
- 只把 `/var/lib/vnstat` 以 **只读** 挂给面板；
- **不要**再挂宿主的 `/usr/bin/vnstat`（可避免 glibc 冲突）。

**docker-compose.yml**

```yaml
version: "3.8"
services:
  dashboard:
    image: ghcr.io/<your-org-or-user>/vnstat-dashboard:latest
    container_name: vnstat-dashboard
    restart: unless-stopped
    ports:
      - "8686:80"
    environment:
      - TZ=America/Phoenix
    volumes:
      - /var/lib/vnstat:/var/lib/vnstat:ro
    read_only: true
```

### B）Sidecar 运行 vnstatd（宿主无需安装）

- 以独立容器运行 `vnstatd`（需 **host 网络** 才能看到真实接口）；
- 与面板共享一个命名卷存放数据库。

**docker-compose.sidecar.yml**

```yaml
version: "3.8"
services:
  vnstatd:
    image: vergoh/vnstat
    container_name: vnstatd
    restart: unless-stopped
    network_mode: host           # 让 vnstatd 看到宿主接口
    volumes:
      - vnstat-db:/var/lib/vnstat

  dashboard:
    image: ghcr.io/<your-org-or-user>/vnstat-dashboard:latest
    container_name: vnstat-dashboard
    restart: unless-stopped
    ports:
      - "8686:80"
    environment:
      - TZ=America/Phoenix
    volumes:
      - vnstat-db:/var/lib/vnstat:ro
    read_only: true

volumes:
  vnstat-db:
```

> 在 SELinux 系统上，为卷加 `:Z`（如 `/var/lib/vnstat:/var/lib/vnstat:ro,Z`）。

---

## Dockerfile（现代基底）

在镜像中直接安装 `vnstat`（Debian 12 / PHP 8.2），避免失效镜像源与 glibc 兼容性问题。

```dockerfile
# Dockerfile
FROM php:8.2-apache-bookworm

# 安装 vnstat 和常用工具
RUN set -eux;     apt-get update;     apt-get install -y --no-install-recommends vnstat tzdata ca-certificates curl;     rm -rf /var/lib/apt/lists/*

# 可选 Apache 模块
RUN a2enmod expires headers rewrite

# 拷贝应用
COPY app/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# 健康检查
HEALTHCHECK --interval=30s --timeout=5s --retries=5 CMD curl -fsS http://localhost/ || exit 1
```

---

## 安全加固（可选）

- 容器根文件系统使用 **只读** 模式，仅以 `:ro` 挂载 vnstat 数据库；
- 如需临时目录，可加 tmpfs：
  ```yaml
  tmpfs:
    - /tmp
  ```
- 尽量 **丢弃能力**：
  ```yaml
  cap_drop:
    - ALL
  ```
- 避免占用宿主的低号端口，采用 `8686:80` 之类的映射。

> Debian 基础镜像中容器进程以 root 运行，Apache 工作进程为 `www-data`。完全切换到非 root 运行需要额外配置，本文不做展开。

---

## 故障排查

**PHP 报 “JSON is invalid”**  
- `vnstat --json` 输出为空 → 数据库还没数据或没正确挂载；  
- 在面板容器内验证：`docker exec -it vnstat-dashboard vnstat --json | head`；  
- 如果用 sidecar：确认 `vnstatd` 已运行且是 host 网络；在宿主或 sidecar 容器里跑 `vnstat -i <iface> --json` 应有输出。

**GLIBC 相关错误（如 GLIBC_2.33 not found）**  
- 这是把宿主 `/usr/bin/vnstat` 挂进旧容器导致的 ABI 冲突。**请不要这么做**；改为在镜像内安装 `vnstat`。

**Docker 端口映射/iptables 报错**  
- 若见到 `No chain/target/match by that name`：  
  1. `sudo systemctl restart docker`  
  2. 必要时切回 legacy iptables（`update-alternatives --set iptables /usr/sbin/iptables-legacy`），再重启 Docker  
  3. 检查 UFW/Firewalld 的转发/伪装设置  
  4. 临时绕过：`--network=host`

**SELinux 阻止读取 /var/lib/vnstat**  
- 在卷后加 `:Z`（例如 `/var/lib/vnstat:/var/lib/vnstat:ro,Z`）。

---

## 常见问题

**问：必须在宿主跑 `vnstatd` 吗？**  
答：不必须。可以在宿主跑（挂数据库）或用 sidecar 运行。

**问：为什么不挂宿主的 `/usr/bin/vnstat`？**  
答：常见 glibc 版本不匹配问题。在镜像中安装 `vnstat` 更安全简单。

**问：为什么刚启动面板时为空？**  
答：`vnstatd` 需要时间收集数据。确认数据库存在并有对应接口的数据。

---

## 许可证

GPLv3（或更高版本）。© 2019–至今 Alexander Marston 及贡献者。

详见 `LICENSE`。
