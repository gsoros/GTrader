<form id="exchangeForm">
    <input type="hidden" name="id" value="{{ $exchange->getId() }}">
    <div class="row bdr-rad">
        <div class="col-sm-12">
            <h4>Settings for OKCoin Futures Exchange</h4>
        </div>
        <div class="col-sm-4 editable form-group">
            <label for="api_key">API Key</label>
            <input class="btn-primary form-control form-control-sm"
                    type="text"
                    id="api_key"
                    name="api_key"
                    title="API Key"
                    value="{{ $options['api_key'] }}">
        </div>
        <div class="col-sm-4 editable form-group">
            <label for="api_secret">API Secret</label>
            <input class="btn-primary form-control form-control-sm"
                    type="text"
                    id="api_secret"
                    name="api_secret"
                    title="API Secret"
                    value="{{ $options['api_secret'] }}">
        </div>
        <div class="col-sm-4 editable form-group">
            <label for="position_size">Position Size</label>
            <select title="Maximum percentage of capital to be used in a position"
                    class="btn-primary form-control form-control-sm"
                    id="position_size"
                    name="position_size">
            @for ($i=1; $i<=100; $i++)
                <option value="{{ $i }}"
                @if (isset($options['position_size']))
                    @if ($options['position_size'] == $i)
                        selected
                    @endif
                @endif
                >{{ $i }}%</option>
            @endfor
            </select>
        </div>
        <div class="col-sm-4 editable form-group">
            <label for="leverage">Leverage</label>
            <select title="Leverage"
                    class="btn-primary form-control form-control-sm"
                    id="leverage"
                    name="leverage">
            @foreach ([10, 20] as $i)
                <option value="{{ $i }}"
                @if (isset($options['leverage']))
                    @if ($options['leverage'] == $i)
                        selected
                    @endif
                @endif
                >{{ $i }}X</option>
            @endforeach
            </select>
        </div>
        <div class="col-sm-4 editable form-group">
            <label for="max_contracts">Maximum Number of Contracts</label>
            <input class="btn-primary form-control form-control-sm"
                    type="number"
                    id="max_contracts"
                    name="max_contracts"
                    title="Maximum Amount of Contracts"
                    value="{{ $options['max_contracts'] }}">
        </div>
        <div class="col-sm-4 editable form-group">
            <label for="market_orders">Order Type</label>
            <select title="Order Type"
                    class="btn-primary form-control form-control-sm"
                    id="market_orders"
                    name="market_orders">
            @foreach ([0 => 'Limit', 1 => 'Market'] as $k => $v)
                <option value="{{ $k }}"
                @if (isset($options['market_orders']))
                    @if ($options['market_orders'] == $k)
                        selected
                    @endif
                @endif
                >{{ $v }}</option>
            @endforeach
            </select>
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
