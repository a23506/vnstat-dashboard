<?php
function getLargestValue($array) {
    return array_reduce($array, function ($a, $b) {
        return $a > $b['total'] ? $a : $b['total'];
    }, 0);
}

function formatSize($bytes, $vnstatJsonVersion) {
    if ($vnstatJsonVersion == 1) { $bytes *= 1024; }
    return formatBytes($bytes);
}

function formatBytes($bytes, $decimals = 2) {
    if ($bytes <= 0) return '0 B';
    $base = log((float)$bytes, 1024);
    $suffixes = ['B','KB','MB','GB','TB','PB','EB','ZB','YB'];
    return round(pow(1024, $base - floor($base)), $decimals) . ' ' . $suffixes[(int)floor($base)];
}

function formatBytesTo($bytes, $delimiter, $decimals = 2) {
    if ($bytes == 0) return '0';
    $sizes = ['B','KB','MB','GB','TB','PB','EB','ZB','YB'];
    $i = array_search($delimiter, $sizes, true);
    if ($i === false) $i = 2; // 默认 MB
    return number_format(($bytes / pow(1024, $i)), $decimals, '.', '');
}

function kibibytesToBytes($kibibytes, $vnstatJsonVersion) {
    return ($vnstatJsonVersion == 1) ? ($kibibytes * 1024) : $kibibytes;
}

function getLargestPrefix($bytes) {
    $units = ['TB', 'GB', 'MB', 'KB', 'B'];
    $scale = 1024 * 1024 * 1024 * 1024;
    $ui = 0;
    while ((($bytes < $scale) && ($scale > 1))) { $ui++; $scale = $scale / 1024; }
    return $units[$ui];
}

function sortingFunction($item1, $item2) {
    if ($item1['time'] == $item2['time']) { return 0; }
    return ($item1['time'] > $item2['time']) ? -1 : 1;
}
