@php
    if (!$uid = $indicator->getParam('uid')) {
        $uid = uniqid();
    }
    if (!isset($disabled)) {
        $disabled = [];
    }
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

@if (!Arr::get($disabled, 'title'))
<h5 title="{{ $indicator->getParam('display.description') }}">
    {{ $indicator->getParam('display.name') }}
</h5>
@endif
@if (!Arr::get($disabled, 'form'))
<form id="form_{{ $uid }}" class="form-horizontal row">
@endif
    @foreach ($indicator->getParam('adjustable', []) as $key => $param)
        <div class="form-group"
            id="form_group_{{ $uid }}_{{ $key }}"
            @if (isset($param['description']))
                title="{{ $param['description'] }}"
            @endif
        >
            <label class="col-sm-3 control-label" for="{{ $key }}_{{ $uid }}">
                @if ('bool' !== $param['type'])
                    {{ $param['name'] }}
                @endif
            </label>
            <div class="col-sm-9">

                {{-- String --}}
                @if ('string' === $param['type'])
                <input class="btn-primary btn btn-mini form-control form-control-sm"
                        id="{{ $key }}_{{ $uid }}"
                        name="{{ $key }}_{{ $uid }}"
                        title="{{ $param['description'] or '' }}"
                        value="{{ $indicator->getParam('indicator.'.$key) }}">
                </select>

                {{-- Source --}}
                @elseif ('source' === $param['type'])
                <select class="btn-primary btn btn-mini form-control form-control-sm"
                        id="{{ $key }}_{{ $uid }}"
                        name="{{ $key }}_{{ $uid }}"
                        title="{{ $param['description'] or 'Select the source' }}">
                    @php
                    $sources = $indicator->getOwner()->getSourcesAvailable(
                        $indicator->getSignature(),
                        [],
                        Arr::get($param, 'filters', []),
                        Arr::get($param, 'disabled', [])
                    );
                    @endphp
                    @foreach ($sources as $signature => $display_name)
                        <option
                        @php
                        if (!is_string($saved_sig = $indicator->getParam('indicator.'.$key))) {
                            $saved_sig = json_encode($saved_sig);
                        }
                        @endphp
                        @if ($signature === $saved_sig)
                            selected
                        @endif
                        value="{{ urlencode($signature) }}">{{ $display_name }}</option>
                    @endforeach
                </select>
                @if (!in_array('Constant', Arr::get($param, 'disabled', [])))
                <script>
                    $('#{{ $key }}_{{ $uid }}').select2({
                        tags: true,
                        createTag: function (params) {
                            var text = String(parseFloat(params.term));
                            if ('NaN' == text) {
                                return null;
                            }
                            return {
                                id: encodeURIComponent('{"class": "Constant", "params": {"value": "' + text + '"}}'),
                                text: 'Constant (' + text + ')',
                                newOption: true
                            }
                        },
                        templateResult: function (data) {
                            var $result = $('<span></span>');
                            $result.text(data.text);
                            if (data.newOption) {
                                $result.html('<em>' + data.text + '</em>');
                            }
                            return $result;
                        }
                    });
                </script>
                @endif

                {{-- Bool --}}
                @elseif ('bool' === $param['type'])
                <div class="form-check form-check-inline">
                    <label class="form-check-label" title="{{ $param['description'] or '' }}">
                        <input class="form-check-input"
                            id="{{ $key }}_{{ $uid }}"
                            name="{{ $key }}_{{ $uid }}"
                        type="checkbox"
                        value="1"
                        @if ($indicator->getParam('indicator.'.$key))
                            checked
                        @endif
                        >
                        {{ $param['name'] }}
                    </label>
                </div>

                {{-- Select --}}
                @elseif ('select' === $param['type'])
                <select class="btn-primary btn btn-mini form-control form-control-sm"
                        id="{{ $key }}_{{ $uid }}"
                        name="{{ $key }}_{{ $uid }}"
                        title="{{ $param['description'] or '' }}">
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

                {{-- List --}}
                @elseif ('list' === $param['type'])
                <select multiple="mnultiple"  size="20"
                        class="btn-primary btn btn-mini form-control form-control-sm"
                        id="{{ $key }}_{{ $uid }}"
                        name="{{ $key }}_{{ $uid }}"
                        title="{{ $param['description'] or '' }}">
                    @if (is_array($param['items']))
                        @foreach ($param['items'] as $opt_k => $opt_v)
                            <option
                            @if (in_array($opt_k, $indicator->getParam('indicator.'.$key, [])))
                                selected
                            @endif
                            value="{{ $opt_k }}">{{ $opt_v }}</option>
                        @endforeach
                    @endif
                </select>

                {{-- Int, Float --}}
                @elseif (in_array($param['type'], ['int', 'float']))
                    @php
                        $opts = $title = '';
                        foreach (['min', 'max', 'step'] as $field) {
                            if (isset($param[$field])) {
                                if (strlen($opts)) {
                                    $opts .= ' ';
                                }
                                $opts .= $field.'="'.$param[$field].'"';
                                if (strlen($title)) {
                                    $title .= ',';
                                }
                                $title .= ' '.$field.': '.$param[$field];
                            }
                        }
                    @endphp
                    <input type="number"
                        class="btn-primary btn btn-mini form-control form-control-sm"
                        id="{{ $key }}_{{ $uid }}"
                        name="{{ $key }}_{{ $uid }}"
                        title="{{ ucfirst($param['type']) }} {{ $title }} {{ $param['description'] or '' }}"
                        {!! $opts !!}
                        value="{{ $indicator->getParam('indicator.'.$key) }}">
                @endif
            </div>
        </div>
    @endforeach
    @if (!Arr::get($disabled, 'savebutton'))
    <div class="col-sm-2">
        <button id="save_{{ $uid }}"
                class="btn btn-primary btn-sm trans"
                title="Save changes"
                onClick="return window.save_indicator{{ $uid }}()">
            <span class="glyphicon glyphicon-ok"></span>
        </button>
    </div>
    @endif
@if (!Arr::get($disabled, 'form'))
</form>
@endif

@if (!Arr::get($disabled, 'save'))
<script>
    window.save_indicator{{ $uid }} = function(){
        var params = {
            @foreach ($indicator->getParam('adjustable', []) as $key => $param)
                @if ('bool' == $indicator->getParam('adjustable.'.$key.'.type'))
                    {{ $key }}: $('#{{ $key }}_{{ $uid }}').is(':checked') ? 1 : 0,
                @else
                    {{ $key }}: $('#{{ $key }}_{{ $uid }}').val(),
                @endif
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
                signature: '{!! $sig !!}',
                params: JSON.stringify(params)
            },
            'POST',
            '{{ $target_element }}');
        return false;
    };
</script>
@endif
@includeIf('Indicators.'.$indicator->getShortClass().'Form')
