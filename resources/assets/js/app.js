
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

// Select2
require('select2/dist/js/select2.min.js');




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
            var chartObj = window.GTrader.charts[chart.attr('id')];
            if (chartObj.needsRefresh) {
                chartObj.needsRefresh = false;
                chartObj.refresh();
            }
        }
    });
});
