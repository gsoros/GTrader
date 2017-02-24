$(function() {

    window.Chart = {
        /**
        * Registers a chart
        */
        register: function(name) {

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
                    if (chartObj.exchange === exchange.name                     // selected exchange
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
                                    if ((chartObj.resolution === resolution     // selected resolution
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
            * Requests the strategy dropdown and inserts it into the DOM
            */
            chartObj.updateStrategySelector = function() {
                $.ajax({url: '/strategy.selectorOptions?name=' + name,
                    success: function(response) {
                        $('#strategy_select_' + name).html(response);
                    }
                });
            };
            chartObj.updateStrategySelector();

            /**
            * Requests the edit form for an indicator and inserts it into the DOM
            */
            chartObj.requestIndicatorEditForm = function(signature) {
                window.setLoading('form_' + signature, true);
                $.ajax({url: '/indicator.form?name=' + name + '&signature=' + signature,
                    success: function(response) {
                        $('#form_' + signature).html(response);
                    }
                });
            };

            /**
            * Requests deletion for an indicator and inserts the result into the modal
            */
            chartObj.requestIndicatorDelete = function(signature) {
                window.setLoading('settings_content', true);
                $.ajax({url: '/indicator.delete?name=' + name + '&signature=' + signature,
                    success: function(response) {
                        $('#settings_content').html(response);
                        chartObj.refresh();
                    }
                });
            };

            /**
            * Requests creation of a new indicator and inserts the result into the modal
            */
            chartObj.requestIndicatorNew = function(signature) {
                $.ajax({url: '/indicator.new?name=' + name + '&signature=' + signature,
                    success: function(response) {
                        $('#settings_content').html(response);
                        chartObj.refresh();
                    }
                });
            };

            /**
            * Sends an edited indicator and inserts the reply into the modal
            */
            chartObj.requestIndicatorSaveForm = function(signature, params) {
                window.setLoading('settings_content', true);
                $.ajax({
                    type: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url: '/indicator.save',
                    data: { name: name,
                            signature: signature,
                            params: JSON.stringify(params)},
                    success: function(response) {
                        $('#settings_content').html(response);
                        chartObj.refresh();
                    }
                });
            };

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
                    chartObj.refresh('ESR', JSON.stringify(chartObj.getSelectedESR()));
                });
            });

            /**
            * Register change handler Strategy dropdown
            */
            $('#strategy_select_' + name).on('change', function() {
                //console.log('#strategy_select_' + name + '.change()');
                // Send strategy change request to backend
                $.ajax({
                    url: '/strategy.select?name=' + name
                            + '&strategy=' + $('#strategy_select_' + name).val(),
                    success: function(response) {
                        // Send refresh command to the chart
                        chartObj.refresh();
                    }
                });
            });

            /**
            * Register click handler for settings button
            */
            $('#settings_' + name).on('click', function() {
                // Request settings form from backend
                $.ajax({
                    url: '/settings.form?name=' + name,
                    success: function(response) {
                        $('#settings_content').html(response);
                    }
                });
            });
        }, // Chart.register

        /**
        * Updates all strategy selectors
        */
        updateAllStrategySelectors: function() {
            $('.GTraderChart').each(function() {
                window[$( this ).attr('id')].updateStrategySelector();
            });
        } // Chart.updateAllStrategySelectors
    }; // window.Chart
}); // doc.ready

