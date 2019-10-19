<div class="col-sm-3 editable container">
    <div class="row">
        <div class="col-sm-6">
            <strong>More Exchanges</strong>
        </div>
        <div class="col-sm-6">
            <div class="form-group editbuttons">
                <button type="button"
                        class="btn btn-primary btn-mini editbutton trans"
                        title="Configure More Exchanges"
                        onClick="window.GTrader.request(
                            'exchange',
                            'form',
                            'id={{ $exchange->getId() }}',
                            'GET',
                            'settingsTab'
                        )">
                    <span class="fas fa-wrench"></span> Configure
                </button>
            </div>
        </div>
    </div>
</div>
