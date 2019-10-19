<ul id="devNav" class="nav navbar nav-pills">
    <li class="nav-item" role="presentation">
        <a class="nav-link" data-toggle="tab" role="tab" href="#"
            onClick="window.GTrader.request('dev', 'vis', [], 'GET', 'devArea');">
            Visualize Object Dump
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" data-toggle="tab" role="tab" href="#"
            onClick="window.GTrader.request('dev', 'dist', [], 'GET', 'devArea');">
            Distributions
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" data-toggle="tab" role="tab" href="#"
            onClick="window.GTrader.request('dev', 'pcache', [], 'GET', 'devArea');">
            PCache
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" data-toggle="tab" role="tab" href="#"
            onClick="window.GTrader.request('dev', 'dump', [], 'GET', 'devArea');">
            Dump Variables
        </a>
    </li>
</ul>

<div id="devArea" style="height: 550px;" class="np"></div>

<script>

$(function() {
    $('#devNav li a').on('click', function() {
        $(this).closest('ul').find('li.active').removeClass('active');
        $(this).parent().addClass('active');
    });
});

$(window).resize(function() {
    window.GTrader.waitForFinalEvent(function() {
        console.log('setDevSize');
        $('#devArea').height($(window).height() - 100);
    }, 500, 'setDevSize');
});

</script>
