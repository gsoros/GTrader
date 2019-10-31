<form class="form-horizontal container-fluid" id="strategyForm">
    <input type="hidden" name="id" value="{{ $strategy->getParam('id') }}">
    <div class="row bdr-rad">
        <div class="col-sm-8">
            Common Strategy Settings
        </div>

        <div class="col-sm-4 text-right">
            @section('buttons')
                <button onClick="window.GTrader.request('strategy', 'list')"
                        type="button"
                        class="btn btn-primary btn-mini trans"
                        title="Discard Changes">
                    <span class="fas fa-ban"></span> Discard Changes
                </button>
                <button onClick="window.GTrader.request('strategy', 'save', $('#strategyForm').serialize(), 'POST')"
                        type="button"
                        class="btn btn-primary btn-mini trans"
                        title="Save Strategy">
                    <span class="fas fa-check"></span> Save Strategy
                </button>
            @show
        </div>

        <div class="col-sm-12 container">
            <div class="form-group editable row">
                <label class="col-sm-3 control-label npl" for="name">Name</label>
                <div class="col-sm-9">
                    <input class="btn-primary form-control form-control-sm"
                        type="text"
                        id="name"
                        name="name"
                        title="Strategy Name"
                        value="{{ $strategy->getParam('name') }}">
                </div>
            </div>
            <div class="form-group editable row">
                <label  class="col-sm-3 control-label npl" for="description">Description</label>
                <div class="col-sm-9">
                    <textarea class="btn-primary form-control form-control-sm"
                        name="description"
                        title="Description">{{ $strategy->getParam('description') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {!! $injected !!}

    <div class="col-sm-12 container">
        <div class="row">
            <div class="col-sm-12 text-right">
                @yield('buttons')
            </div>
        </div>
    </div>
</form>
