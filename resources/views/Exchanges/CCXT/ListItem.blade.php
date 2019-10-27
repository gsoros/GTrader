<div class="col-sm-3 editable container">
    <div class="row">
        <div class="col-sm-8" title="CCXT ID: {{ $exchange->getParam('ccxt_id') ?? '' }}">
            @php
                $name = $exchange->getLongName();
                $logo = is_array($urls = $exchange->getCCXTProperty('urls'))
                    && isset($urls['logo']) ?
                    $urls['logo'] :
                    null;
            @endphp
            @if ($logo)
                <img style="border-radius: 3px" src="{{ $logo }}" title="{{ $name }}" alt="{{ $name }}">
            @endif
            <strong>{{ $name }}</strong>
        </div>
        <div class="col-sm-4">
            <div class="form-group editbuttons">
                <button type="button"
                        class="btn btn-primary btn-mini editbutton trans"
                        title="Preferences"
                        onClick="window.GTrader.request(
                            'exchange',
                            'form',
                            'id={{ $exchange->getId() }}',
                            'GET',
                            'settingsTab'
                        )">
                    <span class="fas fa-wrench"></span>
                </button>
                <button type="button"
                        class="btn btn-primary btn-mini editbutton trans"
                        title="Delete"
                        onClick="window.GTrader.request(
                            'exchange',
                            'delete',
                            'id={{ $exchange->getId() }}',
                            'GET',
                            'settingsTab'
                        )">
                    <span class="fas fa-trash"></span>
                </button>
            </div>
        </div>
    </div>
</div>
