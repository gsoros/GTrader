<div class="row bdr-rad">

    <div class="col-sm-6 editable"
        title="Swap training and test ranges after this number of epochs without improvement">
        <label for="crosstrain">Cross-train</label>
        <select class="btn-primary btn btn-mini form-control form-control-sm"
                id="crosstrain"
                name="crosstrain">
            @foreach ([
                0 => 'No cross-train',
                10 => '10',
                100 => '100',
                250 => '250',
                500 => '500',
                1000 => '1 000',
                2500 => '2 500',
                5000 => '5 000',
            ] as $val => $lab)
                <option value="{{ $val }}"
                @if ($val == $preferences['crosstrain'])
                    selected
                @endif
                >{{ $lab }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-sm-6 editable"
        title="Restart training from scratch after this number of epochs without improvement">
        <label for="reset_after">Reset</label>
        <select class="btn-primary btn btn-mini form-control form-control-sm"
                id="reset_after"
                name="reset_after">
            @foreach ([
                0 => 'No reset',
                100 => '100',
                250 => '250',
                500 => '500',
                1000 => '1 000',
                2500 => '2 500',
                5000 => '5 000',
                10000 => '10 000',
            ] as $val => $lab)
                <option value="{{ $val }}"
                @if ($val == $preferences['reset_after'])
                    selected
                @endif
                >{{ $lab }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-sm-6 editable">
        @php
            if ($strategy->hasBeenTrained()) {
                $disabled = '';
                $checked = '';
            }
            else {
                $disabled = ' disabled';
                $checked = ' checked';
            }
        @endphp
        <div class="form-check form-check-inline {{ $disabled }}">
            <label class="form-check-label">
                <input class="form-check-input"
                        type="checkbox"
                        id="from_scratch"
                        name="from_scratch"
                        value="1"{{ $checked }}{{ $disabled }}> Train From Scratch
            </label>
        </div>
    </div>

</div>
