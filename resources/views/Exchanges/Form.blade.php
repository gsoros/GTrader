<form id="exchangeForm">
    <input type="hidden" name="id" value="{{ $exchange->getId() }}">
    <div class="col-sm-12 form-group container">
        <div class="row">
            <div class="col-sm-8">
                <h4>Settings for {{ $exchange->getLongName() }}</h4>
            </div>

            <div class="col-sm-4 text-right">
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
        <label>Symbols / Resolutions to Track</label>
        <div id="exchange_{{ $exchange->getId() }}_symbols" class="row bdr-rad"></div>
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
<script>
    $(function() {
        window.GTrader.request(
            'exchange',
            'formSymbols',
            {id: {{ $exchange->getId() }}},
            'GET',
            'exchange_{{ $exchange->getId() }}_symbols'
        );
    });
</script>
