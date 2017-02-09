function setChartSize () {
    $('#chartContainer').width($(window).width() - 2).height($(window).height() - 120);
};

function setChartLoading(loading) {
    if (true === loading) {
        var container = $('#chartContainer');
        if (0 === $('#loading').length)
            container.append('<img style="position: absolute" id="loading" src="/img/ajax-loader.gif">');
        $('#loading').css('top', container.height() / 2 - 20);
        $('#loading').css('left', container.width() / 2 - 20);
    }
    else
        $('#loading').remove();
};

function requestPlot() {
    setChartSize();
    setChartLoading(true);
    var container = $('#chartContainer');
    //console.log('setPlotSrc()');
    var url = '/plot?width=' + (container.width()) + '&height=' + (container.height());
    $.ajax({url: url, success: function(result){
        container.html(result);
    }});
};



var waitForFinalEvent = (function() {
    var timers = {};
    return function (callback, ms, uniqueId) {
        if (!uniqueId) {
            uniqueId = "uniqueId";
        }
        if (timers[uniqueId]) {
            clearTimeout (timers[uniqueId]);
        }
        timers[uniqueId] = setTimeout(callback, ms);
    };
})();

$(window).ready(function() { 
    requestPlot();
});


$(window).resize(function() {
    waitForFinalEvent(function() {
        requestPlot();
    }, 500, 'requestPlot');
});
