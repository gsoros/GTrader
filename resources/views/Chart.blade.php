<div id="{{ $name }}" class="GTraderChart"></div>
{!! $content !!}
<div class="container-fluid">
    <div class="row">
        <!-- Exchange, Symbol, Resolution Selectors -->
        <div class="col-sm-8 npl" id="esr_{{ $name }}">
            <form class="form-inline">
                <div class="form-group">
                    <select title="Exchange Selector"
                            class="btn-primary btn btn-mini"
                            id="exchange_{{ $name }}"></select>
                    <select title="Symbol Selector"
                            class="btn-primary btn btn-mini"
                            id="symbol_{{ $name }}"></select>
                    <select title="Resolution Selector"
                            class="btn-primary btn btn-mini"
                            id="resolution_{{ $name }}"></select>
                </div>
            </form>
        </div>
        <!-- Strategy Selector -->
        <div class="col-sm-3 npl">
            <form class="form-inline">
                <div class="col-sm-3 text-right" id="strategy_{{ $name }}">
                    <select title="Strategy Selector"
                            class="btn-primary btn btn-mini"
                            id="strategy_select_{{ $name }}"></select>
                </div>
            </form>
        </div>
        <!-- Chart Settings Button -->
        <div class="col-sm-1 text-right">
            <div class="btn-group">

                <!-- Modal -->
                <button type="button"
                        class="btn btn-primary btn-sm"
                        id="settings_{{ $name }}"
                        data-toggle="modal"
                        data-target=".bs-modal-lg">
                    <span class="glyphicon glyphicon-wrench"></span>
                </button>


            </div>
        </div>
    </div>
</div>
