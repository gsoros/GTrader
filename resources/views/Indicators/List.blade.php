@php
    $uid = uniqid();
    foreach ([
        'name' => '',
        'owner_class' => 'Chart',
        'owner_id' => 0,
        'target_element' => 'settings_content',
        'available' => [],
        'display_outputs' => false,
        'format' => 'short',
        'disabled' => [],
    ] as $varname => $default) {
        $$varname = isset($$varname) ? $$varname : $default;
    };
@endphp
@if (count($indicators))
    <div class="row">
        @foreach ($indicators as $indicator)
            @php
                $uid = uniqid();
                $sig = $indicator->getSignature();
                $adjustable = $indicator->getParam('adjustable');
                $num_params = is_array($adjustable) ? count($adjustable) : 0;

                if ($display_outputs) {
                    $inputs = $owner->getParam('inputs', []);
                    $outputs = $indicator->getOutputs();
                    $num_outputs = count($outputs);
                    $output_checkboxes = [];
                    foreach ($outputs as $output) {
                        $cbname = $indicator->getSignature($output);
                        //dump($cbname);
                        $output_checkboxes[] = [
                            'value' => $cbname,
                            'label' => ucwords($output),
                            'checked' => false !== array_search($cbname, $inputs),
                        ];
                    }
                }
            @endphp
            <div id="form_{{ $uid }}" class="col-sm-12 editable trans">
                <span title="{{ $indicator->getParam('display.description') }}">
                    @if ($display_outputs &&  (1 === $num_outputs))
                        @php
                            $checkbox = array_shift($output_checkboxes);
                        @endphp
                        <label>
                            <input type="checkbox"
                            name="inputs[]"
                            value="{{ $checkbox['value'] }}"
                            @if ($checkbox['checked'])
                                checked
                            @endif
                            >
                            {{ $indicator->getDisplaySignature($format) }}
                        </label>
                    @else
                    {{ $indicator->getDisplaySignature($format) }}
                    @endif
                </span>
                <div class="form-group editbuttons">
                    @if (($mutability ?? false) && $indicator->canBeMutable())
                        @php
                            $mutable_ratio = $indicator->mutableRatio();
                            if (0 >= $mutable_ratio) {
                                $mutable_class = 'locked';
                                $mutable_title = 'immutable, click to enable';
                                $mutable_icon = 'lock';
                                $mutable_action = 1;
                            } elseif (1 > $mutable_ratio) {
                                $mutable_class = 'partially-locked';
                                $mutable_title = 'some parameters are mutable, click to disable';
                                $mutable_icon = 'dna';
                                $mutable_action = 0;
                            } else {
                                $mutable_class = 'unlocked';
                                $mutable_title = 'mutable, click to disable';
                                $mutable_icon = 'dna';
                                $mutable_action = 0;
                            }
                        @endphp
                        <button class="{{ $mutable_class }} btn btn-primary btn-mini editbutton trans"
                                title="Currently {{ $mutable_title }} mutation"
                                onClick="window.GTrader.request(
                                    'indicator',
                                    'toggleMutable',
                                    {
                                        owner_class: '{{ $owner_class }}',
                                        owner_id: '{{ $owner_id }}',
                                        name: '{{ $name }}',
                                        signature: '{{ urlencode($sig) }}',
                                        mutable: {{ $mutable_action }}
                                    },
                                    'POST',
                                    '{{ $target_element }}'
                                ); return false">
                            <span class="fas fa-{{ $mutable_icon }}"></span>
                        </button>
                    @endif
                    <button class="btn btn-primary btn-mini editbutton trans"
                            title="Copy to clipboard"
                            onClick="window.GTrader.clipboardText('{{ addslashes($sig) }}'); return false">
                        <span class="fas fa-clipboard"></span>
                    </button>
                    @if ($num_params)
                        <button class="btn btn-primary btn-mini editbutton trans"
                                title="Edit"
                                onClick="window.GTrader.request(
                                    'indicator',
                                    'form',
                                    {
                                        owner_class: '{{ $owner_class }}',
                                        owner_id: '{{ $owner_id }}',
                                        name: '{{ $name }}',
                                        signature: '{{ urlencode($sig) }}',
                                        target_element: '{{ $target_element }}',
                                        mutability: {{ ($mutability ?? false) ? 1 : 0 }},
                                        disabled: {{ json_encode($disabled) }}
                                    },
                                    'POST',
                                    'form_{{ $uid }}'
                                ); return false">
                            <span class="fas fa-wrench"></span>
                        </button>
                    @endif
                    <button class="btn btn-primary btn-mini editbutton trans"
                            title="Delete"
                            onClick="window.GTrader.request(
                                'indicator',
                                'delete',
                                {
                                    owner_class: '{{ $owner_class }}',
                                    owner_id: '{{ $owner_id }}',
                                    name: '{{ $name }}',
                                    signature: '{{ urlencode($sig) }}',
                                    mutability: {{ ($mutability ?? false) ? 1 : 0 }}
                                },
                                'POST',
                                '{{ $target_element }}'
                            ); return false">
                        <span class="fas fa-trash"></span>
                    </button>
                </div>
                @if ($display_outputs && (1 < $num_outputs))
                    <div>
                        @foreach ($output_checkboxes as $checkbox)
                            <label>
                                <input type="checkbox"
                                    name="inputs[]"
                                    value="{{ $checkbox['value'] }}"
                                    @if ($checkbox['checked'])
                                        checked
                                    @endif
                                    >
                                    {{ $checkbox['label'] }}
                            </label> &nbsp;
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
<div class="row editable trans text-right">
    <div class="col-sm-12">
        New indicator:
        <select style="width: 200px"
                class="btn-primary btn btn-mini"
                id="new_indicator_{{ $uid }}"
                title="Select the type of indicator">
            @foreach ($available as $class => $indicator)
                <option
                    value="{{ $class }}"
                    title="{{ $indicator['description'] ?? 'no description' }}"
                    >{{ $indicator['name'] ?? 'unnamed' }}</option>
            @endforeach
        </select>
        <script>
            $('#new_indicator_{{ $uid }}').select2();
            function indicatorNewRequest(signature) {
                window.GTrader.request(
                    'indicator',
                    'new',
                    {
                        owner_class: '{{ $owner_class }}',
                        owner_id: '{{ $owner_id }}',
                        name: '{{ $name }}',
                        signature: signature
                    },
                    'GET',
                    '{{ $target_element }}'
                );
            }
        </script>
        <button onClick="
            var signature;
            if ('FromClipboard' == $('#new_indicator_{{ $uid }}').val()) {
                window.GTrader.clipboardText()
                .then(function(signature) {
                    console.log('From clipboard ', signature);
                    indicatorNewRequest(signature);
                })
                .catch(function(e) {
                    console.log('Clipboard failed', e);
                });
            } else {
                indicatorNewRequest($('#new_indicator_{{ $uid }}').val());
            }

            return false"
                class="btn btn-primary btn-mini trans"
                title="Add new indicator">
            <span class="fas fa-check"></span>
        </button>
    </div>
</div>
