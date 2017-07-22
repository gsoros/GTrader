<div class="row bdr-rad">
    <div class="col-sm-12" id='training'>
        <h3>Select ranges for {{ $strategy->getParam('name') }}</h3>
        <div id="train_slider"
            class="center-block"
            style="width: 95%; height: 61px; margin-bottom: -61px"></div>
        <div id="test_slider"
            class="center-block"
            style="position: relative; top: 61px; width: 95%; height: 61px; margin-bottom: -61px"></div>
        <div id="verify_slider"
            class="center-block"
            style="position: relative; top: 122px; width: 95%; height: 61px; margin-bottom: -61px"></div>
        <script>
            [   {   name: 'train_slider',
                    start: {{ $preferences['train_start_percent'] }},
                    end: {{ $preferences['train_end_percent'] }}},
                {name: 'test_slider',
                    start: {{ $preferences['test_start_percent'] }},
                    end: {{ $preferences['test_end_percent'] }}},
                {name: 'verify_slider',
                    start: {{ $preferences['verify_start_percent'] }},
                    end: {{ $preferences['verify_end_percent'] }}}
            ].forEach(function(item) {
                noUiSlider.create(document.getElementById(item.name), {
                    start: [item.start, item.end],
                    connect: true,
                    behaviour: "tap-drag",
                    margin: 5,
                    range: {
                        'min': 0,
                        'max': 100
                    }
                });
            });
        </script>
        {!! $strategy->getTrainingChart()->toHTML() !!}
    </div>
</div>
<div class="row bdr-rad">
    <div class="col-sm-6 editable"
        title="Swap training and test ranges after this number of epochs without improvement">
        <label for="crosstrain">Cross-train</label>
        <select class="btn-primary btn btn-mini form-control form-control-sm"
                id="crosstrain">
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
                id="reset_after">
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
    <div class="col-sm-6 editable"
        title="Select the indicator to maximise training on">
        <label for="maximize_for">Maximise Strategy For</label>
        <select class="btn-primary btn btn-mini form-control form-control-sm"
                id="maximize_for">
            @foreach (\Config::get('GTrader.FannTraining.maximize') as $val => $label)
                <option value="{{ $val }}"
                @if ($val == $preferences['maximize'])
                    selected
                @endif
                >{{ $label }}</option>
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
                        value="1"{{ $checked }}{{ $disabled }}> Train From Scratch
            </label>
        </div>
    </div>
</div>
<div class="row bdr-rad">
    <div class="col-sm-12">
        <span class="pull-right">
            <button onClick="window.GTrader.request(
                                'strategy',
                                'trainStart',
                                $.extend(
                                    window.GTrader.charts.trainingChart.getSelectedESR(),
                                    {
                                        id: {{ $strategy->getParam('id') }},
                                        train_start_percent: train_slider.noUiSlider.get()[0],
                                        train_end_percent: train_slider.noUiSlider.get()[1],
                                        test_start_percent: test_slider.noUiSlider.get()[0],
                                        test_end_percent: test_slider.noUiSlider.get()[1],
                                        verify_start_percent: verify_slider.noUiSlider.get()[0],
                                        verify_end_percent: verify_slider.noUiSlider.get()[1],
                                        from_scratch: $('#from_scratch').prop('checked') ? 1 : 0,
                                        crosstrain: $('#crosstrain').val(),
                                        reset_after: $('#reset_after').val(),
                                        maximize_for: $('#maximize_for').val()
                                    }
                                ))"
                    type="button"
                    class="btn btn-primary btn-sm trans"
                    title="Start Training">
                <span class="glyphicon glyphicon-fire"></span> Start Training
            </button>
            <button onClick="window.GTrader.request('strategy', 'list')"
                    type="button"
                    class="btn btn-primary btn-sm trans"
                    title="Back to the List of Strategies">
                <span class="glyphicon glyphicon-arrow-left"></span> Back
            </button>
        </span>
    </div>
</div>
