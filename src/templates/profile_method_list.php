<link rel="stylesheet" href="/js/rrd/libs/jquery-tablesorter/theme.blue.css">
<script src="/js/rrd/libs/jquery-tablesorter/jquery.tablesorter.min.js"></script>
<script src="/js/rrd/libs/jquery-tablesorter/jquery.tablesorter.pager.js"></script>
<script src="/js/rrd/libs/jquery-tablesorter/jquery.tablesorter.widgets.js"></script>
<script src="/profiler/widget-columnSelector.js"></script>

<style>
    @media (min-width: 768px) {
        .nav .breadcrumb {
            float: left;
            margin: 7px 10px;
        }
    }
    .jqstooltip {
        box-sizing: content-box;
        -webkit-box-sizing: content-box;
        -moz-box-sizing: content-box;
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
    /*** custom css only popup ***/
    .columnSelectorWrapper {
        position: relative;
        margin: 10px 0;
        display: inline-block;
    }
    .columnSelector, .hidden {
        display: none;
    }
    .columnSelectorButton {
        background: #99bfe6;
        border: #888 1px solid;
        color: #111;
        border-radius: 5px;
        padding: 5px;
    }
    #colSelect1:checked + label {
        background: #5797d7;
        border-color: #555;
    }
    #colSelect1:checked ~ #columnSelector {
        display: block;
    }
    .columnSelector {
        width: 120px;
        position: absolute;
        top: 30px;
        padding: 10px;
        background: #fff;
        border: #99bfe6 1px solid;
        border-radius: 5px;
    }
    .columnSelector label {
        display: block;
    }
    .columnSelector label:nth-child(1) {
        border-bottom: #99bfe6 solid 1px;
        margin-bottom: 5px;
    }
    .columnSelector input {
        margin-right: 5px;
    }
    .columnSelector .disabled {
        color: #ddd;
    }

    /*** Bootstrap popover ***/
    #popover-target label {
        margin: 0 5px;
        display: block;
    }
    #popover-target input {
        margin-right: 5px;
    }
    #popover-target .disabled {
        color: #ddd;
    }

    .tablesorter-filter.disabled {
        display: none;
    }
</style>

<?php include __DIR__ . '/navbar.block.php'; ?>

<h3>
    Method calls list for <?= $data['snapshot']->getDate() ?> - <?= $data['snapshot']->getApp() ?> - <?= $data['snapshot']->getLabel() ?>
    <a href="https://github.com/badoo/liveprof-ui/wiki/Web-interface#Method-list" class="glyphicon glyphicon-question-sign" target="_blank" data-toggle="tooltip" title="See the page documentation"></a>
</h3>

<div class="btn-group" role="group">
    <a class="btn btn-default<?php if (empty($data['wall'])): ?> btn-primary<?php endif; ?>" href="/profiler/tree-view.phtml?app=<?= urlencode($data['snapshot']->getApp()) ?>&label=<?= urlencode($data['snapshot']->getLabel()) ?>&method_id=0">Methods tree</a>
    <a class="btn btn-default" href="/profiler/result-diff.phtml?app=<?= urlencode($data['snapshot']->getApp()) ?>&label=<?= urlencode($data['snapshot']->getLabel()) ?>">Diff interface</a>
    <a class="btn btn-default<?php if (!empty($data['wall'])): ?> btn-primary<?php endif; ?>" href="/profiler/list-view.phtml?snapshot_id=<?= $data['snapshot']->getId() ?>">Methods list</a>
    <a class="btn btn-default" href="/profiler/result-flamegraph.phtml?app=<?= urlencode($data['snapshot']->getApp()) ?>&label=<?= urlencode($data['snapshot']->getLabel()) ?>&snapshot_id=<?= $data['snapshot']->getId() ?>">Flame graph</a>
</div>

<?php if (!empty($data['wall'])): ?>
<div class="columnSelectorWrapper">
    <input id="colSelect1" type="checkbox" class="hidden">
    <label class="columnSelectorButton" for="colSelect1">Column selector</label>
    <div id="columnSelector" class="columnSelector">
        <!-- this div is where the column selector is added -->
    </div>
    <?php if (empty($data['all'])): ?>
        <a class="btn btn-default" href="/profiler/list-view.phtml?snapshot_id=<?= $data['snapshot']->getId() ?>&all=1">Show all columns</a>
    <?php else: ?>
        <a class="btn btn-default" href="/profiler/list-view.phtml?snapshot_id=<?= $data['snapshot']->getId() ?>&all=0">Show compact</a>
    <?php endif; ?>
</div>

<table class="table table-striped sortable hidden">
    <?= $data['wall'] ?>
</table>

<div id="pager" class="pager hidden">
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

<script>
    // this code may be included a few times
    $(function(){
        if (window.tablesorter_processed) return;
        window.tablesorter_processed = true;

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

        options.widgets = ['zebra', 'columnSelector', 'stickyHeaders', 'filter'];
        options.widgetOptions = {
            // target the column selector markup
            columnSelector_container : $('#columnSelector'),
            // column status, true = display, false = hide
            // disable = do not display on list
            columnSelector_columns : {
                0: 'disable' /* set to disabled; not allowed to unselect it */,
                1: 'disable' /* set to disabled; not allowed to unselect it */,
            },
            // remember selected columns (requires $.tablesorter.storage)
            columnSelector_saveColumns: true,

            // container layout
            columnSelector_layout : '<label><input type="checkbox">{name}</label>',
            // layout customizer callback called for each column
            // function($cell, name, column) { return name || $cell.html(); }
            columnSelector_layoutCustomizer : null,
            // data attribute containing column name to use in the selector container
            columnSelector_name  : 'data-selector-name',

            /* Responsive Media Query settings */
            // enable/disable mediaquery breakpoints
            columnSelector_mediaquery: true,
            // toggle checkbox name
            columnSelector_mediaqueryName: 'Auto: ',
            // breakpoints checkbox initial setting
            columnSelector_mediaqueryState: true,
            // hide columnSelector false columns while in auto mode
            columnSelector_mediaqueryHidden: true,

            // set the maximum and/or minimum number of visible columns; use null to disable
            columnSelector_maxVisible: null,
            columnSelector_minVisible: null,
            // responsive table hides columns with priority 1-6 at these breakpoints
            // see http://view.jquerymobile.com/1.3.2/dist/demos/widgets/table-column-toggle/#Applyingapresetbreakpoint
            // *** set to false to disable ***
            columnSelector_breakpoints : [ '20em', '30em', '40em', '50em', '60em', '70em' ],
            // data attribute containing column priority
            // duplicates how jQuery mobile uses priorities:
            // http://view.jquerymobile.com/1.3.2/dist/demos/widgets/table-column-toggle/
            columnSelector_priority : 'data-priority',

            // class name added to checked checkboxes - this fixes an issue with Chrome not updating FontAwesome
            // applied icons; use this class name (input.checked) instead of input:checked
            columnSelector_cssChecked : 'checked',

            // class name added to rows that have a span (e.g. grouping widget & other rows inside the tbody)
            columnSelector_classHasSpan : 'hasSpan',

            // event triggered when columnSelector completes
            columnSelector_updated : 'columnUpdate',



            // extra class name added to the sticky header row
            stickyHeaders : '',
            // number or jquery selector targeting the position:fixed element
            stickyHeaders_offset : $('.top-bar'),
            // added to table ID, if it exists
            stickyHeaders_cloneId : '-sticky',
            // trigger "resize" event on headers
            stickyHeaders_addResizeEvent : true,
            // if false and a caption exist, it won't be included in the sticky header
            stickyHeaders_includeCaption : true,
            // The zIndex of the stickyHeaders, allows the user to adjust this to their needs
            stickyHeaders_zIndex : 2,
            // jQuery selector or object to attach sticky header to
            stickyHeaders_attachTo : null,
            // jQuery selector or object to monitor horizontal scroll position (defaults: xScroll > attachTo > window)
            stickyHeaders_xScroll : null,
            // jQuery selector or object to monitor vertical scroll position (defaults: yScroll > attachTo > window)
            stickyHeaders_yScroll : null,

            // scroll table top into view after filtering
            stickyHeaders_filteredToTop: true,

            filter_searchDelay : 50,
            filter_ignoreCase : true
        }
        $('#columnSelector').css('z-index', 1);
        $('.sortable')
            .removeClass('hidden')
            .tablesorter(options)
            .tablesorterPager({
                container: $("#pager"),
                size: 50,
                savePages : true,
                updateArrows: true,
                output: '{startRow:input} – {endRow} / {totalRows} rows',
            });
        $('#pager').removeClass('hidden');
        $('[data-toggle="tooltip"]').tooltip({
            placement: "bottom"
        });
    });
</script>
<?php endif; ?>
