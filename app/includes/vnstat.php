<?php
class vnStat {
    protected $executablePath;
    protected $vnstatVersion;
    protected $vnstatJsonVersion;
    protected $vnstatData;

    public function __construct($executablePath) {
        if (!isset($executablePath)) { die('vnstat binary path not set'); }
        $this->executablePath = $executablePath;
        $vnstatStream = @popen("$this->executablePath --json", 'r');
        if (is_resource($vnstatStream)) {
            $streamBuffer = '';
            while (!feof($vnstatStream)) { $streamBuffer .= fgets($vnstatStream); }
            pclose($vnstatStream);
            $this->processVnstatData($streamBuffer);
        } else {
            throw new \Exception('Unable to execute vnstat. Check path/permissions.');
        }
    }

    private function normalizeInterfaces() {
        if (!isset($this->vnstatData['interfaces']) || !is_array($this->vnstatData['interfaces'])) {
            $this->vnstatData['interfaces'] = [];
            return;
        }
        foreach ($this->vnstatData['interfaces'] as &$iface) {
            if (!isset($iface['id']) && isset($iface['name'])) { $iface['id'] = $iface['name']; }
        }
        unset($iface);
    }

    private function processVnstatData($vnstatJson) {
        $decodedJson = json_decode($vnstatJson, true);
        if (json_last_error() != JSON_ERROR_NONE) { throw new \Exception('JSON is invalid'); }
        $this->vnstatData = $decodedJson;
        $this->vnstatVersion = $decodedJson['vnstatversion'] ?? 'unknown';
        $this->vnstatJsonVersion = $decodedJson['jsonversion'] ?? 2;
        $this->normalizeInterfaces();
    }

    public function getVnstatVersion() { return $this->vnstatVersion; }
    public function getVnstatJsonVersion() { return $this->vnstatJsonVersion; }

    public function getInterfaces() {
        if (function_exists('config_interfaces_override')) {
            $over = config_interfaces_override();
            if (is_array($over) && !empty($over)) return $over;
        }
        $vnstatInterfaces = [];
        foreach ($this->vnstatData['interfaces'] as $interface) {
            if (isset($interface['id'])) $vnstatInterfaces[] = $interface['id'];
        }
        return $vnstatInterfaces;
    }

    public function getInterfaceData($timeperiod, $type, $interface) {
        $typeAppend = ($this->vnstatJsonVersion == 1) ? 's' : '';
        $trafficData = [];
        $i = 0;

        $ids = array_column($this->vnstatData['interfaces'], 'id');
        $arrayIndex = array_search($interface, $ids, true);
        if ($arrayIndex === false) { return $trafficData; }
        $traffic = $this->vnstatData['interfaces'][$arrayIndex]['traffic'] ?? [];

        // 5分钟
        if ($timeperiod === 'fiveminute' && isset($traffic['fiveminute']) && is_array($traffic['fiveminute'])) {
            if ($type === 'table') {
                foreach ($traffic['fiveminute'] as $t) {
                    if (!is_array($t)) continue;
                    $i++;
                    $hour = $t['time']['hour'];
                    $min  = $t['time']['minute'];
                    $ts = mktime($hour, $min, 0, $t['date']['month'], $t['date']['day'], $t['date']['year']);
                    $trafficData[$i] = [
                        'label' => date("Y年m月d日 H:i", $ts),
                        'time'  => $ts,
                        'rx'    => formatSize($t['rx'], $this->vnstatJsonVersion),
                        'tx'    => formatSize($t['tx'], $this->vnstatJsonVersion),
                        'total' => formatSize(($t['rx'] + $t['tx']), $this->vnstatJsonVersion),
                    ];
                }
                usort($trafficData, 'sortingFunction');
            } elseif ($type === 'graph') {
                foreach ($traffic['fiveminute'] as $t) {
                    if (!is_array($t)) continue;
                    $i++;
                    $hour = $t['time']['hour'];
                    $min  = $t['time']['minute'];
                    $trafficData[$i] = [
                        'label' => sprintf("Date(%d, %d, %d, %d, %d, %d)",
                                           $t['date']['year'], $t['date']['month']-1, $t['date']['day'],
                                           $hour, $min, 0),
                        'rx'    => kibibytesToBytes($t['rx'], $this->vnstatJsonVersion),
                        'tx'    => kibibytesToBytes($t['tx'], $this->vnstatJsonVersion),
                        'total' => kibibytesToBytes(($t['rx'] + $t['tx']), $this->vnstatJsonVersion),
                    ];
                }
            }
        }

        // 小时
        if ($timeperiod === 'hourly' && isset($traffic['hour' . $typeAppend]) && is_array($traffic['hour' . $typeAppend])) {
            if ($type === 'table') {
                foreach ($traffic['hour' . $typeAppend] as $t) {
                    if (!is_array($t)) continue;
                    $i++;
                    $hour = ($this->vnstatJsonVersion == 1) ? ($t['id']) : ($t['time']['hour']);
                    $ts = mktime($hour, 0, 0, $t['date']['month'], $t['date']['day'], $t['date']['year']);
                    $trafficData[$i] = [
                        'label' => date("Y年m月d日 H:00", $ts),
                        'time'  => $ts,
                        'rx'    => formatSize($t['rx'], $this->vnstatJsonVersion),
                        'tx'    => formatSize($t['tx'], $this->vnstatJsonVersion),
                        'total' => formatSize(($t['rx'] + $t['tx']), $this->vnstatJsonVersion),
                    ];
                }
                usort($trafficData, 'sortingFunction');
            } elseif ($type === 'graph') {
                foreach ($traffic['hour' . $typeAppend] as $t) {
                    if (!is_array($t)) continue;
                    $i++;
                    $hour = ($this->vnstatJsonVersion == 1) ? ($t['id']) : ($t['time']['hour']);
                    $trafficData[$i] = [
                        'label' => sprintf("Date(%d, %d, %d, %d, %d, %d)", $t['date']['year'], $t['date']['month']-1, $t['date']['day'], $hour, 0, 0),
                        'rx'    => kibibytesToBytes($t['rx'], $this->vnstatJsonVersion),
                        'tx'    => kibibytesToBytes($t['tx'], $this->vnstatJsonVersion),
                        'total' => kibibytesToBytes(($t['rx'] + $t['tx']), $this->vnstatJsonVersion),
                    ];
                }
            }
        }

        // 天
        if ($timeperiod === 'daily' && isset($traffic['day' . $typeAppend]) && is_array($traffic['day' . $typeAppend])) {
            if ($type === 'table') {
                foreach ($traffic['day' . $typeAppend] as $t) {
                    if (!is_array($t)) continue;
                    $i++;
                    $ts = mktime(0, 0, 0, $t['date']['month'], $t['date']['day'], $t['date']['year']);
                    $trafficData[$i] = [
                        'label' => date('Y年m月d日', $ts),
                        'rx'    => formatSize($t['rx'], $this->vnstatJsonVersion),
                        'tx'    => formatSize($t['tx'], $this->vnstatJsonVersion),
                        'total' => formatSize(($t['rx'] + $t['tx']), $this->vnstatJsonVersion),
                    ];
                }
            } elseif ($type === 'graph') {
                foreach ($traffic['day' . $typeAppend] as $t) {
                    if (!is_array($t)) continue;
                    $i++;
                    $trafficData[$i] = [
                        'label' => sprintf("Date(%d, %d, %d, %d, %d, %d)", $t['date']['year'], $t['date']['month']-1, $t['date']['day'], 0, 0, 0),
                        'rx'    => kibibytesToBytes($t['rx'], $this->vnstatJsonVersion),
                        'tx'    => kibibytesToBytes($t['tx'], $this->vnstatJsonVersion),
                        'total' => kibibytesToBytes(($t['rx'] + $t['tx']), $this->vnstatJsonVersion),
                    ];
                }
            }
        }

        // 月
        if ($timeperiod === 'monthly' && isset($traffic['month' . $typeAppend]) && is_array($traffic['month' . $typeAppend])) {
            if ($type === 'table') {
                foreach ($traffic['month' . $typeAppend] as $t) {
                    if (!is_array($t)) continue;
                    $i++;
                    $trafficData[$i] = [
                        'label' => date('Y年m月', mktime(0, 0, 0, $t['date']['month'], 10, $t['date']['year'])),
                        'rx'    => formatSize($t['rx'], $this->vnstatJsonVersion),
                        'tx'    => formatSize($t['tx'], $this->vnstatJsonVersion),
                        'total' => formatSize(($t['rx'] + $t['tx']), $this->vnstatJsonVersion),
                    ];
                }
            } elseif ($type === 'graph') {
                foreach ($traffic['month' . $typeAppend] as $t) {
                    if (!is_array($t)) continue;
                    $i++;
                    $trafficData[$i] = [
                        'label' => sprintf("Date(%d, %d, %d, %d, %d, %d)", $t['date']['year'], $t['date']['month']-1, 10, 0, 0, 0),
                        'rx'    => kibibytesToBytes($t['rx'], $this->vnstatJsonVersion),
                        'tx'    => kibibytesToBytes($t['tx'], $this->vnstatJsonVersion),
                        'total' => kibibytesToBytes(($t['rx'] + $t['tx']), $this->vnstatJsonVersion),
                    ];
                }
            }
        }

        // Graph 统一单位
        if ($type === 'graph') {
            $trafficLargestValue  = getLargestValue($trafficData);
            $trafficLargestPrefix = getLargestPrefix($trafficLargestValue);
            foreach ($trafficData as $key => $value) {
                $trafficData[$key]['rx']    = formatBytesTo($value['rx'], $trafficLargestPrefix);
                $trafficData[$key]['tx']    = formatBytesTo($value['tx'], $trafficLargestPrefix);
                $trafficData[$key]['total'] = formatBytesTo($value['total'], $trafficLargestPrefix);
                $trafficData[$key]['delimiter'] = $trafficLargestPrefix;
            }
        }
        return $trafficData;
    }
}
