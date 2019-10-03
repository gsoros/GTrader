@if (!in_array('panZoom', $disabled))
    <div class="PHPlot-panzoom" id="panzoom_{{ $name }}">
        <div class="btn-group">
            <button type="button"
                    title="Backward"
                    class="btn btn-primary btn-mini"
                    id="backward_{{ $name }}">
                <span class="fas fa-backward"></span>
            </button>
            <button type="button"
                    title="Zoom In"
                    class="btn btn-primary btn-mini"
                    id="zoomIn_{{ $name }}">
                <span class="fas fa-search-plus"></span>
            </button>
            <button type="button"
                    title="Zoom Out"
                    class="btn btn-primary btn-mini"
                    id="zoomOut_{{ $name }}">
                <span class="fas fa-search-minus"></span>
            </button>
            <button type="button"
                    title="Forward"
                    class="btn btn-primary btn-mini"
                    id="forward_{{ $name }}">
                <span class="fas fa-forward"></span>
            </button>
            @if (!in_array('fullscreen', $disabled))
            <button type="button"
                    title="Fullscreen"
                    class="btn btn-primary btn-mini"
                    id="fullscreen_{{ $name }}">
                <span class="fas fa-expand"></span>
            </button>
            @endif
        </div>
    </div>
@endif
@php
    $initial_refresh = in_array('initial_refresh', $disabled) ? 'false' : 'true';
@endphp
<script>
    $(function() {
        window.GTrader = $.extend(true, window.GTrader, {
            charts: {
                {{ $name }}: {
                    registerCallbacks: [
                        function () {
                            GTrader.registerPHPlot('{{ $name }}', {{ $initial_refresh }});
                        }
                    ]
                }
            }
        });
    });
</script>
