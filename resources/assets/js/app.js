
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

//Vue.component('example', require('./components/Example.vue'));

//const app = new Vue({
//    el: '#app'
//});


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

/**
 * Add CSRF header to all ajax requests
 */
/*
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
*/
