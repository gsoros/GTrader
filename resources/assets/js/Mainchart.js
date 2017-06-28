$(function() {

    /**
    * Set chart dimensions
    */
    window.mainchart.setChartSize = function() {
        console.log('setChartSize');
        $('#mainchart').width($(window).width()-2).height($(window).height() - 100);
        this.refresh();
    };
    waitForFinalEvent(function() {
        window.mainchart.setChartSize();
    }, 500, 'setChartSize');

});


$(window).resize(function() {
    waitForFinalEvent(function() {
        window.mainchart.setChartSize();
    }, 500, 'setChartSize');
});
