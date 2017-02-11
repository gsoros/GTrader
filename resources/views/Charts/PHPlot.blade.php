<div id="{{ $id }}" class="PHPlot"></div>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <form class="form-inline">
                <div class="form-group">
                    <select class="form-control btn-primary" id="exchange">
                        <option>OKCoin Futures</option>
                        <option>Kraken</option>
                        <option>Bitfinex</option>
                        <option>Another Exchange with a long name</option>
                    </select>
                    <select class="form-control btn-primary" id="symbol">
                        <option>BTC_USD</option>
                        <option>LTC</option>
                    </select>
                    <select class="form-control btn-primary" id="resolution">
                        <option>1 min</option>
                        <option>5 min</option>
                        <option>15 min</option>
                        <option>30 min</option>
                        <option>1 hour</option>
                        <option>2 hours</option>
                    </select>
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


