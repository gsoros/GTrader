@php
    if (!$uid = $strategy->getParam('uid')) {
        $uid = uniqid();
    }
@endphp
<div class="row bdr-rad">
    <div class="col-md-12">
        {{ $strategy->getShortClass() }} Strategy Settings
    </div>
    <div class="col-md-12 container-fluid">
        <div class="form-group editable row">
            <label class="col-md-3 control-label npl"
                for="strategy_indicators_list"
                title="Add indicators">
                Indicators Pool
                <p><small>These indicators will not be automatically used by the strategy, unless explicitly selected below.</small></p>
            </label>
            <div class="col-md-9" style="padding: 0 25px">
                <div id="strategy_indicators_list">
                    {!! $strategy->viewIndicatorsList() !!}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12 container-fluid">
        <div class="form-group editable row">
            <label class="col-md-3 control-label npl"
                for="strategy_signals"
                title="Signal Settings">
                Signal Settings
                <p><small>Compare A and B using a condition to generate a short or a long signal.</small></p>
            </label>
            <div class="col-md-9" style="padding: 0 25px">
                <div id="strategy_signals">
                    {!! $strategy->viewSignalsForm() !!}
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    /* Reload the signals form */
    window.refreshSignals_{{ $uid }} = function() {
        window.GTrader.request(
            'strategy.Simple',
            'signalsForm',
            {
                id: {{ $strategy->getParam('id') }}
            },
            'GET',
            'strategy_signals'
        );
    }
</script>
