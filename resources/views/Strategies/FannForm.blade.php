<div class="row bdr-rad">
    <div class="col-sm-3">
        {{ $strategy->getShortClass() }} Strategy Settings
        <p><small>Changing the topology will delete any trained ANNs.</small></p>
    </div>
    <div class="col-sm-4">
        <div class="editable form-group">
            <label for="num_samples">Sample size</label>
            <input class="btn-primary form-control form-control-sm"
                    type="number"
                    id="num_samples"
                    name="num_samples"
                    title="Sample size"
                    value="{{ $strategy->getParam('num_samples') }}">
        </div>
    </div>
    <div class="col-sm-4">
        <div class="editable form-group">

            <div class="form-check form-check-inline">
                <label class="form-check-label" title="Include volume in the samples">
                    <input class="form-check-input"
                            type="checkbox"
                            id="use_volume"
                            name="use_volume"
                            value="1"
                                @if ($strategy->getParam('use_volume'))
                                    checked
                                @endif
                            > Include Volume
                </label>
            </div>

        </div>
    </div>
    <div class="col-sm-4">
        <div class="editable form-group">
            <label for="hidden_array">Hidden layers</label>
            <input class="btn-primary form-control form-control-sm"
                    type="text"
                    id="hidden_array"
                    name="hidden_array"
                    title="Comma-separated list of the number of neurons in the hidden layers"
                    value="{{ join(', ', $strategy->getParam('hidden_array')) }}">
        </div>
    </div>
    <div class="col-sm-4">
        <div class="editable form-group">
            <label for="target_distance">Prediction distance</label>
            <input class="btn-primary form-control form-control-sm"
                    type="number"
                    id="target_distance"
                    name="target_distance"
                    title="Prediction distance in candles"
                    value="{{ $strategy->getParam('target_distance') }}">
        </div>
    </div>
    <div class="col-sm-4">
        <div class="editable form-group row">
            <div class="col-sm-12">Thresholds</div>
            <label for="long_threshold" class="col-sm-2 col-form-label">Long</label>
            <div class="col-sm-10">
                <input class="btn-primary form-control form-control-sm"
                        type="number"
                        id="long_threshold"
                        name="long_threshold"
                        title="Prediction fraction of price to trigger a long signal"
                        value="{{ $strategy->getParam('long_threshold') }}">
            </div>
            <label for="short_threshold" class="col-sm-2 col-form-label">Short</label>
            <div class="col-sm-10">
                <input class="btn-primary form-control form-control-sm"
                        type="number"
                        id="short_threshold"
                        name="short_threshold"
                        title="Prediction fraction of price to trigger a short signal"
                        value="{{ $strategy->getParam('short_threshold') }}">
            </div>
        </div>
    </div>
</div>
