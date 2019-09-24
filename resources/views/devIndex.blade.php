<button onClick="window.GTrader.request('dev', 'vis', [], 'GET', 'devArea');"
        type="button"
        class="btn btn-primary btn-sm trans"
        title="Vis">
    <span class="glyphicon glyphicon-ok"></span> Vis
</button>

<div id="devArea" style="height: 550px;" class="npr npl"></div>

<script>
$(window).resize(function() {
    window.GTrader.waitForFinalEvent(function() {
        console.log('setDevSize');
        $('#devArea').height($(window).height() - 100);
    }, 500, 'setDevSize');
});
</script>
