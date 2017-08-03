<div class="col-sm-4 editable form-group">
    <label for="leverage">Leverage</label>
    <select title="Leverage"
            class="btn-primary form-control form-control-sm"
            id="leverage"
            name="leverage">
    @foreach ([10, 20] as $i)
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
            name="max_contracts"
            title="Maximum Amount of Contracts"
            value="{{ $options['max_contracts'] }}">
</div>
