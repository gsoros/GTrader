<div class="container">
    @if ($version = $exchange->getCCXTProperty('version'))
        <div class="row">
            <div class="col-sm-12 editable">
                <p class="col-sm-12">
                    Version: {{ $version }}
                </p>
            </div>
        </div>
    @endif
    <div class="row">
        <div class="col-sm-12 editable">
            <label>Symbols</label>
            <p style="max-height: 100px" class="col-sm-12 overflow-auto">
                @php
                    $symbol_ids = array_keys($exchange->getSymbols());
                    $symbol_names = [];
                    foreach ($symbol_ids as $symbol_id) {
                        $symbol_names[] = $exchange->getSymbolName($symbol_id);
                    }
                @endphp
                {{ join(', ', $symbol_names) }}
            </p>
        </div>
    </div>
    @if (count($timeframes = $exchange->getTimeframes()))
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
                            title="Set up {{ $exchange->getCCXTProperty('name') }}">
                        <span class="fas fa-wrench"></span> Set up
                    </button>
                @else
                    Exchange does not support candlestick data.
                @endif
            </div>
        </div>
    </div>
</div>
