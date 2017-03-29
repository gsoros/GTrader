<div class="row bdr-rad">
    <div class="col-sm-12" id='training'>
        <h3>Select ranges for {{ $strategy->getParam('name') }}</h3>
        <div id="train_slider" class="center-block" style="width: 90%; height: 81px; margin-bottom: -81px"></div>
        <div id="test_slider" class="center-block" style="position: relative; top: 81px; width: 90%; height: 81px; margin-bottom: -81px"></div>
        <script>
            var train_slider = document.getElementById('train_slider');
            noUiSlider.create(train_slider, {
                start: [50, 100],
                connect: true,
                behaviour: "tap-drag",
                margin: 5,
                range: {
                    'min': 0,
                    'max': 100
                }
            });
            var test_slider = document.getElementById('test_slider');
            noUiSlider.create(test_slider, {
                start: [50, 100],
                connect: true,
                behaviour: "tap-drag",
                margin: 5,
                range: {
                    'min': 0,
                    'max': 100
                }
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
