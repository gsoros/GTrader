<form id="exchangeForm">
    <input type="hidden" name="id" value="{{ $exchange->getId() }}">
    <div class="row bdr-rad">
        <div class="col-sm-12">
            <h4>Settings for {{ $exchange->getName() }}</h4>
        </div>
        <div class="col-sm-4 editable form-group">
            <label for="api_key">API Key</label>
            <input class="btn-primary form-control form-control-sm"
                    type="text"
                    id="api_key"
                    name="options[api_key]"
                    title="API Key"
                    value="{{ $options['api_key'] }}">
        </div>
        <div class="col-sm-4 editable form-group">
            <label for="api_secret">API Secret</label>
            <input class="btn-primary form-control form-control-sm"
                    type="text"
                    id="api_secret"
                    name="options[api_secret]"
                    title="API Secret"
                    value="{{ $options['api_secret'] }}">
        </div>
        <div class="col-sm-4 editable form-group">
            <label for="position_size">Position Size</label>
            <select title="Maximum percentage of capital to be used in a position"
                    class="btn-primary form-control form-control-sm"
                    id="position_size"
                    name="options[position_size]">
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

        @includeIf('Exchanges.'.$exchange->getShortClass().'_form')

        <div class="col-sm-4 editable form-group">
            <label for="market_orders">Order Type</label>
            <select title="Order Type"
                    class="btn-primary form-control form-control-sm"
                    id="market_orders"
                    name="options[market_orders]">
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
            <div class="float-right">
                <button onClick="window.GTrader.request('exchange', 'list', null, 'GET', 'settingsTab')"
                        type="button"
                        class="btn btn-primary btn-mini trans"
                        title="Discard Changes">
                    <span class="fas fa-ban"></span> Discard Changes
                </button>
                <button onClick="window.GTrader.request('exchange', 'save', $('#exchangeForm').serialize(), 'POST', 'settingsTab')"
                        type="button"
                        class="btn btn-primary btn-mini trans"
                        title="Save Settings">
                    <span class="fas fa-check"></span> Save Settings
                </button>
            </div>
        </div>
    </div>
</form>
