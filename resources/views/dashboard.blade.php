@extends('layouts.app')

@section('content')




        <!-- Tab panes -->
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane container-fluid np fade show active" id="chartTab">
                {!! $chart !!}
            </div>
            <div role="tabpanel" class="tab-pane container-fluid np" id="strategyTab">
                {!! $strategies !!}
            </div>
            <div role="tabpanel" class="tab-pane container-fluid np" id="settingsTab">
                {!! $exchanges !!}
            </div>
            <div role="tabpanel" class="tab-pane container-fluid np" id="botTab">
                {!! $bots !!}
            </div>
            @env('local')
            <div role="tabpanel" class="tab-pane container-fluid np" id="devTab">
                {!! $dev !!}
            </div>
            @endenv
        </div>




<!-- Chart Settings Modal -->
<div class="modal fade bs-modal-lg">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-body container" id="settings_content">

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
