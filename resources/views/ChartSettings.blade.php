@foreach ($indicators as $indicator)
    @php
    $sig = $indicator->getSignature();
    @endphp
    <div id="form_{{ $sig }}" class="editable trans">
        {{ $indicator->getDisplaySignature() }}
        <div class="form-group editbuttons">
            <button id="edit_{{ $sig }}"
                    class="btn btn-primary btn-sm editbutton trans"
                    title="Edit"
                    onClick="window.{{ $id }}.requestIndicatorEditForm('{{ $sig }}')">
                <span class="glyphicon glyphicon-wrench"></span>
            </button>
            <button id="delete_{{ $sig }}"
                    class="btn btn-primary btn-sm editbutton trans"
                    title="Delete"
                    onClick="window.{{ $id }}.requestIndicatorDelete('{{ $sig }}')">
                <span class="glyphicon glyphicon-trash"></span>
            </button>
        </div>
    </div>
@endforeach
<div class="editable trans">
    New indicator:
    <select class="btn-primary btn btn-mini"
            id="new_indicator_{{ $id }}"
            title="Select the type of indicator">
        @foreach ($available as $class => $name)
        <option value="{{ $class }}">{{ $name }}</option>
        @endforeach
    </select>
    <button onClick="window.{{ $id }}.requestIndicatorNew($('#new_indicator_{{ $id }}').val())"
            id="add_indicator"
            class="btn btn-primary btn-sm trans"
            title="Add new indicator">
        <span class="glyphicon glyphicon-ok"></span>
    </button>
</div>
