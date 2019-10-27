@if ($exchange->getSandboxApiUrl())
    <div class="row bdr-rad">
        <div class="col-sm-6 editable form-group">
            <label for="options[use_sandbox]">Use Sandbox or Live API</label>
            <select class="btn-primary form-control form-control-sm"
                    id="use_sandbox"
                    name="options[use_sandbox]"
                    title="Use Sandbox">
                @foreach ([0 => 'Use Live API', 1 => 'Use Sandbox API'] as $val => $label)
                    <option value="{{ $val }}"
                    @if ($val == ($options['use_sandbox'] ?? false))
                        selected
                    @endif
                    >{{ $label }}</option>
                @endforeach
            </select>
            <script>
                $('#use_sandbox').select2();
            </script>
        </div>
        <div class="col-sm-6 editable form-group">
            <label for="options[apiKeyPassword]">API Key Passphrase</label>
            <input class="btn-primary form-control form-control-sm"
                    type="text"
                    id="apiKeyPassword"
                    name="options[apiKeyPassword]"
                    title="API Key Password"
                    value="{{ $options['apiKeyPassword'] ?? ''}}">
        </div>
    </div>
@endif
