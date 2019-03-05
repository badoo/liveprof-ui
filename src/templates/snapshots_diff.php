<style>
    .sortable {
        font-size: smaller;
    }
</style>

<?php include __DIR__ . '/navbar.block.php'; ?>

<h2>
    Diff interface - <?= $data['app'] ?>:<?= $data['label'] ?>
    <a href="https://github.com/badoo/liveprof-ui/wiki/Web-interface#Snapshots-comparison-interface" class="glyphicon glyphicon-question-sign" target="_blank" data-toggle="tooltip" title="See the page documentation"></a>
</h2>

<div class="btn-group" role="group">
    <a class="btn btn-default" href="/profiler/tree-view.phtml?app=<?= $data['app'] ?>&label=<?= $data['label'] ?>&method_id=0">Methods tree</a>
    <a class="btn btn-default btn-primary" href="/profiler/result-diff.phtml?app=<?= $data['app'] ?>&label=<?= $data['label'] ?>">Diff interface</a>
    <a class="btn btn-default" href="/profiler/list-view.phtml?app=<?= $data['app'] ?>&label=<?= $data['label'] ?>">Methods list</a>
    <a class="btn btn-default" href="/profiler/result-flamegraph.phtml?app=<?= $data['app'] ?>&label=<?= $data['label'] ?>">Flame graph</a>
</div>

<p>This interface allows to compare two snapshots with different dates. Please, select the dates.</p>

<form class="form-inline">
    <input type="hidden" name="app" value="<?= $data['app'] ?>">
    <input type="hidden" name="label" value="<?= $data['label'] ?>">
    <input type="hidden" name="diff" value="1">
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
    <button type="submit" class="btn btn-default">Compare versions</button>
    <button type="button" class="btn btn-success flame-graph-btn">Go to flame graph</button>
</form>

<?php if (!empty($data['date1']) && empty($data['snapshot1'])): ?>
    <p>Cannot fetch snapshot for date <?= $data['date1'] ?></p>
<?php endif; ?>

<?php if (!empty($data['date2']) && empty($data['snapshot2'])): ?>
    <p>Cannot fetch snapshot for date <?= $data['date2'] ?></p>
<?php endif; ?>

<?php if (!empty($data['diff'])): ?>
    <table class="table sortable">
        <tr>
            <th class="text-right">name</th>
            <th class="text-right">
                <?= $data['param'] ?> delta&nbsp;<span data-toggle="tooltip" title="Difference of the method in first and second snapshots" class="glyphicon glyphicon-question-sign"></span>
            </th>
            <th class="text-right">
                ct1&nbsp;<span data-toggle="tooltip" title="Average calls count of the method during snapshot 1" class="glyphicon glyphicon-question-sign"></span>
            </th>
            <th class="text-right">
                ct2&nbsp;<span data-toggle="tooltip" title="Average calls count of the method during snapshot 2" class="glyphicon glyphicon-question-sign"></span>
            </th>
            <th class="text-right">
                info&nbsp;<span data-toggle="tooltip" title="Diffence inside the method, which led to Delta" class="glyphicon glyphicon-question-sign"></span>
            </th>
        </tr>
        <?php foreach ($data['diff'] as $diff): ?>
            <tr>
                <td>
                    <a href="<?= $data['link_base'] ?>&method_id=<?= $diff['method_id'] ?>" title="<?= $diff['name_alt'] ?>"><?= $diff['name'] ?></a>
                </td>
                <td class="text-right">
                    <?= $diff['delta'] ?>
                </td>
                <td class="text-right"><?= $diff['ct1'] ?></td>
                <td class="text-right"><?= $diff['ct2'] ?></td>
                <td class="text-right">
                    <table class="table">
                        <tr>
                            <th class="text-right">name</th>
                            <?php
                            $fields = current($diff['info'])['fields'];
                            foreach ($fields as $field => $field_values):
                            ?>
                                <th class="text-right">
                                    <?= $field ?>1
                                    <span data-toggle="tooltip" title="<?= $data['field_descriptions'][$field] ?? '' ?> in snapshot 1" class="glyphicon glyphicon-question-sign"></span>
                                </th>
                                <th class="text-right">
                                    <?= $field ?>2
                                    <span data-toggle="tooltip" title="<?= $data['field_descriptions'][$field] ?? '' ?> in snapshot 1" class="glyphicon glyphicon-question-sign"></span>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                        <?php foreach ($diff['info'] as $info): ?>
                            <tr>
                                <td><a href="<?= $data['link_base'] ?>&method_id=<?= $info['method_id'] ?>" title="<?= $info['name_alt'] ?>"><?= $info['name'] ?></a></td>
                                <?php foreach ($info['fields'] as $field_values): ?>
                                    <td><?= $field_values[1] ?></td>
                                    <td><?= $field_values[2] ?></td>
                                <?php endforeach; ?>

                            </tr>
                        <?php endforeach; ?>
                    </table>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<script>
    $(function(){
        $('.flame-graph-btn').on('click', function () {
            var form = $(this).closest('form');
            form.attr('action', '/profiler/result-flamegraph.phtml');
            form.submit();
        })
    });
</script>
