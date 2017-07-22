<script>
    var updateElements_{{ $uid }} = function () {
        var mode = $('#mode_{{ $uid }}').val();
        if ('line' == mode) {
            $('#form_group_{{ $uid }}_input_open label').text('Source');
            $('#form_group_{{ $uid }}_input_high').hide();
            $('#form_group_{{ $uid }}_input_low').hide();
            $('#form_group_{{ $uid }}_input_close').hide();
        }
        else {
            $('#form_group_{{ $uid }}_input_open label').text('Open Source');
            $('#form_group_{{ $uid }}_input_high').show();
            $('#form_group_{{ $uid }}_input_low').show();
            $('#form_group_{{ $uid }}_input_close').show();
        }
    };
    $('#mode_{{ $uid }}').on('change', function () {
        updateElements_{{ $uid }}();
    });
    updateElements_{{ $uid }}();
</script>
