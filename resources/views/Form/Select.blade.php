<select class="{{ $class ?? '' }}"
        id="{{ $key }}_{{ $uid }}"
        name="{{ $key }}_{{ $uid }}"
        {{ ($description ?? null) ? 'title="'.$description.'"' : ''}}
        >
    @php
        $options = is_array($options) ? $options : [];
    @endphp
    @foreach ($options as $opt_k => $opt_v)
        <option
        {{ $opt_k == ($value ?? null) ? 'selected' : '' }}
        value="{{ $opt_k }}">{{ $opt_v }}</option>
    @endforeach
</select>
