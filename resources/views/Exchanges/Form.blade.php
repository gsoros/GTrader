<div class="col-sm-12 editable form-group">
    <label>Symbols</label>
    <div class="row bdr-rad">
        @php
            $exchange_id = $exchange->getId();
        @endphp
        @foreach ($exchange->getSymbols() as $symbol)
            <div class="col-sm-6 editable form-group">
                <div class="form-check form-check-inline">
                    <label class="form-check-label" title="{{ $symbol }}">
                        <input class="form-check-input"
                            id="exchange_{{ $exchange_id }}_{{ $symbol }}"
                            name="symbols[]"
                            type="checkbox"
                            value="{{ $symbol }}"
                            @if (in_array($symbol, $selected_symbols))
                                checked
                            @endif
                        >
                        {{ $symbol }}
                    </label>
                </div>
            </div>
        @endforeach
    </div>
</div>
