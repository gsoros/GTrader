<form id="strategyForm">
    <input type="hidden" name="id" value="{{ $strategy->getParam('id') }}">
    <div class="row bdr-rad">
        <div class="col-sm-3">
            Common Strategy Settings
        </div>
        <div class="col-sm-3">
            <div class="editable">
                <label for="setting1">Name</label>
                <input class="btn-primary"
                        type="text"
                        size="20"
                        id="name"
                        name="name"
                        title="Strategy Name"
                        value="{{ $strategy->getParam('name') }}">
            </div>
        </div>
    </div>
    {!! $child_settings !!}
    <div class="row bdr-rad">
        <div class="col-sm-12">
            <span class="pull-right">
                <button onClick="window.strategyRequest('list')"
                        type="button"
                        id="discard_strategy"
                        class="btn btn-primary btn-sm trans"
                        title="Discard Changes">
                    <span class="glyphicon glyphicon-remove"></span> Discard Changes
                </button>
                <button onClick="window.strategyRequest('save', $('#strategyForm').serialize(), 'POST')"
                        type="button"
                        id="save_strategy"
                        class="btn btn-primary btn-sm trans"
                        title="Save Strategy">
                    <span class="glyphicon glyphicon-ok"></span> Save Strategy
                </button>
            </span>
        </div>
    </div>
</form>
