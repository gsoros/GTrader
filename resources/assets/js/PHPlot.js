

function setPanZoomPosition(id) {
    $('#panzoom_' + id).css({left: ($(window).width() / 2 - 68) + 'px'});
};

function setChartLoading(id, loading) {
    if (true === loading) {
        var container = $('#' + id);
        if (0 === $('#loading-' + id).length)
            container.append('<img id="loading-' + id + '" src="/img/ajax-loader.gif">');
        $('#loading-' + id).css({
                position: 'absolute',
                top: (container.height() / 2 - 20) + 'px',
                left: (container.width() / 2 - 20) + 'px'});
    }
    else
        $('#loading-' + id).remove();
};

function requestPlot(id, command, args) {
    setChartLoading(id, true);
    var container = $('#' + id);
    var plot = window[id];
    //console.log('res: ' + plot.resolution);
    //console.log('requestPlot(' + id + ', ' + method + ', ' + param + ')');
    var url = '/plot.json?id=' + id +
                '&width=' + container.width() +
                '&height=' + container.height();
    if (undefined !== command)
        url += '&command=' + command;
    if (undefined !== args)
        url += '&args=' + args;
    //['start', 'end', 'limit', 'resolution', 'symbol', 'exchange']
    //.forEach(function(prop) {
    //    if (undefined !== plot[prop] && null !== plot[prop])
    //        url += '&' + prop + '=' + plot[prop];
    //});
    //console.log('request url: ' + url);
    $.ajax({url: url,
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            //console.log(response);
            //console.log('response.start:' + response.start);
            //console.log('response.end:' + response.end)
            window[response.id].start = response.start;
            window[response.id].end = response.end;
            container.html(response.html);
            //updateESR(response.id);
        }});
};

function updateAllPlots() {
    $('.GTraderChart').each(function() {
        var id = $( this ).attr('id');
        requestPlot(id);
        setPanZoomPosition(id);
    });
};

function registerPanZoomHandler(id) {
    ['zoomIn_' + id, 'zoomOut_' + id, 'backward_' + id, 'forward_' + id].forEach(function(id) {
        //console.log(id);
        $('#' + id).on('click', function() {
            var split = this.id.split('_');
            requestPlot(split[1], split[0]);
        });
    });
};

function registerRefreshFunc(id) {
    //console.log('registering refresh() for window.' + id);
    // Register a refresh func
    window[id].refresh = function (command, args) {
        requestPlot(id, command, args);
    };
    //console.log(window[id].refresh);
};






$(window).ready(function() {
    updateAllPlots();
    // For each chart ...
    $('.GTraderChart').each(function() {
        // Ask for ID
        var id = $( this ).attr('id');
        registerRefreshFunc(id);
        registerPanZoomHandler(id);
        setPanZoomPosition(id);
    });
});


$(window).resize(function() {
    waitForFinalEvent(function() {
        updateAllPlots();
    }, 500, 'updateAllPlots');
});
