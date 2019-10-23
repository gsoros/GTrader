<div class="container">
    <div class="row">
        <div class="col-sm-3">
            @foreach ($keys as $key)
                <div class="editable">
                    <a href="#" onClick="window.GTrader.request(
                        'dev',
                        'pcache',
                        'key={{ $key->cache_key }}',
                        'GET',
                        'pcacheValue')">
                        {{ $key->cache_key }}
                    </a>
                </div>
            @endforeach
        </div>
        <div id="pcacheValue" class="col-sm-9"></div>
    </div>
</div>
