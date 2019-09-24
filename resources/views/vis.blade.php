<div id="vis" class="npr npl"></div>
<button onClick="refreshVis();"
        type="button"
        class="btn btn-primary btn-sm trans"
        title="Refresh">
    <span class="glyphicon glyphicon-ok"></span> Refresh
</button>

<script>

options = {
    nodes: {
        font: {color: 'white'},
    },
    edges: {
        arrows: 'from',
        'smooth': {
            //'type': 'dynamic',
            'type': 'cubicBezier',
            'roundness': 0.5,
        }
    },
    groups: {
        root_input: {
            shape: 'box',
            color: 'red',

        },
        strategies: {
            shape: 'ellipse',
            color: '#5c5002',
        },
        indicators: {
            shape: 'circle',
            color: '#2e063e',

        },
        candles: {
            shape: 'box',
            color: '#1f4d00',
        },
    },
    physics: {
        barnesHut: {
            gravitationalConstant: -50000,
            springLength: 100,
            centralGravity: 10,
            stabilization: false,
        }
    },
    layout: {
        randomSeed: 3
    },
    //manipulation: true,
    //configure: true,
};
container = $('#vis')[0];

var g = window.GTrader;
g.visNetwork = new vis.Network(container, {}, options);

var refreshVis = function () {
    window.GTrader.setLoading('devArea', true);
    console.log('refreshVis');
    $.ajax({
        url: 'dev.json?file=debug.json',
        dataType: 'json',
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            g.setLoading('devArea', false);
            g.visNetwork.setData(response);
        },
        error: function(response) {
            g.setLoading('devArea', false);
            if (0 == response.status && 'abort' === response.statusText) {
                return;
            }
        }
    });
}

$(function() {
    refreshVis();
});


</script>
