@php
@endphp
<div class="container-fluid">
    @foreach ($strategies as $strategy)
    <div class="row editable" id="strategy_{{ $strategy->id }}">
        <div class="col-sm-10">
            {{ $strategy->name }}
        </div>
        <div class="col-sm-2">
            <div class="form-group editbuttons">
                <button id="edit_{{ $strategy->id }}"
                        class="btn btn-primary btn-sm editbutton trans"
                        title="Edit Strategy"
                        onClick="window.strategyRequest('form',
                                                        {id: {{ $strategy->id }}})">
                    <span class="glyphicon glyphicon-wrench"></span>
                </button>
                <button id="delete_{{ $strategy->id }}"
                        class="btn btn-primary btn-sm editbutton trans"
                        title="Delete Strategy"
                        onClick="window.strategyRequest('delete',
                                                        {id: {{ $strategy->id }}})">
                    <span class="glyphicon glyphicon-trash"></span>
                </button>
            </div>
        </div>
    </div>
    @endforeach

    <div class="row" id="new_strategy">
        <div class="col-sm-12 editable">
            <label for="new_strategy_class">New strategy:</label>
            <select class="btn-primary btn btn-mini"
                    id="new_strategy_class"
                    title="Select type of strategy">
                @foreach ($available as $name)
                    <option value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </select>

            <button id="new_strategy"
                    class="btn btn-primary btn-sm trans"
                    title="Create new strategy"
                    onClick="return window.strategyRequest('new',
                                    {strategyClass: $('#new_strategy_class').val()})">
                <span class="glyphicon glyphicon-ok"></span> Create
            </button>
        </div>
    </div>

</div>
