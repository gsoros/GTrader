<form class="form-inline">
    <select class="btn-primary btn btn-mini" id="strategy_select_{{ $chart_name }}">
        @foreach ($strategies as $strategy)
            <option
            @if ($strategy->id === $selected_strategy)
                selected
            @endif
            value="{{ $strategy->id }}">{{ $strategy->name }}</option>
        @endforeach
    </select>
</form>
