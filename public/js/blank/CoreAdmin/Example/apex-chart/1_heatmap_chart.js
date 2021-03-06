var _seed = 42;
Math.random = function() {
  _seed = _seed * 16807 % 2147483647;
  return (_seed - 1) / 2147483646;
};

function generateData(baseval, count, yrange) {
  var i = 0;
  var series = [];
  while (i < count) {
    var x = Math.floor(Math.random() * (750 - 1 + 1)) + 1;;
    var y = Math.floor(Math.random() * (yrange.max - yrange.min + 1)) + yrange.min;
    var z = Math.floor(Math.random() * (75 - 15 + 1)) + 15;

    series.push([x, y, z]);
    baseval += 86400000;
    i++;
  }
  return series;
}

var data = [
  {
    name: 'W1',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  },
  {
    name: 'W2',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  },
  {
    name: 'W3',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  },
  {
    name: 'W4',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  },
  {
    name: 'W5',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  },
  {
    name: 'W6',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  },
  {
    name: 'W7',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  },
  {
    name: 'W8',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  },
  {
    name: 'W9',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  },
  {
    name: 'W10',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  },
  {
    name: 'W11',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  },
  {
    name: 'W12',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  },
  {
    name: 'W13',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  },
  {
    name: 'W14',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  },
  {
    name: 'W15',
    data: generateData(8, {
      min: 0,
      max: 90
    })
  }
];

data.reverse();

var colors = ["#F3B415", "#F27036", "#663F59", "#6A6E94", "#4E88B4", "#00A7C6", "#18D8D8", '#A9D794', '#46AF78', '#A93F55', '#8C5E58', '#2176FF', '#33A1FD', '#7A918D', '#BAFF29'];

colors.reverse();

var options = {
  series: data,
  chart: {
  height: 450,
  type: 'heatmap',
},
dataLabels: {
  enabled: false
},
colors: colors,
xaxis: {
  type: 'category',
  categories: ['10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '01:00', '01:30']
},
title: {
  text: 'HeatMap Chart (Different color shades for each series)'
},
grid: {
  padding: {
    right: 20
  }
}
};

var chart = new ApexCharts(document.querySelector("#heatmap_chart"), options);
chart.render();