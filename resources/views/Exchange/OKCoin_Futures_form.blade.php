<form id="exchangeForm">
    <input type="hidden" name="id" value="{{ $exchange->getId() }}">
    <div class="row bdr-rad">
        <div class="col-sm-12">
            <h4>Settings for OKCoin Futures Exchange</h4>
        </div>
        <div class="col-sm-6 editable form-group">
            <label for="api_key">API Key</label>
            <input class="btn-primary form-control form-control-sm"
                    type="text"
                    id="api_key"
                    name="api_key"
                    title="API Key"
                    value="{{ $options['api_key'] }}">
        </div>
        <div class="col-sm-6 editable form-group">
            <label for="api_secret">API Secret</label>
            <input class="btn-primary form-control form-control-sm"
                    type="text"
                    id="api_secret"
                    name="api_secret"
                    title="API Secret"
                    value="{{ $options['api_secret'] }}">
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
