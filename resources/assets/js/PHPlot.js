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
    console.log('requestPlot(' + id + ', ' + method + ', ' + param + ')');
    var url = '/plot.json?id=' + id +
                '&width=' + container.width() +
                '&height=' + container.height();
    if (undefined !== method)
        url += '&method=' + method;
    if (undefined !== param)
        url += '&param=' + param;
    ['start', 'end', 'limit', 'resolution', 'symbol', 'exchange'].forEach(function(prop) {
        if (undefined !== plot[prop])
            url += '&' + prop + '=' + plot[prop];
    });
    console.log('url: ' + url);
    $.ajax({url: url,
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            //console.log('response id:' + response.id);
            console.log('response.start:' + response.start);
            console.log('response.end:' + response.end)
            window[response.id] = response;
            container.html(response.html);
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
    });
};

var waitForFinalEvent = (function() {
    var timers = {};
    return function (callback, ms, uniqueId) {
        if (!uniqueId) {
            uniqueId = "uniqueId";
        }
        if (timers[uniqueId]) {
            clearTimeout(timers[uniqueId]);
        }
        timers[uniqueId] = setTimeout(callback, ms);
    };
})();

$(window).ready(function() {
    updateAllPlots();
    registerHandlers();
});


$(window).resize(function() {
    waitForFinalEvent(function() {
        updateAllPlots();
    }, 500, 'updateAllPlots');
});
