$(window).ready(function() {
    /**
    * Requests an action and inserts the result into the Strategy Tab
    */
    window.strategyRequest = function(request, params, type, target) {
        if (!type) type = 'GET';
        if (!target) target = 'strategyTab';
        window.setLoading(target, true);
        var url = '/strategy.' + request;
        var data = null;
        if (type === 'POST') {
            data = params;
        }
        else {
            if (typeof params === 'object') {
                if (Object.keys(params).length) {
                    var i = 0;
                    $.each(params, function(k, v) {
                        url += (i === 0) ? '?' : '&';
                        url += k + '=' + v;
                        i++;
                    });
                }
            }
            else if (typeof params === 'string')
                url += '?' + params;
        }
        console.log(url);
        $.ajax({
            url: url,
            type: type,
            data: data,
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success: function(response) {
                $('#' + target).html(response);
                console.log('request: ' + request);
                if (-1 == ['list', 'form', 'train', 'trainStart', 'trainStop'].indexOf(request)) {
                    window.Chart.updateAllStrategySelectors();
                    window.mainchart.refresh();
                }
            }
        });
    };

});
