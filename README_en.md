# vnstat-dashboard (Improved)

A lightweight, responsive PHP dashboard to visualize network traffic statistics provided by **vnStat** (2.x). This fork/packaging aims to make the container **safer, simpler, and modern**:

- ✅ **No more glibc mismatches**: install `vnstat` *inside* the image instead of bind-mounting host binaries.
- ✅ **Works on current Debian** (Bookworm), no dead APT mirrors.
- ✅ **Two deployment patterns**: (A) reuse host `vnstatd` database; (B) run `vnstatd` as a sidecar.
- ✅ **Docker/Compose examples**, **healthcheck**, and **troubleshooting**.

> If you only want the quickest start, jump to [Quick Start](#quick-start).

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Build From Source](#build-from-source)
- [Recommended Deployments](#recommended-deployments)
  - [A) Reuse host vnstatd (mount DB)](#a-reuse-host-vnstatd-mount-db)
  - [B) Sidecar vnstatd (no host install)](#b-sidecar-vnstatd-no-host-install)
- [Dockerfile (modern base)](#dockerfile-modern-base)
- [Security Hardening (optional)](#security-hardening-optional)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)
- [License](#license)

---

## Features

- Hourly, daily, monthly charts (Google Charts)
- Top-10 days
- Auto-detected interfaces list
- Simple PHP/Bootstrap frontend

> The UI reads `vnstat --json` at runtime; **the JSON must exist** (db must contain data).

## Requirements

- **vnStat database** at `/var/lib/vnstat` (populated by `vnstatd`).
- Docker 20.10+ (or Podman) recommended.
- Optional: `TZ` for time zone, e.g. `TZ=America/Phoenix`.

---

## Quick Start

**Prebuilt image (recommended)**

```bash
docker run -d --name vnstat-dashboard   -p 8686:80   -e TZ=America/Phoenix   -v /var/lib/vnstat:/var/lib/vnstat:ro   ghcr.io/<your-org-or-user>/vnstat-dashboard:latest
```

Open http://localhost:8686

> Make sure your host is already running `vnstatd` and that `/var/lib/vnstat` has data. Example:
> ```bash
> # Host only, once per interface (replace <iface> with eth0/ens3/etc.)
> sudo apt-get install -y vnstat
> sudo systemctl enable --now vnstat
> sudo vnstat -u -i <iface>
> vnstat -i <iface> --json | head  # should print JSON
> ```

---

## Build From Source

```bash
# Build
docker build -t vnstat-dashboard:local .

# Run
docker run -d --name vnstat-dashboard   -p 8686:80   -e TZ=America/Phoenix   -v /var/lib/vnstat:/var/lib/vnstat:ro   vnstat-dashboard:local
```

---

## Recommended Deployments

### A) Reuse host vnstatd (mount DB)

- Keep running `vnstatd` on the **host**.
- Mount **only** `/var/lib/vnstat` **read-only** into the dashboard.
- **Do not** bind-mount `/usr/bin/vnstat` from the host (avoids glibc issues).

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

### B) Sidecar vnstatd (no host install)

- Run `vnstatd` as a separate container (requires **host network** so it sees real interfaces).
- Share a named volume for the database with the dashboard.

**docker-compose.sidecar.yml**

```yaml
version: "3.8"
services:
  vnstatd:
    image: vergoh/vnstat
    container_name: vnstatd
    restart: unless-stopped
    network_mode: host           # makes vnstatd see host interfaces
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

> On SELinux systems, add `:Z` to volume mounts (e.g., `/var/lib/vnstat:/var/lib/vnstat:ro,Z`).

---

## Dockerfile (modern base)

This Dockerfile installs `vnstat` **inside** the image (Debian 12 / PHP 8.2), avoiding broken mirrors and glibc mismatches.

```dockerfile
# Dockerfile
FROM php:8.2-apache-bookworm

# Install vnstat & useful tools
RUN set -eux;     apt-get update;     apt-get install -y --no-install-recommends vnstat tzdata ca-certificates curl;     rm -rf /var/lib/apt/lists/*

# Optional Apache modules
RUN a2enmod expires headers rewrite

# Copy app
COPY app/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --retries=5 CMD curl -fsS http://localhost/ || exit 1
```

Build & push (multi-arch recommended via GitHub Actions).

---

## Security Hardening (optional)

- Run filesystem **read-only** and mount only the vnstat DB as `:ro`.
- Add a tmpfs for PHP temp if needed:
  ```yaml
  tmpfs:
    - /tmp
  ```
- Consider dropping unnecessary capabilities:
  ```yaml
  cap_drop:
    - ALL
  ```
- Avoid mapping privileged host ports if not required (use `8686:80` like above).

> The Apache worker runs as `www-data` inside the container; the container process itself is root on Debian base images. Moving to a strict non-root runtime requires additional Apache configuration and is beyond the scope of this README.

---

## Troubleshooting

**“JSON is invalid”** in PHP
- `vnstat --json` returns empty → database has no data yet or not mounted correctly.
- Verify inside the container (dashboard):  
  `docker exec -it vnstat-dashboard vnstat --json | head`
- If sidecar: check `vnstatd` is up and using host network; confirm `vnstat -i <iface> --json` prints data either in the host or sidecar container.

**GLIBC errors (e.g., GLIBC_2.33 not found)**  
- Caused by bind-mounting host `/usr/bin/vnstat` into an older container. **Do not do this.** Install `vnstat` in the image as shown above.

**Docker port publishing / iptables errors**  
- If you see errors like `No chain/target/match by that name` from iptables:
  1. `sudo systemctl restart docker`
  2. Switch to legacy iptables if needed (`update-alternatives --set iptables /usr/sbin/iptables-legacy`), then restart Docker
  3. Check UFW/Firewalld forwarding/masquerade settings
  4. As a temporary workaround, try `--network=host`

**SELinux denies access to /var/lib/vnstat**  
- Add `:Z` to the volume, e.g. `/var/lib/vnstat:/var/lib/vnstat:ro,Z`.

---

## FAQ

**Q: Do I need to run `vnstatd` on the host?**  
A: No. Either run it on the host (mount db) **or** run the sidecar service with host networking.

**Q: Why not mount `/usr/bin/vnstat` from host?**  
A: It causes glibc ABI mismatches between host and container. Installing `vnstat` in the image is safer and simpler.

**Q: Why is my dashboard empty right after first start?**  
A: `vnstatd` needs time to collect data. Ensure the database exists and contains records for your interface(s).

---

## License

GPLv3 (or later), Copyright © 2019–present Alexander Marston and contributors.

See `LICENSE` for details.
