<form id="strategyForm">
    <input type="hidden" name="id" value="{{ $strategy->getParam('id') }}">
    <div class="row bdr-rad">
        <div class="col-sm-3">
            Common Strategy Settings
        </div>
        <div class="col-sm-4">
            <div class="editable form-group">
                <label for="name">Name</label>
                <input class="btn-primary form-control form-control-sm"
                        type="text"
                        id="name"
                        name="name"
                        title="Strategy Name"
                        value="{{ $strategy->getParam('name') }}">
            </div>
        </div>
        <div class="col-sm-4">
            <div class="editable form-group">
                <label for="setting2">Another setting</label>
                <input class="btn-primary form-control form-control-sm"
                        type="text"
                        id="setting2"
                        name="setting2"
                        title="Another setting"
                        value="unused"
                        disabled>
            </div>
        </div>
    </div>
    {!! $child_settings !!}
    <div class="row bdr-rad">
        <div class="col-sm-12">
            <span class="pull-right">
                <button onClick="window.GTrader.request('strategy', 'list')"
                        type="button"
                        class="btn btn-primary btn-sm trans"
                        title="Discard Changes">
                    <span class="glyphicon glyphicon-remove"></span> Discard Changes
                </button>
                <button onClick="window.GTrader.request('strategy', 'save', $('#strategyForm').serialize(), 'POST')"
                        type="button"
                        class="btn btn-primary btn-sm trans"
                        title="Save Strategy">
                    <span class="glyphicon glyphicon-ok"></span> Save Strategy
                </button>
            </span>
        </div>
    </div>
</form>
