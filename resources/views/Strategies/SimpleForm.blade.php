@php
    if (!$uid = $strategy->getParam('uid')) {
        $uid = uniqid();
    }
@endphp
<div class="row bdr-rad">
    <div class="col-sm-12">
        {{ $strategy->getShortClass() }} Strategy Settings
    </div>
</div>
<div class="row bdr-rad">
    <div class="form-group editable">
        <label class="col-sm-3 control-label"
            for="strategy_indicators_list"
            title="Add indicators">
            Indicators Pool
            <p><small>These indicators will not be automatically used by the strategy, unless explicitly selected below.</small></p>
        </label>
        <div class="col-sm-9" style="padding: 0 25px">
            <div id="strategy_indicators_list">
                {!! $strategy->viewIndicatorsList() !!}
            </div>
        </div>
    </div>
</div>
<div class="row bdr-rad">
    <div class="form-group editable">
        <label class="col-sm-3 control-label"
            for="strategy_signals"
            title="Signal Settings">
            Signal Settings
            <p><small>Compare A and B using a condition to generate a short or a long signal.</small></p>
        </label>
        <div class="col-sm-9" style="padding: 0 25px">
            <div id="strategy_signals">
                {!! $strategy->viewSignalsForm() !!}
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
