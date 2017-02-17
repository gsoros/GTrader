@php
    $available = $strategy->getParam('available');
    error_log('Strat avail: '.serialize($strategy->getParams()));
    $active = $strategy->getShortClass();
@endphp
<div class="container-fluid">
    <div class="row lm10">
        <div class="col-xs-8">
            <h4>Active strategy: {{ $active }}</h4>
        </div>
        <div class="col-xs-4 editable">
            <label for="strategy_select">Change strategy:</label>
            <select class="btn-primary btn btn-mini"
                    id="strategy_select"
                    title="Select the index for the indicator">
                @foreach ($available as $name)
                    <option
                    @if ($name === $active)
                        selected
                    @endif
                    value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </select>

            <button id="save_strategy"
                    class="btn btn-primary btn-sm trans"
                    title="Save changes"
                    onClick="return window.change_strategy()">
                <span class="glyphicon glyphicon-ok"></span> Update
            </button>
        </div>
    </div>
    <script>
        window.change_strategy = function(){
            var strategy = $('#strategy_select').val();
            console.log('new strat: ' + strategy);
            return false;
        };
    </script>
    <div class="row lm10">
        <div class="col-xs-12">
            {{ $content }}
        </div>
    </div>
</div>
