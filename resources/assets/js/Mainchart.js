$(function() {

    /**
    * Set chart dimensions
    */
    window.mainchart.setChartSize = function() {
        console.log('setChartSize');
        $('#mainchart').width($(window).width()-2).height($(window).height() - 100);
    };
    window.mainchart.setChartSize();

});


$(window).resize(function() {
    waitForFinalEvent(function() {
        window.mainchart.setChartSize();
    }, 500, 'setChartSize');
});
