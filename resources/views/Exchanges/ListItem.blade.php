<div class="col-sm-3 editable container">
    <div class="row">
        <div class="col-sm-8">
            <strong>{{ $exchange->getLongName() }}</strong>
        </div>
        <div class="col-sm-4">
            <div class="form-group editbuttons">
                <button type="button"
                        class="btn btn-primary btn-mini editbutton trans"
                        title="Preferences"
                        onClick="window.GTrader.request(
                            'exchange',
                            'form',
                            'id={{ $exchange->getId() }}',
                            'GET',
                            'settingsTab'
                        )">
                    <span class="fas fa-wrench"></span>
                </button>
            </div>
        </div>
    </div>
</div>
