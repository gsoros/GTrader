@php
$selected = false;
@endphp
@foreach ($strategies as $strategy_id => $strategy_name)
    <option
    @if ($strategy_id === $selected_strategy)
        @php
        $selected = true;
        @endphp
        selected
    @endif
    value="{{ $strategy_id }}">{{ $strategy_name }}</option>
@endforeach
@if (!$selected)
    <option selected disabled>No strategy</option>
@endif
