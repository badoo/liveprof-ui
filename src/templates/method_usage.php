<link rel="stylesheet" href="/js/rrd/libs/jquery-tablesorter/theme.blue.css">
<link rel="stylesheet" href="/js/jquery-ui/css/jquery-ui.css">
<script src="/js/jquery-ui/jquery-ui.min.js"></script>
<script src="/js/rrd/libs/jquery-tablesorter/jquery.tablesorter.min.js"></script>

<style>
    .sortable {
        font-size: smaller;
    }
</style>

<?php include __DIR__ . '/navbar.block.php'; ?>

<h2>
    Method usage stats for <?= $data['method'] ?> for last <?= $data['period'] ?> days
    <a href="https://github.com/badoo/liveprof-ui/wiki/Web-interface#Find-method-usage" class="glyphicon glyphicon-question-sign" target="_blank" data-toggle="tooltip" title="See the page documentation"></a>
</h2>

<form>
    <label for="method">Method: </label>
    <input id="search-method" name="method" value="<?= $data['method'] ?>">
    <label for="period">Period(days): </label>
    <input type="number" name="period" value="<?= $data['period'] ?>">
    <button class="btn btn-default btn-sm" id="create-ticket-link">Show method stat</button>
</form>

<?php if (!empty($data['error'])) { ?>
<div class="alert alert-danger"><?= $data['error'] ?></div>
<?php } ?>

<?php if (!empty($data['results'])) { ?>
    <table class="table sortable">
        <thead>
        <tr>
            <th class="sorter-false" style="width: 20px;">#</th>
            <th class="sorter-text">date</th>
            <th>method</th>
            <th>label</th>
            <th>app</th>
            <?php foreach ($data['results'][0]['fields'] as $field_name => $field_value) { ?>
                <th>
                    <?= $field_name ?>
                    <span data-toggle="tooltip"  title="<?= $data['field_descriptions'][$field_name] ?? '' ?>" class="glyphicon glyphicon-question-sign">
                    </span>
                </th>
            <?php } ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data['results'] as $result) { ?>
        <tr>
            <td>
                <a href="/profiler/tree-view.phtml?app=<?= urlencode($result['app']) ?>&label=<?= urlencode($result['label']) ?>&method_id=<?= $result['method_id'] ?>">
                    <span class="glyphicon glyphicon-stats" data-toggle="tooltip" title="Goto methods tree"></span>
                </a>
            </td>
            <td><?= $result['date'] ?></td>
            <td><?= $result['method_name'] ?></td>
            <td><?= $result['label'] ?></td>
            <td><?= $result['app'] ?></td>
            <?php foreach ($result['fields'] as $field) { ?>
                <td><?= $field ?></td>
            <?php } ?>
        </tr>
        <?php } ?>
        </tbody>
    </table>
<?php } ?>

<script>
    $(function(){
        $('.sortable').tablesorter({
            theme : 'blue',
            widthFixed: false,
            widgets: ['zebra'],
        });
        $('[data-toggle="tooltip"]').tooltip();

        $('select[data-column=3]').unbind().change(function (event) {
            event.stopPropagation();
            location.replace('?app=' + $(this).val());
        });

        let methods_cache = {};
        $( "#search-method" ).autocomplete({
            minLength: 2,
            source: function( request, response ) {
                let term = request.term;
                if (term in methods_cache) {
                    response( methods_cache[term] );
                    return;
                }

                $.post("/profiler/search-method.json", request, function( data, status, xhr ) {
                    methods_cache[term] = data;
                    response(data);
                },
                'json');
            }
        });
    });
</script>
