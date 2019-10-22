@extends('Exchanges.Form')
@section('child_rows')
    @if ($exchange->has('privateAPI'))
        <div class="row bdr-rad">
            <div class="col-sm-6 editable form-group">
                <label for="api_key">API Key</label>
                <input class="btn-primary form-control form-control-sm"
                        type="text"
                        id="apiKey"
                        name="options[apiKey]"
                        title="API Key"
                        value="{{ $options['apiKey'] ?? ''}}">
            </div>
            <div class="col-sm-6 editable form-group">
                <label for="api_secret">API Secret</label>
                <input class="btn-primary form-control form-control-sm"
                        type="text"
                        id="secret"
                        name="options[secret]"
                        title="API Secret"
                        value="{{ $options['secret'] ?? ''}}">
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
