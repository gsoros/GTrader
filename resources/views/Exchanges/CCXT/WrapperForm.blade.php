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
</div>
@php
    $exchanges_with = $exchanges_without = [];
    foreach ($supported_exchanges as $supported) {
        if (true === $supported->has('fetchOHLCV')) {
            $exchanges_with[] = $supported;
            continue;
        }
        if ('emulated' === $supported->has('fetchOHLCV')) {
            $exchanges_without[] = $supported;
        }
    }
@endphp
@foreach (['with', 'without'] as $with_or_without)
    <div class="row">
        <div class="col-sm-12">
            Exchanges {{ $with_or_without }} candlestick data support
        </div>
        <div class="col-sm-12 card-columns">
            @foreach (${'exchanges_'.$with_or_without} as $supported)
                @php
                    $ccxt_id = $supported->getParam('ccxt_id');
                @endphp
                <div class="card trans">
                    <div id="ccxt_card_{{ $ccxt_id }}" class="card-title" title="CCXT ID: {{ $ccxt_id ?? '' }}">
                        @php
                            $name = $supported->getLongName();
                            $logo = is_array($urls = $supported->getCCXTProperty('urls'))
                                && isset($urls['logo']) ?
                                $urls['logo'] :
                                null;
                        @endphp
                        @if ($logo)
                            <img style="border-radius: 3px" src="{{ $logo }}" title="{{ $name }}" alt="{{ $name }}">
                        @endif
                        <strong>{{ $name }}</strong>
                    </div>
                    <div class="card-body">
                        <p id="ccxt_info_{{ $ccxt_id }}"
                            style="display: none;"
                            class="card-text"></p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach
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

<script>
var g = window.GTrader;
g.ccxtInfoLoaded = [];
g.ccxtToggleInfo = function(ccxt_id) {
    if ($('#ccxt_info_' + ccxt_id).is(':visible')) {
        $('#ccxt_info_' + ccxt_id).hide();
        return;
    }
    if (-1 === $.inArray(ccxt_id, g.ccxtInfoLoaded)) {
        g.ccxtGetInfo(ccxt_id);
        return;
    }
    $('#ccxt_info_' + ccxt_id).show();
};

g.ccxtGetInfo = function (ccxt_id) {
    g.setLoading('ccxt_card_' + ccxt_id, true);
    $.ajax({
        url: '/exchange.info?' +
            $.param({
                class: '{{ addslashes($exchange->getClassPrefix()) }}' + ccxt_id
            }, false),
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            g.setLoading('ccxt_card_' + ccxt_id, false);
            if (!response.length) {
                return;
            }
            g.ccxtInfoLoaded.push(ccxt_id);
            $('#ccxt_info_' + ccxt_id).html(response);
            $('#ccxt_info_' + ccxt_id).show();
        },
        error: function(response) {
            g.setLoading('ccxt_card_' + ccxt_id, false);
            if (0 == response.status && 'abort' === response.statusText) {
                return;
            }
            g.errorBubble(
                'ccxt_card_' + ccxt_id,
                response.status + ': ' +
                response.statusText + '<br>' +
                response.responseText.substring(0, 300)
            );
        }
    });
}

$(function() {
    {!! json_encode($supported_exchange_ids) !!}.forEach(function(ccxt_id) {
        var element = $('#ccxt_card_' + ccxt_id);
        element.on('click', function() {
            g.ccxtToggleInfo(ccxt_id);
        }).on('mouseover', function() {
            element.css('cursor', 'pointer');
        });
    });

});
</script>
