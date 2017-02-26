$(function() {
    // For each chart ...
    $('.GTraderChart').each(function() {
        // Ask for ID
        var name = $( this ).attr('id');
        // add border to chart element
        $('#' + name).css({border: '1px solid red'});
        // Register a refresh func
        window[name].refresh = function (command, args) {
            // Just display received command and args
            $('#' + name).html('refresh("' + command + '", ' + JSON.stringify(args) +')');
        };
    });
});
