$(function() {

    /**
    * Set chart dimensions
    */
    window.mainchart.setChartSize = function() {
        console.log('setChartSize');
        $('#mainchart').width($(window).width()-2);
        if (fscreen.default.fullscreenElement !== null) {
            $('#mainchart').height($(window).height());
        }
        else {
            $('#mainchart').height($(window).height() - 100);
        }
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
