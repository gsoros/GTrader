$(window).ready(function() {

    // For each chart ...
    $('.GTraderChart').each(function() {
        var id = $( this ).attr('id');
        var chartObj = window[id];

        /* Set chart size */
        chartObj.setChartSize = function() {
            $('#' + this.id).width($(window).width()).height($(window).height() - 100);
        };
        chartObj.setChartSize();

        /* Get selected Exchange, Symbol, Resolution values */
        chartObj.getSelectedESR = function() {
            var ret = {};
            var id = this.id;
            ['exchange', 'symbol', 'resolution'].forEach(function(select) {
                ret[select] = $('#' + select + '_' + id).val();
            });
            return ret;
        };

        /* Update the Exchange, Symbol, Resolution dropdown options according to selected values */
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
            $('#exchange_' + id).html(opts.exchange);
            $('#symbol_' + id).html(opts.symbol);
            $('#resolution_' + id).html(opts.resolution);
        };
        chartObj.updateESR();

        /* Register handlers for Exchange, Symbol, Resolution dropdowns */
        ['exchange', 'symbol', 'resolution'].forEach(function(select) {
            // Register onChange func
            $('#' + select + '_' + id).on('change', function() {
                // Get selected values
                chartObj[select] = $('#' + select + '_' + id).val();
                // Update dropdowns
                chartObj.updateESR(select);
                // Send refresh command to the chart
                chartObj.refresh('ESR', JSON.stringify(chartObj.getSelectedESR()));
            });
        });

        /* Register click handler for settings button */
        $('#settings_' + id).on('click', function() {
            // Request settings form from backend
            $.ajax({
                url: '/settings.form?id=' + id,
                success: function(response) {
                    $('#settings_content').html(response);
                }
            });
        });
    });
});

$(window).resize(function() {
    waitForFinalEvent(function() {
        $('.GTraderChart').each(function() {
            window[$( this ).attr('id')].setChartSize();
        });
    }, 500, 'setChartSizes');
});
