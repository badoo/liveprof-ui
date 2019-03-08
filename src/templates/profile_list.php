<link rel="stylesheet" href="/js/rrd/libs/jquery-tablesorter/theme.blue.css">
<script src="/js/rrd/libs/jquery-tablesorter/jquery.tablesorter.min.js"></script>
<script src="/js/rrd/libs/jquery-tablesorter/jquery.tablesorter.pager.js"></script>
<script src="/js/rrd/libs/jquery-tablesorter/jquery.tablesorter.widgets.js"></script>

<style>
    .tablesorter-filter.disabled {
        display: none;
    }
    .sortable {
        font-size: smaller;
    }
    .alert {
        display:none
    }
</style>

<?php include __DIR__ . '/navbar.block.php'; ?>

<h2>
    Profile list
    <a href="https://github.com/badoo/liveprof-ui/wiki/Web-interface#Profile-list" class="glyphicon glyphicon-question-sign" target="_blank" data-toggle="tooltip" title="See the page documentation"></a>
</h2>

<div class="alert alert-info" role="alert"></div>
<div class="alert alert-danger" role="alert"></div>

<form class="aggregate-snapshot-form">
    <label for="app">App: </label>
    <select id="app" name="app">
        <option value="">-select app-</option>
    </select>
    <label for="label">Label: </label>
    <select id="label" name="label">
        <option value="">-select label-</option>
    </select>
    <button class="btn btn-default btn-sm" id="create-ticket-link">Aggregate today snapshot</button>
</form>

<?php if (!empty($data['results'])): ?>
    <table class="table sortable">
        <thead>
        <tr>
            <th class="sorter-false filter-false" style="width: 100px;">#</th>
            <th class="filter-select filter-onlyAvail filter-select-sort-desc sorter-text" data-value="<?= $data['date'] ?>">last snapshot</th>
            <th class="sorter-text filter-label" data-value="<?= $data['label'] ?>">label</th>
            <th class="filter-select filter-onlyAvail sorter-text" data-value="<?= $data['app'] ?>">app</th>
            <th class="filter-false">calls count
                <span data-toggle="tooltip"  title="Calls count recorded during specified day" class="glyphicon glyphicon-question-sign"></span>
            </th>
            <?php
                /** @var \Badoo\LiveProfilerUI\Entity\Snapshot $Snapshot */
                $Snapshot = current($data['results']);
            ?>
            <?php foreach ($Snapshot->getFormattedValues() as $field => $value): ?>
                <th class="filter-false"><?= $field ?>
                    <span data-toggle="tooltip"  title="<?= $data['field_descriptions'][$field] ?? '' ?>" class="glyphicon glyphicon-question-sign"></span>
                </th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data['results'] as $Snapshot): ?>
        <tr>
            <td>
                <a href="/profiler/tree-view.phtml?app=<?= urlencode($Snapshot->getApp()) ?>&label=<?= urlencode($Snapshot->getLabel()) ?>&method_id=0"><span class="glyphicon glyphicon-stats" data-toggle="tooltip" title="Goto methods tree"></span></a>
                <a href="/profiler/result-diff.phtml?app=<?= urlencode($Snapshot->getApp()) ?>&label=<?= urlencode($Snapshot->getLabel()) ?>"><span class="glyphicon glyphicon-sort-by-attributes-alt" data-toggle="tooltip"  title="Goto diff interface"></span></a>
                <a href="/profiler/list-view.phtml?snapshot_id=<?= $Snapshot->getId() ?>"><span class="glyphicon glyphicon-unchecked" data-toggle="tooltip"  title="Goto methods list"></span></a>
                <a href="/profiler/result-flamegraph.phtml?app=<?= urlencode($Snapshot->getApp()) ?>&label=<?= urlencode($Snapshot->getLabel()) ?>&snapshot_id=<?= $Snapshot->getId() ?>"><span class="glyphicon glyphicon-fire" data-toggle="tooltip"  title="Goto flame graph"></span></a>
                <a class="aggregate-snapshot-button" href="#" data-app="<?= $Snapshot->getApp() ?>" data-label="<?= $Snapshot->getLabel() ?>"><span class="glyphicon glyphicon-refresh" data-toggle="tooltip"  title="Aggregate last snapshot"></span></a>
            </td>
            <td><?= $Snapshot->getDate() ?></td>
            <td><?= $Snapshot->getLabel() ?></td>
            <td><?= $Snapshot->getApp() ?></td>
            <td><?= $Snapshot->getCallsCount() ?></td>
            <?php foreach ($Snapshot->getFormattedValues() as $field => $value): ?>
                <td><?= $value ?></td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div id="empty-result-link" style="display: none;">
        Empty result. Try to <a href="">search</a> without the app filter.
    </div>

    <div id="pager" class="pager">
        <form>
            <span class="first glyphicon glyphicon-step-backward" title="First page" ></span>
            <span class="prev glyphicon glyphicon-triangle-left" title="Previous page" ></span>
            <span class="pagedisplay" data-pager-output-filtered="{startRow:input} &ndash; {endRow} / {filteredRows} of {totalRows} total rows"></span>
            <span class="next glyphicon glyphicon-triangle-right" title="Next page" ></span>
            <span class="last glyphicon glyphicon-step-forward" title="Last page" ></span>
            <select class="pagesize">
                <option value="10">10</option>
                <option value="20">20</option>
                <option selected="selected" value="50">50</option>
                <option value="all">all</option>
            </select>
        </form>
    </div>
<?php else: ?>
    Empty list
<?php endif; ?>

<script>
    var new_time_limit = 300;
    var finished_time_limit = 3;
    var check_status_interval = 3000;
    var form_locked = false;
    var jobs_state = {};

    function updateJobsState() {
        var error_msg = '';
        var info_msg = '';
        for (var key in jobs_state) {
            var time = new Date().getTime() / 1000 - jobs_state[key]['time'];
            if ((jobs_state[key]['is_finished'] || jobs_state[key]['is_error']) && time > finished_time_limit) {
                delete jobs_state[key];
                continue;
            } else if (jobs_state[key]['is_new'] && time > new_time_limit) {
                delete jobs_state[key];
                continue;
            }
            if (jobs_state[key]['is_new'] || jobs_state[key]['is_processing']) {
                $.ajax({
                    url: '/profiler/check-snapshot.json',
                    data: {'app': jobs_state[key]['app'], 'label': jobs_state[key]['label']},
                    success: function (result) {
                        if (result) {
                            jobs_state[key]['is_new'] = result.is_new;
                            jobs_state[key]['is_processing'] = result.is_processing;
                            jobs_state[key]['is_finished'] = result.is_finished;
                            jobs_state[key]['is_error'] = result.is_error;
                            jobs_state[key]['message'] = result.message;
                            if (result.is_finished || result.is_error) {
                                jobs_state[key]['time'] = new Date().getTime() / 1000;
                            }
                        }
                    },
                    method: 'post',
                    async: false
                });
            }

            if (jobs_state[key]['is_error']) {
                error_msg += jobs_state[key]['message'] + '<br>';
            } else {
                info_msg += jobs_state[key]['message'] + '<br>';
            }
        }
        $('.alert-info').html(info_msg);
        $('.alert-danger').html(error_msg);
        if (!info_msg) {
            $('.alert-info').hide();
        } else {
            $('.alert-info').show();
        }
        if (!error_msg) {
            $('.alert-danger').hide();
        } else {
            $('.alert-danger').show();
        }
        if (Object.keys(jobs_state).length) {
            setTimeout(updateJobsState, check_status_interval);
        }
    }

    function aggregate_snapshot(data)
    {
        if (form_locked) {
            return false;
        }

        if (!data['app'] || !data['label']) {
            return false;
        }

        var job_key = data['app'] + '-' + data['label'];
        if (job_key in jobs_state) {
            return false;
        }

        form_locked = true;

        $.post(
            '/profiler/rebuild-snapshot.json',
            data,
            function (resp) {
                var is_error = true;
                var is_new = false;
                var msg = false;
                if (resp) {
                    msg = resp.message;
                    if (resp.status === true) {
                        is_new = true;
                        is_error = false;
                    }
                } else {
                    msg = 'Something went wrong while aggregating snapshot';
                }
                form_locked = false;

                jobs_state[job_key] = {
                    'app': data['app'],
                    'label': data['label'],
                    'is_new': is_new,
                    'is_processing': false,
                    'is_finished': false,
                    'is_error': is_error,
                    'message': msg,
                    'time': new Date().getTime() / 1000
                };
                updateJobsState();
            }
        );
    }

    $(function(){
        $.tablesorter.addParser({
            // set a unique id
            id: 'parse-values',
            is: function(s, table, cell, $cell) {
                // apply the parser to all columns
                return true;
            },
            format: function(s, table, cell, cellIndex) {
                return s.replace(/,/g,'').replace(/^-$/, '0');
            },
            type: 'numeric'
        });
        $('.sortable').tablesorter({
            theme : 'blue',
            widthFixed: false,
            showProcessing: true,
            widgets: ['filter', 'zebra'],
            widgetOptions : {
                filter_searchDelay : 50,
                filter_ignoreCase : true,
                filter_resetOnEsc : true,
                filter_functions : {
                    2 : function(e, n, f, i, $r, c, data) {
                        var re = new RegExp(f, "i");
                        return re.test(e);
                    },
                },
                filter_selectSource  : {
                    3 : [
                        <?php foreach ($data['apps'] as $app): ?>
                        { value : '<?= $app ?>', 'data-class' : 'ui-icon-script', text : '<?= $app ?>' },
                        <?php endforeach; ?>
                    ]
                }
            }
        })
            .tablesorterPager({
                container: $("#pager"),
                size: 50,
                savePages : true,
                updateArrows: true,
                output: '{startRow:input} – {endRow} / {totalRows} rows',
            }).bind('filterEnd', function() {
                let found = $('.sortable tbody tr:visible').length;
                let app = "<?= $data['app'] ?>";
                let label = $('.tablesorter-filter[data-column=2]').val();

                if (found === 0 && app !== "" && label !== "") {
                    $('#empty-result-link a').attr("href", "/profiler/result-list.phtml?label=" + label + "&app=");
                    $('#empty-result-link').show();
                } else {
                    $('#empty-result-link').hide();
                }
        });

        $('[data-toggle="tooltip"]').tooltip();

        $('select[data-column=3]').unbind().change(function (event) {
            event.stopPropagation();
            let label = $('.tablesorter-filter[data-column=2]').val();
            location.replace('?app=' + $(this).val() + '&label=' + label);
        });

        $('.aggregate-snapshot-button').on('click', function () {
            aggregate_snapshot({
                app: $(this).data('app'),
                label: $(this).data('label')
            });
            return false;
        });

        $('.aggregate-snapshot-form').on('submit', function () {
            var data = {};
            $($(this).serializeArray()).each(function(index, obj){
                data[obj.name] = obj.value;
            });

            aggregate_snapshot(data);
            return false;
        });

        $.getJSON("/profiler/get-source-app-list.json", null, function(data) {
            $.each(data, function(index, item) {
                $("#app").append(
                    $("<option></option>")
                        .text(item)
                        .val(item)
                );
            });
        });

        $.getJSON("/profiler/get-source-label-list.json", null, function(data) {
            $.each(data, function(index, item) {
                $("#label").append(
                    $("<option></option>")
                        .text(item)
                        .val(item)
                );
            });
        });
    });
</script>
