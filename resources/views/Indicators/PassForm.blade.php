<script>
    var updateElements_{{ $uid }} = function () {
        var mode = $('#mode_{{ $uid }}').val();
        if ('high' == mode) {
            $('#form_group_{{ $uid }}_input_lowRef').hide();
            $('#form_group_{{ $uid }}_input_highRef').show();
        }
        else if ('low' == mode) {
            $('#form_group_{{ $uid }}_input_lowRef').show();
            $('#form_group_{{ $uid }}_input_highRef').hide();
        }
        else if ('band' == mode) {
            $('#form_group_{{ $uid }}_input_lowRef').show();
            $('#form_group_{{ $uid }}_input_highRef').show();
        }
    };
    $('#mode_{{ $uid }}').on('change', function () {
        updateElements_{{ $uid }}();
    });
    updateElements_{{ $uid }}();
</script>
