@if (count($indicators))
    <div class="row">
        <div class="col-sm-12">
            Indicators
        </div>
    </div>
    <div class="row">
        @foreach ($indicators as $indicator)
            @php
            $sig = $indicator->getSignature();
            $params = $indicator->getParam('indicator');
            $num_params = is_array($params) ? count($params) : 0;
            @endphp
            <div id="form_{{ $sig }}" class="col-sm-6 editable trans">
                {{ $indicator->getDisplaySignature() }}
                <div class="form-group editbuttons">
                    @if ($num_params)
                    <button id="edit_{{ $sig }}"
                            class="btn btn-primary btn-sm editbutton trans"
                            title="Edit"
                            onClick="window.{{ $name }}.requestIndicatorEditForm('{{ $sig }}')">
                        <span class="glyphicon glyphicon-wrench"></span>
                    </button>
                    @endif
                    <button id="delete_{{ $sig }}"
                            class="btn btn-primary btn-sm editbutton trans"
                            title="Delete"
                            onClick="window.{{ $name }}.requestIndicatorDelete('{{ $sig }}')">
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
        <button onClick="window.{{ $name }}.requestIndicatorNew($('#new_indicator_{{ $name }}').val())"
                id="add_indicator"
                class="btn btn-primary btn-sm trans"
                title="Add new indicator">
            <span class="glyphicon glyphicon-ok"></span>
        </button>
    </div>
</div>
