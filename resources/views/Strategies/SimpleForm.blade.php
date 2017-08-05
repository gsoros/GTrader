@php
    if (!$uid = $strategy->getParam('uid')) {
        $uid = uniqid();
    }
@endphp
<div class="row bdr-rad">
    <div class="col-sm-12">
        {{ $strategy->getShortClass() }} Strategy Settings
    </div>
</div>
<div class="row bdr-rad">
    <div class="form-group editable">
        <label class="col-sm-3 control-label"
            for="strategy_indicators_list"
            title="Add indicators">
            Indicators Pool
        </label>
        <div class="col-sm-9" style="padding: 0 25px">
            <div id="strategy_indicators_list">
                {!! $strategy->viewIndicatorsList() !!}
            </div>
        </div>
    </div>
</div>
<div class="row bdr-rad">
    <div class="form-group editable">
        <label class="col-sm-3 control-label"
            for="strategy_signals"
            title="Signal Settings">
            Signal Settings
        </label>
        <div class="col-sm-9" style="padding: 0 25px">
            <div id="strategy_signals">
                {!! $strategy->viewSignalForm() !!}
            </div>
        </div>
    </div>
</div>
<script>
    /* Update the list of sources if the pool changes */
    window.refresh_{{ $uid }} = function() {
        $.ajax({
            url: 'indicator.sources?owner_class=Strategy&owner_id={{ $strategy->getParam('id')}}',
            dataType: 'json',
            success: function(reply) {
                var sigs = [];
                for (var sig in reply) {
                    sigs[encodeURIComponent(sig)] = reply[sig];
                }
                $('#strategy_signals select').each(function() {
                    if (0 != $(this).attr('id').indexOf('input_')) {
                        return;
                    }
                    var selected = $(this).val();
                    $('#' + $(this).attr('id') + ' option').remove();
                    for (var sig in sigs) {
                        $(this).append($('<option>', {
                            selected: (selected == sig),
                            value: sig,
                            text: sigs[sig]
                        }));
                    }
                });
            }
        });
    }
</script>
