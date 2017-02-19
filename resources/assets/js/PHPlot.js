

function setPanZoomPosition(name) {
    $('#panzoom_' + name).css({left: ($(window).width() / 2 - 68) + 'px'});
};

function setChartLoading(name, loading) {
    if (true === loading) {
        var container = $('#' + name);
        if (0 === $('#loading-' + name).length)
            container.append('<img id="loading-' + name + '" src="/img/ajax-loader.gif">');
        $('#loading-' + name).css({
                position: 'absolute',
                top: (container.height() / 2 - 20) + 'px',
                left: (container.width() / 2 - 20) + 'px'});
    }
    else
        $('#loading-' + name).remove();
};

function requestPlot(name, command, args) {
    setChartLoading(name, true);
    var container = $('#' + name);
    var plot = window[name];
    var url = '/plot.json?name=' + name +
                '&width=' + container.width() +
                '&height=' + container.height();
    if (undefined !== command)
        url += '&command=' + command;
    if (undefined !== args)
        url += '&args=' + args;
    $.ajax({url: url,
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            window[response.name].start = response.start;
            window[response.name].end = response.end;
            container.html(response.html);
        }
    });
};

function updateAllPlots() {
    $('.GTraderChart').each(function() {
        var name = $( this ).attr('id');
        requestPlot(name);
        setPanZoomPosition(name);
    });
};

function registerPanZoomHandler(name) {
    ['zoomIn_' + name, 'zoomOut_' + name, 'backward_' + name, 'forward_' + name].forEach(function(name) {
        $('#' + name).on('click', function() {
            var split = this.id.split('_');
            requestPlot(split[1], split[0]);
        });
    });
};

function registerRefreshFunc(name) {
    //console.log('registering refresh() for window.' + name);
    // Register a refresh func
    window[name].refresh = function (command, args) {
        requestPlot(name, command, args);
    };
    //console.log(window[name].refresh);
};






$(window).ready(function() {
    updateAllPlots();
    // For each chart ...
    $('.GTraderChart').each(function() {
        // Ask for ID
        var name = $( this ).attr('id');
        registerRefreshFunc(name);
        registerPanZoomHandler(name);
        setPanZoomPosition(name);
    });
});


$(window).resize(function() {
    waitForFinalEvent(function() {
        updateAllPlots();
    }, 500, 'updateAllPlots');
});
