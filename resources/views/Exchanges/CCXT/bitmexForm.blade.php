@if ($exchange->getTestnetApiUrl())
    <div class="row bdr-rad">
        <div class="col-sm-6 editable form-group">
            <label for="options[use_testnet]">Use Testnet or Live API</label>
            <select class="btn-primary form-control form-control-sm"
                    id="use_testnet"
                    name="options[use_testnet]"
                    title="Use Testnet">
                @foreach ([0 => 'Use Live API', 1 => 'Use Testnet API'] as $val => $label)
                    <option value="{{ $val }}"
                    @if ($val == ($options['use_testnet'] ?? false))
                        selected
                    @endif
                    >{{ $label }}</option>
                @endforeach
            </select>
            <script>
                $('#use_testnet').select2();
            </script>
        </div>
        <div class="col-sm-6 editable form-group">
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
@endif
