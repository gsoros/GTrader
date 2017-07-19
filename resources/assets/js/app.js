
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

// Select2
require('select2/dist/js/select2.min.js');

/**
 * Helps avoiding frequent events e.g. on resize
 */
window.waitForFinalEvent = (function() {
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


window.setLoading = function (element, loading) {
    console.log('setLoading(' + element + ')');
    if (true === loading) {
        var container = $('#' + element);
        if (0 === $('#loading-' + element).length)
            container.append('<img id="loading-' + element + '" src="/img/ajax-loader.gif">');
        $('#loading-' + element).css({
                position: 'absolute',
                top: (container.height() / 2 - 20) + 'px',
                left: (container.width() / 2 - 20) + 'px'});
    }
    else
        $('#loading-' + element).remove();
};


$(function () {

    $.fn.select2.defaults.set( "theme", "bootstrap" );

    $.ajaxSetup({
        /**
         * Add CSRF header to all ajax requests
         */
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        /**
         * Handle ajax errors
         */
        error: function (x, status, error) {
            if (x.status == 401 || x.status == 403) {
                console.log('Session expired, redirecting to login.');
                window.location.href ='/login';
            }
            else {
                console.log('Ajax ' + status + ': ' + error);
            }
        }
    });

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr('href');
        console.log('tab change: ' + target);

        var chart = $(target + ' .GTraderChart');
        console.log(chart.attr('id') ? 'we have a chart: ' + chart.attr('id') : 'no chart here');

        if (chart.attr('id')) {
            var chartObj = window[chart.attr('id')];
            if (chartObj.needsRefresh) {
                chartObj.needsRefresh = false;
                chartObj.refresh();
            }
        }
    });
});
