<div class="container mt-4">
  <ul class="nav nav-tabs" id="tableTab" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-5min" role="tab">5 分钟</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-hour" role="tab">小时</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-day" role="tab">天</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-month" role="tab">月</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-top10" role="tab">前 10</a></li>
  </ul>
  <div class="tab-content pt-3">

    <div class="tab-pane fade show active" id="tab-5min" role="tabpanel">
      <table class="table table-sm table-striped">
        <thead><tr><th>时间</th><th>下载</th><th>上传</th><th>总计</th></tr></thead>
        <tbody>
        {foreach from=$fiveMinTableData item=value}
          <tr><td>{$value.label}</td><td>{$value.rx}</td><td>{$value.tx}</td><td>{$value.total}</td></tr>
        {/foreach}
        </tbody>
      </table>
    </div>

    <div class="tab-pane fade" id="tab-hour" role="tabpanel">
      <table class="table table-sm table-striped">
        <thead><tr><th>时间戳</th><th>下载</th><th>上传</th><th>总计</th></tr></thead>
        <tbody>
        {foreach from=$hourlyTableData item=value}
          <tr><td>{$value.label}</td><td>{$value.rx}</td><td>{$value.tx}</td><td>{$value.total}</td></tr>
        {/foreach}
        </tbody>
      </table>
    </div>

    <div class="tab-pane fade" id="tab-day" role="tabpanel">
      <table class="table table-sm table-striped">
        <thead><tr><th>日期</th><th>下载</th><th>上传</th><th>总计</th></tr></thead>
        <tbody>
        {foreach from=$dailyTableData item=value}
          <tr><td>{$value.label}</td><td>{$value.rx}</td><td>{$value.tx}</td><td>{$value.total}</td></tr>
        {/foreach}
        </tbody>
      </table>
    </div>

    <div class="tab-pane fade" id="tab-month" role="tabpanel">
      <table class="table table-sm table-striped">
        <thead><tr><th>月份</th><th>下载</th><th>上传</th><th>总计</th></tr></thead>
        <tbody>
        {foreach from=$monthlyTableData item=value}
          <tr><td>{$value.label}</td><td>{$value.rx}</td><td>{$value.tx}</td><td>{$value.total}</td></tr>
        {/foreach}
        </tbody>
      </table>
    </div>

    <div class="tab-pane fade" id="tab-top10" role="tabpanel">
      <table class="table table-sm table-striped">
        <thead><tr><th>日期</th><th>下载</th><th>上传</th><th>总计</th></tr></thead>
        <tbody>
        {foreach from=$top10TableData item=value}
          <tr><td>{$value.label}</td><td>{$value.rx}</td><td>{$value.tx}</td><td>{$value.total}</td></tr>
        {/foreach}
        </tbody>
      </table>
    </div>

  </div>
</div>
