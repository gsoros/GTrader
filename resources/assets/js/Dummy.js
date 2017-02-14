$(window).ready(function() {
    // For each chart ...
    $('.GTraderChart').each(function() {
        // Ask for ID
        var id = $( this ).attr('id');
        // add border to chart element
        $('#' + id).css({border: '1px solid red'});
        // Register a refresh func
        window[id].refresh = function (command, args) {
            // Just display received command and args
            $('#' + id).html('refresh(' + command + ', ' + JSON.stringify(args) +')');
        };
    });
});
