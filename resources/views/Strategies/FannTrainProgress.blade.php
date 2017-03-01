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
        <span class="editable" id="trainProgressState"></span>
        &nbsp; Epoch: <span class="editable" id="trainProgressEpochs"></span>
        &nbsp; Balance: <span class="editable" id="trainProgressBalance"></span>
        &nbsp; Best: <span class="editable" id="trainProgressBalanceMax"></span>
        &nbsp; Signals: <span class="editable" id="trainProgressSignals"></span>
        &nbsp; Step Up In: <span class="editable" id="trainProgressNoImprovement"></span>
    </div>
</div>
<script>
    var pollTimeout,
        balance_max = 0;
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
                $('#trainProgressBalance').html(reply.balance);
                $('#trainProgressBalanceMax').html(reply.balance_max);
                $('#trainProgressSignals').html(reply.signals);
                $('#trainProgressNoImprovement').html(10 - parseInt(reply.no_improvement));
                if (parseFloat(reply.balance_max) > balance_max) {
                    balance_max = parseFloat(reply.balance_max);
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
    $('#trainProgressBalance').html(' ... ');
    $('#trainProgressBalanceMax').html(' ... ');
    $('#trainProgressSignals').html(' ... ');
    $('#trainProgressNoImprovement').html(' ... ');
    pollStatus();

</script>
<div class="row bdr-rad">
    <div class="col-sm-12">
        <span class="pull-right">
            <button onClick="clearTimeout(pollTimeout);
                                window.strategyRequest(
                                    'trainStop',
                                    'id={{ $strategy->getParam('id') }}'
                                    )"
                    type="button"
                    id="stopTrainingButton"
                    class="btn btn-primary btn-sm trans"
                    title="Stop Training">
                <span class="glyphicon glyphicon-fire"></span> Stop Training
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


<pre style="font-size: 10px; height: 280px; overflow: auto; color: #fff; background-color: #111">
    Training:
        {{ var_export($training, true) }}

    Strategy:
        {{ var_export($strategy->getParams(), true) }}
</pre>


