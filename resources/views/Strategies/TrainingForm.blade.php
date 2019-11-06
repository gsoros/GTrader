@php
    //dump($training->getParams(), $preferences);
    foreach ([
        'ranges' => [],
        'maximize' => [],
        'maximize_for' => ''
    ] as $field => $default) {
        $$field =
            $training->getParam($field) ??
            $preferences[$field] ??
            $default;
    }
    $range_keys = array_keys($ranges);
    foreach ($ranges as $range_key => $range) {
        ${$range_key.'_start_percent'} = ${$range_key.'_start_percent'} ?? $range['start_percent'];
        ${$range_key.'_end_percent'} = ${$range_key.'_end_percent'} ?? $range['end_percent'];
    }
@endphp
<form id="training_form">

    <div class="row bdr-rad">
        <div class="col-sm-12">
            <h3>Select range{{ (1 < count($ranges)) ? 's' : '' }} for {{ $strategy->getParam('name') }}</h3>
            @php
                $height = floor(230 / count($ranges));
                $displayed = 0;
                foreach ($ranges as $range_key => $range) {
                    echo '<div id="'.$range_key.'_slider"
                        class="center-block"
                        style="width: 95%;
                            height: '.$height.'px;
                            margin-bottom: -'.$height.'px;';
                    if (0 < $displayed) {
                        echo 'position: relative; top: '.($displayed * $height).'px;';
                    }
                    $displayed++;
                    echo '"></div>';
                }
            @endphp
            <script>
                [
                @foreach ($range_keys as $range)
                    {
                        name: '{{ $range }}_slider',
                        start: '{{ $preferences[$range.'_start_percent'] ?? '' }}',
                        end: '{{ $preferences[$range.'_end_percent'] ?? ''}}'
                    }
                    @if (false !== next($range_keys))
                        ,
                    @endif
                @endforeach
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
        <div class="col-sm-3 editable"
            title="Select the indicator to maximise training on">
            <label for="maximize_for">Maximise Strategy For</label>
            <select class="btn-primary btn btn-mini form-control form-control-sm"
                    id="maximize_for"
                    name="maximize_for">
                @foreach ($maximize as $val => $label)
                    <option value="{{ $val }}"
                    @if ($val == $maximize_for)
                        selected
                    @endif
                    >{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @includeIf('Strategies/'.$strategy->getParam('training_class').'Form')
    </div>

</form>
<div class="row bdr-rad">
    <div class="col-sm-12">
        <span class="float-right">
            <button onClick="
                    window.GTrader.request(
                        'strategy',
                        'trainStart',
                        $.extend(
                            true,
                            window.GTrader.charts.trainingChart.getSelectedESR(),
                            {
                                @foreach ($range_keys as $range)
                                    {{ $range }}_start_percent: {{ $range }}_slider.noUiSlider.get()[0],
                                    {{ $range }}_end_percent: {{ $range }}_slider.noUiSlider.get()[1],
                                @endforeach
                                id: {{ $strategy->getParam('id') }}
                            },
                            window.GTrader.serializeObject($('#training_form'))
                        )
                    )"
                    type="button"
                    class="btn btn-primary btn-mini trans"
                    title="Start Training">
                <span class="fas fa-fire"></span> Start Training
            </button>
            <button onClick="window.GTrader.request('strategy', 'list')"
                    type="button"
                    class="btn btn-primary btn-mini trans"
                    title="Back to the List of Strategies">
                <span class="fas fa-arrow-left"></span> Back
            </button>
        </span>
    </div>
</div>
