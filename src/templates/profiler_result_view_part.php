<thead style="background-color: white">
<tr>
    <th class="sorter-false">#</th>
    <?php if (!empty($data['hide_lines_column'])): ?>
        <th class="sorter-false">hide
            <span data-toggle="tooltip" title="Hide the method from graphs" class="glyphicon glyphicon-question-sign"></span>
        </th>
    <?php endif; ?>
    <th class="sorter-text">name</th>
    <?php foreach ($data['fields'] as $param): ?>
        <th class="text-right"><?= $param ?>
            <span data-toggle="tooltip" title="<?= $data['field_descriptions'][$param] ?? '' ?>" class="glyphicon glyphicon-question-sign"></span>
        </th>
    <?php endforeach; ?>
</tr>
</thead>
<tbody>
<?php
    /** @var \Badoo\LiveProfilerUI\Entity\MethodData $MethodData */
    foreach ($data['data'] as $MethodData):
?>
    <tr>
        <td>
            <a href="/profiler/method-usage.phtml?method=<?= $MethodData->getMethodNameAlt() ?>">
                <span class="glyphicon glyphicon-stats" data-toggle="tooltip" title="Goto method usage stats"></span>
            </a>
        </td>
        <?php if (!empty($data['hide_lines_column'])): ?>
            <td>
                <input type="checkbox" class="method-selector" data-method="<?= $MethodData->getMethodName() ?>" checked="checked">
            </td>
        <?php endif; ?>
        <td>
            <a href="<?= $data['link_base'] ?>&method_id=<?= $MethodData->getMethodId() ?>" title="<?= $MethodData->getMethodNameAlt() ?>">
                <?= $MethodData->getMethodName() ?>
            </a>
        </td>
        <?php foreach ($data['fields'] as $param): ?>
            <td class="text-right"><?= $MethodData->getFormattedValue($param) ?></td>
        <?php endforeach; ?>
    </tr>
<?php endforeach; ?>
</tbody>

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
        if ($('#columnSelector').length) {
            options.widgets = ['zebra', 'columnSelector', 'stickyHeaders'];
            options.widgetOptions = {
                // target the column selector markup
                columnSelector_container : $('#columnSelector'),
                // column status, true = display, false = hide
                // disable = do not display on list
                columnSelector_columns : {
                    0: 'disable' /* set to disabled; not allowed to unselect it */
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
                stickyHeaders_filteredToTop: true
            }
            $('#columnSelector').css('z-index', 1);
        }
        $('.sortable').tablesorter(options);
        $('[data-toggle="tooltip"]').tooltip({
            placement: "bottom"
        });
    });
</script>
