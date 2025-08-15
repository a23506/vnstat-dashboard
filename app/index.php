<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/proc/self/fd/2');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/utilities.php';
require __DIR__ . '/includes/vnstat.php';

$errors = [];
try {
    $tplDir   = __DIR__ . '/templates';
    $compDir  = __DIR__ . '/templates_c';
    $cacheDir = __DIR__ . '/cache';
    $confDir  = __DIR__ . '/configs';
    if (!is_dir($compDir)) @mkdir($compDir, 0775, true);
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
    if (!is_dir($confDir))  @mkdir($confDir, 0775, true);

    $smarty = new Smarty();
    $smarty->setTemplateDir($tplDir);
    $smarty->setCompileDir($compDir);
    $smarty->setCacheDir($cacheDir);
    $smarty->setConfigDir($confDir);
    $smarty->assign('year', date("Y"));

    $vnstat = null;
    try { $vnstat = new vnStat($vnstat_bin_dir ?? '/usr/bin/vnstat'); }
    catch (Throwable $e) { $errors[] = 'vnStat init: ' . $e->getMessage(); error_log('[vnstat-dashboard] ' . end($errors)); }

    $interface_list = [];
    if ($vnstat) {
        try { $interface_list = $vnstat->getInterfaces(); }
        catch (Throwable $e) { $errors[] = 'getInterfaces: ' . $e->getMessage(); error_log('[vnstat-dashboard] ' . end($errors)); }
    }
    if (empty($interface_list)) { $interface_list = ['eth0']; }
    $queryIface = $_GET['i'] ?? ($_GET['iface'] ?? null);
    $thisInterface = $queryIface && in_array($queryIface, $interface_list, true) ? $queryIface : $interface_list[0];
    $smarty->assign('current_interface', $thisInterface);
    $smarty->assign('interface_list', $interface_list);

    $safeGet = function($period, $type) use ($vnstat, $thisInterface, &$errors) {
        try { if ($vnstat) return $vnstat->getInterfaceData($period, $type, $thisInterface); }
        catch (Throwable $e) { $errors[] = $period . '/' . $type . ': ' . $e->getMessage(); error_log('[vnstat-dashboard] ' . end($errors)); }
        return [];
    };

    // 表格数据
    $smarty->assign('fiveMinTableData', $safeGet('fiveminute', 'table'));
    $smarty->assign('hourlyTableData',  $safeGet('hourly',  'table'));
    $smarty->assign('dailyTableData',   $safeGet('daily',   'table'));
    $smarty->assign('monthlyTableData', $safeGet('monthly', 'table'));
    $smarty->assign('top10TableData',   $safeGet('top10',   'table'));

    // 图表数据
    $fg = $safeGet('fiveminute', 'graph');
    $hg = $safeGet('hourly',     'graph');
    $dg = $safeGet('daily',      'graph');
    $mg = $safeGet('monthly',    'graph');
    $smarty->assign('fiveMinGraphData', $fg);   $smarty->assign('fiveMinLargestPrefix',  $fg[1]['delimiter'] ?? 'MB');
    $smarty->assign('hourlyGraphData',  $hg);   $smarty->assign('hourlyLargestPrefix',   $hg[1]['delimiter'] ?? 'MB');
    $smarty->assign('dailyGraphData',   $dg);   $smarty->assign('dailyLargestPrefix',    $dg[1]['delimiter'] ?? 'MB');
    $smarty->assign('monthlyGraphData', $mg);   $smarty->assign('monthlyLargestPrefix',  $mg[1]['delimiter'] ?? 'MB');

    $smarty->display('site_index.tpl');
    if (!empty($errors)) {
        echo "\n<!-- vnstat-dashboard errors:\n" . implode("\n", $errors) . "\n-->\n";
    }
} catch (Throwable $e) {
    http_response_code(200);
    $msg = '[vnstat-dashboard] FATAL index: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    error_log($msg);
    echo '<!doctype html><meta charset="utf-8"><title>vnStat Dashboard</title>';
    echo '<pre>仪表盘临时错误，请查看 docker logs 获取详情。</pre>';
    echo "\n<!-- $msg -->\n";
    exit;
}
