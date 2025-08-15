<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/proc/self/fd/2');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);

date_default_timezone_set(getenv('TZ') ?: 'Asia/Shanghai');

$vnstat_bin_dir = getenv('VNSTAT_BIN') ?: '/usr/bin/vnstat';

// 如需固定接口列表（不读 vnstat 输出），返回数组；否则返回空
if (!function_exists('config_interfaces_override')) {
  function config_interfaces_override() {
    // 例如：return ['eth0', 'ens18'];
    return [];
  }
}
