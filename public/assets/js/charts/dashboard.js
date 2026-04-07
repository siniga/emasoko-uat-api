const handleBarClick = (
    { clientX, clientY },
    chartContext,
    { dataPointIndex }
) => {
    //console.log("== data points ==");
    //console.log(dataPointIndex);
};

const renderMandatesPostedVsDeductedChart = (selector, data) => {
    if (!document.querySelector(selector)) {
        return;
    }

    const arr_month = [];
    const arr_paid = [];
    const arr_gap = [];

    for (let k = 0; k < data.chart.length; k++) {
        arr_month.push(data.chart[k].month);
        arr_paid.push(data.chart[k].paid);
        arr_gap.push(Math.max(data.chart[k].total - data.chart[k].paid, 0));
    }

    const options = {
        series: [
            {
                name: "Deducted (collected)",
                data: arr_paid,
            },
            {
                name: "Gap (not collected)",
                data: arr_gap,
            },
        ],
        chart: {
            type: "bar",
            height: 360,
            stacked: true,
            toolbar: {
                show: false,
            },
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: false,
                columnWidth: "58%",
            },
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            show: false,
        },
        fill: {
            opacity: 1,
        },
        legend: {
            position: "top",
            horizontalAlign: "left",
        },
        yaxis: {
            labels: {
                formatter: function (value) {
                    return value.toLocaleString();
                },
            },
        },
        xaxis: {
            categories: arr_month,
            labels: {
                rotate: -35,
            },
        },
        colors: ["#2E7D32", "#F8BBD0"],
    };

    $(selector).html("");
    const chart = new ApexCharts(document.querySelector(selector), options);
    chart.render();
};

const fetchMandatesPostedVsDeducted = (year) => {
    $.getJSON("charts/collected-mandate", { year: year, channel: channel }, (data) => {
        if (data.error == false) {
            renderMandatesPostedVsDeductedChart("#mandates-posted-vs-deducted-chart", data);
        }
    });
};

//Filters
const filters = window.DASHBOARD_FILTERS || {};
const channel = filters.channel;
console.log('Dashboard Channel:', channel);

//Payment mandate value
$.getJSON("charts/mandate-values", { channel: channel }, (data) => {
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
                    data: arr_total,
                },
                {
                    name: "Approved",
                    data: arr_approved,
                },
                {
                    name: "Pending",
                    data: arr_pending,
                },
                {
                    name: "Failed",
                    data: arr_rejected,
                },
            ],
            chart: {
                type: "bar",
                height: 350,
                events: {
                    click: handleBarClick,
                },
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: false,
                    columnWidth: "75%",
                    endingShape: "rounded",
                    dataLabels: {
                        position: "bottom",
                    },
                },
            },
            dataLabels: {
                enabled: !1,
            },
            stroke: {
                show: true,
                curve: "smooth",
                width: 3,
                colors: ["transparent"],
            },
            fill: {
                opacity: 1,
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return value.toLocaleString();
                    },
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
            colors: ["#1565C0", "#66BB6A", "#F44336", "#C62828"],
        };

        //render chart
        var chart = new ApexCharts(
            document.querySelector("#mandate-value-chart"),
            options
        );
        chart.render();
    }
});

//Select Year => pending mandate value
$("#mandate-value-select-year").on("change", function (e) {
    e.preventDefault;

    //variables
    var yearName = $(this).val();

    $.getJSON("charts/mandate-values", { year: yearName, channel: channel }, (data) => {
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
                        data: arr_total,
                    },
                    {
                        name: "Approved",
                        data: arr_approved,
                    },
                    {
                        name: "Pending",
                        data: arr_pending,
                    },
                    {
                        name: "Failed",
                        data: arr_rejected,
                    },
                ],
                chart: {
                    type: "bar",
                    height: 350,
                    events: {
                        click: handleBarClick,
                    },
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: false,
                        columnWidth: "75%",
                        endingShape: "rounded",
                        dataLabels: {
                            position: "bottom",
                        },
                    },
                },
                dataLabels: {
                    enabled: !1,
                },
                stroke: {
                    show: true,
                    curve: "smooth",
                    width: 3,
                    colors: ["transparent"],
                },
                fill: {
                    opacity: 1,
                },
                yaxis: {
                    labels: {
                        formatter: function (value) {
                            return value.toLocaleString();
                        },
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
                colors: ["#1565C0", "#66BB6A", "#F44336", "#C62828"],
            };

            //clear the chart
            $("#mandate-value-chart").html("");

            //render chart
            var chart = new ApexCharts(
                document.querySelector("#mandate-value-chart"),
                options
            );
            chart.render();
        }
    });
});

//Payment mandate percentage
$.getJSON("charts/mandate-percentages", { channel: channel }, (data) => {
    if (data.error == false) {
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
                show: !1,
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: "70%",
                    },
                },
            },
        };

        //render chart
        var chart = new ApexCharts(
            document.querySelector("#consent-rate-chart"),
            options
        );
        chart.render();
    }
});

//Expected and collected Mandates
$.getJSON("charts/collected-mandate", { channel: channel }, (data) => {
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
                    data: arr_total,
                },
                {
                    name: "Collected",
                    data: arr_paid,
                },
            ],
            chart: {
                type: "bar",
                height: 350,
                events: {
                    click: handleBarClick,
                },
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: false,
                    columnWidth: "75%",
                    endingShape: "rounded",
                    dataLabels: {
                        position: "bottom",
                    },
                },
            },
            dataLabels: {
                enabled: !1,
            },
            stroke: {
                show: true,
                curve: "smooth",
                width: 3,
                colors: ["transparent"],
            },
            fill: {
                opacity: 1,
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return value.toLocaleString();
                    },
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
            colors: ["#01579B", "#66BB6A"],
        };

        //render chart
        var chart = new ApexCharts(
            document.querySelector("#collected-mandate-value-chart"),
            options
        );
        chart.render();
    }
});

//Select Year => expected and collected mandate
$("#collected-mandate-value-select-year").on("change", function (e) {
    e.preventDefault;

    //variables
    var yearName = $(this).val();

    $.getJSON("charts/collected-mandate", { year: yearName, channel: channel }, (data) => {
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
                        data: arr_total,
                    },
                    {
                        name: "Collected",
                        data: arr_paid,
                    },
                ],
                chart: {
                    type: "bar",
                    height: 350,
                    events: {
                        click: handleBarClick,
                    },
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: false,
                        columnWidth: "75%",
                        endingShape: "rounded",
                        dataLabels: {
                            position: "bottom",
                        },
                    },
                },
                dataLabels: {
                    enabled: !1,
                },
                stroke: {
                    show: true,
                    curve: "smooth",
                    width: 3,
                    colors: ["transparent"],
                },
                fill: {
                    opacity: 1,
                },
                yaxis: {
                    labels: {
                        formatter: function (value) {
                            return value.toLocaleString();
                        },
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
                colors: ["#01579B", "#66BB6A"],
            };

            //clear the chart
            $("#collected-mandate-value-chart").html("");

            //render chart
            var chart = new ApexCharts(
                document.querySelector("#collected-mandate-value-chart"),
                options
            );
            chart.render();
        }
    });
});

fetchMandatesPostedVsDeducted(filters.year || "");

$("#mandates-posted-vs-deducted-select-year").on("change", function (e) {
    e.preventDefault;
    var yearName = $(this).val();
    fetchMandatesPostedVsDeducted(yearName);
});

//Expected and collected Mandates
$.getJSON("charts/weekly-collected-mandate", { channel: channel }, (data) => {
    if (data.error == false) {
        var arr_dates = [];
        var arr_total = [];
        var arr_paid = [];

        for (k = 0; k < data.chart.length; k++) {
            arr_dates.push(data.chart[k].date);
            arr_total.push(data.chart[k].total);
            arr_paid.push(data.chart[k].paid);
        }

        var options = {
            series: [
                {
                    name: "Expected",
                    data: arr_total,
                },
                {
                    name: "Collected",
                    data: arr_paid,
                },
            ],
            chart: {
                type: "bar",
                height: 350,
                events: {
                    click: handleBarClick,
                },
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: false,
                    columnWidth: "75%",
                    endingShape: "rounded",
                    dataLabels: {
                        position: "bottom",
                    },
                },
            },
            dataLabels: {
                enabled: !1,
            },
            stroke: {
                show: true,
                curve: "smooth",
                width: 3,
                colors: ["transparent"],
            },
            fill: {
                opacity: 1,
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return value.toLocaleString();
                    },
                },
            },
            xaxis: {
                labels: {
                    show: true,
                    rotate: -45,
                    rotateAlways: true,
                },
                crosshair: true,
                categories: arr_dates,
            },
            colors: ["#01579B", "#66BB6A"],
        };

        //render chart
        var chart = new ApexCharts(
            document.querySelector("#collected-mandate-value-weekly-chart"),
            options
        );
        chart.render();
    }
});

//change date
$("#collected-mandate-value-weekly-date").on("change", function (e) {
    e.preventDefault;

    //variables
    var currentDate = $(this).val();

    $.getJSON("charts/weekly-collected-mandate", { curr_date: currentDate, channel: channel }, (data) => {
        if (data.error == false) {
            var arr_dates = [];
            var arr_total = [];
            var arr_paid = [];

            for (k = 0; k < data.chart.length; k++) {
                arr_dates.push(data.chart[k].date);
                arr_total.push(data.chart[k].total);
                arr_paid.push(data.chart[k].paid);
            }

            var options = {
                series: [
                    {
                        name: "Expected",
                        data: arr_total,
                    },
                    {
                        name: "Collected",
                        data: arr_paid,
                    },
                ],
                chart: {
                    type: "bar",
                    height: 350,
                    events: {
                        click: handleBarClick,
                    },
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: false,
                        columnWidth: "75%",
                        endingShape: "rounded",
                        dataLabels: {
                            position: "bottom",
                        },
                    },
                },
                dataLabels: {
                    enabled: !1,
                },
                stroke: {
                    show: true,
                    curve: "smooth",
                    width: 3,
                    colors: ["transparent"],
                },
                fill: {
                    opacity: 1,
                },
                yaxis: {
                    labels: {
                        formatter: function (value) {
                            return value.toLocaleString();
                        },
                    },
                },
                xaxis: {
                    labels: {
                        show: true,
                        rotate: -45,
                        rotateAlways: true,
                    },
                    crosshair: true,
                    categories: arr_dates,
                },
                colors: ["#01579B", "#66BB6A"],
            };

            //clear the chart
            $("#collected-mandate-value-weekly-chart").html("");

            //render chart
            var chart = new ApexCharts(
                document.querySelector("#collected-mandate-value-weekly-chart"),
                options
            );
            chart.render();
        }
    });
});

//pending and approved by payment channel
$.getJSON("charts/mandate-channel", { channel: channel }, (data) => {
    if (data.error == false) {
        var arr_channels = [];
        var arr_approved = [];
        var arr_pending = [];
        var arr_rejected = [];

        for (k = 0; k < data.chart.length; k++) {
            arr_channels.push(data.chart[k].name);
            arr_approved.push(data.chart[k].approved);
            arr_pending.push(data.chart[k].pending);
            arr_rejected.push(data.chart[k].rejected);
        }

        //charts
        var options = {
            series: [
                {
                    name: "Approved",
                    data: arr_approved,
                },
                {
                    name: "Pending",
                    data: arr_pending,
                },
                {
                    name: "Failed",
                    data: arr_rejected,
                },
            ],
            chart: {
                type: "bar",
                height: 320,
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: true,
                    dataLabels: {
                        position: "bottom",
                    },
                },
            },
            dataLabels: {
                enabled: false,
            },
            xaxis: {
                categories: arr_channels,
            },
            labels: ["Approved", "Pending", "Failed"],
            colors: ["#66BB6A", "#F44336", "#C62828"],
        };

        //render chart
        var chart = new ApexCharts(
            document.querySelector("#mandate-channel-chart"),
            options
        );
        chart.render();
    }
});

//on CLICK
$("#select-month").on("change", function (e) {
    e.preventDefault;

    //variables
    var monthName = $(this).val();

    $.getJSON("charts/mandate-channel", { month: monthName, channel: channel }, (data) => {
        if (data.error == false) {
            var arr_filter_channels = [];
            var arr_filter_approved = [];
            var arr_filter_pending = [];
            var arr_filter_rejected = [];

            for (k = 0; k < data.chart.length; k++) {
                arr_filter_channels.push(data.chart[k].name);
                arr_filter_approved.push(data.chart[k].approved);
                arr_filter_pending.push(data.chart[k].pending);
                arr_filter_rejected.push(data.chart[k].rejected);
            }

            //charts
            var options = {
                series: [
                    {
                        name: "Approved",
                        data: arr_filter_approved,
                    },
                    {
                        name: "Pending",
                        data: arr_filter_pending,
                    },
                    {
                        name: "Failed",
                        data: arr_filter_rejected,
                    },
                ],
                chart: {
                    type: "bar",
                    height: 320,
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: true,
                        dataLabels: {
                            position: "bottom",
                        },
                    },
                },
                dataLabels: {
                    enabled: false,
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
            var chart = new ApexCharts(
                document.querySelector("#mandate-channel-chart"),
                options
            );
            chart.render();
        }
    });
});
