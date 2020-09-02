<?php
include "../fusioncharts/fusioncharts.php"; ?>

<script type="text/javascript" src="https://cdn.fusioncharts.com/fusioncharts/latest/fusioncharts.js"></script>
<script type="text/javascript" src="https://cdn.fusioncharts.com/fusioncharts/latest/themes/fusioncharts.theme.fusion.js"></script>

<script>

let trendsChart = null;

FusionCharts.ready(function() {
  trendsChart = new FusionCharts({
    id: "trendsChart",
    type: 'realtimecolumn',
    renderAt: 'trendsChartContainer',
    width: '500',
    height: '260',
    dataFormat: 'json',
    dataSource: {
      "chart": {
        "animationDuration": 0,
        "caption": "Factors Affecting Popularity",
        "xaxisname": "",
        "yaxisname": "",
        "numbersuffix": "",
        "theme": "fusion",
        "yAxisMinValue": 0,
        "yAxisMaxValue": 100,
        "bgColor": "#ebffe0",
        "numdisplaysets": "5",
        "showRealTimeValue": "0"
      },
      "categories": [{
        "category": [{
          "label": "Love"
        }, {
          "label": "Weather"
        },{
          "label": "Politics"
        },{
          "label": "Conformity"
        },{
          "label": "Decadence"
        }]
      }],
      "dataset": [{
        "data": [{
            "label": "Love",
          "value": <?php echo json_decode($_SESSION['trend_calculates'])
            ->love; ?>
        },{
            "label": "Weather",
          "value": <?php echo json_decode($_SESSION['trend_calculates'])
            ->weather; ?>
        },{
            "label": "Politics",
          "value": <?php echo json_decode($_SESSION['trend_calculates'])
            ->politics; ?>
        },{
            "label": "Conformity",
          "value": <?php echo json_decode($_SESSION['trend_calculates'])
            ->conformity; ?>
        },{
            "label": "Decadence",
          "value": <?php echo json_decode($_SESSION['trend_calculates'])
            ->decadence; ?>
        }]
      }]
    },
  });

  trendsChart.render();
});

function updateTrendsGraph(XTC){

        let currentChartData = trendsChart.getChartData()
        
        let XTCArray = []

        let keys = ["love", "weather", "politics", "conformity", "decadence"]
        
        keys.forEach(key => {
            let capitalisedKey = key[0].toUpperCase() + key.slice(1).toLowerCase()
            XTCArray.push({"label": capitalisedKey, "value": XTC[key]})
        })

        currentChartData["dataset"][0]["data"] = XTCArray

    trendsChart.setChartData(currentChartData)
}

let salesChart = null;

function makeSalesGraph(initial_data){

  let keys = Object.keys(initial_data)
  let newCategorySet = []
  let salesObject = {
            "seriesname": "Sales",
            "data": []
  }

  let spendingObject = {
            "seriesname": "Spending",
            "data": []
  }

  keys.forEach(key => {
    newCategorySet.push({"label": key.toString()})
    salesObject["data"].push({"value": initial_data[key]["profit"].toString()})
    spendingObject["data"].push({"value": initial_data[key]["costs"].toString()})
  })

  FusionCharts.ready(function() {
    salesChart = new FusionCharts({
      type: 'realtimeline',
      renderAt: 'salesChartContainer',
      width: '500',
      height: '260',
      dataFormat: 'json',
      dataSource: {
        "chart": {
          "theme": "fusion",
          "caption": "Visitors",
          "xAxisName": "",
          "numDisplaySets": "30",
          "setadaptiveymin": "1",
          "setadaptivesymin": "1",
          "labeldisplay": "auto",
          "bgColor": "#ebffe0",
          "showRealTimeValue": "0",
          "yAxisMaxValue": "50",
        },
        "categories": [{
          "category": newCategorySet
        }],
        "dataset": [salesObject, spendingObject],
        "trendlines": [{
          "line": [{
            "startvalue": "0",
            "color": "#62B58F",
            "valueOnRight": "",
          }]
        }]
      }
    }).render();
  });
}

function updateSalesGraph(overall_sales_history){
  
  let day = Object.keys(overall_sales_history).sort((a, b) => b - a)[0].toString()
  let newSalesValue = overall_sales_history[day]['profit']
  let newSpendingValue = overall_sales_history[day]['costs']

  let currentChartData = salesChart.getChartData()
  let salesArray = currentChartData["dataset"][0]["data"]
  let spendingArray = currentChartData["dataset"][1]["data"]
  let categoryArray = currentChartData["categories"][0]["category"]

  let arrays = [salesArray, spendingArray, categoryArray]
  arrays.forEach(array => {if (array.length > 30){array.shift()}})

  salesArray.push({"value": newSalesValue})
  spendingArray.push({"value": newSpendingValue})
  categoryArray.push({"label": day.toString()})

  currentChartData["dataset"][0]["data"] = salesArray
  currentChartData["dataset"][1]["data"] = spendingArray
  currentChartData["categories"][0]["category"] = categoryArray

  salesChart.setChartData(currentChartData)
}

</script>