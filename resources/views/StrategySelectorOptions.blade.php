@foreach ($strategies as $strategy)
    <option
    @if ($strategy->id === $selected_strategy)
        selected
    @endif
    value="{{ $strategy->id }}">{{ $strategy->name }}</option>
@endforeach

