<link rel="stylesheet" href="/js/rrd/libs/jquery-tablesorter/theme.blue.css">
<script src="/js/rrd/libs/jquery-tablesorter/jquery.tablesorter.min.js"></script>
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
</style>

<?php include __DIR__ . '/navbar.block.php'; ?>

<h3>Method calls list for <?= $data['snapshot']->getDate() ?> - <?= $data['snapshot']->getApp() ?> - <?= $data['snapshot']->getLabel() ?></h3>

<div class="btn-group" role="group">
    <a class="btn btn-default<?php if (empty($data['wall'])): ?> btn-primary<?php endif; ?>" href="/profiler/tree-view.phtml?app=<?= $data['snapshot']->getApp() ?>&label=<?= $data['snapshot']->getLabel() ?>&method_id=0">Methods tree</a>
    <a class="btn btn-default" href="/profiler/result-diff.phtml?app=<?= $data['snapshot']->getApp() ?>&label=<?= $data['snapshot']->getLabel() ?>">Diff interface</a>
    <a class="btn btn-default<?php if (!empty($data['wall'])): ?> btn-primary<?php endif; ?>" href="/profiler/list-view.phtml?snapshot_id=<?= $data['snapshot']->getId() ?>">Methods list</a>
    <a class="btn btn-default" href="/profiler/result-flamegraph.phtml?app=<?= $data['snapshot']->getApp() ?>&label=<?= $data['snapshot']->getLabel() ?>&snapshot_id=<?= $data['snapshot']->getId() ?>">Flame graph</a>
</div>

<?php if (!empty($data['wall'])): ?>
<div class="columnSelectorWrapper">
    <input id="colSelect1" type="checkbox" class="hidden">
    <label class="columnSelectorButton" for="colSelect1">Column selector</label>
    <div id="columnSelector" class="columnSelector">
        <!-- this div is where the column selector is added -->
    </div>
</div>
<table class="table table-striped sortable">
    <?= $data['wall'] ?>
</table>
<?php endif; ?>
