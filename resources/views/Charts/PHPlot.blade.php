<div id="{{ $id }}" class="PHPlot"></div>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6" id="esr_{{ $id }}">
            <form class="form-inline">
                <div class="form-group">
                    <select class="form-control btn-primary btn-sm" id="exchange_{{ $id }}"></select>
                    <select class="form-control btn-primary btn-sm" id="symbol_{{ $id }}"></select>
                    <select class="form-control btn-primary btn-sm" id="resolution_{{ $id }}"></select>
                </div>
            </form>
        </div>
        <div class="col-md-6" id="controls_{{ $id }}">
            <div class="btn-group">
                <button type="button" class="btn btn-primary btn-lg" id="backward_{{ $id }}">
                    <span class="glyphicon glyphicon-backward"></span>
                </button>
                <button type="button" class="btn btn-primary btn-lg" id="zoomIn_{{ $id }}">
                    <span class="glyphicon glyphicon-zoom-in"></span>
                </button>
                <button type="button" class="btn btn-primary btn-lg" id="zoomOut_{{ $id }}">
                    <span class="glyphicon glyphicon-zoom-out"></span>
                </button>
                <button type="button" class="btn btn-primary btn-lg" id="forward_{{ $id }}">
                    <span class="glyphicon glyphicon-forward"></span>
                </button>
            </div>
        </div>
    </div>
</div>


