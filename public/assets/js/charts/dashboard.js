$(function () {
    Highcharts.setOptions({
        lang: {
            thousandsSep: ','
        }
    });


    //pending and approved by payment channel
    $.getJSON('api/dashboard/consent-per-channel', (data) => {
        if (data.error == false) {
            var arr_channels = []
            var arr_pending = []
            var arr_approved = []

            for (k = 0; k < data.chart.length; k++) {
                arr_channels.push(data.chart[k].name);
                arr_pending.push(data.chart[k].pending);
                arr_approved.push(data.chart[k].approved);
            }

            $('#consent-channel-chart').highcharts({
                chart: {
                    type: 'column',
                    backgroundColor: '#FFF'
                },
                title: {
                    text: null
                },
                xAxis: {
                    categories: arr_channels,
                    crosshair: true,
                    title: {
                        text: "Payment Channels"
                    }
                },
                yAxis: {
                    allowDecimals: false,
                    title: {
                        text: "No. of Payment Mandate"
                    }
                },
                tooltip: {
                    headerFormat: '<span style="font-size:12px; font-weight:600;">{point.key}</span><table>',
                    pointFormat: '<tr><td style="padding:0">{series.name}: </td>' +
                        '<td style="padding:0"><b>{point.y:,.0f}</b></td></tr>',
                    footerFormat: '</table>',
                    shared: true,
                    useHTML: true
                },
                plotOptions: {
                    column: {
                        // stacking: 'normal',
                        pointPadding: 0.1,
                        borderWidth: 0,
                        dataLabels: {
                            enabled: false,
                            style: {
                                fontWeight: 'bold'
                            }
                        }
                    }
                },
                series: [{
                    name: 'Pending',
                    color: '#F9A825',
                    data: arr_pending
                }, {
                    name: 'Approved',
                    color: '#33691E',
                    data: arr_approved
                }
                ],
                credits: {
                    enabled: false
                }
            });
        }
    });




});