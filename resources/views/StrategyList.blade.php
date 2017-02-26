@php
@endphp
<div class="container-fluid">
    @foreach ($strategies as $strategy)
        @php
            $id = $strategy->getParam('id');
        @endphp
        <div class="row editable" id="strategy_{{ $id }}">
            <div class="col-sm-10">
                {!! $strategy->listItem() !!}
            </div>
            <div class="col-sm-2">
                <div class="form-group editbuttons">
                    <button id="edit_{{ $id }}"
                            type="button"
                            class="btn btn-primary btn-sm editbutton trans"
                            title="Edit Strategy"
                            onClick="window.strategyRequest(
                                        'form', 'id={{ $id }}')">
                        <span class="glyphicon glyphicon-wrench"></span>
                    </button>
                    <button id="delete_{{ $id }}"
                            type="button"
                            class="btn btn-primary btn-sm editbutton trans"
                            title="Delete Strategy"
                            onClick="window.strategyRequest(
                                        'delete', 'id={{ $id }}')">
                        <span class="glyphicon glyphicon-trash"></span>
                    </button>
                </div>
            </div>
        </div>
    @endforeach

    <div class="row" id="new_strategy">
        <div class="col-sm-12 editable text-right">
            <label for="new_strategy_class">New strategy:</label>
            <select class="btn-primary btn btn-mini"
                    id="new_strategy_class"
                    title="Select type of strategy">
                @foreach ($available as $name)
                    <option value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </select>

            <button id="new_strategy"
                    type="button"
                    class="btn btn-primary btn-sm trans"
                    title="Create new strategy"
                    onClick="window.strategyRequest(
                                'new', {strategyClass: $('#new_strategy_class').val()})">
                <span class="glyphicon glyphicon-ok"></span> Create
            </button>
        </div>
    </div>

</div>
