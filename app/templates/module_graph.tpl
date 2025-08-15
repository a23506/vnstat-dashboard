<div class="container">
  <ul class="nav nav-tabs" id="graphTab" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" id="fivemin-graph-tab" data-toggle="tab" href="#fivemin-graph" role="tab" aria-controls="fivemin-graph" aria-selected="true">5 分钟</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="hourly-graph-tab" data-toggle="tab" href="#hourly-graph" role="tab" aria-controls="hourly-graph" aria-selected="false">按小时</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="daily-graph-tab" data-toggle="tab" href="#daily-graph" role="tab" aria-controls="daily-graph" aria-selected="false">按天</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="monthly-graph-tab" data-toggle="tab" href="#monthly-graph" role="tab" aria-controls="monthly-graph" aria-selected="false">按月</a>
    </li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active pt-3" id="fivemin-graph" role="tabpanel" aria-labelledby="fivemin-graph-tab">
      <div id="fiveMinNetworkTrafficGraph" class="chart-wrap"></div>
    </div>
    <div class="tab-pane fade pt-3" id="hourly-graph" role="tabpanel" aria-labelledby="hourly-graph-tab">
      <div id="hourlyNetworkTrafficGraph" class="chart-wrap"></div>
    </div>
    <div class="tab-pane fade pt-3" id="daily-graph" role="tabpanel" aria-labelledby="daily-graph-tab">
      <div id="dailyNetworkTrafficGraph" class="chart-wrap"></div>
    </div>
    <div class="tab-pane fade pt-3" id="monthly-graph" role="tabpanel" aria-labelledby="monthly-graph-tab">
      <div id="monthlyNetworkTrafficGraph" class="chart-wrap"></div>
    </div>
  </div>
</div>
