(function () {
    var el = document.getElementById('statistic-chart');
    if (!el) return;

    var countNew             = parseInt(el.dataset.countNew)             || 0;
    var countCorrection      = parseInt(el.dataset.countCorrection)      || 0;
    var countFinalCorrection = parseInt(el.dataset.countFinalCorrection) || 0;
    var countCompleted       = parseInt(el.dataset.countCompleted)       || 0;
    var countAll             = parseInt(el.dataset.countAll)             || 0;

    if (countAll === 0) return;

    var segments = [
        { label: el.dataset.labelNew             || 'Neu',           value: countNew,             color: '#198754' },
        { label: el.dataset.labelCorrection      || 'Korrektur',     value: countCorrection,      color: '#ffc107' },
        { label: el.dataset.labelFinalCorrection || 'Endkorrektur',  value: countFinalCorrection, color: '#6c757d' },
        { label: el.dataset.labelCompleted       || 'Abgeschlossen', value: countCompleted,       color: '#212529' }
    ];

    var labelHeight  = 18;
    var barHeight    = 28;
    var legendHeight = 24;
    var totalHeight  = labelHeight + 4 + barHeight + 12 + legendHeight;
    var barOffsetY   = labelHeight + 4;
    var width        = el.clientWidth || 400;
    var labelAll     = el.dataset.labelAll || 'Gesamt';

    var svg = d3.select(el)
        .append('svg')
        .attr('width', '100%')
        .attr('height', totalHeight)
        .attr('viewBox', '0 0 ' + width + ' ' + totalHeight)
        .attr('preserveAspectRatio', 'none');

    svg.append('text')
        .attr('x', width)
        .attr('y', labelHeight - 2)
        .attr('text-anchor', 'end')
        .attr('font-size', '12')
        .attr('font-weight', 'bold')
        .attr('fill', '#0d6efd')
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
    segments.forEach(function (seg) {
        var dotSize   = 10;
        var textWidth = seg.label.length * 7 + dotSize + 6;

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

        legendX += textWidth + 14;
    });
})();
