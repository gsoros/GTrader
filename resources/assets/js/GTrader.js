$(function() {
    /**
    * Requests an action and inserts the result into the DOM
    */
    window.GTrader = {

        request: function(request, method, params, type, target) {

            if (!type) type = 'GET';
            if (!target) target = request + 'Tab';
            window.setLoading(target, true);
            var url = '/' + request + '.' + method;
            var data = null;
            if (type === 'POST') {
                data = params;
            }
            else {
                if (params) {
                    if (typeof params === 'object') {
                        if (Object.keys(params).length) {
                            var i = 0;
                            $.each(params, function(k, v) {
                                url += (i === 0) ? '?' : '&';
                                url += k + '=' + encodeURIComponent(v);
                                i++;
                            });
                        }
                    }
                    else if (typeof params === 'string')
                        url += '?' + params;
                }
            }
            console.log('Url: ' + url);
            $.ajax({
                url: url,
                type: type,
                data: data,
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                success: function(response) {
                    $('#' + target).html(response);
                    console.log('GTraderRequest: ' + request + '.' + method);
                    if (-1 == ['list', 'form', 'train', 'trainStart', 'trainStop'].indexOf(method)) {
                        window.GTrader.updateAllStrategySelectors();
                        window.mainchart.refresh();
                    }
                },
                error: function(response) {
                    window.setLoading(target, false);
                    var top = $('#' + target).height() / 2 - 10;
                    var left = $('#' + target).width() / 2 - 70;
                    console.log('left: ' + left);
                    $('#' + target).append('<span id="errorBubble" class="errorBubble" ' +
                                'style="top: -' + top + 'px; left: ' + left + 'px"><strong>' +
                                response.status + ': ' + response.statusText + '</strong></span>');
                    $('#errorBubble').css({opacity: 1});
                    setTimeout(function() {
                        $('#errorBubble').css({opacity: 0});
                        setTimeout(function() {
                            $('#errorBubble').remove();
                        }, 1000);
                    }, 3000)
                }
            });
        },


        /**
        * Registers a chart
        */
        registerChart: function(name) {

            var chartObj = window[name];

            /**
            * Register click handler for settings button
            */
            $('#settings_' + name).on('click', function() {
                // Request settings form from backend
                window.GTrader.request('settings', 'form', {name: name}, 'GET', 'settings_content');
            });
        }, // registerChart



        /**
        * Registers a strategy selector
        */
        registerStrategySelector: function (name, register_onchange = true, selected) {

            var chartObj = window[name];
            /**
            * Requests the strategy dropdown and inserts it into the DOM
            */
            chartObj.updateStrategySelector = function() {
                var url = '/strategy.selectorOptions?name=' + name;
                if (selected)
                    url += '&selected=' + selected;
                $.ajax({url: url,
                    success: function(response) {
                        $('#strategy_select_' + name).html(response);
                    }
                });
            };
            chartObj.updateStrategySelector();

            /**
            * Register change handler Strategy dropdown
            */
            if (register_onchange) {
                $('#strategy_select_' + name).on('change', function() {
                    //console.log('#strategy_select_' + name + '.change()');
                    // Send strategy change request to backend
                    $.ajax({
                        url: '/strategy.select?name=' + name
                                + '&strategy=' + $('#strategy_select_' + name).val(),
                        success: function() {
                            // Send refresh command to the chart
                            if (chartObj.refresh)
                                chartObj.refresh();
                        }
                    });
                });
            }
        }, // registerStrategySelector



        /**
        * Registers a set of exchange, symbol, resolution selectors
        */
        registerESR: function (name) {

            var chartObj = window[name];

            /**
            * Get selected Exchange, Symbol, Resolution values
            */
            chartObj.getSelectedESR = function() {
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
            chartObj.updateESR = function (changed) {
                var opts = {exchange: '', symbol: '', resolution: ''},
                    selected = {};
                // loop through all exchanges
                window.ESR.forEach(function(exchange) {                         // loop through all exchanges
                    opts.exchange += '<option ';
                    if (!chartObj.exchange ||                                   // nothing
                        chartObj.exchange === exchange.name                     // or selected exchange
                            || 1 === window.ESR.length) {                       // or only one exchange
                        opts.exchange += 'selected ';                           // found selected exchange
                        exchange.symbols.forEach(function(symbol) {             // loop through all symbols within selected exchange
                            opts.symbol += '<option ';
                            chartObj.exchange = exchange.name;
                            if ((chartObj.symbol === symbol.name                // selected symbol
                                    || 1 === exchange.symbols.length            // or only one symbol
                                    || 'exchange' === changed)                  // or a different exchange was selected, so we select the first symbol
                                    && !selected.symbol) {                      // but we do not yet have a selected symbol
                                opts.symbol += 'selected ';                     // found selected symbol
                                selected.symbol = true;
                                chartObj.symbol = symbol.name
                                for (var resolution in symbol.resolutions) {    // loop through all supported resolutions
                                    opts.resolution += '<option ';
                                    if ((chartObj.resolution == resolution      // selected resolution
                                            || 1 === symbol.resolutions.length  // or only one resolution
                                            || 'exchange' === changed           // or a different exchange was selected, so we select the first resolution
                                            || 'symbol' === changed)            // or a different symbol was selected, so we select the first resolution
                                            && !selected.resolution) {          // but we do not yet have a selected resoluion
                                        opts.resolution += 'selected ';         // found selected resolution
                                        selected.resolution = true;
                                        chartObj.resolution = resolution;
                                    }
                                    opts.resolution += 'value="' + resolution + '">'
                                                        + symbol.resolutions[resolution] + '</option>';
                                }
                            }
                            opts.symbol += 'value="' + symbol.name + '">'
                                            + symbol.long_name + '</option>';
                        });
                    }
                    opts.exchange += 'value="' + exchange.name + '">'
                                        + exchange.long_name + '</option>';
                });
                $('#exchange_' + name).html(opts.exchange);
                $('#symbol_' + name).html(opts.symbol);
                $('#resolution_' + name).html(opts.resolution);
            };
            chartObj.updateESR();


            /**
            * Register handlers for Exchange, Symbol, Resolution dropdowns
            */
            ['exchange', 'symbol', 'resolution'].forEach(function(select) {
                // Register onChange func
                $('#' + select + '_' + name).on('change', function() {
                    // Get selected values
                    chartObj[select] = $('#' + select + '_' + name).val();
                    // Update dropdowns
                    chartObj.updateESR(select);
                    // Send refresh command to the chart
                    if (chartObj.refresh)
                        chartObj.refresh('ESR', JSON.stringify(chartObj.getSelectedESR()));
                });
            });
        }, // registerESR

        /**
        * Updates all strategy selectors
        */
        updateAllStrategySelectors: function() {
            $('.GTraderChart').each(function() {
                var chartObj = window[$( this ).attr('id')];
                if (chartObj.updateStrategySelector)
                    chartObj.updateStrategySelector();
            });
        } // updateAllStrategySelectors
    } // window.GTrader
});
