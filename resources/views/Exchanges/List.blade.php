<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <h3>Settings for Exchanges</h3>
        </div>
    </div>
    <div class="row">
        @foreach ($exchanges as $exchange)
            {{ $exchange->getListItem() }}
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
