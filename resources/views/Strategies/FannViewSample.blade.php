<div class="row">
    <div class="col-xs-12" title="Normalized sample">
        {!! $plot !!}
    </div>
</div>
<div class="row">
    <div class="col-xs-12 text-center">
        <div class="btn-group">
            <button type="button"
                    title="Step back one sample"
                    class="btn btn-primary btn-sm"
                    onClick="window.GTrader.viewSample('{{ $chart_name }}', '{{ $prev }}', false)">
                <span class="glyphicon glyphicon-backward"></span>
            </button>
            <button type="button"
                    class="editable btn btn-primary btn-sm">
                {{ date('Y-m-d H:i', $now) }}
            </button>
            <button type="button"
                    title="Go forward one sample"
                    class="btn btn-primary btn-sm"
                    onClick="window.GTrader.viewSample('{{ $chart_name }}', '{{ $next }}', false)">
                <span class="glyphicon glyphicon-forward"></span>
            </button>
        </div>
    </div>
</div>
