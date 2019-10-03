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
                {{ join(', ', $exchange->getSymbols()) }}
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
                <button onClick="alert('TODO'); console.log(
                            'exchange',
                            'new',
                            [{class: 'CCXTWrapper'}, {ccxt_id: '{{ $exchange->getCCXTProperty('id') }}'}],
                            'GET',
                            'settingsTab'
                        )"
                        type="button"
                        class="btn btn-primary btn-mini trans"
                        title="Set up {{ $exchange->getCCXTProperty('name') }}">
                    <span class="fas fa-wrench"></span> Set up
                </button>
            </div>
        </div>
    </div>
</div>
