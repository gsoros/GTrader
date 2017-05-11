@php
    $uid = uniqid();
    $sig = $indicator->getSignature();
    $mode = $indicator->getParam('indicator.mode');
    $capital = $indicator->getParam('indicator.capital');
@endphp

<h5>Balance</h5>
<div class="row">
    <div class="col-sm-5">
        <label for="mode_{{ $uid }}">Mode</label>
        <select class="btn-primary btn btn-mini form-control form-control-sm"
                id="mode_{{ $uid }}"
                title="Select mode">
            @foreach (['fixed', 'dynamic'] as $m)
                <option
                @if ($m == $mode)
                    selected
                @endif
                value="{{ $m }}">{{ $m }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-5">
        <label for="capital_{{ $uid }}">Initial Capital</label>
        <input class="btn-primary btn-mini form-control form-control-sm"
                type="number"
                id="capital_{{ $uid }}"
                title="Select the initial cap for the indicator"
                value="{{ $capital }}">
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
            mode: $('#mode_{{ $uid }}').val(),
            capital: Math.abs(parseInt($('#capital_{{ $uid }}').val()))
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
