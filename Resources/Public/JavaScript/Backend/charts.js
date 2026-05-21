import DocumentService from '@typo3/core/document-service.js';
import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import {
    Chart, BarController, BarElement, LineController, LineElement, PointElement,
    DoughnutController, ArcElement, CategoryScale, LinearScale, Filler, Tooltip
} from 'chart';


DocumentService.ready().then(() => {

    Chart.register(
        BarController, BarElement, LineController, LineElement, PointElement,
        DoughnutController, ArcElement, CategoryScale, LinearScale, Filler, Tooltip
    );

    const ctxTrafficBar = document.getElementById('trafficChart');
    const ctxUserBar = document.getElementById('userChart');
    const trafficYearSelect = document.getElementById('trafficYearSelect');
    const userYearSelect = document.getElementById('userYearSelect');
    if (!ctxTrafficBar || !ctxUserBar || !trafficYearSelect || !userYearSelect || !Chart) return;

    let trafficChart = null;
    let userChart = null;
    let colors = getTypo3ThemeColors();

    // Initialise traffic chart
    const trafficMonthsArray = JSON.parse(ctxTrafficBar.dataset.months);
    const trafficLabel = ctxTrafficBar.dataset.label;
    trafficChart = new Chart(ctxTrafficBar, {
        type: 'bar',
        data: {
            labels: trafficMonthsArray,
            datasets: [{
                label: trafficLabel,
                data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                backgroundColor: colors.primary,
                borderColor: colors.primary,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: colors.text } } },
            scales: {
                x: { grid: { color: colors.grid }, ticks: { color: colors.text } },
                y: {
                    grid: { color: colors.grid },
                    ticks: {
                        color: colors.text,
                        precision: 0
                    },
                    beginAtZero: true
                }
            }
        }
    });


    // Initialise user chart
    const userMonthsArray = JSON.parse(ctxUserBar.dataset.months);
    const userLabel = ctxUserBar.dataset.label;
    userChart = new Chart(ctxUserBar, {
        type: 'bar',
        data: {
            labels: userMonthsArray,
            datasets: [{
                label: userLabel,
                data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                backgroundColor: colors.primary,
                borderColor: colors.primary,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: colors.text } } },
            scales: {
                x: { grid: { color: colors.grid }, ticks: { color: colors.text } },
                y: {
                    grid: { color: colors.grid },
                    ticks: {
                        color: colors.text,
                        precision: 0
                    },
                    beginAtZero: true
                }
            }
        }
    });

    // Load initial data via AJAX
    loadChartDataViaAjax(trafficYearSelect, trafficChart, 'traffic');
    loadChartDataViaAjax(userYearSelect, userChart, 'user');

    // Event listeners to update charts when year selection changes
    trafficYearSelect.addEventListener('change', () => loadChartDataViaAjax(trafficYearSelect, trafficChart, 'traffic'));
    userYearSelect.addEventListener('change', () => loadChartDataViaAjax(userYearSelect, userChart, 'user'));

    // Update chart theme on theme change
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        setTimeout(() => {
            refreshChartTheme(trafficChart);
            refreshChartTheme(userChart);
        }, 50);
    });
});

function loadChartDataViaAjax(selectElement, chartInstance, chartType) {
    let endpoint = '';
    if (chartType === 'traffic') {
        endpoint = TYPO3.settings.ajaxUrls.crowdsourcing_statistics_get_page_views;
        if (!endpoint) {
            console.error("TYPO3 AJAX endpoint 'crowdsourcing_statistics_get_page_views' not found!");
            return;
        }
    } else {
        if (chartType === 'user') {
            endpoint = TYPO3.settings.ajaxUrls.crowdsourcing_statistics_get_active_users;
            if (!endpoint) {
                console.error("TYPO3 AJAX endpoint 'crowdsourcing_statistics_get_active_users' not found!");
                return;
            }
        }
    }

    const selectedYear = selectElement.value;

    chartInstance.canvas.style.opacity = '0.4';

    new AjaxRequest(endpoint)
        .withQueryArguments({
            year: selectedYear
        })
        .get()
        .then(async (response) => {
            const responseData = await response.resolve();
            chartInstance.data.datasets[0].data = responseData;
            chartInstance.update();
        }, function (error) {
            console.error('TYPO3 AjaxRequest failed:', error);
        })
        .finally(() => {
            chartInstance.canvas.style.opacity = '1';
        });
}

function getTypo3ThemeColors() {
    const style = getComputedStyle(document.documentElement);
    const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    return {
        text: isDark ? '#ffffff' : '#000000',
        grid: isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.05)',
        primary: '#3b82f6',
        secondary: '#10b981'
    };
}

function refreshChartTheme(chartInstance) {
    if (!chartInstance) return;
    const colors = getTypo3ThemeColors();

    chartInstance.options.scales.x.grid.color = colors.grid;
    chartInstance.options.scales.x.ticks.color = colors.text;
    chartInstance.options.scales.y.grid.color = colors.grid;
    chartInstance.options.scales.y.ticks.color = colors.text;
    chartInstance.options.plugins.legend.labels.color = colors.text;

    chartInstance.update();
}
