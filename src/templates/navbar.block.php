<style>
    .jqstooltip {
        box-sizing: content-box;
        -webkit-box-sizing: content-box;
        -moz-box-sizing: content-box;
    }
    a.glyphicon-question-sign{
        font-size: 0.5em;
        vertical-align: 5px;
        text-decoration: none;
    }
</style>

<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>

<nav class="navbar navbar-default">
    <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Live profiler</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="/profiler/result-list.phtml">Live profiler</a>
    </div>
    <div id="navbar" class="navbar-collapse collapse">
        <ul class="nav navbar-nav">
            <li><a href="/profiler/result-list.phtml">Profile list</a></li>
            <li><a href="/profiler/top-diff.phtml">Top differences</a></li>
            <li><a href="/profiler/method-usage.phtml">Find usage of method</a></li>
        </ul>
    </div>
</nav>
