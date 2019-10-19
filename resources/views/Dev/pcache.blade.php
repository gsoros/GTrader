<div class="container">
    <div class="row">
        <div class="col-sm-3">
            @foreach ($keys as $key)
                <a href="#" onClick="window.GTrader.request(
                    'dev',
                    'pcache',
                    'key={{ $key->cache_key }}',
                    'GET',
                    'pcacheValue')">
                    {{ $key->cache_key }}
                </a>
            @endforeach
        </div>
        <div id="pcacheValue" class="col-sm-9"></div>
    </div>
</div>
