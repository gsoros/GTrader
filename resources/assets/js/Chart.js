
function setChartSize(id) {
    $('#' + id).width($(window).width()).height($(window).height() - 100);
};

/* Updates the Exchange, Symbol, Resolution dropdown options accoring to selected values */
function updateESR(id, changed) {
    //console.log('updateESR(' + id + ', ' + changed + ')');
    var opts = {exchange: '', symbol: '', resolution: ''},
        selected = {};
    // loop through all exchanges
    window.ESR.forEach(function(exchange) {                         // loop through all exchanges
        opts.exchange += '<option ';
        if (window[id].exchange === exchange.name                   // selected exchange
                || 1 === window.ESR.length) {                       // or only one exchange
            opts.exchange += 'selected ';                           // found selected exchange
            exchange.symbols.forEach(function(symbol) {             // loop through all symbols within selected exchange
                opts.symbol += '<option ';
                window[id].exchange = exchange.name;
                if ((window[id].symbol === symbol.name              // selected symbol
                        || 1 === exchange.symbols.length            // or only one symbol
                        || 'exchange' === changed)                  // or a different exchange was selected, so we select the first symbol
                        && !selected.symbol) {                      // but we do not yet have a selected symbol
                    opts.symbol += 'selected ';                     // found selected symbol
                    selected.symbol = true;
                    window[id].symbol = symbol.name
                    for (var resolution in symbol.resolutions) {    // loop through all supported resolutions
                        opts.resolution += '<option ';
                        if ((window[id].resolution === resolution   // selected resolution
                                || 1 === symbol.resolutions.length  // or only one resolution
                                || 'exchange' === changed           // or a different exchange was selected, so we select the first resolution
                                || 'symbol' === changed)            // or a different symbol was selected, so we select the first resolution
                                && !selected.resolution) {          // but we do not yet have a selected resoluion
                            opts.resolution += 'selected ';         // found selected resolution
                            selected.resolution = true;
                            window[id].resolution = resolution;
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
    //console.log('UpdateESR() done. E:' + window[id].exchange
    //                + ' S:' + window[id].symbol
    //                + ' R:' + window[id].resolution);
};



/* Get selected Exchange, Symbol, Resolution values */
function getSelectedESR(id) {
    var ret = {};
    ['exchange_' + id, 'symbol_' + id, 'resolution_' + id].forEach(function(select) {
        var split = select.split('_');
        ret[split[0]] = $('#' + select).val();
    });
    return ret;
};
/*
function getSelectedESR(id) {
    var ret = {},
        success = 0,
        val;
    ['exchange_' + id, 'symbol_' + id, 'resolution_' + id].forEach(function(select) {
        val = $('#' + select).val();
        // Sometimes populating the dropdowns is slower than getting the value, retry
        if (null === val || undefined === val) {
            console.log(select + '.val() is null');
            //console.log($('#' + select));;
            waitForFinalEvent(function() {
                console.log('timeout for ' + id);
                ret = getSelectedESR(id);
                }, 500, 'getSelectedESR', id);
        }
        else {
            var split = select.split('_');
            //window[split[1]][split[0]] = val;
            ret[split[0]] = val;
            success++;
        }
    });
    return ret;
};
*/

// Register handlers for Exchange, Symbol, Resolution dropdowns
function registerESRHandlers(id) {
    //console.log('registerESRHandlers(' + id + ')');
    // For each dropdown ...
    ['exchange_' + id, 'symbol_' + id, 'resolution_' + id].forEach(function(id) {
        //console.log('registering ESR handlers for ' + id);
        // Register onChange func
        $('#' + id).on('change', function() {
            var split = this.id.split('_');
            // Get selected values
            window[split[1]][split[0]] = $('#' + id).val();
            // Update dropdowns
            updateESR(split[1], split[0]);
            // Send refresh command to the chart
            //console.log('sending refresh() to window.' + split[1]);
            //console.log(window[split[1]].refresh);
            window[split[1]].refresh('ESR', JSON.stringify(getSelectedESR(split[1])));
        });
    });
};

$(window).ready(function() {
    // For each chart ...
    $('.GTraderChart').each(function() {
        var id = $( this ).attr('id');
        // Populate ESR dropdown options
        updateESR(id);
        // Register handlers
        registerESRHandlers(id);
        // Set chart size
        setChartSize(id);
    });
});

$(window).resize(function() {
    waitForFinalEvent(function() {
        $('.GTraderChart').each(function() {
            var id = $( this ).attr('id');
            setChartSize(id);
        });
    }, 500, 'setChartSizes');
});
