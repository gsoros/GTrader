$(function() {

    window.GTrader = $.extend(true, window.GTrader, {

        charts: {},
        lastRequest: [],


        waitForFinalEvent: (function() {
            var timers = [];
            return function(callback, ms, uid, arg) {
                if (!uid) {
                    uid = 'uid';
                }
                if (timers[uid]) {
                    clearTimeout(timers[uid]);
                }
                timers[uid] = setTimeout(callback, ms, arg);
                console.log('timeout set for ' + uid);
            };
        })(),


        setLoading: function(element, loading) {
            console.log('setLoading(' + element + ', ' + (true === loading ? 'true' : 'false') + ')');
            if (true === loading) {
                var container = $('#' + element);
                //console.log(element + '.width = ' + container.width());
                if (0 === $('#loading-' + element).length)
                    container.append('<img id="loading-' + element + '" src="/img/ajax-loader.gif">');
                $('#loading-' + element).css({
                    position: 'absolute',
                    top: (container.height() / 2 - 20) + 'px',
                    left: (container.width() / 2 - 20) + 'px'
                });
            } else
                $('#loading-' + element).remove();
        },


        request: function(request, method, params, type, target) {

            if (!type) type = 'GET';
            if (!target) target = request + 'Tab';
            var width = $('#' + target).width();
            if (100 > width) {
                width = $(window).width();
            }
            window.GTrader.setLoading(target, true);
            var url = '/' + request + '.' + method + '?width=' + width;
            var data = null;
            if (type === 'POST') {
                data = params;
            } else {
                if (params) {
                    if (typeof params === 'object') {
                        //console.log(params);
                        url += '&' + $.param(params, false);
                        /*
                        if (Object.keys(params).length) {
                            var i = 0;
                            $.each(params, function(k, v) {
                                //url += (i === 0) ? '?' : '&';
                                url += '&' + k + '=' + encodeURIComponent(v);
                                i++;
                            });
                        }
                        */
                    } else if (typeof params === 'string')
                        url += '&' + params;
                }
            }
            console.log('GTrader.request() Url: ' + url);
            if (typeof this.lastRequest[target] !== 'undefined') {
                //console.log('GTRader.request() Aborting previous request');
                this.lastRequest[target].abort();
            }
            this.lastRequest[target] = $.ajax({
                url: url,
                type: type,
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#' + target).html(response);
                    //console.log('GTraderRequest success: ' + request + '.' + method);
                    if (-1 == [
                            'change',
                            'image',
                            'list',
                            'form',
                            'train',
                            'trainStart',
                            'trainStop',
                            'sample',
                            'sources'
                        ].indexOf(method)) {
                        window.GTrader.updateAllStrategySelectors();
                        window.GTrader.charts.mainchart.refresh();
                    }
                },
                error: function(response) {
                    if (401  == response.status || 403 == response.status) {
                        console.log('Session expired, redirecting to login.');
                        window.location.href = '/login';
                    }
                    if (0 == response.status && 'abort' === response.statusText) {
                        return;
                    }
                    console.log(response);
                    window.GTrader.setLoading(target, false);
                    var msg =
                        (response.status ? response.status + ': ' : '') +
                        (response.statusText ? response.statusText + '<br>' : '') +
                        (response.responseText ? response.responseText : '');
                    msg = msg.length ? msg : 'Unknown error';
                    window.GTrader.errorBubble(target, msg);
                }
            });
        },


        /**
         * Registers a chart
         */
        registerChart: function(name) {

            console.log('registering chart ' + name);
            if (!this.charts[name]) this.charts[name] = {};
            if (this.charts[name].registerCallbacks) {
                this.charts[name].registerCallbacks.forEach(function(callback) {
                    console.log('running callback: ' + callback);
                    callback();
                });
            }

            /**
             * Register click handler for settings button
             */
            $('#settings_' + name).on('click', function() {
                // Request settings form from backend
                window.GTrader.request('settings', 'form', {
                    name: name,
                }, 'GET', 'settings_content');
            });
        }, // registerChart


        /**
         * Registers a strategy selector
         */
        registerStrategySelector: function(name, register_onchange = true, selected) {

            var chart = this.charts[name];
            if (!chart) chart = window[name];
            if (!chart) {
                console.log('registerStrategySelector() could not get obj for ' + name);
                return false;
            }
            /**
             * Requests the strategy dropdown and inserts it into the DOM
             */
            chart.updateStrategySelector = function() {
                var url = '/strategy.selectorOptions?name=' + name;
                if (selected)
                    url += '&selected=' + selected;
                $.ajax({
                    url: url,
                    success: function(response) {
                        $('#strategy_select_' + name).html(response);
                    }
                });
            };
            chart.updateStrategySelector();

            /**
             * Register change handler Strategy dropdown
             */
            if (register_onchange) {
                $('#strategy_select_' + name).on('change', function() {
                    //console.log('#strategy_select_' + name + '.change()');
                    // Send strategy change request to backend
                    $.ajax({
                        url: '/strategy.select?name=' + name +
                            '&strategy=' + $('#strategy_select_' + name).val(),
                        success: function() {
                            // Send refresh command to the chart
                            if (chart.refresh)
                                chart.refresh();
                        }
                    });
                });
            }
        }, // registerStrategySelector


        /**
         * Registers a set of exchange, symbol, resolution selectors
         */
        registerESR: function(name, options) {

            var chart = this.charts[name];
            if (!chart) chart = window[name];
            if (!chart) {
                console.log('registerESR() could not get obj for ' + name);
                return false;
            }
            chart.source = window.GTrader.ESR;
            if ((undefined !== options) && (undefined !== options.source)) {
                chart.source = options.source;
            }

            /**
             * Get selected Exchange, Symbol, Resolution values
             */
            chart.getSelectedESR = function() {
                var ret = {};
                var name = this.name;
                ['exchange', 'symbol', 'resolution'].forEach(function(select) {
                    ret[select] = $('#' + select + '_' + name).val();
                });
                return ret;
            };

            /**
             * Update the Exchange, Symbol, Resolution dropdown options according to selected values
             */
            chart.updateESR = function(changed) {
                var opts = {
                        exchange: '',
                        symbol: '',
                        resolution: ''
                    },
                    selected = {};
                if (undefined === window.GTrader.ESR) {
                    console.log('ESR is undefined');
                    return;
                }
                //console.log('Updating ESR');
                //console.log('chart.esr = ' + chart.exchange + chart.symbol + chart.resolution);
                //console.log(this.source);
                // loop through all exchanges
                this.source.forEach(function(exchange) { // loop through all exchanges
                    opts.exchange += '<option ';
                    if (!chart.exchange || // nothing
                        chart.exchange === exchange.name // or selected exchange
                        ||
                        1 === window.GTrader.ESR.length) { // or only one exchange
                        opts.exchange += 'selected '; // found selected exchange
                        if (undefined === exchange.symbols) return;
                        exchange.symbols.forEach(function(symbol) { // loop through all symbols within selected exchange
                            opts.symbol += '<option ';
                            chart.exchange = exchange.name;
                            if ((chart.symbol === symbol.name // selected symbol
                                    ||
                                    1 === exchange.symbols.length // or only one symbol
                                    ||
                                    ('exchange' === changed && !exchange.symbols.find (
                                        function(s) { return s.name === chart.symbol }
                                    )) // or a different exchange was selected and it does not have the symbol so we select the first symbol
                                ) && !selected.symbol // but we do not yet have a selected symbol
                            ) {
                                opts.symbol += 'selected '; // found selected symbol
                                selected.symbol = true;
                                chart.symbol = symbol.name
                                for (var resolution in symbol.resolutions) { // loop through all supported resolutions
                                    opts.resolution += '<option ';
                                    if ((chart.resolution == resolution // selected resolution
                                            ||
                                            1 === symbol.resolutions.length // or only one resolution
                                            ||
                                            (('exchange' === changed || 'symbol' === changed)
                                            && undefined === symbol.resolutions[resolution]) // or a different exchangue or symbol was selected and it does not have the resolution, so we select the first resolution
                                        ) && !selected.resolution // but we do not yet have a selected resoluion
                                    ) {
                                        opts.resolution += 'selected '; // found selected resolution
                                        selected.resolution = true;
                                        chart.resolution = resolution;
                                    }
                                    opts.resolution += 'value="' + resolution + '">' +
                                        symbol.resolutions[resolution] + '</option>';
                                }
                            }
                            opts.symbol += 'value="' + symbol.name + '" title="' + symbol.name + '">' +
                                symbol.long_name + '</option>';
                        });
                    }
                    opts.exchange += 'value="' + exchange.name + '" title="' + exchange.name + '">' +
                        exchange.long_name + '</option>';
                });
                $('#exchange_' + name).html(opts.exchange);
                $('#symbol_' + name).html(opts.symbol);
                $('#resolution_' + name).html(opts.resolution);
            };
            chart.updateESR();


            /**
             * Register handlers for Exchange, Symbol, Resolution dropdowns
             */
            ['exchange', 'symbol', 'resolution'].forEach(function(select) {
                // Register onChange func
                $('#' + select + '_' + name).on('change', function() {
                    // Get selected values
                    chart[select] = $('#' + select + '_' + name).val();
                    // Update dropdowns
                    chart.updateESR(select);
                    // Send refresh command to the chart
                    if (chart.refresh)
                        chart.refresh('ESR', JSON.stringify(chart.getSelectedESR()));
                    else console.log('refresh not registered for:' + name);
                });
            });
        }, // registerESR


        reloadESR: function() {
            console.log('reloading ESR');
            var g = window.GTrader;

            $.ajax({
                url: '/exchange.ESR',
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    var ESR = JSON.parse(response);
                    if (!ESR.length) {
                        return;
                    }
                    //console.log('old: ', g.ESR);
                    g.ESR.length = 0;
                    g.ESR.push(... ESR);
                    //console.log('new: ', g.ESR);
                    $('.GTraderChart').each(function() {
                        var chart = g.charts[$(this).attr('id')];
                        if (chart.source !== g.ESR) {
                            return;
                        }
                        if (chart.updateESR) {
                            chart.updateESR();
                        }
                        if (chart.refresh) {
                            chart.refresh();
                        }
                    });
                },
                error: function(response) {
                    if (0 == response.status && 'abort' === response.statusText) {
                        return;
                    }
                }
            });
        },


        /**
         * Updates all strategy selectors
         */
        updateAllStrategySelectors: function() {
            $('.GTraderChart').each(function() {
                var chart = window.GTrader.charts[$(this).attr('id')];
                if (chart.updateStrategySelector)
                    chart.updateStrategySelector();
            });
        }, // updateAllStrategySelectors


        errorBubble: function(target, message) {
            var top = $('#' + target).height() / 2 - 10;
            var left = $('#' + target).width() / 2 - 70;
            $('#' + target).append('<div id="errorBubble" class="errorBubble" ' +
                'style="top: -' + top + 'px; left: ' + left + 'px"></div>');
            $('#errorBubble').css({
                opacity: 1,
                transition: 'opacity .5s linear',
            });
            $('#errorBubble').html(message);
            setTimeout(function() {
                $('#errorBubble').css({
                    opacity: 0
                });
                setTimeout(function() {
                    $('#errorBubble').remove();
                }, 3000);
            }, 4000);
        }, // errorBubble


        serializeObject: function(sel) {
            var result = {};
            $.each(sel.serializeArray(), function() {
                result[this.name] = this.value;
            });
            return result;
        },


        clipboardText: async function(text) {
            if (!navigator.clipboard) {
                console.log('Clipboard API not available');
                return;
            }
            if (!text) {
                console.log('Trying to get clipboard contents');
                try {
                    const text = await navigator.clipboard.readText();
                    console.log('Clipboard:', text);
                    return text;
                }
                catch (e) {
                    console.log('Failed to paste text from clipboard', e);
                }
                return;
            }
            navigator.clipboard.writeText(text).then(function() {
                console.log('Success copying this into clipboard:', text);
            }, function() {
                console.log('Failed to copy text to clipboard');
            });
        }
    })
});
