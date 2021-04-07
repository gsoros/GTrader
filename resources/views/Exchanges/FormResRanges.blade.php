<form id="rangeForm">
    <input type="hidden" name="id" value="{{ $exchange->getId() }}">
    <input type="hidden" name="symbol_id" value="{{ $symbol_id }}">
    <input type="hidden" name="res" value="{{ $res }}">
    <div class="col-sm-12 form-group container">
        <div class="row">
            <div class="col-sm-12">
                <h4>Date ranges for
                    {{ $exchange->getLongName() }}
                    {{ $symbol_id }}
                    {{ $exchange->resolutionName($res) }}
                </h4>
            </div>
        </div>
        <div class="row bdr-rad">
            <p class="col-sm-12">Currently we have
                {{ number_format($num_candles) }} candles
                @if ($num_candles)
                    from {{ date('Y-m-d', $candles_start) }}
                    to {{ date('Y-m-d', $candles_end) }}
                @endif
            </p>
        </div>
            @foreach (['start', 'end'] as $pos)
                <div class="row bdr-rad">
                    <div class="col-sm-4">
                        <input
                            type="checkbox"
                            value="1"
                            name="enable_{{ $pos }}"
                            id="enable_{{ $pos }}"
                            @if (${'range_'.$pos}) checked @endif
                            onChange="toggleEnable('{{ $pos }}')">
                        <label for="enable_{{ $pos }}">Limit {{ $pos }}</label>
                    </div>
                    <div class="col-sm-8">
                        <input
                            type="text"
                            name="range_{{ $pos }}"
                            id="range_{{ $pos }}"
                            size="10"
                            autocomplete="off">
                    </div>
                </div>
            @endforeach
        </div>
        <script>
            function toggleEnable(pos) {
                $('#range_'+pos).attr('hidden', !$('#enable_'+pos).is(':checked'));
            }

            function removeDatepickers() {
                range_start.remove();
                range_end.remove();
            }

            id = '{{ $exchange->getName() }}_{{ $symbol_id }}_{{ $res }}';

            @foreach (['start', 'end'] as $pos)
                var range_{{ $pos }} = datepicker('#range_{{ $pos }}', {
                    @if (${'range_'.$pos})
                        dateSelected: new Date({{ ${'range_'.$pos} }}000),
                    @endif
                    formatter: function(input, date, instance) {
                        input.value = date.getFullYear()
                            + '-' + (date.getMonth()+1).toString().padStart(2, '0')
                            + '-' + date.getDate().toString().padStart(2, '0');
                    },
                    id: id
                });
                toggleEnable('{{ $pos }}');
            @endforeach
        </script>

        <div class="row">
            <div class="col-sm-12 text-right">
                <button onClick="
                            window.GTrader.request(
                                'exchange',
                                'form',
                                'id={{ $exchange->getId() }}',
                                'GET',
                                'settingsTab');
                                removeDatepickers()"
                        type="button"
                        class="btn btn-primary btn-mini trans"
                        title="Discard Changes">
                    <span class="fas fa-ban"></span> Discard Changes
                </button>
                <button onClick="
                            window.GTrader.request(
                                'exchange',
                                'resRangeUpdate',
                                $('#rangeForm').serialize(),
                                'POST',
                                'settingsTab');
                                removeDatepickers()"
                        type="button"
                        class="btn btn-primary btn-mini trans"
                        title="Save Settings">
                    <span class="fas fa-check"></span> Save Settings
                </button>
            </div>
        </div>

    </div>
</form>
