<div class="row bdr-rad">
    <div class="col-sm-12">
        {{ $strategy->getShortClass() }} Strategy Settings
        <p><small>Changing the topology will delete any trained ANNs.</small></p>
    </div>
</div>
<div class="row bdr-rad">
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
        <div class="form-group editable row">
            <label class="col-sm-6 control-label" for="sample_size">Sample size</label>
            <div class="col-sm-6">
                <input class="btn-primary form-control form-control-sm"
                    type="number"
                    id="sample_size"
                    name="sample_size"
                    title="Sample size"
                    min="1"
                    step="1"
                    max="99"
                    value="{{ $strategy->getParam('sample_size') }}">
            </div>
        </div>
    </div>
    <div class="col-sm-6 container">
        <div class="form-group editable row">
            <label class="col-sm-6 control-label" for="hidden_array">Hidden layers</label>
            <div class="col-sm-6">
                <input class="btn-primary form-control form-control-sm"
                    type="text"
                    id="hidden_array"
                    name="hidden_array"
                    title="Comma-separated list of the number of neurons in the hidden layers"
                    value="{{ join(', ', $strategy->getParam('hidden_array')) }}">
            </div>
        </div>
    </div>
</div>
<div class="row bdr-rad">
    <div class="col-sm-6 container">
        <div class="form-group editable row">
            <label class="col-sm-6 control-label" for="target_distance">Prediction distance</label>
            <div class="col-sm-6">
                <input class="btn-primary form-control form-control-sm"
                    type="number"
                    id="target_distance"
                    name="target_distance"
                    min="1"
                    step="1"
                    max="99"
                    title="Prediction distance in candles"
                    value="{{ $strategy->getParam('target_distance') }}">
            </div>
        </div>
    </div>
    <div class="col-sm-6 container">
        <div class="form-group editable row">
            <label class="col-sm-6 control-label" for="min_trade_distance">Minimum trade distance</label>
            <div class="col-sm-6">
                <input class="btn-primary form-control form-control-sm"
                    type="number"
                    id="min_trade_distance"
                    name="min_trade_distance"
                    min="0"
                    step="1"
                    max="99"
                    title="Do not trade if last trade was more recent than this number of candles"
                    value="{{ $strategy->getParam('min_trade_distance') }}">
            </div>
        </div>
    </div>
</div>
<div class="row bdr-rad">
    <div class="col-sm-6 container">
        <div class="form-group editable row">
            <label for="long_source" class="col-sm-6 control-label">Long signal price source</label>
            <div class="col-sm-6">
                <select class="btn-primary form-control form-control-sm" name="long_source" id="long_source">
                @php
                $sources = [
                    'open' => 'Open',
                    'high' => 'High',
                    'low' => 'Low',
                    'close' => 'Close',
                    //'ohlc4' => 'OHLC4',
                ];
                $setting = $strategy->getParam('long_source', 'open');
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
    <div class="col-sm-6 container">
        <div class="form-group editable row">
            <label for="long_threshold" class="col-sm-6 control-label">Long threshold</label>
            <div class="col-sm-6">
                <input class="btn-primary form-control form-control-sm"
                    type="number"
                    id="long_threshold"
                    name="long_threshold"
                    min="0"
                    step=".01"
                    max="10"
                    title="Trigger long signal if prediction exeeds price by this percentage"
                    value="{{ $strategy->getParam('long_threshold') }}">
            </div>
        </div>
    </div>
</div>
<div class="row bdr-rad">
    <div class="col-sm-6 container">
        <div class="form-group editable row">
            <label for="short_source" class="col-sm-6 control-label">Short signal price source</label>
            <div class="col-sm-6">
                <select class="btn-primary form-control form-control-sm" name="short_source" id="short_source">
                @php
                $setting = $strategy->getParam('short_source', 'open');
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
    <div class="col-sm-6 container">
        <div class="form-group editable row">
            <label for="short_threshold" class="col-sm-6 control-label">Short threshold</label>
            <div class="col-sm-6">
                <input class="btn-primary form-control form-control-sm"
                    type="number"
                    id="short_threshold"
                    name="short_threshold"
                    min="0"
                    step=".01"
                    max="10"
                    title="Trigger short signal if prediction is lower than price minus this percentage"
                    value="{{ $strategy->getParam('short_threshold') }}">

            </div>            
        </div>
    </div>
</div>
