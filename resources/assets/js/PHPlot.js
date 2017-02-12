function setChartSize(id) {
    $('#' + id).width($(window).width()).height($(window).height() - 100);
};

function setChartLoading(id, loading) {
    if (true === loading) {
        var container = $('#' + id);
        if (0 === $('#loading-' + id).length)
            container.append('<img style="position: absolute" id="loading-' + id + '" src="/img/ajax-loader.gif">');
        $('#loading-' + id).css('top', container.height() / 2 - 20);
        $('#loading-' + id).css('left', container.width() / 2 - 20);
    }
    else
        $('#loading-' + id).remove();
};

function requestPlot(id, method, param) {
    setChartSize(id);
    setChartLoading(id, true);
    var container = $('#' + id);
    var plot = window[id];
    //console.log('requestPlot(' + id + ', ' + method + ', ' + param + ')');
    var url = '/plot.json?id=' + id +
                '&width=' + container.width() +
                '&height=' + container.height();
    if (undefined !== method)
        url += '&method=' + method;
    if (undefined !== param)
        url += '&param=' + param;
    ['start', 'end', 'limit', 'resolution', 'symbol', 'exchange']
    .forEach(function(prop) {
        if (undefined !== plot[prop] && null !== plot[prop])
            url += '&' + prop + '=' + plot[prop];
    });
    console.log('request url: ' + url);
    $.ajax({url: url,
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            //console.log(response);
            //console.log('response.start:' + response.start);
            //console.log('response.end:' + response.end)
            window[response.id] = response;
            container.html(response.html);
            updateESR(response.id);
        }});
};

function updateAllPlots() {
    $('.PHPlot').each(function() {
            //console.log($( this ).attr('id'));
            requestPlot($( this ).attr('id'));
        });
};

function registerHandlers() {
    $('.PHPlot').each(function() {
        var id = $( this ).attr('id');
        ['zoomIn_' + id, 'zoomOut_' + id, 'backward_' + id, 'forward_' + id].forEach(function(id) {
            //console.log(id);
            $('#' + id).on('click', function() {
                var split = this.id.split('_');
                requestPlot(split[1], split[0]);
            });
        });
        ['exchange_' + id, 'symbol_' + id, 'resolution_' + id].forEach(function(id) {
            //console.log(id);
            $('#' + id).on('change', function() {
                var split = this.id.split('_');
                window[split[1]][split[0]] = $('#' + id).val();
                updateESR(split[1]);
                getSelectedESR(split[1]);
            });
        });
    });
};

function getSelectedESR(id) {
    console.log('getSelectedESR(' + id + ')');
    var success = 0;
    ['exchange_' + id, 'symbol_' + id, 'resolution_' + id].forEach(function(select) {
        var val = $('#' + select).val();
        if (null === val) {
            console.log(select + ' val is null');
            //console.log($('#' + select));;
            waitForFinalEvent(function() {
                console.log('timeout for ' + id);
                getSelectedESR(id);
                }, 500, 'getSelectedESR', id);
            //debugger;
            //val = $('#' + id + ' option')[0].value;
        }
        else {
            var split = select.split('_');
            window[split[1]][split[0]] = val;
            success++;
        }
    });
    requestPlot(id);
};

function updateESR(id) {
    console.log('updateESR(' + id + ')');
    var opts_exchange = '',
        opts_symbol = '',
        opts_resolution = '';
    window['ESR_' + id].forEach(function(exchange) {
        opts_exchange += '<option ';
        if (window[id].exchange === exchange.name || 1 == window['ESR_' + id].length) {
            opts_exchange += 'selected ';
            exchange.symbols.forEach(function(symbol) {
                opts_symbol += '<option ';
                if (window[id].symbol === symbol.name || 1 == exchange.symbols.length) {
                    opts_symbol += 'selected ';
                    for (var resolution in symbol.resolutions) {
                        opts_resolution += '<option ';
                        if (window[id].resolution == resolution || 1 == symbol.resolutions.length)
                            opts_resolution += 'selected ';
                        opts_resolution += 'value="' + resolution + '">' + symbol.resolutions[resolution] + '</option>';
                    }
                }
                opts_symbol += 'value="' + symbol.name + '">' + symbol.long_name + '</option>';
            });
        }
        opts_exchange += 'value="' + exchange.name + '">' + exchange.long_name + '</option>';
    });
    $('#exchange_' + id).html(opts_exchange);
    $('#symbol_' + id).html(opts_symbol);
    $('#resolution_' + id).html(opts_resolution);
    console.log('options updated');
};

function updateAllESRs() {
    $('.PHPlot').each(function() {
        updateESR($( this ).attr('id'));
    });
};

var waitForFinalEvent = (function() {
    var timers = {};
    return function (callback, ms, uniqueId, arg) {
        if (!uniqueId) {
            uniqueId = "uniqueId";
        }
        if (timers[uniqueId]) {
            clearTimeout(timers[uniqueId]);
        }
        timers[uniqueId] = setTimeout(callback, ms, arg);
    };
})();

$(window).ready(function() {
    updateAllPlots();
    registerHandlers();
    //console.log(esr);
});


$(window).resize(function() {
    waitForFinalEvent(function() {
        updateAllPlots();
    }, 500, 'updateAllPlots');
});
