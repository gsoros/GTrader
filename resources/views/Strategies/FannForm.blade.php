<div class="row bdr-rad">
    <div class="col-sm-12">
        {{ $strategy->getShortClass() }} Strategy Settings
        <p><small>Changing the topology will delete the trained ANN.</small></p>
    </div>
    <div class="col-sm-12 container">
        <div class="form-group editable row">
            <label class="col-sm-3 control-label"
                for="inputs"
                title="Select the input values to be used by the ANN">
                Inputs
            </label>
            <div class="col-sm-9" style="padding: 0 25px">
                <div id="strategy_indicators_list">
                    {!! $strategy->viewIndicatorsList() !!}
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 container">
        <div class="form-group editable row" title="Sample size">
            <label class="col-sm-6 control-label" for="sample_size">Sample size</label>
            <div class="col-sm-6">
                <input class="btn-primary form-control form-control-sm"
                    type="number"
                    id="sample_size"
                    name="sample_size"
                    min="1"
                    step="1"
                    max="99"
                    value="{{ $strategy->getParam('sample_size') }}">
            </div>
        </div>
    </div>
    <div class="col-sm-6 container">
        <div class="form-group editable row" title="Comma-separated list of the number of neurons in the hidden layers">
            <label class="col-sm-6 control-label" for="hidden_array">Hidden layers</label>
            <div class="col-sm-6">
                <input class="btn-primary form-control form-control-sm"
                    type="text"
                    id="hidden_array"
                    name="hidden_array"
                    value="{{ join(', ', $strategy->getParam('hidden_array')) }}">
            </div>
        </div>
    </div>
</div>
<div class="row bdr-rad">
    <div class="col-sm-4 container">
        <div class="form-group editable row" title="Prediction distance in candles">
            <label class="col-sm-6 control-label" for="target_distance">Prediction distance</label>
            <div class="col-sm-6">
                <input class="btn-primary form-control form-control-sm"
                    type="number"
                    id="target_distance"
                    name="target_distance"
                    min="1"
                    step="1"
                    max="99"
                    value="{{ $strategy->getParam('target_distance') }}">
            </div>
        </div>
    </div>
    <div class="col-sm-4 container">
        <div class="form-group editable row" title="Do not open a position if last trade is more recent than this number of candles">
            <label class="col-sm-6 control-label" for="min_trade_distance">Minimum trade distance</label>
            <div class="col-sm-6">
                <input class="btn-primary form-control form-control-sm"
                    type="number"
                    id="min_trade_distance"
                    name="min_trade_distance"
                    min="0"
                    step="1"
                    max="99"
                    value="{{ $strategy->getParam('min_trade_distance') }}">
            </div>
        </div>
    </div>
    <div class="col-sm-4 container">
        <div class="form-group editable row" title="Apply exponential moving average to the prediction. This will add signal lag.">
            <label class="col-sm-6 control-label" for="prediction_ema">Prediction EMA <small><=1: disabled</small></label>
            <div class="col-sm-6">
                <input class="btn-primary form-control form-control-sm"
                    type="number"
                    id="prediction_ema"
                    name="prediction_ema"
                    min="0"
                    step="1"
                    max="20"
                    value="{{ $strategy->getParam('prediction_ema') }}">
            </div>
        </div>
    </div>
</div>
@php
    $sources = [
        'open' => 'Open',
        'high' => 'High',
        'low' => 'Low',
        'close' => 'Close',
        //'ohlc4' => 'OHLC4',
    ];
@endphp
@foreach (['long' => 'plus', 'short' => 'minus'] as $dir => $dir_thresh_title)
    <div class="row bdr-rad">
        <div class="col-sm-4 container">
            <div class="form-group editable row">
                <label for="{{ $dir }}_source" class="col-sm-6 control-label">{{ ucfirst($dir) }} signal price source</label>
                <div class="col-sm-6">
                    <select class="btn-primary form-control form-control-sm" name="{{ $dir }}_source" id="{{ $dir }}_source">
                    @php
                        $setting = $strategy->getParam($dir.'_source', 'open');
                    @endphp
                    @foreach ($sources as $value => $label)
                        <option
                        @if ($value === $setting)
                            selected
                        @endif
                        value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                    </select>
                </div>
            </div>
        </div>
        @foreach ([
            'open' => ['long' => '>=', 'short' => '<='],
            'close' => ['long' => '<=', 'short' => '>='],
        ] as $action => $cond)
            @php
                $a_d = $action.'_'.$dir;
            @endphp
            <div class="col-sm-4 container">
                <div class="form-group editable row" title="Trigger {{ $action}} {{ $dir }} signal if prediction {{ $cond[$dir] }} price {{ $dir_thresh_title }} this percentage">
                    <label for="{{ $a_d }}_threshold" class="col-sm-6 control-label">{{ ucfirst($action) }} {{ $dir }} threshold</label>
                    <div class="col-sm-6">
                        <input class="btn-primary form-control form-control-sm"
                            type="number"
                            id="{{ $a_d }}_threshold"
                            name="{{ $a_d }}_threshold"
                            min="-10"
                            step=".01"
                            max="10"
                            value="{{ $strategy->getParam($action.'_'.$dir.'_threshold') }}">
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endforeach
