<select title="Exchange Selector"
        class="btn-primary btn btn-mini"
        id="exchange_{{ $name }}"
        name="exchange_{{ $name }}"></select>
<select title="Symbol Selector"
        class="btn-primary btn btn-mini"
        id="symbol_{{ $name }}"
        name="symbol_{{ $name }}"></select>
<select title="Resolution Selector"
        class="btn-primary btn btn-mini"
        id="resolution_{{ $name }}"
        name="resolution_{{ $name }}"></select>
<script>
    $(function() {
        window.GTrader.registerESR(
            '{{ $name }}',
            {!! json_encode($options) !!}
        );
        $('#exchange_{{ $name }}').trigger('change');
    });
</script>
