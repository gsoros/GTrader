<strong>{{ $strategy->getParam('name') }}</strong>

<button onClick="window.strategyRequest('train', {id: {{ $strategy->getParam('id') }}})"
        type="button"
        class="btn btn-primary btn-sm trans"
        title="Training">
    <span class="glyphicon glyphicon-fire"></span> Train
</button>
