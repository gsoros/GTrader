@php
    $train = $training_status ?
        (('paused' === $training_status) ?
            'Evolution Paused' :
            'Now Evolving'
        ) :
        'Evolve';
@endphp
<button onClick="
        window.GTrader.request(
            'strategy',
            'train',
            {
                id: {{ $strategy->getParam('id') }}
            }
        )"
        type="button"
        class="btn btn-primary btn-mini trans"
        title="{{ $train }} ID: {{ $training_id }}">
    <span class="fas fa-fire"></span> {{ $train }}
</button>
<span title="ID: {{ $strategy->getParam('id') }}">
    <strong>{{ $strategy->getParam('name') }}</strong>
</span>
