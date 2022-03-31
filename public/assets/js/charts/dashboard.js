//pending and approval total
$.getJSON('api/dashboard/consent-percentage', (data) => {
    if (data.error == false) {
        //charts
        var options = {
            series: [data.approved, data.pending],
            chart: {
                type: "donut",
                height: 260,
            },
            labels: ["Approved", "Pending"],
            colors: ["#388E3C", "#C62828"],
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
        var chart = new ApexCharts(document.querySelector("#pending-approve-chart"), options);
        chart.render()
    }
});

//pending and approved by payment channel
$.getJSON('api/dashboard/consent-per-channel', (data) => {
    if (data.error == false) {

        var arrChannels = [];
        var arrApproved = [];
        var arrPending = [];

        for (k = 0; k < data.data_array.length; k++) {
            arrChannels.push(data.data_array[k].name);
            arrPending.push(data.data_array[k].pending);
            arrApproved.push(data.data_array[k].approved);
        }

        //charts
        var options = {
            series: [{
                name: "Approved",
                data: arrApproved
            }, {
                name: "Pending",
                data: arrPending
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
                categories: arrChannels,
            },
            labels: ["Approved", "Pending"],
            colors: ["#388E3C", "#C62828"],
        };

        var chart = new ApexCharts(document.querySelector("#bar-chart"), options);
        chart.render();
    }
});

//on CLICK 
$('#select-month').on('change', function (e) {
    e.preventDefault;

    //variables
    var monthName = $(this).val();

    $.getJSON('api/dashboard/consent-per-channel', { month: monthName }, (data) => {
        if (data.error == false) {

            var arrChannels = [];
            var arrApproved = [];
            var arrPending = [];

            for (k = 0; k < data.data_array.length; k++) {
                arrChannels.push(data.data_array[k].name);
                arrPending.push(data.data_array[k].pending);
                arrApproved.push(data.data_array[k].approved);
            }

            //charts
            var options = {
                series: [{
                    name: "Approved",
                    data: arrApproved
                }, {
                    name: "Pending",
                    data: arrPending
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
                    categories: arrChannels,
                },
                labels: ["Approved", "Pending"],
                colors: ["#388E3C", "#C62828"],
            };

            var chart = new ApexCharts(document.querySelector("#bar-chart"), options);
            chart.render();
        }
    });

});

