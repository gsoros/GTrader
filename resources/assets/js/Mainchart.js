$(function() {

    /**
    * Set chart dimensions
    */
    window.mainchart.setChartSize = function() {
        $('#mainchart').width($(window).width()-2).height($(window).height() - 100);
    };
    window.mainchart.setChartSize();

});


$(window).resize(function() {
    waitForFinalEvent(function() {
        window.mainchart.setChartSize();
    }, 500, 'setChartSize');
});
