<link rel="stylesheet" href="/js/rrd/libs/jquery-tablesorter/theme.blue.css">
<script src="/js/rrd/libs/jquery-tablesorter/jquery.tablesorter.min.js"></script>
<script src="/js/rrd/libs/jquery-tablesorter/jquery.tablesorter.widgets.js"></script>

<style>
    .sortable {
        font-size: smaller;
    }
    .tablesorter-filter.disabled {
        display: none;
    }
</style>

<?php include __DIR__ . '/navbar.block.php'; ?>

<h2>
    Most changed snapshots (by <?= $data['param'] ?>) from <?= $data['date1'] ?> to <?= $data['date2'] ?>
    <a href="https://github.com/badoo/liveprof-ui/wiki/Web-interface#Most-changed-snapshots" class="glyphicon glyphicon-question-sign" target="_blank" data-toggle="tooltip" title="See the page documentation"></a>
</h2>

<p>This interface allows to find the most differences in method calls between two date. Please, select the dates.</p>

<form class="form-inline">
    <div class="form-group">
        <label for="exampleInputName2">Date from</label>
        <input name="date1" type="date" value="<?= $data['date1'] ?>"  class="form-control" id="exampleInputName2">
    </div>
    <div class="form-group">
        <label for="exampleInputEmail2">Date to</label>
        <input name="date2" type="date" value="<?= $data['date2'] ?>" class="form-control" id="exampleInputEmail2">
    </div>
    <div class="form-group">
        <label for="exampleInputEmail2">Param</label>
        <select id="param" name="param">
            <?php foreach ($data['params'] as $param): ?>
                <option value="<?= $param ?>" <?php if ($param === $data['param']): ?>selected="selected"<?php endif; ?>>
                    <?= $param?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="exampleInputEmail2">Mode</label>
        <select id="mode" name="mode">
            <option value="snapshots" <?php if ($data['mode'] === 'snapshots'): ?>selected="selected"<?php endif; ?>>Snapshots</option>
            <option value="methods_exclude" <?php if ($data['mode'] === 'methods_exclude'): ?>selected="selected"<?php endif; ?>>Methods exclude children</option>
            <option value="methods_include" <?php if ($data['mode'] === 'methods_include'): ?>selected="selected"<?php endif; ?>>Methods include children</option>
        </select>
    </div>
    <button type="submit" class="btn btn-default">Run</button>
</form>

<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger"><?= $data['error'] ?></div>
<?php endif; ?>

<table class="table sortable">
    <thead>
    <tr>
        <th class="sorter-false filter-false" style="width: 100px;">#</th>
        <?php if ($data['mode'] !== 'snapshots'): ?><th>method</th><?php endif; ?>
        <th class="filter-select filter-onlyAvail sorter-text">label</th>
        <th class="filter-select filter-onlyAvail sorter-text">app</th>
        <th class="filter-false"><?= $data['param'] ?> before</th>
        <th class="filter-false"><?= $data['param'] ?> after</th>
        <th class="filter-false">Diff of <?= $data['param'] ?></th>
        <th class="filter-false">Percent diff of <?= $data['param'] ?></th>
    </tr>
    </thead>
    <tbody>
    <?php /** @var \Badoo\LiveProfilerUI\Entity\TopDiff $Result */ ?>
    <?php foreach ($data['data'] as $Result): ?>
        <tr>
            <td>
                <a href="/profiler/tree-view.phtml?app=<?= urlencode($Result->getApp()) ?>&label=<?= urlencode($Result->getLabel()) ?>&method_id=<?= $Result->getMethodId() ?>&date1=<?= $data['date1'] ?>&date2=<?= $data['date2'] ?>"><span class="glyphicon glyphicon-stats" data-toggle="tooltip" title="Goto methods tree"></span></a>
                <a href="/profiler/result-diff.phtml?app=<?= urlencode($Result->getApp()) ?>&label=<?= urlencode($Result->getLabel()) ?>&date1=<?= $data['date1'] ?>&date2=<?= $data['date2'] ?>"><span class="glyphicon glyphicon-sort-by-attributes-alt" data-toggle="tooltip" title="Goto diff interface"></span></a>
                <a href="/profiler/list-view.phtml?app=<?= urlencode($Result->getApp()) ?>&label=<?= urlencode($Result->getLabel()) ?>"><span class="glyphicon glyphicon-unchecked" data-toggle="tooltip" title="Goto methods list"></span></a>
                <a href="/profiler/result-flamegraph.phtml?app=<?= urlencode($Result->getApp()) ?>&label=<?= urlencode($Result->getLabel()) ?>"><span class="glyphicon glyphicon-fire" data-toggle="tooltip" title="Goto flame graph"></span></a>
            </td>
            <?php if ($data['mode'] !== 'snapshots'): ?>
                <td>
                    <a href="/profiler/method-usage.phtml?method=<?= $Result->getMethodName() ?>">
                        <?= $Result->getMethodName() ?>
                    </a>
                </td>
            <?php endif; ?>
            <td><?= $Result->getLabel() ?></td>
            <td><?= $Result->getApp() ?></td>
            <td><?= $Result->getFromValue() ?></td>
            <td><?= $Result->getToValue() ?></td>
            <td><?= $Result->getFormattedValue() ?></td>
            <td><?= $Result->getPercent() ?>%</td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
    $(function(){
        $('.sortable').tablesorter({
            theme : 'blue',
            widthFixed: false,
            widgets: ['zebra', 'filter'],
        });
    });
</script>
