<div class="row bdr-rad">
    <div class="col-sm-3">
        {{ $strategy->getShortClass() }} Strategy Settings
    </div>
    <div class="col-sm-3">
        <div class="editable">
            <label for="config_file">Fann filename</label>
            <input class="btn-primary"
                    type="text"
                    size="15"
                    id="config_file"
                    name="config_file"
                    title="Filename"
                    value="{{ $strategy->getParam('config_file') }}">
        </div>
    </div>
    <div class="col-sm-3">
        <div class="editable">
            <label for="num_samples">Sample size</label>
            <input class="btn-primary"
                    type="text"
                    size="15"
                    id="num_samples"
                    name="num_samples"
                    title="Sample size"
                    value="{{ $strategy->getParam('num_samples') }}">
        </div>
    </div>
    <div class="col-sm-3">
        <div class="editable">
            Setting 2
        </div>
    </div>
</div>
<div class="row bdr-rad">
    <div class="col-sm-12">
        <h4>Training</h4>
        {!! $training_chart !!}
        <div id="slider" class="center-block" style="width: 90%; height: 10px"></div>
        <script>
            var slider = document.getElementById('slider');
            noUiSlider.create(slider, {
                start: [50, 100],
                connect: true,
                range: {
                    'min': 0,
                    'max': 100
                }
            });
        </script>
    </div>
</div>

