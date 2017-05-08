<div class="row bdr-rad">
    <div class="col-sm-12" id='training'>
        <h3>Select ranges for {{ $strategy->getParam('name') }}</h3>
        <div id="train_slider"
            class="center-block"
            style="width: 90%; height: 61px; margin-bottom: -61px"></div>
        <div id="test_slider"
            class="center-block"
            style="position: relative; top: 61px; width: 90%; height: 61px; margin-bottom: -61px"></div>
        <div id="verify_slider"
            class="center-block"
            style="position: relative; top: 122px; width: 90%; height: 61px; margin-bottom: -61px"></div>
        <script>
            [   {   name: 'train_slider',
                    start: {{ \Config::get('GTrader.FannTraining.train_range.start_percent') }},
                    end: {{ \Config::get('GTrader.FannTraining.train_range.end_percent') }}},
                {name: 'test_slider',
                    start: {{ \Config::get('GTrader.FannTraining.test_range.start_percent') }},
                    end: {{ \Config::get('GTrader.FannTraining.test_range.end_percent') }}},
                {name: 'verify_slider',
                    start: {{ \Config::get('GTrader.FannTraining.verify_range.start_percent') }},
                    end: {{ \Config::get('GTrader.FannTraining.verify_range.end_percent') }}}
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
    <div class="col-sm-6 editable">
        @php
            if ($strategy->hasBeenTrained())
            {
                $disabled = '';
                $checked = '';
            }
            else
            {
                $disabled = 'disabled';
                $checked = 'checked';
            }
        @endphp
        <div class="form-check form-check-inline {{ $disabled }}">
            <label class="form-check-label">
                <input class="form-check-input"
                        type="checkbox"
                        id="from_scratch"
                        value="1" {{ $checked }} {{ $disabled }}> Train from scratch
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
                                    window.trainingChart.getSelectedESR(),
                                    {
                                        id: {{ $strategy->getParam('id') }},
                                        train_start_percent: train_slider.noUiSlider.get()[0],
                                        train_end_percent: train_slider.noUiSlider.get()[1],
                                        test_start_percent: test_slider.noUiSlider.get()[0],
                                        test_end_percent: test_slider.noUiSlider.get()[1],
                                        verify_start_percent: verify_slider.noUiSlider.get()[0],
                                        verify_end_percent: verify_slider.noUiSlider.get()[1],
                                        from_scratch: $('#from_scratch').prop('checked') ? 1 : 0
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
