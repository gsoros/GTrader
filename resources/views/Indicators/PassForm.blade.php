<script>
    var disableElements_{{ $uid }} = function () {
        var mode = $('#mode_{{ $uid }}').val();
        console.log('mode is: ' + mode);
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
        disableElements_{{ $uid }}();
    });
    disableElements_{{ $uid }}();
</script>
