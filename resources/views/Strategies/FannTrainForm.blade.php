<div class="row bdr-rad">
    <div class="col-sm-12" id='training'>
        <h3>Train {{ $strategy->getParam('name') }}</h3>
        <div id="slider" class="center-block" style="width: 90%; height: 162px; margin-bottom: -177px"></div>
        <script>
            var slider = document.getElementById('slider');
            noUiSlider.create(slider, {
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
    <div class="col-sm-6 editable">
        <label>Test on</label>
        <div class="form-check form-check-inline">
            <label class="form-check-label">
                <input type="radio"
                        class="form-check-input"
                        name="test_on"
                        value="train"
                        checked>
                Training period
            </label>
        </div>
        <div class="form-check form-check-inline">
            <label class="form-check-label">
                <input type="radio"
                        class="form-check-input"
                        name="test_on"
                        value="whole">
                Entire period
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
                                        start_percent: slider.noUiSlider.get()[0],
                                        end_percent: slider.noUiSlider.get()[1],
                                        from_scratch: $('#from_scratch').prop('checked') ? 1 : 0,
                                        test_on: $('input[name=test_on]:checked').val()
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

