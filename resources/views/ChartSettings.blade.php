@if (count($indicators))
    <div class="row">
        <div class="col-sm-12">
            Indicators
        </div>
    </div>
    <div class="row">
        @foreach ($indicators as $indicator)
            @php
                $uid = uniqid();
                $sig = $indicator->getSignature();
                $params = $indicator->getParam('indicator');
                $num_params = is_array($params) ? count($params) : 0;
            @endphp
            <div id="form_{{ $uid }}" class="col-sm-6 editable trans">
                {{ $indicator->getDisplaySignature() }}
                <div class="form-group editbuttons">
                    @if ($num_params)
                    <button class="btn btn-primary btn-sm editbutton trans"
                            title="Edit"
                            onClick="window.GTrader.request(
                                'indicator',
                                'form',
                                {name: '{{ $name }}', signature: '{{ $sig }}'},
                                'GET',
                                'form_{{ $uid }}'
                            )">
                        <span class="glyphicon glyphicon-wrench"></span>
                    </button>
                    @endif
                    <button class="btn btn-primary btn-sm editbutton trans"
                            title="Delete"
                            onClick="window.GTrader.request(
                                'indicator',
                                'delete',
                                'name={{ $name }}&signature={{ $sig }}',
                                'GET',
                                'settings_content'
                            )">
                        <span class="glyphicon glyphicon-trash"></span>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif
<div class="row editable trans text-right">
    <div class="col-sm-12">
        New indicator:
        <select class="btn-primary btn btn-mini"
                id="new_indicator_{{ $name }}"
                title="Select the type of indicator">
            @foreach ($available as $class => $indicator)
            <option value="{{ $class }}">{{ $indicator }}</option>
            @endforeach
        </select>
        <button onClick="window.GTrader.request(
                    'indicator',
                    'new',
                    {name: '{{ $name }}', signature: $('#new_indicator_{{ $name }}').val()},
                    'GET',
                    'settings_content'
                )"
                class="btn btn-primary btn-sm trans"
                title="Add new indicator">
            <span class="glyphicon glyphicon-ok"></span>
        </button>
    </div>
</div>
