<?php
/**
 * Multi-series line chart for category trends.
 *
 * @var array  $labels_1       Date labels for X axis
 * @var array  $series_data_1  Array of series, each with 'name' and 'data'
 * @var bool   $show_currency
 * @var string $xaxis_title
 * @var string $yaxis_title
 * @var array  $config
 * @var array  $legend_labels  Category names for the legend
 */

$colors = ['#d70206', '#f05b4f', '#f4c63d', '#56b881', '#4a90d9', '#9b59b6', '#e67e22', '#1abc9c', '#e74c3c', '#3498db'];
?>

<style>
    #chart1.ct-chart {
        overflow: visible !important;
    }
    .chart-legend {
        text-align: center;
        margin: 15px 0 5px 0;
        padding: 0;
    }
    .chart-legend .legend-item {
        display: inline-block;
        margin: 3px 12px;
        font-size: 13px;
    }
    .chart-legend .legend-color {
        display: inline-block;
        width: 14px;
        height: 14px;
        margin-right: 5px;
        vertical-align: middle;
        border-radius: 2px;
    }
    <?php foreach ($legend_labels as $i => $label) { ?>
    .ct-series-<?= chr(97 + $i) ?> .ct-line,
    .ct-series-<?= chr(97 + $i) ?> .ct-point {
        stroke: <?= $colors[$i % count($colors)] ?> !important;
    }
    .ct-series-<?= chr(97 + $i) ?> .ct-tooltip-point {
        fill: <?= $colors[$i % count($colors)] ?> !important;
    }
    <?php } ?>
</style>

<div class="chart-legend">
    <?php foreach ($legend_labels as $i => $label) { ?>
        <span class="legend-item">
            <span class="legend-color" style="background: <?= $colors[$i % count($colors)] ?>;"></span>
            <?= esc($label) ?>
        </span>
    <?php } ?>
</div>

<script type="text/javascript">
    var data = {
        labels: <?= json_encode($labels_1) ?>,
        series: <?= json_encode($series_data_1) ?>
    };

    var options = {
        width: '100%',
        height: '100%',
        showPoint: true,
        lineSmooth: Chartist.Interpolation.cardinal({ tension: 0.3 }),
        chartPadding: {
            top: 20,
            bottom: 120
        },
        axisX: {
            offset: 150,
            position: 'end',
            labelOffset: {
                x: 0,
                y: -5
            }
        },
        axisY: {
            offset: 120,
            labelOffset: {
                x: -10,
                y: 0
            },
            labelInterpolationFnc: function(value) {
                <?php
                if ($show_currency) {
                    if (is_right_side_currency_symbol()) {
                ?>
                        return value + '<?= esc($config['currency_symbol'], 'js') ?>';
                    <?php } else { ?>
                        return '<?= esc($config['currency_symbol'], 'js') ?>' + value;
                    <?php
                    }
                } else {
                    ?>
                    return value;
                <?php } ?>
            }
        },
        plugins: [
            Chartist.plugins.ctAxisTitle({
                axisY: {
                    axisTitle: '<?= esc($yaxis_title, 'js') ?>',
                    axisClass: 'ct-axis-title',
                    offset: {
                        x: 0,
                        y: 15
                    },
                    textAnchor: 'middle',
                    flipTitle: false
                }
            }),
            Chartist.plugins.tooltip({
                pointClass: 'ct-tooltip-point',
                transformTooltipTextFnc: function(value) {
                    <?php
                    if ($show_currency) {
                        if (is_right_side_currency_symbol()) {
                    ?>
                            return value + '<?= esc($config['currency_symbol'], 'js') ?>';
                        <?php } else { ?>
                            return '<?= esc($config['currency_symbol'], 'js') ?>' + value;
                        <?php
                        }
                    } else {
                        ?>
                        return value;
                    <?php } ?>
                }
            })
        ]
    };

    var responsiveOptions = [
        ['screen and (min-width: 640px)', {
            height: '80%',
            chartPadding: {
                top: 20,
                bottom: 10
            }
        }]
    ];

    var chart = new Chartist.Line('#chart1', data, options, responsiveOptions);

    var seriesNames = <?= json_encode($legend_labels) ?>;

    chart.on('draw', function(data) {
        if (data.type === 'point') {
            var seriesName = seriesNames[data.seriesIndex] || '';
            var displayValue = data.value.y;
            <?php
            if ($show_currency) {
                if (is_right_side_currency_symbol()) {
            ?>
                    displayValue = displayValue + '<?= esc($config['currency_symbol'], 'js') ?>';
                <?php } else { ?>
                    displayValue = '<?= esc($config['currency_symbol'], 'js') ?>' + displayValue;
                <?php
                }
            }
            ?>

            var circle = new Chartist.Svg('circle', {
                cx: [data.x],
                cy: [data.y],
                r: [5],
                'ct:value': data.value.y,
                'ct:meta': seriesName + ': ' + displayValue,
                class: 'ct-tooltip-point'
            }, 'ct-area');

            data.element.replace(circle);
        }
    });
</script>
