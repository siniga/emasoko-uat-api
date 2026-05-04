(function () {
    const payload = window.BI_DASHBOARD_PAYLOAD || {};
    const tabButtons = document.querySelectorAll("[data-bi-tab]");
    const panes = document.querySelectorAll("[data-bi-pane]");
    const activeTabInputs = document.querySelectorAll("[data-bi-active-tab-input]");
    const clearLinks = document.querySelectorAll("[data-bi-clear-link]");
    const charts = {};

    function formatNumber(value) {
        return Number(value || 0).toLocaleString();
    }

    function currentUrl() {
        return new URL(window.location.href);
    }

    function syncActiveTabState(tab) {
        activeTabInputs.forEach((input) => {
            input.value = tab;
        });

        clearLinks.forEach((link) => {
            const url = new URL(link.href, window.location.origin);
            url.searchParams.set("active_tab", tab);
            link.href = url.toString();
        });

        const url = currentUrl();
        url.searchParams.set("active_tab", tab);
        window.history.replaceState({}, "", url.toString());
    }

    function activateTab(tab) {
        tabButtons.forEach((button) => {
            button.classList.toggle("is-active", button.dataset.biTab === tab);
        });

        panes.forEach((pane) => {
            pane.classList.toggle("is-active", pane.dataset.biPane === tab);
        });

        syncActiveTabState(tab);

        if (tab === "reconciliation") {
            renderReconciliationStatusChart();
        }
    }

    function renderGrowthChart(viewKey) {
        const target = document.querySelector("#bi-growth-chart");
        const titleNode = document.querySelector("#bi-growth-title");
        if (!target || !payload.growth || !payload.growth.views) {
            return;
        }

        const config = payload.growth.views[viewKey] || payload.growth.views.collections;
        titleNode.textContent = config.label;

        if (charts.growth) {
            charts.growth.destroy();
        }

        const isLine = config.type === "line";
        const isStacked = config.type === "stacked-bar";

        charts.growth = new ApexCharts(target, {
            series: config.series || [],
            chart: {
                type: isLine ? "line" : "bar",
                height: 360,
                stacked: isStacked,
                toolbar: {
                    show: false,
                },
            },
            colors: config.colors || ["#153567"],
            stroke: {
                show: true,
                width: isLine ? 3 : 0,
                curve: "smooth",
            },
            dataLabels: {
                enabled: false,
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: false,
                    columnWidth: isStacked ? "58%" : "72%",
                },
            },
            fill: {
                opacity: 1,
            },
            legend: {
                position: "top",
                horizontalAlign: "left",
            },
            xaxis: {
                categories: payload.growth.months || [],
                labels: {
                    rotate: -35,
                },
            },
            yaxis: {
                labels: {
                    formatter(value) {
                        return formatNumber(value);
                    },
                },
            },
            tooltip: {
                y: {
                    formatter(value) {
                        return formatNumber(value);
                    },
                },
            },
        });

        charts.growth.render();
    }

    function renderChannelMixChart() {
        const target = document.querySelector("#bi-channel-mix-chart");
        if (!target || !payload.channel_mix) {
            return;
        }

        if (charts.channelMix) {
            charts.channelMix.destroy();
        }

        charts.channelMix = new ApexCharts(target, {
            series: payload.channel_mix.series || [],
            chart: {
                type: "donut",
                height: 320,
            },
            labels: payload.channel_mix.labels || [],
            colors: payload.channel_mix.colors || [],
            legend: {
                position: "bottom",
            },
            dataLabels: {
                enabled: false,
            },
            tooltip: {
                y: {
                    formatter(value) {
                        return formatNumber(value);
                    },
                },
            },
        });

        charts.channelMix.render();
    }

    function renderReconciliationStatusChart() {
        const target = document.querySelector("#bi-reconciliation-status-chart");
        if (!target || !payload.status_breakdown) {
            return;
        }

        if (charts.statusBreakdown) {
            charts.statusBreakdown.destroy();
        }

        charts.statusBreakdown = new ApexCharts(target, {
            series: payload.status_breakdown.series || [],
            chart: {
                type: "bar",
                height: 320,
                toolbar: {
                    show: false,
                },
            },
            colors: payload.status_breakdown.colors || ["#153567"],
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    distributed: true,
                    columnWidth: "55%",
                },
            },
            legend: {
                show: false,
            },
            dataLabels: {
                enabled: false,
            },
            xaxis: {
                categories: payload.status_breakdown.categories || [],
            },
            yaxis: {
                labels: {
                    formatter(value) {
                        return formatNumber(value);
                    },
                },
            },
            tooltip: {
                y: {
                    formatter(value) {
                        return formatNumber(value);
                    },
                },
            },
        });

        charts.statusBreakdown.render();
    }

    tabButtons.forEach((button) => {
        button.addEventListener("click", function () {
            activateTab(this.dataset.biTab);
        });
    });

    const growthSelector = document.querySelector("#bi-growth-view");
    if (growthSelector) {
        growthSelector.addEventListener("change", function () {
            renderGrowthChart(this.value);
        });
    }

    const activeTab = currentUrl().searchParams.get("active_tab") || "growth";
    activateTab(activeTab);
    renderGrowthChart(growthSelector ? growthSelector.value : "collections");
    renderChannelMixChart();
})();
