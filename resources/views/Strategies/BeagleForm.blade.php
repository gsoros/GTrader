<div class="col-sm-3 editable"
    title="Number of strategies in each generation">
    <label for="population">Population Size</label>
    <input class="btn-primary btn btn-mini form-control form-control-sm"
        id="population"
        name="population"
        type="number"
        min="2"
        max="1000"
        step="1"
        value="{{ $preferences['population'] ?? 100 }}">
</div>
<div class="col-sm-3 editable"
    title="Limit the length of chains of indicators">
    <label for="max_nesting">Nesting</label>
    <input class="btn-primary btn btn-mini form-control form-control-sm"
        id="max_nesting"
        name="max_nesting"
        type="number"
        min="1"
        max="100"
        step="1"
        value="{{ $preferences['max_nesting'] ?? 3 }}">
    </select>
</div>
<div class="col-sm-3 editable"
    title="A higher mutation rate results in more dramatic changes">
    <label for="mutation_rate">Mutation Rate</label>
    <input class="btn-primary btn btn-mini form-control form-control-sm"
        id="mutation_rate"
        name="mutation_rate"
        type="number"
        min="1"
        max="100"
        step="1"
        value="{{ $preferences['mutation_rate'] ?? 10 }}">
    </select>
</div>
<div class="col-sm-3 editable"
    title="Skip strategies that have a maximum loss percentage higher than this value">
    <label for="loss_tolerance">Tolerance for loss</label>
    <input class="btn-primary btn btn-mini form-control form-control-sm"
        title="%, 0 or 100 to disable"
        id="loss_tolerance"
        name="loss_tolerance"
        type="number"
        min="0"
        max="100"
        step="1"
        value="{{ $preferences['loss_tolerance'] ?? 50 }}">
    </select>
</div>
<div class="col-sm-3 editable"
    title="Limit memory usage">
    <label for="memory_limit">Memory Limit</label>
    <input class="btn-primary btn btn-mini form-control form-control-sm"
        title="MB"
        id="memory_limit"
        name="memory_limit"
        type="number"
        min="64"
        max="65536"
        step="8"
        value="{{ $preferences['memory_limit'] ?? 512 }}">
    </select>
</div>
