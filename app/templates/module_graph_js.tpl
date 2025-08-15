<script type="text/javascript">
var FMP = "{$fiveMinLargestPrefix|default:'MB'|escape:'javascript'}";
var HLP = "{$hourlyLargestPrefix|default:'MB'|escape:'javascript'}";
var DLP = "{$dailyLargestPrefix|default:'MB'|escape:'javascript'}";
var MLP = "{$monthlyLargestPrefix|default:'MB'|escape:'javascript'}";

{literal}
google.charts.load('current', {'packages':['corechart'], 'language':'zh-CN'});
google.charts.setOnLoadCallback(drawCharts);

function baseOptions(hfmt) {
  return {
    legend: { position: 'bottom' },
    isStacked: true,
    height: 360,
    focusTarget: 'category',
    explorer: { actions: ['dragToZoom', 'rightClickToReset'], axis: 'horizontal' },
    hAxis: { format: hfmt }
  };
}

function drawCharts(){ drawFiveMin(); drawHourly(); drawDaily(); drawMonthly(); }

/** 计算 DataTable X 轴最小/最大（Date 对象） */
function dataDomain(dt){
  var rows = dt.getNumberOfRows();
  if (rows === 0) return null;
  var min = dt.getValue(0, 0);
  var max = dt.getValue(rows - 1, 0);
  if (min > max) { var t = min; min = max; max = t; }
  return {min:min, max:max};
}

/** 根据滚轮缩放（deltaY），生成新的 viewWindow */
function zoomViewWindow(cur, dom, deltaY){
  var min = cur && cur.min ? cur.min : dom.min;
  var max = cur && cur.max ? cur.max : dom.max;
  if (!min || !max) return {min:dom.min, max:dom.max};

  // 缩放比例：上滑放大（0.8），下滑缩小（1.25）
  var factor = (deltaY < 0) ? 0.8 : 1.25;

  var mid = new Date((min.getTime() + max.getTime()) / 2);
  var half = (max.getTime() - min.getTime()) / 2 * factor;
  var newMin = new Date(mid.getTime() - half);
  var newMax = new Date(mid.getTime() + half);

  // 边界限制到数据域
  if (newMin < dom.min) newMin = dom.min;
  if (newMax > dom.max) newMax = dom.max;
  // 最小窗口宽度（避免无限放大至同一时间点）
  var minWidthMs = 60 * 1000; // 1 分钟
  if ((newMax - newMin) < minWidthMs) {
    newMin = new Date(mid.getTime() - minWidthMs/2);
    newMax = new Date(mid.getTime() + minWidthMs/2);
    if (newMin < dom.min) newMin = dom.min;
    if (newMax > dom.max) newMax = dom.max;
  }
  return {min:newMin, max:newMax};
}

/** 绑定滚轮缩放与 Tab/resize 重绘 */
function enableWheelZoom(containerId, chart, data, options, hfmt){
  var dom = dataDomain(data);
  if (!dom) return;
  var current = null; // 当前 viewWindow

  // 初始化时设置初始窗口为全域
  options.hAxis.format = hfmt;
  options.hAxis.viewWindow = { min: dom.min, max: dom.max };

  // 滚轮缩放
  var el = document.getElementById(containerId);
  el.addEventListener('wheel', function(evt){
    evt.preventDefault();
    current = zoomViewWindow(options.hAxis.viewWindow, dom, evt.deltaY);
    options.hAxis.viewWindow = current;
    chart.draw(data, options);
  }, { passive: false });

  // 右键重置（Google Charts explorer 自带重置，但这里也兜底）
  el.addEventListener('contextmenu', function(evt){
    evt.preventDefault();
    current = {min: dom.min, max: dom.max};
    options.hAxis.viewWindow = current;
    chart.draw(data, options);
  });

  return function redraw(){
    // resize / shown.bs.tab 时重绘，保留当前缩放窗口
    chart.draw(data, options);
  };
}

// --- 5 分钟 ---
function drawFiveMin(){
  var data = new google.visualization.DataTable();
  data.addColumn('datetime', '时间');
  data.addColumn('number', '下载 (' + FMP + ')');
  data.addColumn('number', '上传 (' + FMP + ')');
  data.addColumn('number', '总计 (' + FMP + ')');
  data.addRows([
{/literal}
    {foreach from=$fiveMinGraphData item=point}
{literal}      [new {/literal}{$point.label}{literal}, {/literal}{$point.rx}{literal}, {/literal}{$point.tx}{literal}, {/literal}{$point.total}{literal}],{/literal}
    {/foreach}
{literal}  ]);
  var options = baseOptions('yyyy/MM/dd HH:mm');
  var chart = new google.visualization.AreaChart(document.getElementById('fiveMinNetworkTrafficGraph'));
  window.__redraw_fivemin = enableWheelZoom('fiveMinNetworkTrafficGraph', chart, data, options, 'yyyy/MM/dd HH:mm');
  chart.draw(data, options);
}

// --- 小时 ---
function drawHourly(){
  var data = new google.visualization.DataTable();
  data.addColumn('datetime', '时间');
  data.addColumn('number', '下载 (' + HLP + ')');
  data.addColumn('number', '上传 (' + HLP + ')');
  data.addColumn('number', '总计 (' + HLP + ')');
  data.addRows([
{/literal}
    {foreach from=$hourlyGraphData item=point}
{literal}      [new {/literal}{$point.label}{literal}, {/literal}{$point.rx}{literal}, {/literal}{$point.tx}{literal}, {/literal}{$point.total}{literal}],{/literal}
    {/foreach}
{literal}  ]);
  var options = baseOptions('yyyy/MM/dd HH:mm');
  var chart = new google.visualization.AreaChart(document.getElementById('hourlyNetworkTrafficGraph'));
  window.__redraw_hourly = enableWheelZoom('hourlyNetworkTrafficGraph', chart, data, options, 'yyyy/MM/dd HH:mm');
  chart.draw(data, options);
}

// --- 天 ---
function drawDaily(){
  var data = new google.visualization.DataTable();
  data.addColumn('date', '日期');
  data.addColumn('number', '下载 (' + DLP + ')');
  data.addColumn('number', '上传 (' + DLP + ')');
  data.addColumn('number', '总计 (' + DLP + ')');
  data.addRows([
{/literal}
    {foreach from=$dailyGraphData item=point}
{literal}      [new {/literal}{$point.label}{literal}, {/literal}{$point.rx}{literal}, {/literal}{$point.tx}{literal}, {/literal}{$point.total}{literal}],{/literal}
    {/foreach}
{literal}  ]);
  var options = baseOptions('yyyy/MM/dd');
  var chart = new google.visualization.AreaChart(document.getElementById('dailyNetworkTrafficGraph'));
  window.__redraw_daily = enableWheelZoom('dailyNetworkTrafficGraph', chart, data, options, 'yyyy/MM/dd');
  chart.draw(data, options);
}

// --- 月 ---
function drawMonthly(){
  var data = new google.visualization.DataTable();
  data.addColumn('date', '月份');
  data.addColumn('number', '下载 (' + MLP + ')');
  data.addColumn('number', '上传 (' + MLP + ')');
  data.addColumn('number', '总计 (' + MLP + ')');
  data.addRows([
{/literal}
    {foreach from=$monthlyGraphData item=point}
{literal}      [new {/literal}{$point.label}{literal}, {/literal}{$point.rx}{literal}, {/literal}{$point.tx}{literal}, {/literal}{$point.total}{literal}],{/literal}
    {/foreach}
{literal}  ]);
  var options = baseOptions('yyyy年MM月');
  var chart = new google.visualization.AreaChart(document.getElementById('monthlyNetworkTrafficGraph'));
  window.__redraw_monthly = enableWheelZoom('monthlyNetworkTrafficGraph', chart, data, options, 'yyyy年MM月');
  chart.draw(data, options);
}

// --- 隐藏 Tab/窗口变化重绘 ---
(function(){
  function redrawActive(){
    var active = document.querySelector('.tab-pane.active');
    if (!active) return;
    if (active.id === 'fivemin-graph' && window.__redraw_fivemin) window.__redraw_fivemin();
    if (active.id === 'hourly-graph'  && window.__redraw_hourly)  window.__redraw_hourly();
    if (active.id === 'daily-graph'   && window.__redraw_daily)   window.__redraw_daily();
    if (active.id === 'monthly-graph' && window.__redraw_monthly) window.__redraw_monthly();
  }
  if (window.jQuery) {
    jQuery('a[data-toggle="tab"]').on('shown.bs.tab', function(){ setTimeout(redrawActive, 0); });
  }
  window.addEventListener('resize', function(){ setTimeout(redrawActive, 0); });
})();
{/literal}
</script>
