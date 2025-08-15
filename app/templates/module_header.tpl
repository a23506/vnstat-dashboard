<!doctype html>
<html lang="zh-CN">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>vnStat 仪表盘</title>
    <link rel="icon" href="./assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
      integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <style>
      body { padding-top: 1.2rem; padding-bottom: 2rem; }
      .iface-select { max-width: 280px; }
      img { max-width: 100%; height: auto; } /* 如页面中有图片则自适应 */
      .chart-wrap { min-height: 360px; }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">网络流量（{$current_interface}）</h3>
        <form method="get" class="form-inline">
          <label class="mr-2">接口</label>
          <select class="custom-select iface-select" name="i" onchange="this.form.submit()">
            {foreach from=$interface_list item=value}
              <option value="{$value}" {if $value==$current_interface}selected{/if}>{$value}</option>
            {/foreach}
          </select>
        </form>
      </div>
    </div>
