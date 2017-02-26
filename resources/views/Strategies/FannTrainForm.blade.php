<div class="row bdr-rad">
    <div class="col-sm-12" id='training'>
        <h4>Train {{ $strategy->getParam('name') }}</h4>
        {!! $strategy->getTrainingChart()->toHTML() !!}
        <div id="slider" class="center-block" style="width: 90%; height: 162px; margin-top: -230px"></div>
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
    </div>
</div>
<div class="row bdr-rad">
    <div class="col-sm-12">
        <span class="pull-right">
            <button onClick="window.strategyRequest(
                                'trainStart',
                                $.extend(
                                    window.trainingChart.getSelectedESR(),
                                    {
                                        id: {{ $strategy->getParam('id') }},
                                        start_percent: slider.noUiSlider.get()[0],
                                        end_percent: slider.noUiSlider.get()[1]
                                    }
                                ))"
                    type="button"
                    id="startTrainingButton"
                    class="btn btn-primary btn-sm trans"
                    title="Start Training">
                <span class="glyphicon glyphicon-fire"></span> Start Training
            </button>
            <button onClick="window.strategyRequest('list')"
                    type="button"
                    class="btn btn-primary btn-sm trans"
                    title="Back to the List of Strategies">
                <span class="glyphicon glyphicon-arrow-left"></span> Back
            </button>
        </span>
    </div>
</div>
<pre>
    {{ $strategy->getParam('debug') }}
</pre>
