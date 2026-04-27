(function () {
    function renderChart(el) {
        if (!el) return;

        var countUnedited        = parseInt(el.dataset.countUnedited)        || 0;
        var countInProgress      = parseInt(el.dataset.countInProgress)      || 0;
        var countCompleted       = parseInt(el.dataset.countCompleted)       || 0;
        var countAll             = parseInt(el.dataset.countAll)             || 0;

        const colorUnedited        = getComputedStyle(document.documentElement).getPropertyValue('--chart-color-unedited').trim()   || '#a0c5ce';
        const colorInProgress      = getComputedStyle(document.documentElement).getPropertyValue('--chart-color-inprogress').trim() || '#499daf';
        const colorCompleted       = getComputedStyle(document.documentElement).getPropertyValue('--chart-color-completed').trim()  || '#3a7e8c';

        if (countAll === 0) return;

        var segments = [
            { label: el.dataset.labelCompleted       || 'Abgeschlossene Plakate', value: countCompleted,       color: colorCompleted },
            { label: el.dataset.labelInProgress      || 'Plakate in Bearbeitung', value: countInProgress,      color: colorInProgress },
            { label: el.dataset.labelUnedited        || 'Unbearbeitete Plakate', value: countUnedited,         color: colorUnedited }
        ];

        var barHeight    = 28;
        var barOffsetY   = 4;
        var width        = el.clientWidth || 400;

        var svg = d3.select(el)
            .append('svg')
            .attr('width', '100%')
            .attr('viewBox', '0 0 ' + width + ' 100'); // Vorläufige ViewBox

        var x = d3.scaleLinear().domain([0, countAll]).range([0, width]);

        var cumulative = 0;
        segments.forEach(function (seg) {
            if (seg.value <= 0) return;

            var bar = svg.append('g');
            bar.append('rect')
                .attr('x', x(cumulative))
                .attr('y', barOffsetY)
                .attr('width', x(seg.value))
                .attr('height', barHeight)
                .attr('fill', seg.color);

            if (x(seg.value) > 20) {
                var textColor = '#fff';

                bar.append('text')
                    .attr('x', x(cumulative) + x(seg.value) / 2)
                    .attr('y', barOffsetY + barHeight / 2 + 5)
                    .attr('text-anchor', 'middle')
                    .attr('font-size', '12')
                    .attr('font-weight', 'bold')
                    .attr('fill', textColor)
                    .style('pointer-events', 'none')
                    .text(seg.value);
            }

            bar.append('title')
                .text(seg.label + ': ' + seg.value);

            cumulative += seg.value;
        });

        /* Legende */
        var legendY = barOffsetY + barHeight + 20;
        var lineHeight = 30;
        var dotSize = 14;

        segments.forEach(function (seg, index) {
            // Trennlinie vor jedem Element außer dem ersten
            if (index > 0) {
                svg.append('line')
                    .attr('x1', 0)
                    .attr('y1', legendY - 10)
                    .attr('x2', width)
                    .attr('y2', legendY - 10)
                    .attr('stroke', '#dee2e6')
                    .attr('stroke-width', 1);
            }

            svg.append('rect')
                .attr('x', 0)
                .attr('y', legendY)
                .attr('width', dotSize)
                .attr('height', dotSize)
                .attr('fill', seg.color);

            svg.append('text')
                .attr('x', dotSize + 10)
                .attr('y', legendY + dotSize - 1)
                .attr('font-size', '14')
                .attr('fill', '#495057')
                .text(seg.label);

            svg.append('text')
                .attr('x', width * 0.7)
                .attr('y', legendY + dotSize - 1)
                .attr('font-size', '14')
                .attr('font-weight', 'bold')
                .attr('fill', '#495057')
                .attr('text-anchor', 'middle')
                .text(seg.value);

            legendY += lineHeight;
        });

        // SVG Höhe anpassen
        var finalHeight = legendY;
        svg.attr('height', finalHeight)
           .attr('viewBox', '0 0 ' + width + ' ' + finalHeight);
    }
    
    function initCharts() {
        var statCharts = document.querySelectorAll('.statistic-chart');
        var userChart = document.getElementById('user-statistic-chart');

        statCharts.forEach(function (statChart) {
            statChart.innerHTML = '';
            renderChart(statChart);
        });

        if (userChart) {
            userChart.innerHTML = '';
            renderChart(userChart);
        }
    }

    initCharts();

    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(initCharts, 200);
    });

})();
