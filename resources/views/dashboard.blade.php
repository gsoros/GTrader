@extends('layouts.app')

@section('content')
<div class="container-fluid">
                
    <div class="row">

        <!-- Tab panes -->
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane fade in active" id="chart">
                <div id="chartContainer"></div>
            </div>
            <div role="tabpanel" class="tab-pane fade" id="strategy">
                
                    <!-- Small modal -->
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target=".bs-example-modal-sm">Small modal</button>
                
                <div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                  <div class="modal-dialog modal-sm" role="document">
                    <!-- Modal Header -->
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">
                               <span aria-hidden="true">&times;</span>
                               <span class="sr-only">Close</span>
                        </button>
                        <h4 class="modal-title" id="myModalLabel">
                            Title
                        </h4>
                    </div>
                    <div class="modal-content">
                      Modal Content
                    </div>
                  </div>
                </div>
                
            </div>
            <div role="tabpanel" class="tab-pane fade" id="settings">Debug: <pre>{{ $debug }}</pre></div>
            <div role="tabpanel" class="tab-pane fade" id="bot">Bot</div>
        </div>
            
    </div>
</div>
@endsection
@section('pagescripts')
<script src="{{ mix('/js/chart.js') }}"></script>
@endsection
