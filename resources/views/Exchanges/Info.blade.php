<div class="container">
    <div class="row">
        <div class="col-sm-12 editable">
            <label>Symbols</label>
            <p style="max-height: 100px" class="col-sm-12 overflow-auto">
                {{ join(', ', $exchange->getSymbols()) }}
            </p>
        </div>
    </div>
</div>
