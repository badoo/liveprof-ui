<link rel="stylesheet" href="/js/rrd/libs/jquery-tablesorter/theme.blue.css">
<script src="/js/jquery.sparkline.min.js"></script>
<script src="/js/rrd/libs/flot/jquery.flot.min.js"></script>
<script src="/js/rrd/libs/flot/jquery.flot.time.min.js"></script>
<script src="/js/rrd/libs/flot/jquery.flot.fillbetween.min.js"></script>
<script src="/js/rrd/libs/flot/jquery.flot.canvas.min.js"></script>
<script src="/js/rrd/libs/flot/jquery.flot.stack.min.js"></script>
<script src="/js/rrd/libs/flot/jquery.flot.resize.min.js"></script>
<script src="/js/rrd/libs/flot/jquery.flot.selection.min.js"></script>
<script src="/js/rrd/libs/flot-plugins/jquery.flot.dashes.js"></script>
<script src="/js/rrd/libs/flot-plugins/jquery.flot.axislabels.js"></script>
<script src="/js/rrd/libs/flot-plugins/jquery.flot.tickrotor.js"></script>
<script src="/js/rrd/libs/jquery-tablesorter/jquery.tablesorter.min.js"></script>
<script src="/js/rrd/libs/jquery-tablesorter/jquery.tablesorter.widgets.js"></script>

<style>
    @media (min-width: 768px) {
        .nav .breadcrumb {
            float: left;
            margin: 7px 10px;
        }
    }
    .legend span {
        border-bottom: 1px dotted #000;
        text-decoration: none;
        font-size: smaller;
    }
    .legend div {
        display: none;
    }
    .sortable {
        font-size: smaller;
    }
    label {
        font-weight: 1;
    }
    .badoo_graph {
        width: 100%;
        height: 100%;
    }
</style>

<?php include __DIR__ . '/navbar.block.php'; ?>

<h3>
    Method "<?= $data['method_name'] ?>" call list for <?= $data['snapshot']->getDate() ?> - <?= $data['snapshot']->getApp() ?> - <?= $data['snapshot']->getLabel() ?>
    <a href="https://github.com/badoo/liveprof-ui/wiki/Web-interface#Methods-tree" class="glyphicon glyphicon-question-sign" target="_blank" data-toggle="tooltip" title="See the page documentation"></a>
</h3>

<div class="btn-group" role="group">
    <a class="btn btn-default<?php if (empty($data['wall'])): ?> btn-primary<?php endif; ?>" href="/profiler/tree-view.phtml?app=<?= urlencode($data['snapshot']->getApp()) ?>&label=<?= urlencode($data['snapshot']->getLabel()) ?>&method_id=0">Methods tree</a>
    <a class="btn btn-default" href="/profiler/result-diff.phtml?app=<?= urlencode($data['snapshot']->getApp()) ?>&label=<?= urlencode($data['snapshot']->getLabel()) ?>&date1=<?= $data['date1'] ?>&date2=<?= $data['date2'] ?>">Diff interface</a>
    <a class="btn btn-default<?php if (!empty($data['wall'])): ?> btn-primary<?php endif; ?>" href="/profiler/list-view.phtml?snapshot_id=<?= $data['snapshot']->getId() ?>">Methods list</a>
    <a class="btn btn-default" href="/profiler/result-flamegraph.phtml?app=<?= urlencode($data['snapshot']->getApp()) ?>&label=<?= urlencode($data['snapshot']->getLabel()) ?>&snapshot_id=<?= $data['snapshot']->getId() ?>">Flame graph</a>
</div>

<form id="dates-form" class="form-inline" style="margin-top: 5px">
    <input type="hidden" name="app" value="<?= $data['snapshot']->getApp() ?>">
    <input type="hidden" name="label" value="<?= $data['snapshot']->getLabel() ?>">
    <input type="hidden" name="method_id" value="<?= $data['method_id'] ?>">

    <div class="form-group">
        <strong>Select graph period:</strong>
        <div class="btn-group" role="group">
            <?php foreach ($data['stat_intervals'] as $stat_intervals): ?>
                <a class="btn btn-default <?php if (!empty($stat_intervals['selected'])): ?>btn-primary<?php endif; ?>" href="<?= $stat_intervals['link'] ?>" role="button"><?= $stat_intervals['name'] ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-group">
        <label for="exampleInputName2">From</label>
        <input name="date1" type="date" value="<?= $data['date1'] ?>"  class="form-control" id="date1" required>
    </div>

    <div class="form-group">
        <label for="exampleInputEmail2">To</label>
        <input name="date2" type="date" value="<?= $data['date2'] ?>" class="form-control" id="date2" required>
    </div>

    <button type="submit" class="btn btn-default">Show</button>
</form>

<?php if (!empty($data['js_graph_data_all'])): ?>
    <script>
        var available_graphs = <?= json_encode($data['available_graphs']) ?>;
        var data_all = <?= json_encode($data['js_graph_data_all']) ?>;
        var dates = <?= json_encode($data['method_dates']) ?>;
        var tooltip_width = 300;
        var hidden_methods = {};
        var redraw_graphs_on_update = true;

        function getColors(neededColors) {
            var c, colors = [], colorPool = ["#edc240", "#afd8f8", "#cb4b4b", "#4da74d", "#9440ed"],
                colorPoolSize = colorPool.length, variation = 0;

            for (var i = 0; i < neededColors; i++) {
                c = $.color.parse(colorPool[i % colorPoolSize] || "#666");
                if (i % colorPoolSize == 0 && i) {
                    if (variation >= 0) {
                        if (variation < 0.5) {
                            variation = -variation - 0.2;
                        } else variation = 0;
                    } else variation = -variation;
                }

                colors[i] = c.scale('rgb', 1 + variation).add('a', -0.4).toString();
            }

            return colors;
        }

        var formatVal = function(val, param, max) {
            if (param === 'times') {
                return val;
            }
            if (max > 1000000) {
                val = val / 1000000;
                return val.toString() + (param === 'time' ?' s' : ' MB');
            }
            if (max > 1000) {
                val = val / 1000;
                return val.toString() + (param === 'time' ?' ms' : ' kB');
            }
            return val.toString() + (param === 'time' ?' μs' : ' B');
        };

        var tooltipCallback = function(type, max) {
            return function (event, pos, item) {
                if (item) {
                    var x = dates[item.dataIndex], y = formatVal(item.datapoint[1].toFixed(2), type, max);

                    if (item.pageX < window.innerWidth - tooltip_width - 50) {
                        $("#tooltip").html(item.series.label + " of " + x + " = " + y)
                            .css({top: item.pageY+5, left: item.pageX+5})
                            .fadeIn(200);
                    } else {
                        $("#tooltip").html(item.series.label + "<br>of " + x + "<br>" + y)
                            .css({top: item.pageY+5, left: item.pageX - tooltip_width - 5})
                            .fadeIn(200);
                    }
                } else {
                    $("#tooltip").hide();
                }
            };
        };

        function showGraph(data_all, param, param_data, dates) {
            var colors = getColors(data_all.length);
            var ticks = [];
            var tick_counter = Math.round(dates.length / 25);
            for (var id in dates) {
                if (tick_counter > 1 && id % tick_counter != 0) {
                    continue;
                }
                var d = dates[id].split("-");
                d = new Date(d[1] + "/" + d[2] + "/" + d[0] + " UTC").getTime();
                ticks.push([d, dates[id]]);
            }

            var max_val = 0;
            var graphs = [];
            for (var graph_id = data_all.length - 1; graph_id >= 0; graph_id--) {
                var data = data_all[graph_id];

                if (hidden_methods[data['method_name']]) {
                    continue;
                }

                var graph_data = [];
                for (var id in dates) {
                    var d = dates[id].split("-");
                    d = new Date(d[1] + "/" + d[2] + "/" + d[0] + " UTC").getTime();
                    var value = parseFloat(data['history_data'][param][id].val);
                    if (isNaN(value)) {
                        value = 0;
                    }
                    graph_data.push([d, value]);
                    if (value > max_val) max_val = value;
                }
                if (graph_id == 0) {
                    // current method
                    graphs.push({
                        stack: false,
                        lines: {
                            show: true,
                            fill: false,
                            lineWidth: 2,
                        },
                        label: data['method_name'],
                        data: graph_data,
                        color: "#FF0000"
                    });
                } else {
                    // children method
                    graphs.push({
                        stack: true,
                        color: colors[graph_id],
                        lines: {
                            show: true,
                            fill: 0.6,
                            lineWidth: 0
                        },
                        label: data['method_name'],
                        data: graph_data,
                    });
                }
            }

            if (graphs.length > 0) {
                $.plot("#current_method_graph_" + param, graphs, {
                    series: {
                        points: {show: false},
                        shadowSize: 0
                    },
                    xaxis: {
                        mode: param_data['type'],
                        ticks: ticks,
                        rotateTicks: 155
                    },
                    yaxis: {
                        min: 0,
                        labelWidth: 70,
                        tickFormatter: function (val, axis) {
                            return formatVal(val, param_data['type'], max_val);
                        }
                    },
                    grid: {
                        backgroundColor: {colors: ["#fff", "#eee"]},
                        borderWidth: {
                            top: 1,
                            right: 1,
                            bottom: 2,
                            left: 2
                        },
                        hoverable: true
                    },
                    legend: {
                        show: true,
                        container: $("#legend_container_" + param),
                    },
                    selection: {
                        mode: 'x'
                    }
                });
            }

            $("#current_method_graph_" + param).bind("plothover", tooltipCallback(param_data['type'], max_val));
            $("#current_method_graph_" + param).bind("plotselected", function (event, ranges) {
                let from = (new Date(ranges.xaxis.from)).toISOString().substr(0, 10);
                let to_date = new Date(ranges.xaxis.to);
                to_date.setDate(to_date.getDate() + 1);
                let to = to_date.toISOString().substr(0, 10);

                $('#date1').val(from);
                $('#date2').val(to);
                $('#dates-form').submit();
            });
        }

        function drawAllGraphs() {
            for (name in available_graphs) {
                showGraph(data_all, name, available_graphs[name], dates);
                var checked = JSON.parse(localStorage.getItem('graph_' + name));
                $('#graph_select_' + name).prop('checked', checked).trigger('change');
            }
        }

        function methodSelectorChanged(elem) {
            var checked = elem.is(':checked');
            var method_name = elem.data('method');
            hidden_methods[method_name] = !checked;
        }

        $(function() {
            $("<div id='tooltip'></div>").css({
                position: "absolute",
                display: "none",
                border: "1px solid #fdd",
                padding: "2px",
                "background-color": "#fee",
                width: tooltip_width + "px",
                // opacity: 0.90,
                "word-wrap": "break-word"
            }).appendTo("body");

            $('.legend span').click(function () {
                $(this).closest('.legend').find('div').toggle();
            });

            $('.graph_selector').change(
                function () {
                    var checked = $(this).is(':checked');
                    var id = $(this).attr('id');
                    var name = id.replace('graph_select_', '');
                    var graph = $('#current_method_graph_' + name).parent();
                    if (checked) {
                        graph.show();
                    } else {
                        graph.hide();
                    }
                    localStorage.setItem('graph_' + name, checked)
                }
            );

            drawAllGraphs();

            $('.method-selector').change(
                function () {
                    methodSelectorChanged($(this));
                    if (redraw_graphs_on_update) {
                        $('.line-visibility').removeAttr('checked');
                        drawAllGraphs();
                    }
                }
            );

            $('.line-visibility').click(
                function () {
                    var action = $(this).data('action');
                    redraw_graphs_on_update = false;
                    if (action === 'show') {
                        $( ".method-selector" ).each(function() {
                            var checked = $(this).is(':checked');
                            if (!checked) {
                                $(this).attr('checked', true);
                            }
                            $(this).change();
                        });
                    } else if (action === 'hide') {
                        $( ".method-selector" ).each(function() {
                            var checked = $(this).is(':checked');
                            if ($(this).closest('table.self-stats').length) {
                                $(this).attr('checked', true);
                            } else if (checked) {
                                $(this).removeAttr('checked');
                            }
                            $(this).change();
                        });
                    }
                    redraw_graphs_on_update = true;
                    drawAllGraphs();
                }
            );

            $.tablesorter.addParser({
                // set a unique id
                id: 'parse-values',
                is: function(s, table, cell, $cell) {
                    // apply this parser to all columns
                    return true;
                },
                format: function(s, table, cell, cellIndex) {
                    return s.replace(/,/g,'').replace(/^-$/, '0');
                },
                type: 'numeric'
            });
            var options = {
                theme : 'blue',
                showProcessing: true,
                widthFixed: false,
            };
            $('.sortable').tablesorter(options);
            $('[data-toggle="tooltip"]').tooltip({
                placement: "bottom"
            });
        });
    </script>

    <form class="form-inline">
        <strong>Select graphs:</strong>
        <?php foreach ($data['available_graphs'] as $graph_name => $graph): ?>
            <label class="checkbox">
                <input type="checkbox" class="graph_selector" id="graph_select_<?= $graph_name ?>"> <strong><?= $graph['label'] ?></strong>
            </label>
        <?php endforeach; ?>
    </form>

    <form class="form-inline">
        <strong>Line visibility:</strong>
        <input type="button" class="btn btn-default line-visibility" data-action="show" value="Show all">
        <input type="button" class="btn btn-default line-visibility" data-action="hide" value="Hide children">
    </form>

    <?php foreach ($data['available_graphs'] as $graph_name => $graph): ?>
        <div>
            <h5><?= $graph['graph_label'] ?></h5>
            <div class="legend">
                <span>show legend</span>
                <div id="legend_container_<?= $graph_name ?>"></div>
            </div>
            <div id="current_method_graph_<?= $graph_name ?>" class="badoo_graph" style="height: 200px;"></div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($data['method_data'])): ?>
    <h4>self stat</h4>
    <table class="table table-striped sortable self-stats">
        <?= $data['method_data'] ?>
    </table>
<?php else: ?>
    <span>No data for this period</span>
<?php endif; ?>

<?php if (!empty($data['parents'])): ?>
    <h4>parent stat</h4>
    <table class="table table-striped sortable parents-stats">
        <?= $data['parents'] ?>
    </table>
<?php endif; ?>

<?php if (!empty($data['children'])): ?>
    <h4>children stat</h4>
    <table class="table table-striped sortable children-stats">
        <?= $data['children'] ?>
    </table>
<?php endif; ?>
