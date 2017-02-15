<div id="{{ $id }}" class="GTraderChart"></div>
{!! $content !!}
<div class="container-fluid">
    <div class="row">
        <!-- Exchange, Symbol, Resolution Selectors -->
        <div class="col-xs-11 npl" id="esr_{{ $id }}">
            <form class="form-inline">
                <div class="form-group">
                    <select class="btn-primary btn btn-mini" id="exchange_{{ $id }}"></select>
                    <select class="btn-primary btn btn-mini" id="symbol_{{ $id }}"></select>
                    <select class="btn-primary btn btn-mini" id="resolution_{{ $id }}"></select>
                </div>
            </form>
        </div>
        <!-- Chart Settings Button -->
        <div class="col-xs-1 text-right">
            <div class="btn-group">

                <!-- Modal -->
                <button type="button" class="btn btn-primary btn-sm" id="settings_{{ $id }}"
                        data-toggle="modal" data-target=".bs-modal-lg">
                    <span class="glyphicon glyphicon-wrench"></span>
                </button>


            </div>
        </div>
    </div>
</div>
