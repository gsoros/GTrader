<script>
    var updateElements_{{ $uid }} = function (which) {
        var line = $('#show_line_{{ $uid }}').checked;
        var ann = $('#show_annotation_{{ $uid }}').checked;
        if (!line && !ann) {
            if (undefined == which) {
                which = 'annotation';
            }
            $('#show_' + which + '_{{ $uid }}').prop('checked', true);
        }
    };
    $('#show_annotation_{{ $uid }}').on('change', function () {
        updateElements_{{ $uid }}('line');
    });
    $('#show_line_{{ $uid }}').on('change', function () {
        updateElements_{{ $uid }}('annotation');
    });
    //updateElements_{{ $uid }}();
</script>
