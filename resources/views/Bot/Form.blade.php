<script>
    window.bot_{{ $bot->id }} = {!! $bot->toJSON() !!};
</script>
<form id="botForm">
    <input type="hidden" name="id" value="{{ $bot->id }}">
    <div class="row bdr-rad">
        <div class="col-sm-1">
            Bot Settings
        </div>
        <div class="col-sm-3">
            <div class="editable form-group">
                <label for="name">Name</label>
                <input class="btn-primary form-control form-control-sm"
                        type="text"
                        id="name"
                        name="name"
                        title="Bot Name"
                        value="{{ $bot->name }}">
            </div>
        </div>
        <div class="col-sm-5">
            <div class="editable form-group">
                <label>Exchange, Symbol, Resolution</label>
                {!! GTrader\Exchange::getESRSelector('bot_'.$bot->id) !!}
            </div>
        </div>
          <div class="col-sm-3">
            <div class="editable form-group">
                <label>Strategy</label>
                <select title="Strategy Selector"
                        class="btn-primary btn btn-mini"
                        id="strategy_select_bot_{{ $bot->id }}"
                        name="strategy_select_bot_{{ $bot->id }}"></select>
            </div>
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
