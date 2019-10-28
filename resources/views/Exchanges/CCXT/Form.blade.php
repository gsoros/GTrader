@extends('Exchanges.Form')
@section('child_rows')

    @if ($exchange->has('privateAPI'))
        <div class="row bdr-rad">
            <div class="col-sm-3 editable form-group">
                <label for="options[apiKey]">API Key</label>
                <input class="btn-primary form-control form-control-sm"
                        type="text"
                        id="apiKey"
                        name="options[apiKey]"
                        title="API Key"
                        value="{{ $options['apiKey'] ?? ''}}">
            </div>
            <div class="col-sm-3 editable form-group">
                <label for="options[secret]">API Secret</label>
                <input class="btn-primary form-control form-control-sm"
                        type="text"
                        id="secret"
                        name="options[secret]"
                        title="API Secret"
                        value="{{ $options['secret'] ?? ''}}">
            </div>
            <div class="col-sm-3 editable form-group">
                <label for="options[position_size]">Position Size %</label>
                <input class="btn-primary form-control form-control-sm"
                        type="number" min="0" max="100" step="1"
                        id="position_size"
                        name="options[position_size]"
                        title="How much of the available balance will be used to open a position"
                        value="{{ $options['position_size'] ?? 1 }}">
            </div>
            <div class="col-sm-3 editable form-group">
                <label for="options[order_type]">Order Type</label>
                <select class="btn-primary form-control form-control-sm"
                        id="order_type"
                        name="options[order_type]"
                        title="Order Type">
                    @foreach ([
                            'limit' => 'Limit with price from signal',
                            'limit_best' => 'Limit, try to get best price',
                            'market' => 'Market'
                        ] as $val => $label)
                        <option value="{{ $val }}"
                        @if ($val == ($options['order_type'] ?? null))
                            selected
                        @endif
                        >{{ $label }}</option>
                    @endforeach
                </select>
                <script>
                    $('#order_type').select2();
                </script>
            </div>
        </div>
    @endif

    @if ($exchange->getParam('has.leverage'))
        @php
            $levels = $exchange->getParam('has.leverage.levels', [1]);
        @endphp
        <div class="row bdr-rad">
            <div class="col-sm-12 editable form-group">
                <label for="options[leverage]">Leverage</label>
                <select class="btn-primary form-control form-control-sm"
                        id="leverage"
                        name="options[leverage]"
                        title="Leverage">
                    @foreach ($levels as $val)
                        <option value="{{ $val }}"
                        @if ($val == ($options['leverage'] ?? 1))
                            selected
                        @endif
                        >{{ $val }}X</option>
                    @endforeach
                </select>
                <script>
                    $('#leverage').select2();
                </script>
            </div>
        </div>
    @endif

    @if ($exchange->getParam('has.testnet'))
        @php
            $label = $exchange->getParam('has.testnet.label', 'Testnet');
        @endphp
        <div class="row bdr-rad">
            <div class="col-sm-12 editable form-group">
                <label for="options[use_testnet]">
                    Use {{ $label }} or Live API
                </label>
                <select class="btn-primary form-control form-control-sm"
                        id="use_testnet"
                        name="options[use_testnet]"
                        title="Use Testnet">
                    @foreach ([0 => 'Use Live API', 1 => 'Use '.$label.' API'] as $val => $label)
                        <option value="{{ $val }}"
                        @if ($val == ($options['use_testnet'] ?? false))
                            selected
                        @endif
                        >{{ $label }}</option>
                    @endforeach
                </select>
                <script>
                    $('#use_testnet').select2();
                </script>
            </div>
        </div>
    @endif

    @if ($ccxt_id = $exchange->getParam('ccxt_id'))
        @includeif(
            'Exchanges.CCXT.'.$ccxt_id.'Form',
            ['exchange' => $exchange]
        )
    @endif

@endsection
