@if (!in_array('panZoom', $disabled))
    <div class="PHPlot-panzoom" id="panzoom_{{ $name }}">
        <div class="btn-group">
            <button type="button"
                    title="Backward"
                    class="btn btn-primary btn-sm"
                    id="backward_{{ $name }}">
                <span class="glyphicon glyphicon-backward"></span>
            </button>
            <button type="button"
                    title="Zoom In"
                    class="btn btn-primary btn-sm"
                    id="zoomIn_{{ $name }}">
                <span class="glyphicon glyphicon-zoom-in"></span>
            </button>
            <button type="button"
                    title="Zoom Out"
                    class="btn btn-primary btn-sm"
                    id="zoomOut_{{ $name }}">
                <span class="glyphicon glyphicon-zoom-out"></span>
            </button>
            <button type="button"
                    title="Forward"
                    class="btn btn-primary btn-sm"
                    id="forward_{{ $name }}">
                <span class="glyphicon glyphicon-forward"></span>
            </button>
        </div>
    </div>
@endif
<script>
    if (window.PHPlot)
        window.PHPlot.register('{{ $name }}');
    else {
        $(function() {
            window.PHPlot.register('{{ $name }}');
        });
    }
</script>

