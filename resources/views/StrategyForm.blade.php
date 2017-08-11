<form class="form-horizontal" id="strategyForm">
    <input type="hidden" name="id" value="{{ $strategy->getParam('id') }}">
    <div class="row bdr-rad">
        <div class="col-sm-12">
            Common Strategy Settings
        </div>
    </div>
    <div class="row bdr-rad">
        <div class="form-group editable">
            <label class="col-sm-3 control-label" for="name">Name</label>
            <div class="col-sm-9">
                <input class="btn-primary form-control form-control-sm"
                    type="text"
                    id="name"
                    name="name"
                    title="Strategy Name"
                    value="{{ $strategy->getParam('name') }}">
            </div>
        </div>
        <div class="form-group editable">
            <label  class="col-sm-3 control-label" for="setting2">Description</label>
            <div class="col-sm-9">
                <textarea class="btn-primary form-control form-control-sm"
                    name="description"
                    title="Description">{{ $strategy->getParam('description') }}</textarea>
            </div>
        </div>
    </div>

    {!! $injected !!}
    
    <div class="row bdr-rad editable">
        <div class="col-sm-12">
            <span class="pull-right">
                <button onClick="window.GTrader.request('strategy', 'list')"
                        type="button"
                        class="btn btn-primary btn-sm trans"
                        title="Discard Changes">
                    <span class="glyphicon glyphicon-remove"></span> Discard Changes
                </button>
                <button onClick="window.GTrader.request('strategy', 'save', $('#strategyForm').serialize(), 'POST')"
                        id="strategySaveButton"
                        type="button"
                        class="btn btn-primary btn-sm trans"
                        title="Save Strategy">
                    <span class="glyphicon glyphicon-ok"></span> Save Strategy
                </button>
            </span>
        </div>
    </div>
</form>
