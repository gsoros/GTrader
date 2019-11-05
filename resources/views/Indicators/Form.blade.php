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
    $first = true;
@endphp

@if (!in_array('title', $disabled))
    <h5 title="{{ $indicator->getParam('display.description') }}">
        {{ $indicator->getParam('display.name') }}
    </h5>
@endif

@if (!in_array('form', $disabled))
    <form id="form_{{ $uid }}" class="form-horizontal container-fluid np">
@endif

@php
    $row_opened = false;
@endphp

@foreach ($indicator->getParam('adjustable', []) as $key => $param)
    @php
        $desc = $param['description'] ?? '';
        $display = $param['display'] ?? [];
        $hide = $display['hide'] ?? [];
        $prev_group = $group ?? null;
        $group = isset($display['group']) ? $display['group']['label'] ?? null : null;
        $cols = isset($display['group']) ? $display['group']['cols'] ?? 3 : 9;
        $group_desc = isset($display['group']) ? $display['group']['description'] ?? $param['name'] : $param['name'];
        GTrader\Log::debug($param['name'], $prev_group, $group, $cols, $group_desc);
    @endphp
    @if (!$group || ($group && ($prev_group !== $group)))
        @if ($prev_group || (!$prev_group && !$first))
            </div>
        @endif
        <div class="form-group row"
            id="form_group_{{ $uid }}_{{ $key }}"
            @if ($desc)
                title="{{ $desc }}"
            @endif
        >
        @php
            $row_opened = true;
        @endphp
    @endif
        @if (!in_array('label', $hide))
            <label class="col-sm-2 control-label npl" for="{{ $key }}_{{ $uid }}">
                @if ('bool' !== $param['type'])
                    {{ $param['name'] }}
                @endif
            </label>
        @else
            @php
                $desc = $desc ?? $param['name'];
            @endphp
            @if ($group && ($prev_group !== $group))
                <label class="col-sm-2 control-label npl"
                    for="{{ $key }}_{{ $uid }}"
                    title="{{ $group_desc }}">
                    {{ $group }}
                </label>
            @endif
        @endif
        <div class="col-sm-{{ $cols }} np">

            @if ($mutability ?? false)
                <div class="mutable-control">
                    <button class="locked btn btn-primary btn-mini"
                            title="Currently immutable, click to enable mutation"
                            onClick="alert('TODO')">
                        <span class="fas fa-dna"></span>
                    </button>
                </div>
                <div class="mutable-content">
            @endif

            {{-- String --}}
            @if ('string' === $param['type'])
                <input class="btn-primary btn btn-mini form-control form-control-sm"
                        id="{{ $key }}_{{ $uid }}"
                        name="{{ $key }}_{{ $uid }}"
                        title="{{ $desc }}"
                        value="{{ $indicator->getParam('indicator.'.$key) }}">
                </select>

            {{-- Source --}}
            @elseif ('source' === $param['type'])
                <select class="btn-primary btn btn-mini form-control form-control-sm"
                        id="{{ $key }}_{{ $uid }}"
                        name="{{ $key }}_{{ $uid }}"
                        title="{{ $desc ?? 'Select the source' }}">
                    @php
                        $sources = $indicator->getOwner()->getAvailableSources(
                            $indicator->getSignature(),
                            [],
                            Arr::get($param, 'filters', []),
                            Arr::get($param, 'disabled', []),
                            20
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
                            dropdownAutoWidth: true,
                            tags: true,
                            createTag: function (params) {
                                var text = String(parseFloat(params.term));
                                if ('NaN' == text) {
                                    return null;
                                }
                                return {
                                    id: encodeURIComponent('{"class": "Constant","params": {"value": "' + text + '"}}'),
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
                    <label class="form-check-label" title="{{ $desc }}">
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
                        title="{{ $desc }}">
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
                <script>
                    $('#{{ $key }}_{{ $uid }}').select2();
                </script>

            {{-- List --}}
            @elseif ('list' === $param['type'])
                <select multiple="multiple" size="20"
                        class="btn-primary btn btn-mini form-control form-control-sm"
                        id="{{ $key }}_{{ $uid }}"
                        name="{{ $key }}_{{ $uid }}"
                        title="{{ $desc }}">
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
                <script>
                    //$('#{{ $key }}_{{ $uid }}').select2();
                </script>

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
                <input
                    type="number"
                    class="btn-primary btn btn-mini form-control form-control-sm"
                    id="{{ $key }}_{{ $uid }}"
                    name="{{ $key }}_{{ $uid }}"
                    title="{{ ucfirst($param['type']) }} {{ $title }} {{ $desc }}"
                    {!! $opts !!}
                    value="{{ $indicator->getParam('indicator.'.$key) }}">
            @endif

            @if ($mutability ?? false)
                </div>
            @endif

        </div>
    @php
        $first = false;
    @endphp
@endforeach

@if ($row_opened)
    </div>
@endif

@if (!in_array('savebutton', $disabled))
    <div class="row">
        <div class="col-sm-12 text-right">
            <button id="save_{{ $uid }}"
                    class="btn btn-primary btn-mini trans"
                    title="Save changes"
                    onClick="return window.save_indicator{{ $uid }}()">
                <span class="fas fa-check"></span>
                Done
            </button>
        </div>
    </div>
@endif

@if (!in_array('form', $disabled))
    </form>
@endif

@if (!in_array('save', $disabled))
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
