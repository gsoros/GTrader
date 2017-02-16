@php
    $sig = $indicator->getSignature();
    $length = $indicator->getParam('indicator.length');
    $price = $indicator->getParam('indicator.price');
@endphp

<h5>Parameters for Ema</h5>

<label for="length">Length:</label>
<select class="btn-primary btn btn-mini"
        id="length_{{ $sig }}"
        title="Select length">
    @for ($i=2; $i<100; $i++)
        <option
        @if ($i == $length)
            selected
        @endif
        value="{{ $i }}">{{ $i }}</option>
    @endfor
</select>
<label for="price">Price:</label>
<select class="btn-primary btn btn-mini"
        id="price_{{ $sig }}"
        title="Select the index for the indicator">
    @foreach ($chart->getPricesAvailable($sig) as $signature => $name)
        <option
        @if ($signature === $price)
            selected
        @endif
        value="{{ $signature }}">{{ $name }}</option>
    @endforeach
</select>
<button id="save_{{ $sig }}"
        class="btn btn-primary btn-sm trans"
        title="Save changes"
        onClick="return window.save{{ $sig }}()">
    <span class="glyphicon glyphicon-ok"></span>
</button>


<script>
    window.save{{ $sig }} = function(){
        var params = {
                length: $('#length_{{ $sig }}').val(),
                price: $('#price_{{ $sig }}').val()};
        window.{{ $id }}.requestIndicatorSaveForm('{{ $sig }}', params);
        return false;
    };
</script>
