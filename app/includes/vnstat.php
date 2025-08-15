<?php
/**
 * vnstat-dashboard – PHP 8 兼容版数据层
 * - 兼容 vnStat 2.x：接口字段可能为 name 而非 id
 * - 修正 PHP 8 回调：usort 传入显式 callable
 * - 兜底：空 JSON / 未初始化变量
 */

require_once __DIR__ . '/config.php';

class vnStat
{
    private string $binPath;
    private array $vnstatData = [];
    private array $interfaceIds = [];

    public function __construct(string $vnstat_bin_dir = '/usr/bin/vnstat')
    {
        $this->binPath = $vnstat_bin_dir ?: '/usr/bin/vnstat';
        $json = $this->runVnstat(['--json']);
        $this->processVnstatData($json);
    }

    private function runVnstat(array $args): string
    {
        $cmd = escapeshellcmd($this->binPath);
        foreach ($args as $a) {
            $cmd .= ' ' . escapeshellarg($a);
        }
        // 抑制 stderr；生产中更建议写日志文件
        $out = @shell_exec($cmd . ' 2>/dev/null');
        return $out ?: '';
    }

    private function processVnstatData(string $json): void
    {
        $json = trim($json);
        if ($json === '') {
            // 没数据也不要抛异常，让前端显示友好提示
            $this->vnstatData = [];
            $this->interfaceIds = [];
            return;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            $this->vnstatData = [];
            $this->interfaceIds = [];
            return;
        }

        // 兼容：接口可能没有 id、只有 name
        if (isset($data['interfaces']) && is_array($data['interfaces'])) {
            foreach ($data['interfaces'] as &$iface) {
                if (!isset($iface['id']) && isset($iface['name'])) {
                    $iface['id'] = $iface['name']; // 兼容 2.x JSON
                }
            }
            unset($iface);

            $ids = array_map(function ($if) {
                return $if['id'] ?? ($if['name'] ?? null);
            }, $data['interfaces']);
            $this->interfaceIds = array_values(array_filter($ids, fn($v) => !empty($v)));
        }

        $this->vnstatData = $data;
    }

    public function getInterfaces(): array
    {
        // 优先使用预设接口（避免老逻辑里基于 id 的自动探测）
        if (!empty($GLOBALS['use_predefined_interfaces']) &&
            !empty($GLOBALS['interface_list']) &&
            is_array($GLOBALS['interface_list'])) {
            return $GLOBALS['interface_list'];
        }
        return $this->interfaceIds ?: [];
    }

    /**
     * $period: 'hourly'|'daily'|'monthly'
     * $format: 'table'|'array'
     * $iface:  接口名（为空则取列表第一个）
     */
    public function getInterfaceData(string $period, string $format = 'table', ?string $iface = null)
    {
        $iface = $iface ?: ($this->getInterfaces()[0] ?? null);
        if ($iface === null) {
            return $this->formatEmpty($format, 'No interfaces found or vnStat DB is empty.');
        }

        $ifaceData = $this->findInterface($iface);
        if ($ifaceData === null) {
            return $this->formatEmpty($format, "Interface {$iface} not found in vnStat.");
        }

        $traffic = $ifaceData['traffic'] ?? [];
        $key = match (strtolower($period)) {
            'hourly', 'hour', 'h' => 'hour',
            'daily', 'day', 'd' => 'day',
            'monthly', 'month', 'm' => 'month',
            default => 'hour',
        };

        $rows = $traffic[$key] ?? [];
        $normalized = [];

        foreach ($rows as $r) {
            $date = $r['date'] ?? null;
            $time = $r['time'] ?? null;

            $y = (int)($date['year'] ?? 0);
            $mo = (int)($date['month'] ?? 0);
            $d = (int)($date['day'] ?? 0);

            if ($time) {
                $h = (int)($time['hour'] ?? 0);
                $mi = (int)($time['minute'] ?? 0);
                $ts = @mktime($h, $mi, 0, $mo, $d, $y);
            } else {
                $ts = @mktime(0, 0, 0, $mo, $d, $y);
            }

            $rx = (int)($r['rx'] ?? 0);
            $tx = (int)($r['tx'] ?? 0);

            $normalized[] = [
                'timestamp' => $ts ?: 0,
                'label'     => $this->formatLabel($date ?? [], $time ?? []),
                'rx'        => $rx,
                'tx'        => $tx,
                'total'     => $rx + $tx,
            ];
        }

        // PHP 8：必须传入 callable，而不是未定义常量
        usort($normalized, ['vnStat', 'sortingFunction']);

        if ($format === 'array') {
            return $normalized;
        }
        return $this->renderTable($ifaceData, $period, $normalized);
    }

    /** usort 比较函数：按时间戳升序 */
    public static function sortingFunction(array $a, array $b): int
    {
        return ($a['timestamp'] <=> $b['timestamp']);
    }

    private function findInterface(string $id): ?array
    {
        if (!isset($this->vnstatData['interfaces']) || !is_array($this->vnstatData['interfaces'])) {
            return null;
        }
        foreach ($this->vnstatData['interfaces'] as $iface) {
            $iid = $iface['id'] ?? ($iface['name'] ?? null);
            if ($iid === $id) {
                return $iface;
            }
        }
        return null;
    }

    private function formatLabel(array $date, array $time): string
    {
        $y = (int)($date['year'] ?? 0);
        $mo = (int)($date['month'] ?? 0);
        $d = (int)($date['day'] ?? 0);

        if (!empty($time)) {
            $h = (int)($time['hour'] ?? 0);
            $mi = (int)($time['minute'] ?? 0);
            return sprintf('%04d-%02d-%02d %02d:%02d', $y, $mo, $d, $h, $mi);
        }
        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
    }

    private function bytesToHuman(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $val = (float)$bytes;
        while ($val >= 1024 && $i < count($units) - 1) {
            $val /= 1024;
            $i++;
        }
        return sprintf('%.2f %s', $val, $units[$i]);
    }

    private function renderTable(array $ifaceData, string $period, array $rows): string
    {
        $ifaceId = htmlspecialchars($ifaceData['id'] ?? ($ifaceData['name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $html = '<table class="table table-sm table-striped"><thead><tr>';
        $html .= '<th>#</th><th>Time</th><th>RX</th><th>TX</th><th>Total</th></tr></thead><tbody>';

        $i = 1;
        foreach ($rows as $r) {
            $html .= '<tr>'
                . '<td>' . ($i++) . '</td>'
                . '<td>' . htmlspecialchars($r['label'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . $this->bytesToHuman((int)$r['rx']) . '</td>'
                . '<td>' . $this->bytesToHuman((int)$r['tx']) . '</td>'
                . '<td>' . $this->bytesToHuman((int)$r['total']) . '</td>'
                . '</tr>';
        }
        if ($i === 1) {
            $html .= '<tr><td colspan="5"><em>No data yet.</em></td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    private function formatEmpty(string $format, string $msg)
    {
        if ($format === 'array') {
            return [];
        }
        return '<div class="alert alert-warning">' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</div>';
    }
}
