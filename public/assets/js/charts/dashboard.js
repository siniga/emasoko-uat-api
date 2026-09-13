const handleBarClick = (
    { clientX, clientY },
    chartContext,
    { dataPointIndex }
) => {
    //console.log("== data points ==");
    //console.log(dataPointIndex);
};

let mandatesPostedVsDeductedChart = null;

const renderMandatesPostedVsDeductedChart = (selector, data) => {
    if (!document.querySelector(selector)) {
        return;
    }

    const arr_month = [];
    const arr_posted = [];
    const arr_deducted = [];

    for (let k = 0; k < data.chart.length; k++) {
        arr_month.push(data.chart[k].month);
        arr_posted.push(data.chart[k].posted || 0);
        arr_deducted.push(data.chart[k].deducted || 0);
    }

    const options = {
        series: [
            {
                name: "Sent to bank",
                data: arr_posted,
            },
            {
                name: "Successfully deducted",
                data: arr_deducted,
            },
        ],
        chart: {
            type: "bar",
            height: 360,
            stacked: false,
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
        colors: ["#1565C0", "#2E7D32"],
    };

    if (mandatesPostedVsDeductedChart) {
        mandatesPostedVsDeductedChart.destroy();
    }

    $(selector).html("");
    mandatesPostedVsDeductedChart = new ApexCharts(document.querySelector(selector), options);
    mandatesPostedVsDeductedChart.render();
};

//Filters
const filters = window.DASHBOARD_FILTERS || {};
const channel = filters.channel;
const selectedYear = filters.year || "";
console.log('Dashboard Channel:', channel);

//Payment mandate value
$.getJSON("charts/mandate-values", { channel: channel, year: selectedYear }, (data) => {
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

let expectedVsCollectedChart = null;

const renderExpectedVsCollectedChart = (data) => {
    const selector = "#collected-mandate-value-chart";
    const chartElement = document.querySelector(selector);

    if (!chartElement) {
        return;
    }

    const options = {
        series: [
            {
                name: "Expected amount",
                data: data.chart.map((item) => Number(item.total_amount || 0)),
            },
            {
                name: "Collected amount",
                data: data.chart.map((item) => Number(item.paid_amount || 0)),
            },
        ],
        chart: {
            type: "bar",
            height: 350,
            toolbar: { show: false },
        },
        plotOptions: {
            bar: {
                borderRadius: 3,
                columnWidth: "58%",
            },
        },
        dataLabels: { enabled: false },
        legend: {
            position: "top",
            horizontalAlign: "left",
        },
        grid: {
            borderColor: "#edf1f7",
            strokeDashArray: 3,
        },
        xaxis: {
            categories: data.chart.map((item) => item.month),
        },
        yaxis: {
            labels: {
                formatter: (value) => new Intl.NumberFormat("en-US", {
                    notation: "compact",
                    maximumFractionDigits: 1,
                }).format(Number(value || 0)),
            },
        },
        tooltip: {
            y: {
                formatter: (value) => Number(value).toLocaleString() + " TZS",
            },
        },
        colors: ["#1565C0", "#2E7D32"],
    };

    if (expectedVsCollectedChart) {
        expectedVsCollectedChart.destroy();
    }

    $(selector).html("");
    expectedVsCollectedChart = new ApexCharts(chartElement, options);
    expectedVsCollectedChart.render();
};

const showCollectionChartError = () => {
    const message = '<div class="dashboard-chart-state">Unable to load chart data.</div>';
    $("#collected-mandate-value-chart").html(message);
    $("#mandates-posted-vs-deducted-chart").html(message);
};

const fetchMonthlyCollectionCharts = (year) => {
    $.getJSON("charts/collected-mandate", { year: year, channel: channel })
        .done((data) => {
            if (data.error === false && Array.isArray(data.chart)) {
                renderExpectedVsCollectedChart(data);
                renderMandatesPostedVsDeductedChart("#mandates-posted-vs-deducted-chart", data);
                return;
            }

            showCollectionChartError();
        })
        .fail(showCollectionChartError);
};

fetchMonthlyCollectionCharts(selectedYear);

$("#collected-mandate-value-select-year, #mandates-posted-vs-deducted-select-year").on("change", function () {
    const year = $(this).val();
    $("#collected-mandate-value-select-year, #mandates-posted-vs-deducted-select-year").val(year);
    fetchMonthlyCollectionCharts(year);
});

// 7-day collection trend
let weeklyCollectionChart = null;

const formatWeeklyAmount = (value) => {
    return new Intl.NumberFormat("en-US", {
        notation: "compact",
        maximumFractionDigits: 1,
    }).format(Number(value || 0));
};

const updateWeeklyCollectionSummary = (data) => {
    const summary = data.summary || {};
    const change = summary.change_percentage;
    const changeElement = $("#weekly-change");

    $("#weekly-total-collected").text(formatWeeklyAmount(summary.total_collected) + " TZS");
    $("#weekly-previous-total").text(formatWeeklyAmount(summary.previous_total) + " TZS");
    $("#weekly-collection-period").text(data.period.label);

    changeElement.removeClass("trend-positive trend-negative trend-neutral");

    if (change === null || typeof change === "undefined") {
        changeElement.text("No prior data").addClass("trend-neutral");
    } else {
        const prefix = change > 0 ? "+" : "";
        changeElement
            .text(prefix + Number(change).toLocaleString() + "%")
            .addClass(change > 0 ? "trend-positive" : (change < 0 ? "trend-negative" : "trend-neutral"));
    }
};

const renderWeeklyCollectionTrend = (data) => {
    const selector = "#collected-mandate-value-weekly-chart";
    const chartElement = document.querySelector(selector);

    if (!chartElement) {
        return;
    }

    updateWeeklyCollectionSummary(data);

    const hasData = Boolean(data.summary && data.summary.has_data);
    $(selector).toggleClass("d-none", !hasData);
    $("#weekly-collection-empty").toggleClass("d-none", hasData);

    if (weeklyCollectionChart) {
        weeklyCollectionChart.destroy();
        weeklyCollectionChart = null;
    }

    if (!hasData) {
        return;
    }

    const options = {
        series: [{
            name: "Collected",
            data: data.chart.map((item) => item.paid),
        }],
        chart: {
            type: "bar",
            height: 235,
            toolbar: {
                show: false,
            },
            events: {
                dataPointSelection: function (event, chartContext, config) {
                    const selected = data.chart[config.dataPointIndex];

                    if (!selected || !window.DASHBOARD_ROUTES?.paymentCollections) {
                        return;
                    }

                    const url = new URL(window.DASHBOARD_ROUTES.paymentCollections, window.location.origin);
                    url.searchParams.set("start_at", selected.full_date);
                    url.searchParams.set("end_at", selected.full_date);

                    if (channel) {
                        url.searchParams.set("channel", channel);
                    }

                    window.location.href = url.toString();
                },
            },
        },
        plotOptions: {
            bar: {
                borderRadius: 3,
                columnWidth: "52%",
            },
        },
        dataLabels: {
            enabled: false,
        },
        grid: {
            borderColor: "#edf1f7",
            strokeDashArray: 3,
        },
        xaxis: {
            categories: data.chart.map((item) => item.date),
            labels: {
                rotate: 0,
                style: {
                    fontSize: "10px",
                },
            },
        },
        yaxis: {
            labels: {
                formatter: formatWeeklyAmount,
            },
        },
        tooltip: {
            y: {
                formatter: function (value) {
                    return Number(value).toLocaleString() + " TZS";
                },
            },
        },
        colors: ["#2E7D32"],
    };

    weeklyCollectionChart = new ApexCharts(chartElement, options);
    weeklyCollectionChart.render();
};

const fetchWeeklyCollectionTrend = (periodEnd) => {
    $.getJSON("charts/weekly-collected-mandate", {
        curr_date: periodEnd,
        channel: channel,
    }, (data) => {
        if (data.error === false) {
            renderWeeklyCollectionTrend(data);
        }
    });
};

const weeklyPeriodEnd = $("#collected-mandate-value-weekly-date").val();
fetchWeeklyCollectionTrend(weeklyPeriodEnd);

$("#collected-mandate-value-weekly-date").on("change", function () {
    fetchWeeklyCollectionTrend($(this).val());
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
