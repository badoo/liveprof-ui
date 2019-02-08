<link rel="stylesheet" href="/js/rrd/libs/jquery-tablesorter/theme.blue.css">
<script src="/js/rrd/libs/jquery-tablesorter/jquery.tablesorter.min.js"></script>

<style>
    .sortable {
        font-size: smaller;
    }
</style>

<?php include __DIR__ . '/navbar.block.php'; ?>

<h2>Diff of <?= $data['param'] ?> from <?= $data['date1'] ?> to <?= $data['date2'] ?></h2>

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
        <label for="exampleInputEmail2">Param</label>
        <select id="exclude" name="exclude">
            <option value="1" <?php if ($data['exclude']): ?>selected="selected"<?php endif; ?>>Exclude children</option>
            <option value="0" <?php if (!$data['exclude']): ?>selected="selected"<?php endif; ?>>Include children</option>
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
        <th>method</th>
        <th>label</th>
        <th>app</th>
        <th><?= $data['param'] ?> before</th>
        <th><?= $data['param'] ?> after</th>
        <th>Diff of <?= $data['param'] ?></th>
        <th>Percent diff of <?= $data['param'] ?></th>
    </tr>
    </thead>
    <tbody>
    <?php /** @var \Badoo\LiveProfilerUI\Entity\TopDiff $Result */ ?>
    <?php foreach ($data['data'] as $Result): ?>
        <tr>
            <td>
                <a href="/profiler/tree-view.phtml?app=<?= $Result->getApp() ?>&label=<?= $Result->getLabel() ?>&method_id=<?= $Result->getMethodId() ?>"><span class="glyphicon glyphicon-stats" data-toggle="tooltip" title="Goto methods tree"></span></a>
                <a href="/profiler/result-diff.phtml?app=<?= $Result->getApp() ?>&label=<?= $Result->getLabel() ?>&date1=<?= $data['date1'] ?>&date2=<?= $data['date2'] ?>"><span class="glyphicon glyphicon-sort-by-attributes-alt" data-toggle="tooltip" title="Goto diff interface"></span></a>
                <a href="/profiler/list-view.phtml?app=<?= $Result->getApp() ?>&label=<?= $Result->getLabel() ?>"><span class="glyphicon glyphicon-unchecked" data-toggle="tooltip" title="Goto methods list"></span></a>
                <a href="/profiler/result-flamegraph.phtml?app=<?= $Result->getApp() ?>&label=<?= $Result->getLabel() ?>"><span class="glyphicon glyphicon-fire" data-toggle="tooltip" title="Goto flame graph"></span></a>
            </td>
            <td>
                <a href="/profiler/method-usage.phtml?method=<?= $Result->getMethodName() ?>">
                    <?= $Result->getMethodName() ?>
                </a>
            </td>
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
            widgets: ['zebra'],
        });
    });
</script>
