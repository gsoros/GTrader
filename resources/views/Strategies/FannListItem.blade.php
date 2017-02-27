@php
    $train = $training_count ? 'Now Training' : 'Train';
@endphp
<strong>{{ $strategy->getParam('name') }}</strong>

<button onClick="window.strategyRequest('train', {id: {{ $strategy->getParam('id') }}})"
        type="button"
        class="btn btn-primary btn-sm trans"
        title="{{ $train }}">
    <span class="glyphicon glyphicon-fire"></span> {{ $train }}
</button>
