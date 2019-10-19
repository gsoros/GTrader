<select class="btn-primary btn btn-mini"
        id="visFile"
        title="Select dump file to load">
        <option disabled selected>No dump files found</option>
</select>
<button onClick="g.vis.loadFile();"
        type="button"
        class="btn btn-primary btn-mini trans"
        title="Reload">
    <span class="fas fa-check"></span> Reload
</button>
<div id="vis" class="npr npl"></div>
<script>

var g = window.GTrader;

g.vis = {
    getFileList: function () {
        g.setLoading('visFile', true);
        //console.log('vis.getFileList()');
        $.ajax({
            url: 'dev.dumps?path=/',
            dataType: 'json',
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                g.setLoading('visFile', false);
                if (!response.length) {
                    return;
                }
                var items = '';
                $.each(response, function(key, value) {
                    //console.log(value);
                    items += '<option>' + value + '</option>';
                });
                //console.log($('#visFile'));
                $('#visFile').empty();
                $('#visFile').append(items);
                $('#visFile').trigger('change');
            },
            error: function(response) {
                g.setLoading('visFile', false);
                if (0 == response.status && 'abort' === response.statusText) {
                    return;
                }
            }
        });
    },
    loadFile: function () {
        //console.log('vis.loadFile()');
        g.setLoading('vis', true);
        $.ajax({
            url: 'dev.json?file=' + $('#visFile').find(':selected').text(),
            dataType: 'json',
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                g.setLoading('vis', false);
                g.vis.network.setData(response);
            },
            error: function(response) {
                g.setLoading('vis', false);
                if (0 == response.status && 'abort' === response.statusText) {
                    return;
                }
            }
        });
    },
    network: new vis.Network(
        $('#vis')[0],           // element
        {},                     // data
        {                       // options
            nodes: {
                font: {color: 'white'},
            },
            edges: {
                arrows: 'from',
                smooth: {
                    //'type': 'dynamic',
                    'type': 'cubicBezier',
                    'roundness': 0.5,
                },
                selectionWidth: 3,
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
                }
            },
            physics: {
                stabilization: false,
                barnesHut: {
                    gravitationalConstant: -20000,
                    springLength: 100,
                    centralGravity: 1,
                }
            },
            layout: {
                randomSeed: 3
            }
            //manipulation: true,
            //configure: true,
        }
    )
}


$(function() {
    $('#visFile').on('change', function (e) {
        g.vis.loadFile();
    });
    g.vis.getFileList();
});


</script>
