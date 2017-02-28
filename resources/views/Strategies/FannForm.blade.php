<div class="row bdr-rad">
    <div class="col-sm-3">
        {{ $strategy->getShortClass() }} Strategy Settings
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
            <small>Changing the sample size will delete any trained ANNs.</small>
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
</div>


