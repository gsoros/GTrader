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
                        value="{{ $options['position_size'] ?? 0}}">
            </div>
            <div class="col-sm-3 editable form-group">
                <label for="options[order_type]">Order Type</label>
                <select class="btn-primary form-control form-control-sm"
                        id="order_type"
                        name="options[order_type]"
                        title="Order Type">
                    @foreach (['limit' => 'Limit', 'market' => 'Market'] as $val => $label)
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

    @if ($ccxt_id = $exchange->getParam('ccxt_id'))
        @includeif(
            'Exchanges.CCXT.'.$ccxt_id.'Form',
            ['exchange' => $exchange]
        )
    @endif

@endsection
