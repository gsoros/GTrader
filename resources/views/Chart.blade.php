<div id="{{ $name }}" class="GTraderChart"
@if ($height)
    style="height: {{ $height }}px"
@endif
></div>
<script>
    window.{{ $name }} = {!! $JSON !!};
    if (window.Chart)
        window.Chart.register('{{ $name }}');
    else {
        $(function() {
            window.Chart.register('{{ $name }}');
        });
    }
</script>
{!! $content !!}
<div class="container-fluid">
    <div class="row">

        @if (!in_array('esr', $disabled))
        <!-- Exchange, Symbol, Resolution Selectors -->
        <div class="col-sm-8 npl" id="esr_{{ $name }}">
            <form class="form-inline">
                <div class="form-group">
                    @if (in_array('esr', $readonly))
                        @php
                             $candles = $chart->getCandles();
                        @endphp
                        <small>
                        [{{ $candles->getParam('exchange') }}]
                        [{{ $candles->getParam('symbol') }}]
                        [{{ $candles->getParam('resolution') }}]
                        </small>
                    @else
                        <select title="Exchange Selector"
                                class="btn-primary btn btn-mini"
                                id="exchange_{{ $name }}"></select>
                        <select title="Symbol Selector"
                                class="btn-primary btn btn-mini"
                                id="symbol_{{ $name }}"></select>
                        <select title="Resolution Selector"
                                class="btn-primary btn btn-mini"
                                id="resolution_{{ $name }}"></select>
                    @endif
                </div>
            </form>
        </div>
        @endif

        @if (!in_array('strategy', $disabled))
        <!-- Strategy Selector -->
        <div class="col-sm-2 npl">
            <form class="form-inline">
                @if (in_array('strategy', $readonly))
                    <small>[{{ $chart->getStrategy()->getParam('name') }}]</small>
                @else
                    <select title="Strategy Selector"
                            class="btn-primary btn btn-mini"
                            id="strategy_select_{{ $name }}"></select>
                @endif

            </form>
        </div>
        @endif

        @if (!in_array('settings', $disabled))
        <!-- Chart Settings Button -->
        <div class="col-sm-2 text-right">
            <button type="button"
                    class="btn btn-primary btn-sm"
                    id="settings_{{ $name }}"
                    data-toggle="modal"
                    data-target=".bs-modal-lg">
                <span class="glyphicon glyphicon-wrench"></span>
                Chart Settings
            </button>
        </div>
        @endif

    </div>
</div>











