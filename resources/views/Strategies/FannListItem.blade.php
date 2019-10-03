@php
    $train = $training_status ? ('paused' === $training_status) ? 'Training Paused' : 'Now Training' : 'Train';
@endphp
<button onClick="window.GTrader.request('strategy', 'train', {id: {{ $strategy->getParam('id') }}})"
        type="button"
        class="btn btn-primary btn-mini trans"
        title="{{ $train }}">
    <span class="fas fa-fire"></span> {{ $train }}
</button>
<span title="ID: {{ $strategy->getParam('id') }}">
    <strong>{{ $strategy->getParam('name') }}</strong>
    <small>inputs: {{ $strategy->getNumInput() }}</small>
</span>
