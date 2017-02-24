$(function() {

    window.PHPlot = {

        setPanZoomPosition: function (name) {
            $('#panzoom_' + name).css({left: ($(window).width() / 2 - 68) + 'px'});
        },

        requestPlot: function (name, command, args) {
            window.setLoading(name, true);
            var container = $('#' + name);
            var plot = window[name];
            var url = '/plot.image?name=' + name +
                        '&width=' + container.width() +
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
        },



        registerPanZoomHandler: function (name) {
            ['zoomIn_' + name, 'zoomOut_' + name, 'backward_' + name, 'forward_' + name].forEach(function(name) {
                $('#' + name).on('click', function() {
                    var split = this.id.split('_');
                    window.PHPlot.requestPlot(split[1], split[0]);
                });
            });
        },


        registerRefreshFunc: function (name) {
            //console.log('registering refresh() for window.' + name);
            // Register a refresh func
            window[name].refresh = function (command, args) {
                window.PHPlot.requestPlot(name, command, args);
            };
            //console.log(window[name].refresh);
        },

        register: function (name) {
            this.registerRefreshFunc(name);
            this.registerPanZoomHandler(name);
            this.setPanZoomPosition(name);
            this.requestPlot(name);
        }
    }; // window.PHPlot
});


$(window).resize(function() {
    waitForFinalEvent(function() {
        // For each chart ...
        $('.GTraderChart').each(function() {
            var name = $(this).attr('id');
            window.PHPlot.requestPlot(name);
            window.PHPlot.setPanZoomPosition(name);
        });

    }, 500, 'updateAllPlots');
});
