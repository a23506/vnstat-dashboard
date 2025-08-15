<?php
// 生产环境建议关闭屏幕错误（日志另配）
ini_set('display_errors', 0);
error_reporting(0);

// 时区（按需修改）
date_default_timezone_set('America/Phoenix');

// vnstat 可执行文件路径（容器内）
$vnstat_bin_dir = '/usr/bin/vnstat';

/**
 * 是否使用预设接口列表：
 * - true：只显示下面 $interface_list 中的接口（推荐）
 * - false：尝试从 vnstat JSON 自动探测（老逻辑，可能触发“id”告警）
 */
$use_predefined_interfaces = true;

/**
 * 接口列表（按需修改；示例：eth0, ens18 等）
 * 提示：可在宿主机/sidecar 中用 `vnstat --iflist` 或查看 /var/lib/vnstat 下目录名
 */
$interface_list = ["eth0"];

/**
 * 可选：接口别名（用于 UI 展示）
 * 例：$interface_name['eth0'] = "WAN";
 */
$interface_name = [];
