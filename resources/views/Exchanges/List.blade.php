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
                        <strong>{{ $exchange->getLongName() }}</strong>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group editbuttons">
                            <button type="button"
                                    class="btn btn-primary btn-mini editbutton trans"
                                    title="Preferences"
                                    onClick="window.GTrader.request('exchange', 'form', 'id={{ $id }}', 'GET', 'settingsTab')">
                                <span class="fas fa-wrench"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@if (isset($reload) && is_array($reload) && in_array('ESR', $reload))
    <script>
        if (window.GTrader.reloadESR) {
            window.GTrader.reloadESR();
        }
    </script>
@endif
