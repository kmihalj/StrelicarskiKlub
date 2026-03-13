@once
    <style>
        .trening-chart-wrap {
            width: 100%;
            height: 15rem;
        }

        .js-trening-chart {
            width: 100%;
            height: 100%;
            display: block;
        }
    </style>
    <script>
        (function () {
            if (window.__treningChartsInit) {
                return;
            }
            window.__treningChartsInit = true;

            const createSvgElement = (name, attrs = {}) => {
                const node = document.createElementNS('http://www.w3.org/2000/svg', name);
                Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, String(value)));
                return node;
            };

            const renderChart = (svg) => {
                const rawPoints = svg.dataset.points || '[]';
                let points;
                try {
                    points = JSON.parse(rawPoints);
                } catch (e) {
                    points = [];
                }

                while (svg.firstChild) {
                    svg.removeChild(svg.firstChild);
                }

                if (!Array.isArray(points) || points.length === 0) {
                    const empty = createSvgElement('text', {
                        x: 500,
                        y: 160,
                        'text-anchor': 'middle',
                        'font-size': 28,
                        fill: '#6c757d'
                    });
                    empty.textContent = 'Nema podataka za graf';
                    svg.appendChild(empty);
                    return;
                }

                const width = 1000;
                const height = 320;
                const paddingLeft = 60;
                const paddingRight = 30;
                const paddingTop = 20;
                const paddingBottom = 50;
                const plotWidth = width - paddingLeft - paddingRight;
                const plotHeight = height - paddingTop - paddingBottom;

                const totals = points.map((p) => Number(p.total) || 0);
                const minValue = Math.min(...totals, 0);
                const maxValue = Math.max(...totals, 1);
                const range = Math.max(maxValue - minValue, 1);

                const yToPx = (value) => paddingTop + plotHeight - ((value - minValue) / range) * plotHeight;

                const gridCount = 5;
                for (let i = 0; i <= gridCount; i++) {
                    const y = paddingTop + (plotHeight / gridCount) * i;
                    const gridLine = createSvgElement('line', {
                        x1: paddingLeft,
                        y1: y,
                        x2: width - paddingRight,
                        y2: y,
                        stroke: '#dee2e6',
                        'stroke-width': 1,
                    });
                    svg.appendChild(gridLine);

                    const value = (maxValue - (range / gridCount) * i).toFixed(0);
                    const label = createSvgElement('text', {
                        x: paddingLeft - 8,
                        y: y + 4,
                        'text-anchor': 'end',
                        'font-size': 11,
                        fill: '#6c757d',
                    });
                    label.textContent = value;
                    svg.appendChild(label);
                }

                const axisX = createSvgElement('line', {
                    x1: paddingLeft,
                    y1: height - paddingBottom,
                    x2: width - paddingRight,
                    y2: height - paddingBottom,
                    stroke: '#adb5bd',
                    'stroke-width': 1.5,
                });
                svg.appendChild(axisX);

                const axisY = createSvgElement('line', {
                    x1: paddingLeft,
                    y1: paddingTop,
                    x2: paddingLeft,
                    y2: height - paddingBottom,
                    stroke: '#adb5bd',
                    'stroke-width': 1.5,
                });
                svg.appendChild(axisY);

                const stepX = points.length === 1 ? 0 : plotWidth / (points.length - 1);
                const linePath = points.map((point, index) => {
                    const x = paddingLeft + stepX * index;
                    const y = yToPx(Number(point.total) || 0);
                    return `${index === 0 ? 'M' : 'L'}${x},${y}`;
                }).join(' ');

                const path = createSvgElement('path', {
                    d: linePath,
                    fill: 'none',
                    stroke: '#dc3545',
                    'stroke-width': 3,
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                });
                svg.appendChild(path);

                points.forEach((point, index) => {
                    const x = paddingLeft + stepX * index;
                    const y = yToPx(Number(point.total) || 0);

                    const circle = createSvgElement('circle', {
                        cx: x,
                        cy: y,
                        r: 4,
                        fill: '#dc3545',
                    });
                    svg.appendChild(circle);

                    const valueLabel = createSvgElement('text', {
                        x,
                        y: y - 8,
                        'text-anchor': 'middle',
                        'font-size': 10,
                        fill: '#495057',
                    });
                    valueLabel.textContent = String(point.total);
                    svg.appendChild(valueLabel);

                    const xLabel = createSvgElement('text', {
                        x,
                        y: height - 22,
                        'text-anchor': 'middle',
                        'font-size': 10,
                        fill: '#6c757d',
                    });
                    xLabel.textContent = point.datum;
                    svg.appendChild(xLabel);
                });
            };

            document.querySelectorAll('.js-trening-chart').forEach(renderChart);
        })();
    </script>
@endonce
