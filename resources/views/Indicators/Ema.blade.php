@php
    $uid = uniqid();
    $sig = $indicator->getSignature();
    $length = $indicator->getParam('indicator.length');
    $base = $indicator->getParam('indicator.base');
@endphp

<h5>Ema</h5>
<div class="row">
    <div class="col-sm-5">
        <label for="length_{{ $uid }}">Length</label>
        <select class="btn-primary btn btn-mini form-control form-control-sm"
                id="length_{{ $uid }}"
                title="Select length">
            @for ($i=2; $i<100; $i++)
                <option
                @if ($i == $length)
                    selected
                @endif
                value="{{ $i }}">{{ $i }}</option>
            @endfor
        </select>
    </div>
    <div class="col-sm-5">
        <label for="base_{{ $uid }}">Base</label>
        <select class="btn-primary btn btn-mini form-control form-control-sm"
                id="base_{{ $uid }}"
                title="Select the index for the indicator">
            @foreach ($chart->getPricesAvailable($sig) as $signature => $display_name)
                <option
                @if ($signature === $base)
                    selected
                @endif
                value="{{ $signature }}">{{ $display_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-2">
        <button id="save_{{ $uid }}"
                class="btn btn-primary btn-sm trans"
                title="Save changes"
                onClick="return window.save{{ $uid }}()">
            <span class="glyphicon glyphicon-ok"></span>
        </button>
    </div>
</div>

<script>
    window.save{{ $uid }} = function(){
        var params = {
            length: $('#length_{{ $uid }}').val(),
            base: $('#base_{{ $uid }}').val()
        };
        window.GTrader.request(
            'indicator',
            'save',
            {
                name: '{{ $name }}',
                signature: '{{ $sig }}',
                params: JSON.stringify(params)
            },
            'POST',
            'settings_content');
        return false;
    };
</script>
