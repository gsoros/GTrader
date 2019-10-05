<form id="exchangeForm">
    <div class="row">
        <div class="col-sm-8">
            <h4>Exchanges supported by {{ $exchange->getName() }}</h4>
        </div>
        <div class="col-sm-4">
            <span class="float-right">
                <button onClick="window.GTrader.request(
                            'exchange',
                            'list',
                            null,
                            'GET',
                            'settingsTab'
                        )"
                        type="button"
                        class="btn btn-primary btn-mini trans"
                        title="Back">
                    <span class="fas fa-arrow-left"></span> Back
                </button>
            </span>
        </div>
        <div class="col-sm-12 card-columns">
            @foreach ($supported_exchanges as $supported)
                @php
                    $id = $supported->getParam('ccxt_id');
                @endphp
                <div class="card trans">
                    <div id="ccxt_card_{{ $id }}" class="card-title">
                        <!--<img src="" width="25" height="25">-->
                        <b>{{ $supported->getName() }}</b>
                    </div>
                    <div class="card-body">
                        <p id="ccxt_info_{{ $id }}"
                            style="display: none;"
                            class="card-text"></p>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
    <div class="row bdr-rad">
        <div class="col-sm-12">
            <div class="float-right">
                <button onClick="window.GTrader.request(
                            'exchange',
                            'list',
                            null,
                            'GET',
                            'settingsTab'
                        )"
                        type="button"
                        class="btn btn-primary btn-mini trans"
                        title="Back">
                    <span class="fas fa-arrow-left"></span> Back
                </button>
            </div>
        </div>
    </div>
</form>

<script>
var g = window.GTrader;
g.ccxtInfoLoaded = [];
g.ccxtToggleInfo = function(id) {
    if ($('#ccxt_info_' + id).is(':visible')) {
        $('#ccxt_info_' + id).hide();
        return;
    }
    if (-1 === $.inArray(id, g.ccxtInfoLoaded)) {
        g.ccxtGetInfo(id);
        return;
    }
    $('#ccxt_info_' + id).show();
};

g.ccxtGetInfo = function (id) {
    g.setLoading('ccxt_card_' + id, true);
    $.ajax({
        url: '/exchange.info?id={{ $exchange->getId() }}&' + $.param({options: {id: id}}, false),
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            g.setLoading('ccxt_card_' + id, false);
            if (!response.length) {
                return;
            }
            g.ccxtInfoLoaded.push(id);
            $('#ccxt_info_' + id).html(response);
            $('#ccxt_info_' + id).show();
        },
        error: function(response) {
            g.setLoading('ccxt_card_' + id, false);
            if (0 == response.status && 'abort' === response.statusText) {
                return;
            }
            g.errorBubble(
                'ccxt_card_' + id,
                response.status + ': ' +
                response.statusText + '<br>' +
                response.responseText.substring(0, 300)
            );
        }
    });
}

$(function() {
    {!! json_encode($supported_exchange_ids) !!}.forEach(function(exchange_id) {
        var element = $('#ccxt_card_' + exchange_id);
        element.on('click', function() {
            g.ccxtToggleInfo(exchange_id);
        }).on('mouseover', function() {
            element.css('cursor', 'pointer');
        });
    });

});
</script>
