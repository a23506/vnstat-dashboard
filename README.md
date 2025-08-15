# vnStat 仪表盘（中文修正版：5 分钟数据 + 滚轮缩放 + Smarty/JS 冲突修复）

- 原版 UI（Bootstrap + Google Charts + Smarty），仅做兼容与本地化修复
- 新增 **5 分钟** 图表/表格（来自 vnStat 2.x `fiveminute`）
- **鼠标滚轮缩放时间轴**：上滑放大、下滑缩小（右键重置）
- 切换 Tab/窗口尺寸变化自动重绘，避免图表缩小问题
- 中文本地化（标题/表头/日期格式）：
  - 轴格式：小时 `yyyy/MM/dd HH:mm`；天 `yyyy/MM/dd`；月 `yyyy年MM月`
  - 表格：小时 `Y年m月d日 H:00`；天 `Y年m月d日`；月 `Y年m月`
- 兼容 PHP 8.2（Smarty 固定 ^4.5），兼容 vnStat 2.x JSON

## 构建与运行
```bash
docker build -t vnstat-dashboard:cn-fixed .
docker run -d --name vnstat-dashboard \
  -p 18880:80 \
  -e TZ=Asia/Shanghai \
  -v /var/lib/vnstat:/var/lib/vnstat:ro \
  vnstat-dashboard:cn-fixed
# 或
docker compose up -d
