const regTrendGauge = {
    dom: null,
    theme: 'ebis-a',
    render: 'svg',
    chart: null,
    data: [
        {
          value: 100,
          name: 'EBIS Profitability',
          title: {
            show: false,
            offsetCenter: ['0%', '0%']
          },
          detail: {
            show: false,
            offsetCenter: ['0%', '0%']
          }
        }
    ],
    reloadData: function(){
        // let oneDay = 24 * 3600 * 1000;
        // this.data = [];
        this.data[0].value = Math.round(Math.random() * 100);
        // console.log(this.data);
        document.getElementById('chart-reg-label1-gauge').textContent = this.data[0].value+ "%";
    },
    option: {
        grid: {
            show: false,
            containLabel: false,
            left: 0,
            top: 0,
            right: 0,
            bottom: 0
        },
        toolbox: {
            feature: {
                // dataZoom: {
                //     yAxisIndex: 'none'
                // },
                // restore: {},
                saveAsImage: {},
                // dataView: {},
                // magicType: {
                //     type: ['line', 'bar']
                // }
            }
        },
        series: [
          {
            type: 'gauge',
            anchor: {
              show: false,
              showAbove: false,
              size: 18,
              itemStyle: {
                color: '#ffffff'
              }
            },
            pointer: {
              icon: 'path://M2.9,0.7L2.9,0.7c1.4,0,2.6,1.2,2.6,2.6v115c0,1.4-1.2,2.6-2.6,2.6l0,0c-1.4,0-2.6-1.2-2.6-2.6V3.3C0.3,1.9,1.4,0.7,2.9,0.7z',
              width: 8,
              length: '80%',
              offsetCenter: [0, '8%'],
              show: false
            },
            axisTick: {
              length: 10,
              lineStyle: {
                color: 'auto',
                width: 10
              },
              show: false
            },
            splitLine: {
              length: 20,
              lineStyle: {
                color: 'auto',
                width: 5
              },
              show: false
            },
            progress: {
              show: true,
              overlap: true,
              roundCap: true,
              width: 20,
              itemStyle: {
                color: {
                  type: 'linear',
                  x: 0,
                  y: 0,
                  x2: 1,
                  y2: 1,
                  colorStops: [
                    {
                      offset: 0,
                      color: '#5dabd8' // color at 0%
                    },
                    // {
                    //     offset: 0.05, color: '#5dabd8' // color at 0%
                    // },
                    // {
                    //     offset: 0.1, color: '#6687f0' // color at 100%
                    // },
                    // {
                    //     offset: 0.45, color: '#53d4b0' // color at 100%
                    // },
                    {
                      offset: 0.5,
                      color: '#53d4b0' // color at 100%
                    },
                    // {
                    //     offset: 0.55, color: '#53d4b0' // color at 100%
                    // },
                    // {
                    //     offset: 0.75, color: '#6687f0' // color at 100%
                    // },
                    // {
                    //     offset: 0.95, color: '#5dabd8' // color at 100%
                    // },
                    {
                      offset: 1,
                      color: '#5dabd8' // color at 100%
                    }
                  ],
                  global: false // default is false
                }
              }
            },
            axisLabel: {
              show: false
            },
            axisLine: {
              roundCap: true,
              lineStyle: {
                width: 20
              }
            },
            data: this.data,
            title: {
              fontSize: 14
            },
            detail: {
              // width: 40,
              // height: 14,
              // fontSize: 14,
              // color: '#fff',
              // backgroundColor: 'auto',
              // borderRadius: 3,
              formatter: '{value}%'
            }
          }
        ]
    },
    initChart: function (load = false) {
        this.dom = document.getElementById('chart-reg-trend-gauge');
        this.chart = echarts.init(this.dom, this.theme, {
            renderer: this.render
        });
        // console.log(this);
        if (load) this.reloadChart();
        const me = this;
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

const witelGauge = {
    dom: {},
    theme: 'ebis-a',
    render: 'svg',
    name:{},
    idx:{},
    chart: {},
    data: {},
    data_default: [
        {
          value: 100,
          name: 'Witel Profitability',
          title: {
            show: false,
            fontWeight: 'bold',
            fontSize: 12,
            offsetCenter: ['0%', '110%']
          },
          detail: {
            show: false,
            offsetCenter: ['0%', '0%']
          }
        }
    ],
    reloadData: function(idx){
        // let oneDay = 24 * 3600 * 1000;
        // this.data = [];
        // console.log([{...this.data_default}]);
        this.data['witel'+idx] = [...this.data_default];
        // console.log(this.data['witel'+idx]);
        this.data['witel'+idx][0].value = Math.round(Math.random() * 100);
        this.data['witel'+idx][0].name = this.name[idx];
        let _idx = this.idx[idx];
        if(_idx>7)_idx = _idx%7;
        this.data['witel'+idx][0].title.color = _color[_idx];
        // console.log(this.data['witel'+idx]);
        document.getElementById('chart-witel'+idx+'-label1-gauge').textContent = this.data['witel'+idx][0].value;
    },
    option: {},
    option_default: {
        grid: {
            show: false,
            containLabel: false,
            left: 0,
            top: 0,
            right: 0,
            bottom: 0
        },
        toolbox: {
            feature: {
                // dataZoom: {
                //     yAxisIndex: 'none'
                // },
                // restore: {},
                saveAsImage: {},
                // dataView: {},
                // magicType: {
                //     type: ['line', 'bar']
                // }
            }
        },
        series: [
          {
            type: 'gauge',
            anchor: {
              show: false,
              showAbove: false,
              size: 18,
              itemStyle: {
                color: '#ffffff'
              }
            },
            pointer: {
              icon: 'path://M2.9,0.7L2.9,0.7c1.4,0,2.6,1.2,2.6,2.6v115c0,1.4-1.2,2.6-2.6,2.6l0,0c-1.4,0-2.6-1.2-2.6-2.6V3.3C0.3,1.9,1.4,0.7,2.9,0.7z',
              width: 8,
              length: '80%',
              offsetCenter: [0, '8%'],
              show: false
            },
            axisTick: {
              length: 10,
              lineStyle: {
                color: 'auto',
                width: 10
              },
              show: false
            },
            splitLine: {
              length: 20,
              lineStyle: {
                color: 'auto',
                width: 5
              },
              show: false
            },
            progress: {
              show: true,
              overlap: true,
              roundCap: true,
              width: 10,
              itemStyle: {
                color: {
                  type: 'linear',
                  x: 0,
                  y: 0,
                  x2: 1,
                  y2: 1,
                  colorStops: [
                    {
                      offset: 0,
                      color: '#5dabd8' // color at 0%
                    },
                    // {
                    //     offset: 0.05, color: '#5dabd8' // color at 0%
                    // },
                    // {
                    //     offset: 0.1, color: '#6687f0' // color at 100%
                    // },
                    // {
                    //     offset: 0.45, color: '#53d4b0' // color at 100%
                    // },
                    {
                      offset: 0.5,
                      color: '#53d4b0' // color at 100%
                    },
                    // {
                    //     offset: 0.55, color: '#53d4b0' // color at 100%
                    // },
                    // {
                    //     offset: 0.75, color: '#6687f0' // color at 100%
                    // },
                    // {
                    //     offset: 0.95, color: '#5dabd8' // color at 100%
                    // },
                    {
                      offset: 1,
                      color: '#5dabd8' // color at 100%
                    }
                  ],
                  global: false // default is false
                }
              }
            },
            axisLabel: {
              show: false
            },
            axisLine: {
              roundCap: true,
              lineStyle: {
                width: 10
              }
            },
            data: [],
            title: {
              fontSize: 14
            },
            detail: {
              // width: 40,
              // height: 14,
              // fontSize: 14,
              // color: '#fff',
              // backgroundColor: 'auto',
              // borderRadius: 3,
              formatter: '{value}%'
            }
          }
        ]
    },
    initChart: function (load = false) {
      let _idx = 0;
      for (const key in _witel) {
        let idx = _witel[key];
        this.idx[idx] = _idx;
        this.name[idx] = key;
        // console.log(idx);
        this.dom['witel'+idx] = document.getElementById('chart-witel'+idx+'-trend-gauge');
        // console.log(this.dom['witel'+idx]);
        if(this.dom['witel'+idx]!==undefined && this.dom['witel'+idx]!==null){
            this.option['witel'+idx] = {...this.option_default};
            let index = _idx;
            if(_idx>7)index = _idx%7;
            this.option['witel'+idx].series[0].progress.itemStyle.color = _color[index];
            document.getElementById('chart-witel'+idx+'-label2-gauge').style.color = _color[index];
            this.chart['witel'+idx] = echarts.init(this.dom['witel'+idx], this.theme, {
                renderer: this.render
            });
            if (load) this.reloadChart(idx);
            const me = this;
            window.addEventListener("resize", function() {
                me.chart['witel'+idx].resize();
            });
            _idx++;
        }
      }
      // console.log(this)
    },
    reloadChart: function (idx,opt = null) {
        if (opt !== null) {
            this.option['witel'+idx] = opt;
        }
        this.reloadData(idx);
        // console.log(this.data);
        this.option['witel'+idx].series[0].data = this.data['witel'+idx];
        // console.log(this.option['witel'+idx]);
        this.chart['witel'+idx].setOption(this.option['witel'+idx]);
        this.chart['witel'+idx].resize();
    }
};

const regGauge = {
    dom: {},
    theme: 'ebis-a',
    render: 'svg',
    chart: {},
    data: {},
    data_default: [
        {
          value: 100,
          name: 'Reg Profitability',
          title: {
            show: false,
            fontWeight: 'bold',
            fontSize: 18,
            offsetCenter: ['0%', '110%']
          },
          detail: {
            show: false,
            offsetCenter: ['0%', '0%']
          }
        }
    ],
    reloadData: function(idx){
        // let oneDay = 24 * 3600 * 1000;
        // this.data = [];
        // console.log([{...this.data_default}]);
        this.data['reg'+idx] = [...this.data_default];
        // console.log(this.data['reg'+idx]);
        this.data['reg'+idx][0].value = Math.round(Math.random() * 100);
        this.data['reg'+idx][0].name = "Regional " + idx;
        this.data['reg'+idx][0].title.color = _color[idx];
        // console.log(this.data['reg'+idx]);
        document.getElementById('chart-reg'+idx+'-label1-gauge').textContent = this.data['reg'+idx][0].value;
    },
    option: {},
    option_default: {
        grid: {
            show: false,
            containLabel: false,
            left: 0,
            top: 0,
            right: 0,
            bottom: 0
        },
        toolbox: {
            feature: {
                // dataZoom: {
                //     yAxisIndex: 'none'
                // },
                // restore: {},
                saveAsImage: {},
                // dataView: {},
                // magicType: {
                //     type: ['line', 'bar']
                // }
            }
        },
        series: [
          {
            type: 'gauge',
            anchor: {
              show: false,
              showAbove: false,
              size: 18,
              itemStyle: {
                color: '#ffffff'
              }
            },
            pointer: {
              icon: 'path://M2.9,0.7L2.9,0.7c1.4,0,2.6,1.2,2.6,2.6v115c0,1.4-1.2,2.6-2.6,2.6l0,0c-1.4,0-2.6-1.2-2.6-2.6V3.3C0.3,1.9,1.4,0.7,2.9,0.7z',
              width: 8,
              length: '80%',
              offsetCenter: [0, '8%'],
              show: false
            },
            axisTick: {
              length: 10,
              lineStyle: {
                color: 'auto',
                width: 10
              },
              show: false
            },
            splitLine: {
              length: 20,
              lineStyle: {
                color: 'auto',
                width: 5
              },
              show: false
            },
            progress: {
              show: true,
              overlap: true,
              roundCap: true,
              width: 10,
              itemStyle: {
                color: {
                  type: 'linear',
                  x: 0,
                  y: 0,
                  x2: 1,
                  y2: 1,
                  colorStops: [
                    {
                      offset: 0,
                      color: '#5dabd8' // color at 0%
                    },
                    // {
                    //     offset: 0.05, color: '#5dabd8' // color at 0%
                    // },
                    // {
                    //     offset: 0.1, color: '#6687f0' // color at 100%
                    // },
                    // {
                    //     offset: 0.45, color: '#53d4b0' // color at 100%
                    // },
                    {
                      offset: 0.5,
                      color: '#53d4b0' // color at 100%
                    },
                    // {
                    //     offset: 0.55, color: '#53d4b0' // color at 100%
                    // },
                    // {
                    //     offset: 0.75, color: '#6687f0' // color at 100%
                    // },
                    // {
                    //     offset: 0.95, color: '#5dabd8' // color at 100%
                    // },
                    {
                      offset: 1,
                      color: '#5dabd8' // color at 100%
                    }
                  ],
                  global: false // default is false
                }
              }
            },
            axisLabel: {
              show: false
            },
            axisLine: {
              roundCap: true,
              lineStyle: {
                width: 10
              }
            },
            data: [],
            title: {
              fontSize: 14
            },
            detail: {
              // width: 40,
              // height: 14,
              // fontSize: 14,
              // color: '#fff',
              // backgroundColor: 'auto',
              // borderRadius: 3,
              formatter: '{value}%'
            }
          }
        ]
    },
    initChart: function (load = false) {
        for (let idx = 1; idx < 8; idx++) {
            this.dom['reg'+idx] = document.getElementById('chart-reg'+idx+'-trend-gauge');
            // console.log(this.dom['reg'+idx]);
            if(this.dom['reg'+idx]!==undefined && this.dom['reg'+idx]!==null){
                this.option['reg'+idx] = {...this.option_default};
                this.option['reg'+idx].series[0].progress.itemStyle.color = _color[idx];
                document.getElementById('chart-reg'+idx+'-label2-gauge').style.color = _color[idx];
                this.chart['reg'+idx] = echarts.init(this.dom['reg'+idx], this.theme, {
                    renderer: this.render
                });
                if (load) this.reloadChart(idx);
                const me = this;
                window.addEventListener("resize", function() {
                    me.chart['reg'+idx].resize();
                });
            }
        }
        // console.log(this)
    },
    reloadChart: function (idx,opt = null) {
        if (opt !== null) {
            this.option['reg'+idx] = opt;
        }
        this.reloadData(idx);
        // console.log(this.data);
        this.option['reg'+idx].series[0].data = this.data['reg'+idx];
        // console.log(this.option['reg'+idx]);
        this.chart['reg'+idx].setOption(this.option['reg'+idx]);
        this.chart['reg'+idx].resize();
    }
};