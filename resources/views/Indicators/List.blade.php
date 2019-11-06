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
                    @if (($mutability ?? false) && $indicator->hasPossiblyMutableParams())
                        <button class="locked btn btn-primary btn-mini editbutton trans"
                                title="Currently immutable, click to enable mutation"
                                onClick="alert('TODO'); return false; window.GTrader.request(
                                    'indicator',
                                    'mutability',
                                    {
                                        owner_class: '{{ $owner_class }}',
                                        owner_id: '{{ $owner_id }}',
                                        name: '{{ $name }}',
                                        signature: '{{ urlencode($sig) }}',
                                        target_element: '{{ $target_element }}',
                                        mutable: 1
                                    },
                                    'POST',
                                    'form_{{ $uid }}'
                                ); return false">
                            <span class="fas fa-dna"></span>
                        </button>
                    @endif
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
                                        mutability: {{ ($mutability ?? false) ? 1 : 0 }}
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
                                    signature: '{{ urlencode($sig) }}'
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
            <option value="{{ $class }}">{{ $indicator }}</option>
            @endforeach
        </select>
        <script>
            $('#new_indicator_{{ $uid }}').select2();
        </script>
        <button onClick="window.GTrader.request(
                    'indicator',
                    'new',
                    {
                        owner_class: '{{ $owner_class }}',
                        owner_id: '{{ $owner_id }}',
                        name: '{{ $name }}',
                        signature: $('#new_indicator_{{ $uid }}').val()
                    },
                    'GET',
                    '{{ $target_element }}'
                ); return false"
                class="btn btn-primary btn-mini trans"
                title="Add new indicator">
            <span class="fas fa-check"></span>
        </button>
    </div>
</div>
