(function () {
    function renderChart(el) {
        if (!el) return;

        var countNew             = parseInt(el.dataset.countNew)             || 0;
        var countCorrection      = parseInt(el.dataset.countCorrection)      || 0;
        var countFinalCorrection = parseInt(el.dataset.countFinalCorrection) || 0;
        var countCompleted       = parseInt(el.dataset.countCompleted)       || 0;
        var countAll             = parseInt(el.dataset.countAll)             || 0;

        const colorNew             = getComputedStyle(document.documentElement).getPropertyValue('--chart-color-new').trim() || '#ced4da';
        const colorCorrection      = getComputedStyle(document.documentElement).getPropertyValue('--chart-color-correction').trim() || '#ffca28';
        const colorFinalCorrection = getComputedStyle(document.documentElement).getPropertyValue('--chart-color-final-correction').trim() || '#7d89f4';
        const colorCompleted       = getComputedStyle(document.documentElement).getPropertyValue('--chart-color-completed').trim() || '#47c9a2';

        if (countAll === 0) return;

        var segments = [
            { label: el.dataset.labelNew             || 'Neu',           value: countNew,             color: colorNew },
            { label: el.dataset.labelCorrection      || 'Korrektur',     value: countCorrection,      color: colorCorrection },
            { label: el.dataset.labelFinalCorrection || 'Endkorrektur',  value: countFinalCorrection, color: colorFinalCorrection },
            { label: el.dataset.labelCompleted       || 'Abgeschlossen', value: countCompleted,       color: colorCompleted }
        ];

        var labelHeight  = 18;
        var barHeight    = 28;
        var barOffsetY   = labelHeight + 4;
        var width        = el.clientWidth || 400;
        var labelAll     = el.dataset.labelAll || 'Gesamt';

        var svg = d3.select(el)
            .append('svg')
            .attr('width', '100%')
            .attr('viewBox', '0 0 ' + width + ' 100'); // Vorläufige ViewBox

        svg.append('text')
            .attr('x', width)
            .attr('y', labelHeight - 2)
            .attr('text-anchor', 'end')
            .attr('font-size', '12')
            .attr('font-weight', 'bold')
            .attr('fill', '#6c757d')
            .text(labelAll + ': ' + countAll);

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
            bar.append('title')
                .text(seg.label + ': ' + seg.value);

            cumulative += seg.value;
        });

        /* Legende */
        var legendY = barOffsetY + barHeight + 12;
        var legendX = 0;
        var lineHeight = 20;
        var dotSize = 10;
        var marginX = 14;

        segments.forEach(function (seg) {
            // Hilfselement um die Breite des Textes zu messen
            var tempText = svg.append('text')
                .attr('font-size', '11')
                .attr('visibility', 'hidden')
                .text(seg.label + ' (' + seg.value + ')');
            
            var textWidth = tempText.node().getBBox().width;
            tempText.remove();

            var itemWidth = dotSize + 4 + textWidth;

            // Prüfen ob das Item in die aktuelle Zeile passt
            if (legendX > 0 && legendX + itemWidth > width) {
                legendX = 0;
                legendY += lineHeight;
            }

            svg.append('rect')
                .attr('x', legendX)
                .attr('y', legendY)
                .attr('width', dotSize)
                .attr('height', dotSize)
                .attr('rx', 2)
                .attr('fill', seg.color);

            svg.append('text')
                .attr('x', legendX + dotSize + 4)
                .attr('y', legendY + dotSize - 1)
                .attr('font-size', '11')
                .attr('fill', '#495057')
                .text(seg.label + ' (' + seg.value + ')');

            legendX += itemWidth + marginX;
        });

        // SVG Höhe anpassen, falls Legende umgebrochen ist
        var finalHeight = legendY + lineHeight;
        svg.attr('height', finalHeight)
           .attr('viewBox', '0 0 ' + width + ' ' + finalHeight);
    }
    
    function initCharts() {
        var statChart = document.getElementById('statistic-chart');
        var userChart = document.getElementById('user-statistic-chart');

        if (statChart) {
            statChart.innerHTML = '';
            renderChart(statChart);
        }
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
