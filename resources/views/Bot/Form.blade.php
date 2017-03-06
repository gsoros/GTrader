<script>
    window.bot_{{ $bot->id }} = {!! $bot->toJSON() !!};
</script>
<form id="botForm">
    <input type="hidden" name="id" value="{{ $bot->id }}">
    <div class="row bdr-rad">
        <div class="col-sm-12">
            Bot Settings
        </div>
        <div class="col-sm-6 editable form-group">
            <label for="name">Name</label>
            <input class="btn-primary form-control form-control-sm"
                    type="text"
                    id="name"
                    name="name"
                    title="Bot Name"
                    value="{{ $bot->name }}">
        </div>
        <div class="col-sm-6 editable form-group">
            <label>Exchange, Symbol, Resolution</label><br>
            {!! GTrader\Exchange::getESRSelector('bot_'.$bot->id) !!}
        </div>
        <div class="col-sm-6 editable form-group">
            <label>Strategy</label>
            <select title="Strategy Selector"
                    class="btn-primary btn btn-mini"
                    id="strategy_select_bot_{{ $bot->id }}"
                    name="strategy_select_bot_{{ $bot->id }}"></select>
        </div>
        @php
            $unfilled_max = isset($bot->options['unfilled_max']) ?
                                $bot->options['unfilled_max'] :
                                0;
        @endphp
        <div class="col-sm-6 editable form-group">
            <label for="unfilled_max">Cancel Unfilled Orders After This Number of Candles
                                <small>(0 to never remove unfilled orders)</small>
            </label>
            <input class="btn-primary form-control form-control-sm"
                    type="number"
                    id="unfilled_max"
                    name="unfilled_max"
                    title="Cancel Unfilled Orders After This Number of Candles"
                    value="{{ $unfilled_max }}">
        </div>
    </div>
    <div class="row bdr-rad">
        <div class="col-sm-12">
            <span class="pull-right">
                <button onClick="window.GTrader.request('bot', 'list')"
                        type="button"
                        class="btn btn-primary btn-sm trans"
                        title="Discard Changes">
                    <span class="glyphicon glyphicon-remove"></span> Discard Changes
                </button>
                <button onClick="window.GTrader.request('bot', 'save', $('#botForm').serialize(), 'POST')"
                        type="button"
                        class="btn btn-primary btn-sm trans"
                        title="Save Bot">
                    <span class="glyphicon glyphicon-ok"></span> Save Bot
                </button>
            </span>
        </div>
    </div>
</form>
<script>
    if (window.GTrader)
        window.GTrader.registerStrategySelector('bot_{{ $bot->id }}', false, {{ intval($bot->strategy_id) }});
    else {
        $(function() {
            window.GTrader.registerStrategySelector('bot_{{ $bot->id }}', false, {{ intval($bot->strategy_id) }});
        });
    }
</script>
