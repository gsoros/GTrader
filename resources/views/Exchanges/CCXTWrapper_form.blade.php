<form id="exchangeForm">
    <input type="hidden" name="id" value="{{ $exchange->getId() }}">
    <div class="row bdr-rad">
        <div class="col-sm-12">
            <h4>Settings for {{ $exchange->getParam('long_name') }}</h4>
        </div>

        <div class="col-sm-12 editable form-group">
            <label>Exchanges</label>
            <div class="row bdr-rad">
                @foreach ($supported_exchanges as $supported)
                    <div class="col-sm-2 editable form-group">
                        <div class="form-check form-check-inline">
                            <label class="form-check-label" title="{{ $supported['name'] }}">
                                <input class="form-check-input"
                                    id="ccxt_exchange_{{ $supported['id'] }}"
                                    name="exchanges[]"
                                    type="checkbox"
                                    value="{{ $supported['id'] }}"
                                @if (in_array($supported['id'], $selected_exchanges))
                                    checked
                                @endif
                                >
                                {{ $supported['name'] }}
                            </label>
                        </div>
                        <div id="ccxt_symbols_{{ $supported['id'] }}"></div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
    <div class="row bdr-rad">
        <div class="col-sm-12">
            <span class="pull-right">
                <button onClick="window.GTrader.request('exchange', 'list', null, 'GET', 'settingsTab')"
                        type="button"
                        class="btn btn-primary btn-sm trans"
                        title="Discard Changes">
                    <span class="glyphicon glyphicon-remove"></span> Discard Changes
                </button>
                <button onClick="window.GTrader.request('exchange', 'save', $('#exchangeForm').serialize(), 'POST', 'settingsTab')"
                        type="button"
                        class="btn btn-primary btn-sm trans"
                        title="Save Settings">
                    <span class="glyphicon glyphicon-ok"></span> Save Settings
                </button>
            </span>
        </div>
    </div>
</form>

<script>
var g = window.GTrader;
g.ccxtToggleSymbols = function(id, show) {
    if (show) {
        g.request('exchange', 'symbols', {id: {{ $exchange->getId() }}, options: {'id': id}}, 'GET', 'ccxt_symbols_' + id);
        return;
    }
    $('#ccxt_symbols_' + id).html('');
};
$(function() {
    var ccxtExchanges = {!! json_encode($supported_exchanges) !!};
    ccxtExchanges.forEach(function(exchange) {
        var element = $('#ccxt_exchange_' + exchange.id);
        g.ccxtToggleSymbols(exchange.id, element.prop('checked'));
        element.on('change', function() {
            g.ccxtToggleSymbols(exchange.id, this.checked);
        });
    });

});
</script>
