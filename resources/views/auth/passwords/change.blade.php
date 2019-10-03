<div class="panel-body">
    <form role="form" class="form-horizontal container">
        {{csrf_field()}}

        <div class="editable form-group{{ $errors->has('password') ? ' has-error' : '' }} row">
            <label for="password" class="col-md-4 control-label">
                Current Password
            </label>
            <div class="col-md-6">
                <input id="password"
                    type="password"
                    class="btn-primary form-control"
                    name="password"
                    value="{{ $password ?? '' }}">
                @if ($errors->has('password'))
                    <span class="help-block">
                        <strong>{{ $errors->first('password') }}</strong>
                    </span>
                @endif
            </div>
        </div>

        <div class="editable form-group{{ $errors->has('password') ? ' has-error' : '' }} row">
            <label for="new_password" class="col-md-4 control-label">
                New Password
            </label>
            <div class="col-md-6">
                <input id="new_password"
                    type="password"
                    class="btn-primary form-control"
                    name="new_password"
                    value="{{ $new_password ?? '' }}">
                @if ($errors->has('new_password'))
                    <span class="help-block">
                    <strong>{{ $errors->first('new_password') }}</strong>
                </span>
                @endif
            </div>
        </div>

        <div class="editable form-group{{ $errors->has('password_confirmation') ? ' has-error' : '' }} row">
            <label for="new_password_confirmation" class="col-md-4 control-label">
                Confirm New Password
            </label>
            <div class="col-md-6">
                <input id="new_password_confirmation"
                    type="password"
                    class="btn-primary form-control"
                    name="new_password_confirmation"
                    value="{{ $new_password_confirmation ?? '' }}">
                @if ($errors->has('new_password_confirmation'))
                    <span class="help-block">
                    <strong>{{ $errors->first('new_password_confirmation') }}</strong>
                </span>
                @endif
            </div>
        </div>

        <div class="editable form-group row">
            <div class="col-md-6 col-md-offset-4">
                <button
                    class="btn btn-primary form-control"
                    onClick="window.GTrader.request(
                        'password',
                        'change',
                        {
                            password: $('#password').val(),
                            new_password: $('#new_password').val(),
                            new_password_confirmation: $('#new_password_confirmation').val()
                        },
                        'POST',
                        'settings_content');
                        return false;"
                    >Submit</button>
            </div>
        </div>
    </form>
</div>
