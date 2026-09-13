(function () {
    const payload = window.BI_DASHBOARD_PAYLOAD || {};
    const tabButtons = document.querySelectorAll("[data-bi-tab]");
    const panes = document.querySelectorAll("[data-bi-pane]");
    const activeTabInputs = document.querySelectorAll("[data-bi-active-tab-input]");
    const clearLinks = document.querySelectorAll("[data-bi-clear-link]");
    const reconciliationFilters = document.querySelectorAll("[data-bi-reconciliation-filter]");
    const charts = {};

    function formatNumber(value) {
        return Number(value || 0).toLocaleString();
    }

    function formatCompactNumber(value) {
        return new Intl.NumberFormat("en-US", {
            notation: "compact",
            maximumFractionDigits: 1,
        }).format(Number(value || 0));
    }

    function formatChartValue(value, unit, compact = false) {
        if (unit === "percent") {
            return Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 1 }) + "%";
        }

        const formatted = compact ? formatCompactNumber(value) : formatNumber(value);
        return unit === "money" ? formatted + " TZS" : formatted;
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

        reconciliationFilters.forEach((filter) => {
            filter.classList.toggle("d-none", tab !== "reconciliation");
        });

        syncActiveTabState(tab);
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

        charts.growth = new ApexCharts(target, {
            series: config.series || [],
            chart: {
                type: isLine ? "line" : "bar",
                height: 360,
                stacked: false,
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
                    columnWidth: "65%",
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
                categories: payload.growth.categories || [],
                labels: {
                    rotate: -35,
                },
            },
            yaxis: {
                labels: {
                    formatter(value) {
                        return formatChartValue(value, config.unit, true);
                    },
                },
            },
            tooltip: {
                y: {
                    formatter(value) {
                        return formatChartValue(value, config.unit);
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

    const requestedActiveTab = currentUrl().searchParams.get("active_tab");
    const activeTab = ["growth", "reconciliation"].includes(requestedActiveTab)
        ? requestedActiveTab
        : "growth";
    activateTab(activeTab);
    renderGrowthChart(growthSelector ? growthSelector.value : "collections");
    renderChannelMixChart();
})();
