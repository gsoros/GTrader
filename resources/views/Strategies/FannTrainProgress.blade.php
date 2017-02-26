<div class="row bdr-rad">
    <div class="col-sm-12" id='training'>
        <h4>Training Progress for {{ $strategy->getParam('name') }}</h4>
        {!! $strategy->getTrainingChart()->toHTML() !!}
    </div>
</div>
<div class="row bdr-rad">
    <div class="col-sm-12">
        <span class="pull-right">
            <button onClick="window.strategyRequest(
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
<pre>
    {{ $strategy->getParam('debug') }}
</pre>
