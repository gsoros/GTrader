<script>
    var updateElements_{{ $uid }} = function () {
        var custom = (0 == $('#strategy_id_{{ $uid }}').val());
        $('#form_{{ $uid }} .form-group:not(#form_group_{{ $uid }}_strategy_id)').each(function () {
            if (custom) {
                $(this).show();
            }
            else {
                $(this).hide();
            }
        });
    }
    $('#strategy_id_{{ $uid }}').on('change', function () {
        updateElements_{{ $uid }}();
    });
    updateElements_{{ $uid }}();
</script>
