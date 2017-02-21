<div class="row bdr-rad">
    <div class="col-sm-3">
        {{ $strategy->getShortClass() }} Strategy Settings
    </div>
    <div class="col-sm-3">
        <div class="editable">
            <label for="setting1">Fann filename</label>
            <input class="btn-primary btn btn-mini"
                    type="text"
                    size="15"
                    id="config_file"
                    name="config_file"
                    title="Filename"
                    value="{{ $strategy->getParam('config_file') }}">
        </div>
    </div>
    <div class="col-sm-3">
        <div class="editable">
            Setting 2
        </div>
    </div>
    <div class="col-sm-3">
        <div class="editable">
            Setting 2
        </div>
    </div>
</div>
<div class="row bdr-rad">
    <div class="col-sm-12">
        <h4>Training</h4>
    </div>
</div>

