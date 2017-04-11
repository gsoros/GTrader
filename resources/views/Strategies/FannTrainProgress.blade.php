@php
    $chart = $strategy->getTrainingProgressChart($training);
@endphp
<div class="row bdr-rad">
    <div class="col-sm-12">
        <h4>Training Progress for {{ $strategy->getParam('name') }}</h4>
        {!! $chart->toHTML() !!}
    </div>
</div>
<div class="row bdr-rad">
    <div class="col-sm-12" id="trainProgress">
        <span class="editable cap" id="trainProgressState">
        @if ('paused' === $training->status)
            Paused
        @endif
        </span>
        &nbsp; Epoch: <span class="editable" id="trainProgressEpochs"></span>
        &nbsp; Test Balance: <span class="editable" id="trainProgressTestBalance"></span>
        &nbsp; Test Max: <span class="editable" id="trainProgressTestBalanceMax"></span>
        &nbsp; Verify Balance: <span class="editable" id="trainProgressVerifyBalance"></span>
        &nbsp; Verify Max: <span class="editable" id="trainProgressVerifyBalanceMax"></span>
        &nbsp; Signals: <span class="editable" id="trainProgressSignals"></span>
        &nbsp; Step Up In: <span class="editable" id="trainProgressNoImprovement"></span>
    </div>
</div>
@if ('paused' != $training->status)
    <script>
        var pollTimeout,
            verify_balance_max = 0;
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
                    $('#trainProgressEpochs').html(reply.epochs);
                    $('#trainProgressTestBalance').html(reply.test_balance);
                    $('#trainProgressTestBalanceMax').html(reply.test_balance_max);
                    $('#trainProgressVerifyBalance').html(reply.verify_balance);
                    $('#trainProgressVerifyBalanceMax').html(reply.verify_balance_max);
                    $('#trainProgressSignals').html(reply.signals);
                    $('#trainProgressNoImprovement').html(10 - parseInt(reply.no_improvement));
                    var new_max = parseFloat(reply.verify_balance_max);
                    if (new_max > verify_balance_max) {
                        verify_balance_max = new_max;
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
        $('#trainProgressEpochs').html(' ... ');
        $('#trainProgressTestBalance').html(' ... ');
        $('#trainProgressTestBalanceMax').html(' ... ');
        $('#trainProgressVerifyBalance').html(' ... ');
        $('#trainProgressVerifyBalanceMax').html(' ... ');
        $('#trainProgressSignals').html(' ... ');
        $('#trainProgressNoImprovement').html(' ... ');
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

<!--
<pre class="debug">
    Chart->dumpIndicators():
        {{ $chart->dumpIndicators() }}
    Training:
        {{ var_export($training, true) }}

    Strategy:
        {{ var_export($strategy->getParams(), true) }}
</pre>
-->
