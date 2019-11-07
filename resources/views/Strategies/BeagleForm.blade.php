
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
