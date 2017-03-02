<div class="container-fluid">
    <div class="row">
        @foreach ($bots as $bot)
            @php
                $id = $bot->id;
            @endphp
            <div class="col-sm-6 editable">
                <div class="row">
                    <div class="col-sm-8">
                        <strong>{{ $bot->name }}</strong>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group editbuttons">
                            <button type="button"
                                    class="btn btn-primary btn-sm editbutton trans"
                                    title="Edit Bot"
                                    onClick="window.GTrader.request('bot', 'form', 'id={{ $id }}')">
                                <span class="glyphicon glyphicon-wrench"></span>
                            </button>
                            <button type="button"
                                    class="btn btn-primary btn-sm editbutton trans"
                                    title="Delete Bot"
                                    onClick="window.GTrader.request('bot', 'delete', 'id={{ $id }}')">
                                <span class="glyphicon glyphicon-trash"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="row" id="new_bot">
        <div class="col-sm-12 editable text-right">
            <button type="button"
                    class="btn btn-primary btn-sm trans"
                    title="Create new bot"
                    onClick="window.GTrader.request('bot', 'new')">
                <span class="glyphicon glyphicon-ok"></span> Create a Bot
            </button>
        </div>
    </div>

</div>
