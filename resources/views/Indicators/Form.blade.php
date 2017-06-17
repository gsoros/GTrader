@php
    $uid = uniqid();
    $sig = $indicator->getSignature();
    foreach ([
        'name' => '',
        'owner_class' => 'Chart',
        'owner_id' => 0,
        'target_element' => 'settings_content',
    ] as $varname => $default) {
        $$varname = isset($$varname) ? $$varname : $default;
    };
@endphp

<h5 title="{{ $indicator->getParam('display.description') }}">
    {{ $indicator->getParam('display.name') }}
</h5>
<form class="form-horizontal row">
    @foreach ($indicator->getParam('adjustable', []) as $key => $param)
        <div class="form-group"
            @if (isset($param['description']))
                title="{{ $param['description'] }}"
            @endif
        >
            <label class="col-sm-3 control-label" for="{{ $key }}_{{ $uid }}">{{ $param['name'] }}</label>
            <div class="col-sm-9">
                @if ('base' === $param['type'])
                <select class="btn-primary btn btn-mini form-control form-control-sm"
                        id="{{ $key }}_{{ $uid }}"
                        title="Select the base for the indicator">
                    @foreach ($bases as $signature => $display_name)
                        <option
                        @if ($signature === $indicator->getParam('indicator.'.$key))
                            selected
                        @endif
                        value="{{ $signature }}">{{ $display_name }}</option>
                    @endforeach
                </select>
                @elseif ('select' === $param['type'])
                <select class="btn-primary btn btn-mini form-control form-control-sm"
                        id="{{ $key }}_{{ $uid }}"
                        title="Select the {{ $param['name'] }} for the indicator">
                    @if (is_array($param['options']))
                        @foreach ($param['options'] as $opt_k => $opt_v)
                            <option
                            @if ($opt_k == $indicator->getParam('indicator.'.$key))
                                selected
                            @endif
                            value="{{ $opt_k }}">{{ $opt_v }}</option>
                        @endforeach
                    @endif
                </select>
                @elseif (in_array($param['type'], ['int', 'float']))
                <input type="number"
                    class="btn-primary btn btn-mini form-control form-control-sm"
                    id="{{ $key }}_{{ $uid }}"
                    title="Min: {{ $param['min'] }}, max: {{ $param['max'] }}, step: {{ $param['step'] }}"
                    min="{{ $param['min'] }}"
                    step="{{ $param['step'] }}"
                    max="{{ $param['max'] }}"
                    value="{{ $indicator->getParam('indicator.'.$key) }}">
                @endif
            </div>
        </div>
    @endforeach
    <div class="col-sm-2">
        <button id="save_{{ $uid }}"
                class="btn btn-primary btn-sm trans"
                title="Save changes"
                onClick="return window.save{{ $uid }}()">
            <span class="glyphicon glyphicon-ok"></span>
        </button>
    </div>
</form>

<script>
    window.save{{ $uid }} = function(){
        var params = {
            @foreach ($indicator->getParam('adjustable', []) as $key => $param)
                {{ $key }}: $('#{{ $key }}_{{ $uid }}').val(),
            @endforeach
        };
        window.GTrader.request(
            'indicator',
            'save',
            {
                @php
                    if (isset($pass_vars)) {
                        foreach ($pass_vars as $k => $v) {
                            echo $k.": '".$v."',\n";
                        }
                    }
                @endphp
                name: '{{ $name }}',
                owner_class: '{{ $owner_class }}',
                owner_id: '{{ $owner_id }}',
                signature: '{{ $sig }}',
                params: JSON.stringify(params)
            },
            'POST',
            '{{ $target_element }}');
        return false;
    };
</script>
