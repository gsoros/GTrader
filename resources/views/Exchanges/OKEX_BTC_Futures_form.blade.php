<div class="col-sm-4 editable form-group">
    <label for="leverage">Leverage</label>
    <select title="Leverage"
            class="btn-primary form-control form-control-sm"
            id="leverage"
            name="options[leverage]">
    @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 15, 18, 20, 25, 30, 35, 40, 45, 50, 60, 70, 80, 90, 100] as $i)
        <option value="{{ $i }}"
        @if (isset($options['leverage']))
            @if ($options['leverage'] == $i)
                selected
            @endif
        @endif
        >{{ $i }}X</option>
    @endforeach
    </select>
</div>
<div class="col-sm-4 editable form-group">
    <label for="max_contracts">Maximum Number of Contracts</label>
    <input class="btn-primary form-control form-control-sm"
            type="number"
            id="max_contracts"
            name="options[max_contracts]"
            title="Maximum Amount of Contracts"
            value="{{ $options['max_contracts'] }}">
</div>
