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
        </div>
    </div>
    <div class="col-sm-4">
        <div class="editable">
            Setting 2
        </div>
    </div>
</div>


