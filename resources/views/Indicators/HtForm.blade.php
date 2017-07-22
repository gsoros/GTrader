<script>
    var updateElements_{{ $uid }} = function () {
        var mode = $('#mode_{{ $uid }}').val();
        var modes_with_b = [
        @php
            foreach ($indicator->getParam('modes', []) as $mode_key => $mode) {
                if (isset($mode['sources'])) {
                    if (is_array($mode['sources'])) {
                        if (in_array('input_b', $mode['sources'])) {
                            echo '"'.$mode_key.'", ';
                        }
                    }
                }
            }
        @endphp
        ];
        if (-1 !== $.inArray(mode, modes_with_b)) {
            console.log(mode + ' = show() because ' + modes_with_b);
            $('#form_group_{{ $uid }}_input_b').show();
        }
        else {
            console.log(mode + ' = hide()');
            $('#form_group_{{ $uid }}_input_b').hide();
        }
    };
    $('#mode_{{ $uid }}').on('change', function () {
        updateElements_{{ $uid }}();
    });
    updateElements_{{ $uid }}();
</script>
