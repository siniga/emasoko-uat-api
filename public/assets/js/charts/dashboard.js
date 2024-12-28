
const handleBarClick = ({ clientX, clientY }, chartContext, {dataPointIndex}) => {
    //console.log("== data points ==");
    //console.log(dataPointIndex);
 };


//pending and approved mandate value
$.getJSON('api/dashboard/mandate-values', (data) => {
    //console.log("== data ==")
    //console.log(data)

    if (data.error == false) {
        var arr_month = [];
        var arr_total = [];
        var arr_approved = [];
        var arr_pending = [];
        var arr_rejected = [];
       
        for (k = 0; k < data.chart.length; k++) {
            arr_month.push(data.chart[k].month);
            arr_total.push(data.chart[k].total);
            arr_approved.push(data.chart[k].approved);
            arr_pending.push(data.chart[k].pending); 
            arr_rejected.push(data.chart[k].rejected);  
        }

        var options = {
            series: [
                {
                    name: "All",
                    data: arr_total
                },
                {
                    name: "Approved",
                    data: arr_approved
                },
                {
                    name: "Pending",
                    data: arr_pending
                },
                {
                    name: "Failed",
                    data: arr_rejected
                },
            ],
            chart: {
                type: 'bar',
                height: 350,
                events: {
                    click: handleBarClick,
                },
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: false,
                    columnWidth: '75%',
                    endingShape: 'rounded',
                    dataLabels: {
                        position: 'bottom'
                    },
                }
            },
            dataLabels: {
                enabled: !1
            },
            stroke: {
                show: true,
                curve: "smooth",
                width: 3,
                colors: ['transparent']
            },
            fill: {
                opacity: 1
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return value.toLocaleString();
                    }
                },
            },
            xaxis: {
                labels: {
                    show: true,
                    rotate: -45,
                    rotateAlways: false,
                },
                crosshair: true,
                categories: arr_month,
            },
            colors: ["#1565C0", "#66BB6A", "#F44336", "#C62828"]
        };

        //render chart  
        var chart = new ApexCharts(document.querySelector("#mandate-value-chart"), options);
        chart.render();
    }
});

//select year


//pending and approved mandate percentage
$.getJSON('api/dashboard/mandate-percentages', (data) => {
    if (data.error == false) {
        // console.log("== percent data ==");
        // console.log(data);

        //charts
        var options = {
            series: [data.pending, data.approved, data.rejected],
            chart: {
                type: "donut",
                height: 340,
            },
            labels: ["Pending", "Approved", "Failed"],
            colors: ["#F44336", "#66BB6A", "#B71C1C"],
            legend: {
                show: !1
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: "70%"
                    }
                }
            }
        };

        //render chart
        var chart = new ApexCharts(document.querySelector("#consent-rate-chart"), options);
        chart.render()
    }
});

//expected and collected payment mandates
$.getJSON('api/dashboard/collected-mandate', (data) => {
    //console.log("== data ==")
    //console.log(data)

    if (data.error == false) {
        var arr_month = [];
        var arr_total = [];
        var arr_paid = [];
        
        for (k = 0; k < data.chart.length; k++) {
            arr_month.push(data.chart[k].month);
            arr_total.push(data.chart[k].total);
            arr_paid.push(data.chart[k].paid);
        }

        var options = {
            series: [
                {
                    name: "Expected",
                    data: arr_total
                },
                {
                    name: "Collected",
                    data: arr_paid
                },
            ],
            chart: {
                type: 'bar',
                height: 350,
                events: {
                    click: handleBarClick,
                },
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: false,
                    columnWidth: '75%',
                    endingShape: 'rounded',
                    dataLabels: {
                        position: 'bottom'
                    },
                }
            },
            dataLabels: {
                enabled: !1
            },
            stroke: {
                show: true,
                curve: "smooth",
                width: 3,
                colors: ['transparent']
            },
            fill: {
                opacity: 1
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return value.toLocaleString();
                    }
                },
            },
            xaxis: {
                labels: {
                    show: true,
                    rotate: -45,
                    rotateAlways: false,
                },
                crosshair: true,
                categories: arr_month,
            },
            colors: ["#01579B", "#66BB6A"]
        };

        //render chart  
        var chart = new ApexCharts(document.querySelector("#collected-mandate-value-chart"), options);
        chart.render();
    }
});

//pending and approved by payment channel
$.getJSON('api/dashboard/mandate-channel', (data) => {
    if (data.error == false) {

        var arr_channels = [];
        var arr_approved = [];
        var arr_pending = [];
        var arr_rejected = [];

        for (k = 0; k < data.chart.length; k++) {
            arr_channels.push(data.chart[k].name);
            arr_approved.push(data.chart[k].approved);
            arr_pending.push(data.chart[k].pending);
            arr_rejected.push(data.chart[k].rejected)   
        }

        //charts
        var options = {
            series: [{
                name: "Approved",
                data: arr_approved
            }, {
                name: "Pending",
                data: arr_pending
            },
            {
                name: "Failed",
                data: arr_rejected
            }],
            chart: {
                type: 'bar',
                height: 320,
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: true,
                    dataLabels: {
                        position: 'bottom'
                    },
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: arr_channels,
            },
            labels: ["Approved", "Pending", "Failed"],
            colors: ["#66BB6A", "#F44336", "#C62828"],
        };

        //render chart  
        var chart = new ApexCharts(document.querySelector("#mandate-channel-chart"), options);
        chart.render();
    }
});

//on CLICK 
$('#select-month').on('change', function (e) {
    e.preventDefault;

    //variables
    var monthName = $(this).val();

    $.getJSON('api/dashboard/mandate-channel', { month: monthName }, (data) => {
        if (data.error == false) {

            var arr_filter_channels = [];
            var arr_filter_approved = [];
            var arr_filter_pending = [];
            var arr_filter_rejected = [];

            for (k = 0; k < data.chart.length; k++) {
                arr_filter_channels.push(data.chart[k].name);
                arr_filter_approved.push(data.chart[k].approved);
                arr_filter_pending.push(data.chart[k].pending);
                arr_filter_rejected.push(data.chart[k].rejected)   
            }

            //charts
            var options = {
                series: [{
                    name: "Approved",
                    data: arr_filter_approved
                }, {
                    name: "Pending",
                    data: arr_filter_pending
                },
                {
                    name: "Failed",
                    data: arr_filter_rejected
                }],
                chart: {
                    type: 'bar',
                    height: 320,
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: true,
                        dataLabels: {
                            position: 'bottom'
                        },
                    }
                },
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: arr_filter_channels,
                },
                labels: ["Approved", "Pending", "Failed"],
                colors: ["#66BB6A", "#F44336", "#C62828"],
            };

            //clear the chart
            $("#mandate-channel-chart").html("");

            //render chart
            var chart = new ApexCharts(document.querySelector("#mandate-channel-chart"), options);
            chart.render();
        }
    });

});
