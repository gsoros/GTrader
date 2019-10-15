$(function() {

    $(document).keydown(function(event) {
        //console.log('key: ' + event.keyCode);
        if (!$('#mainchart').is(':visible')) {
            return;
        }
        var commands = {};
        commands[37] = 'backward';
        commands[39] = 'forward';
        commands[38] = 'zoomIn'; // up
        commands[40] = 'zoomOut'; // down
        //commands[70] = 'fullscreen'; // f
        if (!(command = commands[event.keyCode])) {
            return;
        }
        if ('fullscreen' === command) {
            window.GTrader.toggleFullscreen('mainchart');
            return;
        }
        event.preventDefault();
        console.log(command);
        window.GTrader.requestPlot('mainchart', command);
    });

});
