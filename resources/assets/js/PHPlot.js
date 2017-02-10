function setChartSize (id) {
    $('#' + id).width($(window).width() - 2).height($(window).height() - 120);
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

function requestPlot(id) {
    setChartSize(id);
    setChartLoading(id, true);
    var container = $('#' + id);
    var url = '/plot?width=' + (container.width()) + '&height=' + (container.height());
    $.ajax({url: url,
        contentType: 'application/json',
        dataType: 'json',
        success: function(result) {
            //console.log(result);
            window[result.id] = result;
            container.html(result.html);
        }});
};

function updateAllPlots() {
    $('.PHPlot').each(function(){
            //console.log($( this ).attr('id'));
            requestPlot($( this ).attr('id'));
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
});


$(window).resize(function() {
    waitForFinalEvent(function() {
        updateAllPlots();
    }, 500, 'updateAllPlots');
});
