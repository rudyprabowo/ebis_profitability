const ebisTrendLine = {
    dom: null,
    theme: 'ebis-a',
    render: 'svg',
    chart: null,
    data: [],
    reloadData: function(){
        // let oneDay = 24 * 3600 * 1000;
        this.data = [];
        const d = new Date();
        let current_year = d.getFullYear();
        let current_month = d.getMonth();
        for (let i = 0; i <= current_month; i++) {
            let now = new Date(current_year,i);
            if(i===0){
                const dt = luxon.DateTime.fromJSDate(now, { zone: 'Asia/Jakarta'});
                this.data.push([dt.minus({ months: 1 }).toJSDate(), '-']);
            }
            this.data.push([+now, Math.round(Math.random() * 1000000)]);
            if(i===current_month){
                const dt = luxon.DateTime.fromJSDate(now, { zone: 'Asia/Jakarta'});
                this.data.push([dt.plus({ months: 1 }).toJSDate(), '-']);
            }
        }
    },
    option: {
        xAxis: {
            // type: 'category',
            // data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            type: 'time',
            // boundaryGap: false,
            axisLine: {
                show: false
            },
            splitLine: {
                show: false
            },
            axisLabel: {
                formatter: function(value){
                    //return mement(value).format('MMM YYYY');
                    // return echarts.format.formatTime("MM", value);
                    const date = new Date(value);
                    const yearStart = new Date(value);
                    yearStart.setMonth(0);
                    yearStart.setDate(1);
                    yearStart.setHours(0);
                    yearStart.setMinutes(0);
                    yearStart.setSeconds(0);
                    yearStart.setMilliseconds(0);
                    // console.log(date.getDate());
                    if(date.getDate()===1){
                        const dt = luxon.DateTime.fromJSDate(date, { zone: 'Asia/Jakarta'});
                        // if (date.getTime() === yearStart.getTime()) {
                            return '{year|' + echarts.format.formatTime("yyyy", value) + '}\n'
                                + '{month|' + dt.toFormat('MMM') + '}';
                        // }else {
                        //     return '{month|' + dt.toFormat('MMM') + '}';
                        // }
                    }else{
                        return '';
                    }
                },
                // formatter: '{MMM}',
                // formatter: {
                //     year: '{yearStyle|{yyyy}}\n{monthStyle|{MMM}}',
                //     month: '{monthStyle|{MMM}}'
                // },
                rich: {
                    year: {
                        // Make yearly text more standing out
                        color: '#000',
                        fontWeight: 'bold'
                    },
                    month: {
                        color: '#000'
                    }
                },
                showMinLabel: true,
                showMaxLabel: true,
            },
        },
        yAxis: {
            type: 'value',
            axisTick: {
                show: false
            },
            axisLabel: {
                show: false
            },
            axisLine: {
                show: false
            },
            splitLine: {
                lineStyle: {
                    width: 2,
                }
            },
        },
        tooltip: {
            trigger: "axis",
            className: 'echarts-tooltip',
            formatter: function (params, ticket, callback) {
                // $.get('detail?name=' + params.name, function (content) {
                //     callback(ticket, toHTML(content));
                // });
                // console.log(params);
                // console.log(params[0]);
                if(params[0].data[1]!=='-'){
                    const dt = luxon.DateTime.fromMillis(params[0].data[0]);
                    // return `<h1>${dt.toFormat('MMMM yyyy')}</h1><br>
                    // <h1>${params[0].marker} ${params[0].seriesName}</h1>`;
                    return `<h1 class="text-sm echart-title-time">${dt.toFormat('yyyy MMMM')}</h1>
                    <h1 class="font-medium echart-value-time">${new Intl.NumberFormat(['id']).format(params[0].data[1])}</h1>`;
                }
            },
            axisPointer: {
                type: "shadow"
            }
        },
        grid: {
            show: false,
            containLabel: false,
            left: 30,
            top: 30,
            right: 30,
            bottom: 40
        },
        toolbox: {
            feature: {
                // dataZoom: {
                //     yAxisIndex: 'none'
                // },
                // restore: {},
                saveAsImage: {},
                // dataView: {},
                magicType: {
                    type: ['line', 'bar']
                }
            }
        },
        // dataZoom: [
        //     {
        //     type: 'inside',
        //     start: 0,
        //     end: 12
        //     },
        //     {
        //     start: 0,
        //     end: 12
        //     }
        // ],
        series: [{
            // data: [150, 230, 224, 218, 135, 147, 260],
            label: {
                show: true,
                position: 'top',
                textStyle: {
                  fontSize: 15
                }
            },
            name: 'EBIS Profitability',
            type: 'line',
            // smooth: true,
            // symbol: 'none',
            // areaStyle: {},
            data: this.data
        }]
    },
    initChart: function (load = false) {
        this.dom = document.getElementById('chart-ebis-trend-line');
        this.chart = echarts.init(this.dom, this.theme, {
            renderer: this.render
        });
        // console.log(this);
        if (load) this.reloadChart();
        const me = this;
        // window.onresize = function() {
        //     me.chart.resize();
        // };
        window.addEventListener("resize", function() {
            me.chart.resize();
        });
    },
    reloadChart: function (opt = null) {
        if (opt !== null) {
            this.option = opt;
        }
        this.reloadData();
        // console.log(this.data);
        this.option.series[0].data = this.data;
        // console.log(this.option);
        this.chart.setOption(this.option);
        this.chart.resize();
    }
};