$(function() {

    window.GTrader.setPanZoomPosition = function (name) {
        $('#panzoom_' + name).css({left: ($(window).width() / 2 - 68) + 'px'});
    };

    window.GTrader.requestPlot = function (name, command, args) {
        window.setLoading(name, true);
        var container = $('#' + name);
        var plot = window[name];
        console.log('requestPlot: ' + name);
        var params = {
            name: name,
            height: container.height()
        }
        if (undefined !== command)
            params.command = command;
        if (undefined !== args)
            params.args = args;
        /*
        if (typeof this.lastRequest !== 'undefined') {
            //console.log('requestPlot() Aborting previous request');
            this.lastRequest.abort();
        }
        this.lastRequest = $.ajax({url: url,
            success: function(response) {
                container.html(response);
            }
        });
        */
        this.request('plot', 'image', params, 'GET', name);
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
        $('#' + name).swipe( {
            //Generic swipe handler for all directions
            swipe: function(event, direction, distance, duration, fingerCount, fingerData) {
                var command;
                if ('left' == direction) {
                    command = 'forward';
                }
                else if ('right' == direction) {
                    command = 'backward';
                }
                else if ('up' == direction) {
                    command = 'zoomIn';
                }
                else if ('down' == direction) {
                    command = 'zoomOut';
                }
                else {
                    return;
                }
                window.GTrader.requestPlot(this.prop('id'), command);
            },
            pinchIn: function(event, direction, distance, duration, fingerCount, pinchZoom) {
                window.GTrader.errorBubble(this.prop('id'), 'pi:' + pinchZoom + '  ds:' +
                        distance + ' dir:' + direction);
            },
            pinchOut: function(event, direction, distance, duration, fingerCount, pinchZoom)
            {
                window.GTrader.errorBubble(this.prop('id'), 'po:' + pinchZoom + '  ds:' +
                        distance + ' dir:' + direction);
            },
            pinchStatus: function(event, phase, direction, distance, duration, fingerCount, pinchZoom) {
            },
        });
    };

    window.GTrader.viewSample = function (name, time) {
        window.GTrader.request(
            'strategy',
            'sample', {
                chart: name,
                t: time
            },
            'GET',
            'settings_content'
        );
        return false;
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
