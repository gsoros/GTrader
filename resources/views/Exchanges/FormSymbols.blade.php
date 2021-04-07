@php
    $exchange_id = $exchange->getId();
    $symbols_available = $symbols_configured = [];
    foreach (array_keys($exchange->getSymbols()) as $symbol_id) {
        $symbol = [
            'db_id' => $exchange->getSymbolId($symbol_id),
            'name' => $exchange->getSymbolName($symbol_id),
            'resolutions' => [],
        ];
        $selected_symbol = $selected[$symbol_id] ?? [];
        foreach ($exchange->getResolutions($symbol_id) as $res_time => $res_name) {
            if (is_array($selected_symbol)
                && isset($selected_symbol['resolutions'])
                && is_array($selected_symbol['resolutions'])
                && in_array($res_time, $selected_symbol['resolutions'])) {
                if (!isset($symbols_configured[$symbol_id])) {
                    $symbols_configured[$symbol_id] = $symbol;
                }
                $symbols_configured[$symbol_id]['resolutions'][$res_time] = $res_name;
                continue;
            }
            if (!isset($symbols_available[$symbol_id])) {
                $symbols_available[$symbol_id] = $symbol;
            }
            $symbols_available[$symbol_id]['resolutions'][$res_time] = $res_name;
        }
    }
    ksort($symbols_available);
    ksort($symbols_configured);
    //dump($symbols_configured, $symbols_available);
@endphp
<div class="col-sm-12 container">
    <div class="row">
        @foreach ($symbols_configured as $symbol_id => $symbol)
            <div class="col col-sm2 editable">
                <label class="editable">
                    <a href="#" onClick="
                        $('#new_symbol').val('{{ $symbol_id }}').trigger('change');
                        ">
                        {{ $symbol['name'] }}
                    </a>
                    <button onClick="window.GTrader.request(
                                'exchange',
                                'deleteSymbol',
                                {
                                    id: {{ $exchange_id}},
                                    symbol: '{{ $symbol['name'] }}',
                                },
                                'GET',
                                'exchange_{{ $exchange_id }}_symbols'
                            )"
                            type="button"
                            class="btn btn-primary btn-mini editbutton trans"
                            title="Delete {{ $symbol['name'] }}">
                        <span class="fas fa-trash"></span>
                    </button>
                </label>
                @foreach ($symbol['resolutions'] as $res_time => $res_name)
                    <span class="editable">
                        {{ $res_name }}
                        <button onClick="window.GTrader.request(
                                    'exchange',
                                    'resRangeForm',
                                    {
                                        id: {{ $exchange_id}},
                                        symbol_id: '{{ $symbol_id }}',
                                        res: {{ $res_time }}
                                    },
                                    'GET',
                                    'settingsTab'
                                )"
                                type="button"
                                class="btn btn-primary btn-mini editbutton trans"
                                title="Select the date range to fetch for {{ $res_name }}">
                            <span class="fas fa-arrows-alt-h"></span>
                        </button>
                        <button onClick="window.GTrader.request(
                                    'exchange',
                                    'deleteRes',
                                    {
                                        id: {{ $exchange_id}},
                                        symbol: '{{ $symbol['name'] }}',
                                        res: {{ $res_time }}
                                    },
                                    'GET',
                                    'exchange_{{ $exchange_id }}_symbols'
                                )"
                                type="button"
                                class="btn btn-primary btn-mini editbutton trans"
                                title="Delete {{ $res_name }}">
                            <span class="fas fa-trash"></span>
                        </button>
                    </span>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
<div class="col-sm-12 form-group container editable trans">
    <div class="row">
        <div class="col-sm-9">
            <select id="new_symbol" name="new_symbol"
                style="width: 100%; max-height: 300px; overflow-y: auto;"
                onChange="populateResolutions()">
                <option></option>
                @foreach ($symbols_available as $symbol_id => $symbol)
                    <option value="{{ $symbol_id }}">
                        {{ $symbol['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-2">
            <select id="new_res" name="new_res" style="width: 100%" disabled="disabled"
                onChange="$('#new_symbol_submit').removeAttr('disabled')">
            </select>
        </div>
        <div class="col-sm-1">
            <button onClick="window.GTrader.request(
                        'exchange',
                        'addSymbol',
                        {
                            id: {{ $exchange_id}},
                            new_symbol: $('#new_symbol').val(),
                            new_res: $('#new_res').val()
                        },
                        'GET',
                        'exchange_{{ $exchange_id }}_symbols'
                    )"
                    id="new_symbol_submit"
                    type="button"
                    disabled="disabled"
                    class="btn btn-primary btn-mini trans"
                    title="Add symbol">
                <span class="fas fa-check"></span>
            </button>
        </div>
    </div>
</div>
<script>
    $('#new_symbol').select2({
        width: 'resolve',
        placeholder: 'Add new symbol',
    });
    $('#new_res').select2({
        width: 'resolve'
    });
    var symbols_available = {!! json_encode($symbols_available) !!};
    var populateResolutions = function () {
        var new_symbol = $('#new_symbol').find(':selected')[0].value;
        var resolutions = symbols_available[new_symbol].resolutions;
        $('#new_res').val(null);
        Object.keys(resolutions).sort(function (a, b) {
            return a > b;
        }).forEach(
            function (res_time) {
                $('#new_res').append(new Option(
                    resolutions[res_time],
                    res_time,
                    false,
                    3600 == res_time
                ));
            }
        );
        $('#new_res').removeAttr('disabled').focus().trigger('change').select2('open');
    };
    @if (isset($reload) && is_array($reload) && in_array('ESR', $reload))
    $(function() {
        if (window.GTrader.reloadESR) {
            window.GTrader.reloadESR();
        }
    });
    @endif
</script>
