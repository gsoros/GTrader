<form id="exchangeForm">
    <input type="hidden" name="id" value="{{ $exchange->getId() }}">
    <div class="col-sm-12 editable form-group container">
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
        <div class="row bdr-rad">
            @php
                $exchange_id = $exchange->getId();
                $selected = $exchange->getUserOption('symbols', []);
            @endphp
            @foreach ($exchange->getSymbols() as $symbol_id => $symbol)
                @php
                    // symbol may contain a slash character
                    $md5_symbol_id = md5($symbol_id);
                    $symbol_name = $exchange->getSymbolName($symbol_id);
                @endphp
                <div class="col-sm-4 editable form-group container">
                    <div class="row">
                        <div class="col-sm-3 editable trans" title="{{ $symbol_name }}">
                            {{ $symbol_name }}
                        </div>
                        <div class="col-sm-9 editable form-group">
                            @foreach ($exchange->getResolutions($symbol_id) as $res_time => $res_name)
                                @php
                                    $selected_symbol = $selected[$symbol_id] ?? [];
                                @endphp
                                <span class="form-check form-check-inline">
                                    <label class="form-check-label" title="{{ $res_name }}">
                                        <input class="form-check-input"
                                            id="{{ $md5_symbol_id }}_{{ $res_time }}"
                                            name="options[symbols][{{ $symbol_id }}][resolutions][]"
                                            type="checkbox"
                                            value="{{ $res_time }}"
                                            @if (is_array($selected_symbol)
                                                    && isset($selected_symbol['resolutions'])
                                                    && is_array($selected_symbol['resolutions'])
                                                    && in_array($res_time, $selected_symbol['resolutions']))
                                                checked
                                            @endif
                                        >
                                        {{ $res_name }}
                                    </label>
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
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
