@php
    $chart = $strategy->getTrainingProgressChart($training);
@endphp
<div class="row bdr-rad">
    <div class="col-sm-12">
        <h4>Training Progress for
            <span title="Strategy ID: {{ $strategy->getParam('id') }} Training ID: {{ $training->id }}">
                {{ $strategy->getParam('name') }}
            </span>
        </h4>
        {!! $chart->toHTML() !!}
    </div>
</div>
<div class="row bdr-rad">
    <div class="col-sm-12" id="trainHistory" title="Training History">
    </div>
</div>
<div class="row bdr-rad">
    <div class="col-sm-12" id="trainProgress">
        <span class="editable cap" id="trainProgressState">
        @if ('paused' === $training->status)
            Paused
        @endif
        </span>
        &nbsp; Epoch: <span class="editable" id="trainProgressEpoch"></span>
        &nbsp; Test: <span class="editable" id="trainProgressTest"></span>
        &nbsp; Train MSER: <span class="editable" id="trainProgressTrainMSER"></span>
        &nbsp; Verify: <span class="editable" id="trainProgressVerify"></span>
        &nbsp; Signals: <span class="editable" id="trainProgressSignals"></span>
        &nbsp; Step Up In: <span class="editable" id="trainProgressNoImprovement"></span>
        &nbsp; Epochs Between Tests: <span class="editable" id="trainProgressEpochJump"></span>
    </div>
</div>
@if ('paused' != $training->status)
    <script>
        var pollTimeout,
            verify_max = 0,
            last_epoch = 0;
        function pollStatus() {
            console.log('pollStatus() ' + $('#trainProgress').length);
            $.ajax({
                url: '/strategy.trainProgress?id={{ $strategy->getParam('id') }}',
                success: function(data) {
                    try {
                        reply = JSON.parse(data);
                    }
                    catch (err) {
                        console.log(err);
                    }
                    var state = ('undefined' === reply.state) ? 'queued' : reply.state;
                    $('#trainProgressState').html(state);
                    $('#trainProgressEpoch').html(reply.epoch);
                    $('#trainProgressTest').html(reply.test + ' / ' + reply.test_max);
                    $('#trainProgressTrainMSER').html(reply.train_mser);
                    $('#trainProgressVerify').html(reply.verify + ' / ' + reply.verify_max);
                    $('#trainProgressSignals').html(reply.signals);
                    $('#trainProgressNoImprovement').html(10 - parseInt(reply.no_improvement));
                    $('#trainProgressEpochJump').html(reply.epoch_jump);
                    var new_epoch = parseInt(reply.epoch);
                    if (new_epoch > last_epoch && $('#trainHistory').is(':visible')) {
                        last_epoch = new_epoch;
                        window.GTrader.request(
                            'strategy',
                            'trainHistory',
                            {
                                id: {{ $strategy->getParam('id') }},
                                width: $('#trainHistory').width(),
                                height: 200
                            },
                            'GET',
                            'trainHistory'
                        );
                    }
                    var new_max = parseFloat(reply.verify_max);
                    if (new_max > verify_max) {
                        verify_max = new_max;
                        window.{{ $chart->getParam('name') }}.refresh();
                    }
                },
                complete: function() {
                    if ($('#trainProgress').length)
                        pollTimeout = setTimeout(pollStatus, 3000);
                }
            });
        }
        $('#trainProgressState').html('queued');
        $('#trainProgressEpoch').html(' ... ');
        $('#trainProgressTest').html(' ... ');
        $('#trainProgressTrainMSER').html(' ... ');
        $('#trainProgressVerify').html(' ... ');
        $('#trainProgressSignals').html(' ... ');
        $('#trainProgressNoImprovement').html(' ... ');
        $('#trainProgressEpochJump').html(' ... ');
        pollStatus();

    </script>
@endif
<div class="row bdr-rad">
    <div class="col-sm-12">
        <span class="pull-right">
            @if ('paused' === $training->status)
                <button onClick="window.GTrader.request(
                                        'strategy',
                                        'trainResume',
                                        'id={{ $strategy->getParam('id') }}'
                                        )"
                        type="button"
                        class="btn btn-primary btn-sm trans"
                        title="Resume Training">
                    <span class="glyphicon glyphicon-play"></span> Resume Training
                </button>
            @else
                <button onClick="clearTimeout(pollTimeout);
                                    window.GTrader.request(
                                        'strategy',
                                        'trainPause',
                                        'id={{ $strategy->getParam('id') }}'
                                        )"
                        type="button"
                        class="btn btn-primary btn-sm trans"
                        title="Pause Training">
                    <span class="glyphicon glyphicon-pause"></span> Pause Training
                </button>
            @endif
            <button onClick="clearTimeout(pollTimeout);
                                window.GTrader.request(
                                    'strategy',
                                    'trainStop',
                                    'id={{ $strategy->getParam('id') }}'
                                    )"
                    type="button"
                    class="btn btn-primary btn-sm trans"
                    title="Stop Training">
                <span class="glyphicon glyphicon-stop"></span> Stop Training
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
