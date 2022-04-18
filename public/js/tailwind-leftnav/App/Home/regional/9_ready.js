$(()=>{
    regTrendGauge.initChart(true);
    regTrendLine.initChart(true);
    if(_reg!=="nasional")witelGauge.initChart(true);
    if(_reg==="nasional")regGauge.initChart(true);
    yearMonthPicker.initPicker();
});