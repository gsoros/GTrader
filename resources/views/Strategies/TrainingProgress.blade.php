@php
    $chart = $strategy->getTrainingProgressChart($training);
    $strategy_id = $strategy->getParam('id');
@endphp
<div class="row bdr-rad">
    <div class="col-sm-12 npl npr">
        <h4>Training Progress for
            <span title="Strategy ID: {{ $strategy_id }} Training ID: {{ $training->id }}">
                {{ $strategy->getParam('name') }}
            </span>
        </h4>
        {!! $chart->toHTML() !!}
    </div>
</div>
<div class="row bdr-rad">
    <div class="col-sm-12 npl npr" id="trainHistory" title="Training History">
    </div>
</div>
<div class="row bdr-rad" id="trainProgress">
    <div class="col-sm editable text-center" title="Status">
        <button class="btn btn-primary btn-mini trans cap" id="trainProgress_state">...</button>
    </div>
    @foreach ($training->getParam('progress.view', []) as $key => $field)
        <div class="col-sm editable text-center" title="{{ $field['title'] ?? '' }}">
            <label>{{ $field['label'] ?? ''}}</label>
            @php
                $field['format'] = str_replace(' ', '&nbsp;', $field['format'] ?? '');
                foreach ($field['items'] ?? [] as $name => $type) {
                    $span = '<span id="trainProgress_'.$name.'">...</span>';
                    $field['format'] = str_replace('{{'.$name.'}}', $span, $field['format']);
                }
            @endphp
            <button class="btn btn-primary btn-mini trans">{!! $field['format'] ?? '' !!}</button>
        </div>
    @endforeach
</div>
<script>
@if ('paused' == $training->status)
    $('#trainProgress_state').html('paused');
@else
    var pollTimeout,
        prev_max = 0,
        last_epoch = 0;
    function pollStatus() {
        //console.log('pollStatus() ' + $('#trainProgress').length);
        $.ajax({
            url: '/strategy.trainProgress?id={{ $strategy_id }}',
            success: function(data) {
                //console.log('pollStatus() success');
                try {
                    reply = JSON.parse(data);
                    //console.log(reply);
                }
                catch (err) {
                    console.log(err);
                }
                var state = (undefined === reply.state) ? 'queued' : reply.state;
                $('#trainProgress_state').html(state);
                @foreach ($training->getParam('progress.view', []) as $key => $field)
                    @foreach ($field['items'] ?? [] as $name => $type)
                        $('#trainProgress_{{ $name }}').html(
                            (undefined === reply.{{ $name }}) ? 0 : reply.{{ $name }}
                        );
                    @endforeach
                @endforeach
                var new_epoch = parseInt(reply.epoch);
                if (new_epoch > last_epoch && $('#trainHistory').is(':visible')) {
                    last_epoch = new_epoch;
                    window.GTrader.request(
                        'strategy',
                        'trainHistory',
                        {
                            id: {{ $strategy_id }},
                            width: $('#trainHistory').width(),
                            height: 200
                        },
                        'GET',
                        'trainHistory'
                    );
                }
                var max = parseFloat(reply.max);
                if (max > prev_max) {
                    prev_max = max;
                    if (window.GTrader.charts.{{ $chart->getParam('name') }}.refresh) {
                        window.GTrader.charts.{{ $chart->getParam('name') }}.refresh();
                    }
                }
            },
            complete: function() {
                //if ($('#trainProgress_state').is(':visible')) {
                if ($('#trainProgress').length) {
                    console.log('setting timeout for pollStatus');
                    pollTimeout = setTimeout(pollStatus, 3000);
                }
            }
        });
    }
    pollStatus();
@endif
</script>
<div class="row bdr-rad">
    <div class="col-sm-12 float-right">
        <span class="float-right">
            @if ('paused' === $training->status)
                <button onClick="window.GTrader.request(
                                        'strategy',
                                        'trainResume',
                                        'id={{ $strategy_id }}'
                                        )"
                        type="button"
                        class="btn btn-primary btn-mini trans"
                        title="Resume Training">
                    <span class="fas fa-play"></span> Resume Training
                </button>
            @else
                <button onClick="clearTimeout(pollTimeout);
                                    window.GTrader.request(
                                        'strategy',
                                        'trainPause',
                                        'id={{ $strategy_id }}'
                                        )"
                        type="button"
                        class="btn btn-primary btn-mini trans"
                        title="Pause Training">
                    <span class="fas fa-pause"></span> Pause Training
                </button>
            @endif
            <button onClick="window.GTrader.request(
                                'strategy',
                                'trainStop',
                                'id={{ $strategy_id }}'
                                )"
                    type="button"
                    class="btn btn-primary btn-mini trans"
                    title="Stop Training">
                <span class="fas fa-stop"></span> Stop Training
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
