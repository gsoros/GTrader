@php
    $uid = uniqid();
    $sig = $indicator->getSignature();
    $base = $indicator->getParam('indicator.base');
@endphp

<h5>Average</h5>
<div class="row">
    <div class="col-sm-5">
        <label for="base_{{ $uid }}">Base</label>
        <select class="btn-primary btn btn-mini form-control form-control-sm"
                id="base_{{ $uid }}"
                title="Select the base for the indicator">
            @foreach ($chart->getBasesAvailable($sig) as $signature => $display_name)
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
