$(function() {

    window.GTrader.setPanZoomPosition = function (name) {
        $('#panzoom_' + name).css({left: ($(window).width() / 2 - 68) + 'px'});
    };

    window.GTrader.requestPlot = function (name, command, args) {
        window.setLoading(name, true);
        var container = $('#' + name);
        var plot = window[name];
        var width = $(window).width() - 2;
        console.log('requestPlot: ' + name + ' width: ' + width);
        var url = '/plot.image?name=' + name +
                    '&width=' + width +
                    '&height=' + container.height();
        if (undefined !== command)
            url += '&command=' + command;
        if (undefined !== args)
            url += '&args=' + args;
        console.log('requestPlot ' + url);
        $.ajax({url: url,
            success: function(response) {
                container.html(response);
            }
        });
    };



    window.GTrader.registerPanZoomHandler = function (name) {
        ['zoomIn_' + name, 'zoomOut_' + name, 'backward_' + name, 'forward_' + name].forEach(function(name) {
            $('#' + name).on('click', function() {
                var split = this.id.split('_');
                window.GTrader.requestPlot(split[1], split[0]);
            });
        });
    };


    window.GTrader.registerRefreshFunc = function (name) {
        //console.log('registering refresh() for window.' + name);
        // Register a refresh func
        window[name].refresh = function (command, args) {
            var visible = $('#' + name).is(':visible');
            console.log('refresh() ' + name + ' visible? ' + visible);
            if (!visible)
                window[name].needsRefresh = true;
            else
                window.GTrader.requestPlot(name, command, args);
        };
        //console.log(window[name].refresh);
    };

    window.GTrader.registerPHPlot = function (name, initialRefresh = true) {
        window.GTrader.registerRefreshFunc(name);
        window.GTrader.registerPanZoomHandler(name);
        window.GTrader.setPanZoomPosition(name);
        if (initialRefresh)
            window.GTrader.requestPlot(name);
    };

});


$(window).resize(function() {
    waitForFinalEvent(function() {
        // For each chart ...
        $('.GTraderChart').each(function() {
            var name = $(this).attr('id');
            console.log(name + ' resize');
            window[name].refresh();
            window.GTrader.setPanZoomPosition(name);
        });

    }, 500, 'updateAllPlots');
});
