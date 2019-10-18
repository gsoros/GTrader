$(function() {

    window.GTrader = $.extend(true, window.GTrader, {

        charts: [],

        setPanZoomPosition: function(name) {
            $('#panzoom_' + name).css({
                left: ($(window).width() / 2 - 68) + 'px'
            });
        },

        requestPlot: function(name, command, args) {
            if (!window.GTrader.setLoading) return;
            window.GTrader.setLoading(name, true);
            var container = $('#' + name);
            //var plot = window[name];
            console.log('requestPlot: ' + name + ' height: ' + container.height());
            var params = {
                name: name,
                height: container.height()
            }
            if ('undefined' !== typeof command)
                params.command = command;
            if ('undefined' !== typeof args)
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
        },

        registerPanZoomHandler: function(name) {
            [
                'zoomIn_' + name,
                'zoomOut_' + name,
                'backward_' + name,
                'forward_' + name
            ].forEach(function(name) {
                $('#' + name).on('click', function() {
                    var split = this.id.split('_');
                    window.GTrader.requestPlot(split[1], split[0]);
                });
            });

            $('#fullscreen_' + name).on('click', function() {
                GTrader.toggleFullscreen(name);
            });
        },

        toggleFullscreen: function(name) {
            if ('undefined' === typeof fscreen) {
                console.log('fscreen not defined');
                return;
            }
            if (fscreen.fullscreenElement !== null) {
                if (fscreen.exitFullscreen) {
                    fscreen.exitFullscreen();
                    return;
                }
            }
            if (!fscreen.fullscreenEnabled) {
                console.log('fullscreenEnabled is false');
                return;
            }
            console.log('fullscreen request');
            if (!fscreen.requestFullscreen) {
                console.log('requestFullscreen is false', fscreen.default);
                return;
            }
            /*
                    fscreen.addEventListener('fullscreenchange', function () {
                        if (fscreen.fullscreenElement !== null) {
                            //window[name].setChartSize(true);
                        } else {
                            //window[name].setChartSize();
                        }
                        window.GTrader.setPanZoomPosition(name);
                    }, false);
            */
            fscreen.requestFullscreen($('#fullscreen-wrap_' + name)[0]);
        },

        registerRefreshFunc: function(name) {

            console.log('registering refresh() for ' + name);
            var chart = this.charts[name];

            // Register refresh func
            chart.refresh = function(command, args) {
                var visible = $('#' + name).is(':visible');
                console.log('refresh() ' + name + ' visible? ' + visible);
                if (!visible)
                    window.GTrader.charts[name].needsRefresh = true;
                else
                    window.GTrader.requestPlot(name, command, args);
            };
            console.log(chart);

            $('#' + name).swipe({
                allowPageScroll: 'vertical',
                swipe: function(event, direction, distance, duration, fingerCount, fingerData) {
                    var command;
                    if ('left' == direction) {
                        command = 'forward';
                    } else if ('right' == direction) {
                        command = 'backward';
                    } else if ('up' == direction) {
                        command = 'zoomIn';
                    } else if ('down' == direction) {
                        command = 'zoomOut';
                    } else {
                        return;
                    }
                    window.GTrader.requestPlot(this.prop('id'), command);
                },
            });
        },

        viewSample: function(name, time, clearContents = true) {
            var width = $(window).width() / 2;
            if (400 > width) width = 400;
            if (clearContents) {
                $('#settings_content').html('<div id="sample_container" style="width: ' + width +
                    'px; height: ' + $(window).height() / 2 + 'px">');
            }
            window.GTrader.request(
                'strategy',
                'sample', {
                    chart: name,
                    t: time
                },
                'GET',
                'sample_container'
            );
            return false;
        },

        registerPHPlot: function(name, initialRefresh = true) {
            window.GTrader.registerRefreshFunc(name);
            window.GTrader.registerPanZoomHandler(name);
            window.GTrader.setPanZoomPosition(name);
            if (initialRefresh) {
                window.GTrader.setChartSize(name);
                //window.GTrader.requestPlot(name);
            }
        },

        setChartSize: function(name) {
            console.log('g.setChartSize ' + name);
            //$('#' + name).width($(window).width() - 4);
            $('#' + name).width($('#' + name).parent().width() - 6);
            if (window.GTrader.charts[name].heightPercentage) {
                if (fscreen.fullscreenElement !== null) {
                    $('#' + name).height($(window).height());
                } else {
                    var height =
                        ($(window).height() - 110) *
                        window.GTrader.charts[name].heightPercentage / 100;
                    $('#' + name).height(height);
                    console.log(name + ' height set to ' + height);
                }
            } else
                console.log(name + ' has no heightPercentage');
            if (window.GTrader.charts[name].refresh)
                window.GTrader.charts[name].refresh();
        }
    });
});


$(window).resize(function() {
    console.log('window.resize');
    window.GTrader.waitForFinalEvent(function() {
        // For each chart ...
        $('.GTraderChart').each(function() {
            var name = $(this).attr('id');
            console.log(name + ' resize');
            if (window.GTrader.setChartSize)
                window.GTrader.setChartSize(name);
            else if (window.GTrader.charts[name].refresh)
                window.GTrader.charts[name].refresh();
            if (window.GTrader.setPanZoomPosition)
                window.GTrader.setPanZoomPosition(name);
        });

    }, 500, 'updateAllPlots');
});
