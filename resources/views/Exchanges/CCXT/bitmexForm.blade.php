<div class="row bdr-rad">
    <div class="col-sm-12 editable form-group">
        <label for="options[leverage]">Leverage</label>
        <select class="btn-primary form-control form-control-sm"
                id="leverage"
                name="options[leverage]"
                title="Leverage">
            @foreach ([1, 2, 3, 5, 10, 25, 50, 100] as $val)
                <option value="{{ $val }}"
                @if ($val == ($options['leverage'] ?? 1))
                    selected
                @endif
                >{{ $val }}X</option>
            @endforeach
        </select>
        <script>
            $('#leverage').select2();
        </script>
    </div>
</div>
