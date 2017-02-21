@php
$selected = false;
@endphp
@foreach ($strategies as $strategy)
    <option
    @if ($strategy->id === $selected_strategy)
        @php
        $selected = true;
        @endphp
        selected
    @endif
    value="{{ $strategy->id }}">{{ $strategy->name }}</option>
@endforeach
@if (!$selected)
    <option selected disabled>No strategy</option>
@endif

