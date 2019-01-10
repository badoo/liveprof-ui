<?php include __DIR__ . '/navbar.block.php'; ?>

<h2>Flame graph
    <?php if (isset($data['snapshot'])): ?>
        for <?= $data['snapshot']->getDate() ?> - <?= $data['snapshot']->getApp() ?> - <?= $data['snapshot']->getLabel() ?>
    <?php endif; ?>
</h2>

<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger" role="alert"><?= $data['error'] ?></div>
<?php else: ?>
    <div class="btn-group" role="group">
        <a class="btn btn-default" href="/profiler/tree-view.phtml?app=<?= $data['snapshot']->getApp() ?>&label=<?= $data['snapshot']->getLabel() ?>&method_id=0">Methods tree</a>
        <a class="btn btn-default" href="/profiler/result-diff.phtml?app=<?= $data['snapshot']->getApp() ?>&label=<?= $data['snapshot']->getLabel() ?>">Diff interface</a>
        <a class="btn btn-default" href="/profiler/list-view.phtml?snapshot_id=<?= $data['snapshot']->getId() ?>">Methods list</a>
        <a class="btn btn-default btn-primary" href="/profiler/result-flamegraph.phtml?app=<?= $data['snapshot']->getApp() ?>&label=<?= $data['snapshot']->getLabel() ?>&snapshot_id=<?= $data['snapshot']->getId() ?>">Flame graph</a>
    </div>

    <div>
        <form style="display: inline;">
            <label for="label">Param: </label>
            <select id="param" name="param">
                <?php foreach ($data['params'] as $param): ?>
                    <option value="<?= $param['value'] ?>" <?php if (!empty($param['selected'])): ?>selected="selected"<?php endif; ?>>
                        <?= $param['label'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="snapshot_id" value="<?= $data['snapshot']->getId() ?>">
            <button class="btn btn-default btn-sm" id="create-ticket-link">Get flame graph</button>
        </form>
    </div>

    <table class="table table-striped">
        <?= $data['svg'] ?>
    </table>
<?php endif; ?>
