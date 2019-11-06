
<div class="col-sm-3 editable"
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

<div class="col-sm-3 editable"
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

<div class="col-sm-3 editable"
    title="Increase number of epochs between tests after this number of epochs without improvement">
    <label for="max_boredom">Increase jumps after</label>
    <input class="btn-primary btn btn-mini form-control form-control-sm"
            type="number"
            id="max_boredom"
            name="max_boredom"
            value="{{ $preferences['max_boredom'] ?? 10 }}"
            min="2"
            max="100"
            step="1">
</div>

<div class="col-sm-3 editable"
    title="Maximum number of epochs between tests">
    <label for="max_epoch_jump">Max epoch jump</label>
    <input class="btn-primary btn btn-mini form-control form-control-sm"
            type="number"
            id="max_epoch_jump"
            name="max_epoch_jump"
            value="{{ $preferences['max_epoch_jump'] ?? 100 }}"
            min="1"
            max="1000"
            step="1">
</div>

@php
    $max_reg = $preferences['max_regression'] ?? [];
@endphp
<div class="col-sm-3 editable"
    title="Allow this percentage of regression from the maximum for the test range">
    <label for="max_regression[test]">Allowed test regression</label>
    <input class="btn-primary btn btn-mini form-control form-control-sm"
            type="number"
            id="max_regression[test]"
            name="max_regression[test]"
            value="{{ $max_reg['test'] ?? 5 }}"
            min="0"
            max="100"
            step="0.1">
</div>

<div class="col-sm-3 editable"
    title="Allow this percentage of regression from the maximum for the verify range">
    <label for="max_regression[verify]">Allowed verify regression</label>
    <input class="btn-primary btn btn-mini form-control form-control-sm"
            type="number"
            id="max_regression[verify]"
            name="max_regression[verify]"
            value="{{ $max_reg['verify'] ?? 23.45 }}"
            min="0"
            max="100"
            step="0.1">
</div>

<div class="col-sm-3 editable">
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
    <div class="form-check form-check-inline{{ $disabled }}">
        <label class="form-check-label">
            <input class="form-check-input"
                    type="checkbox"
                    id="from_scratch"
                    name="from_scratch"
                    value="1"{{ $checked }}{{ $disabled }}> Train From Scratch
        </label>
    </div>
</div>
