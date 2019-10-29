<div class="np" id="fullscreen-wrap_{{ $name }}">
    <div id="{{ $name }}" class="GTraderChart np"
    @if ($height)
        style="height: {{ $height }}px"
    @endif
    ></div>
    {!! $content !!}
</div>
<script>
    $(function() {
        window.GTrader = $.extend(true, window.GTrader, {
            charts: {
                {{ $name }}: {!! $JSON !!}
            }
        });
        window.GTrader.registerChart('{{ $name }}');
    });
</script>
<div class="d-flex">

    @if (!in_array('esr', $disabled))
    <!-- Exchange, Symbol, Resolution Selectors -->
    <div class="p-2" id="esr_{{ $name }}">
        <div class="form-group">
            @if (in_array('esr', $readonly))
                @php
                     $candles = $chart->getCandles();
                @endphp
                <small>
                {{ GTrader\Exchange::getESRReadonly(
                    $candles->getParam('exchange'),
                    $candles->getParam('symbol'),
                    $candles->getParam('resolution')) }}
                </small>
            @else
                {!! GTrader\Exchange::getESRSelector($chart->getParam('name')) !!}
            @endif
        </div>
    </div>
    @endif

    @if (!in_array('strategy', $disabled))
    <!-- Strategy Selector -->
    <div class="p-2">
    @if (in_array('strategy', $readonly))
        <small>[{{ $chart->getStrategy()->getParam('name') }}]</small>
    @else
        <select title="Strategy Selector"
                class="btn-primary btn btn-mini"
                id="strategy_select_{{ $name }}"></select>
        <script>
            $(function() {
                window.GTrader.registerStrategySelector('{{ $name }}');
            });
        </script>
    @endif
    </div>
    @endif

    @if (!in_array('settings', $disabled))
    <!-- Chart Settings Button -->
    <div class="ml-auto p-2">
        <button type="button"
                class="btn btn-primary btn-mini"
                id="settings_{{ $name }}"
                data-toggle="modal"
                data-target=".bs-modal-lg">
            <span class="fas fa-wrench"></span>
        </button>
    </div>
    @endif

</div>
