@php
@endphp
<div class="container-fluid">
    <div class="row">
        @foreach ($strategies as $strategy)
            @php
                $id = $strategy->getParam('id');
            @endphp
            <div class="col-sm-6 editable">
                <div class="row">
                    <div class="col-sm-8" title="{{ $strategy->getParam('description') }}">
                        {!! $strategy->listItem() !!}
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group editbuttons">
                            <button onClick="window.GTrader.request('strategy',
                                                                    'form',
                                                                    'id={{ $id }}')"
                                    type="button"
                                    class="btn btn-primary btn-mini editbutton trans"
                                    title="Edit Strategy">
                                <span class="fas fa-wrench"></span>
                            </button>
                            <button onClick="window.GTrader.request('strategy',
                                                                    'clone',
                                                                    'id={{ $id }}')"
                                    type="button"
                                    class="btn btn-primary btn-mini editbutton trans"
                                    title="Clone Strategy">
                                <span class="fas fa-clone"></span>
                            </button>
                            <button onClick="window.GTrader.request('strategy',
                                                                    'delete',
                                                                    'id={{ $id }}')"
                                    type="button"
                                    class="btn btn-primary btn-mini editbutton trans"
                                    title="Delete Strategy">
                                <span class="fas fa-trash"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row" id="new_strategy">
        <div class="col-sm-12 editable text-right">
            <label for="new_strategy_class">New strategy:</label>
            <select class="btn-primary btn btn-mini"
                    id="new_strategy_class"
                    title="Select type of strategy">
                @foreach ($available as $name)
                    <option
                        {{ (($default ?? null) == $name) ? 'selected' : '' }}
                        value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </select>

            <button onClick="window.GTrader.request(
                            'strategy',
                            'new',
                            {strategyClass: $('#new_strategy_class').val()})"
                    type="button"
                    class="btn btn-primary btn-mini trans"
                    title="Create new strategy">
                <span class="fas fa-check"></span> Create
            </button>
        </div>
    </div>

</div>
