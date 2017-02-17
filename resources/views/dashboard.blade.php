@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="row">

        <!-- Tab panes -->
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane fade in active" id="chartTab">
                {!! $chart !!}
            </div>
            <div role="tabpanel" class="tab-pane fade" id="strategyTab">
                {!! $strategy !!}
            </div>
            <div role="tabpanel" class="tab-pane fade" id="settingsTab">
                Debug: <pre>{{ $debug }}</pre>
            </div>
            <div role="tabpanel" class="tab-pane fade" id="botTab">
                Bot Settings
            </div>
        </div>

    </div>
</div>

<!-- Chart Settings Modal -->
<div class="modal fade bs-modal-lg">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                Chart Settings
                <button type="button" class="btn btn-primary close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="settings_content">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>



@endsection

@section('stylesheets')
{!! $stylesheets !!}
@endsection

@section('scripts_top')
{!! $scripts_top !!}
@endsection

@section('scripts_bottom')
{!! $scripts_bottom !!}
@endsection
