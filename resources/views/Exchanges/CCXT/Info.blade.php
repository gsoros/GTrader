@php
    $version = $exchange->getCCXTProperty('version');
    $ccxt_id = $exchange->getCCXTProperty('id');
    $symbol_ids = array_keys($exchange->getSymbols());
    $symbol_names = [];
    foreach ($symbol_ids as $symbol_id) {
        $symbol_names[] = $exchange->getSymbolName($symbol_id);
    }
    $timeframes = $exchange->getTimeframes();
@endphp
<div class="container">
    <div class="row">
        <div class="col-sm-12 editable">
            <p>
                CCXT ID: {{ $ccxt_id }}
                @if (is_string($version))
                    <span class="float-right">API Version: {{ $version }}</span>
                @endif
            </p>
        </div>
    </div>
    @if ($error = $exchange->lastError())
        <div class="row">
            <div class="col-sm-12 alert alert-warning alert-dismissible fade show" role="alert">
                {!! $error !!}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif
    <div class="row">
        <div class="col-sm-12 editable">
            <label>Symbols</label>
            <p style="max-height: 100px" class="col-sm-12 overflow-auto">
                {{ join(', ', $symbol_names) }}
            </p>
        </div>
    </div>
    @if (count($timeframes))
        <div class="row">
            <div class="col-sm-12 editable">
                <label>Timeframes</label>
                <p style="max-height: 100px" class="col-sm-12 overflow-auto">
                    {{ join(', ', $timeframes) }}
                </p>
            </div>
        </div>
    @endif
    <div class="row">
        <div class="col-sm-12 np">
            <div class="float-right">
                @if (true === $exchange->has('fetchOHLCV'))
                    <button onClick="window.GTrader.request(
                                'exchange',
                                'form',
                                {id: {{ $exchange->getId() }}},
                                'GET',
                                'settingsTab'
                            )"
                            type="button"
                            class="btn btn-primary btn-mini trans"
                            title="Set up {{ $exchange->getName() }}">
                        <span class="fas fa-wrench"></span> Set up
                    </button>
                @else
                    Exchange does not support candlestick data.
                @endif
            </div>
        </div>
    </div>
</div>
