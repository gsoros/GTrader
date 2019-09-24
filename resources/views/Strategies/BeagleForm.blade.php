<div class="row bdr-rad">
    <div class="col-sm-6 editable"
        title="Number of strategies in each generation">
        <label for="population">Population Size</label>
        <input class="btn-primary btn btn-mini form-control form-control-sm"
                id="population"
                name="population"
                type="number"
                min="3"
                max="1000"
                step="1"
                value="{{ $preferences['population'] ?? 100 }}">
    </div>
    <div class="col-sm-6 editable"
        title="Number of strategies that survive into the new generation">
        <label for="survivors">Survivors</label>
        <input class="btn-primary btn btn-mini form-control form-control-sm"
                id="survivors"
                name="survivors"
                type="number"
                min="1"
                max="500"
                step="1"
                value="{{ $preferences['survivors'] ?? 4 }}">
        </select>
    </div>
    <div class="col-sm-6 editable"
        title="A higher mutation rate results in more dramatic changes">
        <label for="mutation_rate">Mutation Rate</label>
        <input class="btn-primary btn btn-mini form-control form-control-sm"
                id="mutation_rate"
                name="mutation_rate"
                type="number"
                min=".01"
                max="100"
                step=".01"
                value="{{ $preferences['mutation_rate'] ?? '1.00' }}">
        </select>
    </div>
</div>
