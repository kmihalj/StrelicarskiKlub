@once
    <style>
        .trening-chart-wrap {
            width: 100%;
            height: 17rem;
            overflow-x: hidden;
            overflow-y: hidden;
        }

        .js-trening-chart {
            width: 100%;
            height: 100%;
            display: block;
        }

        @media (max-width: 767.98px) {
            .trening-chart-wrap {
                height: 14rem;
            }
        }

        @media (max-width: 479.98px) {
            .trening-chart-wrap {
                height: 13rem;
            }
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

                if (!Array.isArray(points) || points.length < 2) {
                    return;
                }

                const hostWidth = Math.max(260, Math.round(svg.parentElement?.clientWidth || 0));
                const height = 320;
                const compact = hostWidth < 576;
                const paddingLeft = compact ? 40 : 56;
                const paddingRight = 6;
                const paddingTop = 16;
                const paddingBottom = compact ? 34 : 38;
                const width = hostWidth;
                const plotWidth = width - paddingLeft - paddingRight;
                const plotHeight = height - paddingTop - paddingBottom;

                svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
                svg.setAttribute('preserveAspectRatio', 'none');
                svg.style.width = '100%';
                svg.style.height = '100%';

                const rootStyles = getComputedStyle(document.documentElement);
                const bodyStyles = getComputedStyle(document.body);
                const fallbackSecondary = bodyStyles.getPropertyValue('--bs-secondary-color')?.trim() || '#6c757d';
                const fallbackBody = bodyStyles.getPropertyValue('--bs-body-color')?.trim() || '#495057';
                const primaryColor = rootStyles.getPropertyValue('--theme-primary')?.trim() || '#dc3545';
                const gridColor = document.body.classList.contains('theme-dark') ? 'rgba(255,255,255,0.18)' : '#dee2e6';
                const axisColor = document.body.classList.contains('theme-dark') ? 'rgba(255,255,255,0.45)' : '#adb5bd';

                const totals = points.map((p) => Number(p.total) || 0);
                const minValue = Math.min(...totals, 0);
                const maxValue = Math.max(...totals, 1);
                const range = Math.max(maxValue - minValue, 1);
                const minTotal = Math.min(...totals);
                const maxTotal = Math.max(...totals);
                const minIndex = totals.findIndex((value) => value === minTotal);
                const maxIndex = totals.findIndex((value) => value === maxTotal);
                const brojTocaka = points.length;
                const labelStep = (() => {
                    if (brojTocaka <= 10) return 1;
                    if (brojTocaka <= 20) return 3;
                    if (brojTocaka <= 35) return 4;
                    if (brojTocaka <= 50) return 5;
                    return 6;
                })();
                const denseProximity = brojTocaka <= 10 ? 0 : (brojTocaka <= 20 ? 1 : 2);

                const yToPx = (value) => paddingTop + plotHeight - ((value - minValue) / range) * plotHeight;

                const gridCount = 5;
                for (let i = 0; i <= gridCount; i++) {
                    const y = paddingTop + (plotHeight / gridCount) * i;
                    const gridLine = createSvgElement('line', {
                        x1: paddingLeft,
                        y1: y,
                        x2: width - paddingRight,
                        y2: y,
                        stroke: gridColor,
                        'stroke-width': 1,
                    });
                    svg.appendChild(gridLine);

                    const value = (maxValue - (range / gridCount) * i).toFixed(0);
                    const label = createSvgElement('text', {
                        x: paddingLeft - 8,
                        y: y + 4,
                        'text-anchor': 'end',
                        'font-size': 11,
                        fill: fallbackSecondary,
                    });
                    label.textContent = value;
                    svg.appendChild(label);
                }

                const axisX = createSvgElement('line', {
                    x1: paddingLeft,
                    y1: height - paddingBottom,
                    x2: width - paddingRight,
                    y2: height - paddingBottom,
                    stroke: axisColor,
                    'stroke-width': 1.5,
                });
                svg.appendChild(axisX);

                const axisY = createSvgElement('line', {
                    x1: paddingLeft,
                    y1: paddingTop,
                    x2: paddingLeft,
                    y2: height - paddingBottom,
                    stroke: axisColor,
                    'stroke-width': 1.5,
                });
                svg.appendChild(axisY);

                const stepX = brojTocaka <= 1
                    ? 0
                    : Math.max(plotWidth, 1) / (brojTocaka - 1);
                const startX = paddingLeft;
                const resolveValueLabelY = (y, preferBelow = false) => {
                    const aboveY = y - 8;
                    const belowY = y + 14;
                    if (preferBelow && belowY <= (height - paddingBottom + 18)) {
                        return belowY;
                    }
                    return aboveY < (paddingTop + 10) ? belowY : aboveY;
                };
                const resolveValueLabelX = (x) => {
                    const rightLimit = width - paddingRight - 6;
                    const leftLimit = paddingLeft + 10;
                    if (x >= rightLimit) {
                        return { x: width - paddingRight - 2, anchor: 'end' };
                    }
                    if (x <= leftLimit) {
                        return { x: paddingLeft + 2, anchor: 'start' };
                    }
                    return { x, anchor: 'middle' };
                };
                const linePath = points.map((point, index) => {
                    const x = startX + stepX * index;
                    const y = yToPx(Number(point.total) || 0);
                    return `${index === 0 ? 'M' : 'L'}${x},${y}`;
                }).join(' ');

                const path = createSvgElement('path', {
                    d: linePath,
                    fill: 'none',
                    stroke: primaryColor,
                    'stroke-width': 3,
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                });
                svg.appendChild(path);

                points.forEach((point, index) => {
                    const x = startX + stepX * index;
                    const y = yToPx(Number(point.total) || 0);

                    const circle = createSvgElement('circle', {
                        cx: x,
                        cy: y,
                        r: 4,
                        fill: primaryColor,
                    });
                    svg.appendChild(circle);

                    const isMinOrMax = index === minIndex || index === maxIndex;
                    const shouldShowByStep = labelStep === 1
                        ? true
                        : (index % labelStep === 0);
                    const tooCloseToExtremes = !isMinOrMax && (
                        Math.abs(index - minIndex) <= denseProximity
                        || Math.abs(index - maxIndex) <= denseProximity
                    );

                    if (isMinOrMax || (shouldShowByStep && !tooCloseToExtremes)) {
                        const preferBelow = index === minIndex && minIndex !== maxIndex;
                        const valueLabelPos = resolveValueLabelX(x);
                        const valueLabel = createSvgElement('text', {
                            x: valueLabelPos.x,
                            y: resolveValueLabelY(y, preferBelow),
                            'text-anchor': valueLabelPos.anchor,
                            'font-size': brojTocaka > 35 ? 9 : 10,
                            'font-weight': isMinOrMax ? '700' : '600',
                            fill: fallbackBody,
                        });
                        valueLabel.textContent = String(point.total);
                        svg.appendChild(valueLabel);
                    }
                });
            };

            document.querySelectorAll('.js-trening-chart').forEach(renderChart);
        })();
    </script>
@endonce
