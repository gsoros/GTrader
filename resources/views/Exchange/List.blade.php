<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <h3>Settings for Exchanges</h3>
        </div>
    </div>
    <div class="row">
        @foreach ($exchanges as $exchange)
            @php
                $id = $exchange->getId();
            @endphp
            <div class="col-sm-6 editable">
                <div class="row">
                    <div class="col-sm-8">
                        <strong>{{ $exchange->getParam('long_name') }}</strong>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group editbuttons">
                            <button type="button"
                                    class="btn btn-primary btn-sm editbutton trans"
                                    title="Preferences"
                                    onClick="window.GTrader.request('exchange', 'form', 'id={{ $id }}', 'GET', 'settingsTab')">
                                <span class="glyphicon glyphicon-wrench"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
