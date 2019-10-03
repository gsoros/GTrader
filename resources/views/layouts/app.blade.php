<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'GTrader') }}</title>

    <!-- Styles -->
    @yield('stylesheets')
    <link href="/css/app.css" rel="stylesheet">

    <!-- Scripts -->
    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
        ]) !!};
    </script>
    <script src="{{ mix('/js/app.js') }}"></script>
    @yield('scripts_top')
</head>
<body>
    <div id="app">

        <nav class="navbar navbar-expand-sm navbar-dark bg-dark">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarToggler" aria-controls="navbarToggler" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarToggler">

                <ul class="nav mr-auto mt-2 mt-lg-0 nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link" href="#chartTab"
                            aria-controls="chartTab"
                            role="tab"
                            data-toggle="tab">Chart</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#strategyTab"
                            aria-controls="strategyTab"
                            role="tab"
                            data-toggle="tab">Strategies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#settingsTab"
                            aria-controls="settingsTab"
                            role="tab"
                            data-toggle="tab">Settings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#botTab"
                            aria-controls="botTab"
                            role="tab"
                            data-toggle="tab">Bots</a>
                    </li>
                    @env('local')
                    <li class="nav-item">
                        <a class="nav-link" href="#devTab"
                            aria-controls="devTab"
                            role="tab"
                            data-toggle="tab">Development Tools</a>
                    </li>
                    @endenv
                </ul>



                <ul class="nav nav-pills">
                    <!-- Authentication Links -->
                    @if (Auth::guest())
                        <!--
                        <li><a href="{{ url('/login') }}">Login</a></li>
                        <li><a href="{{ url('/register') }}">Register</a></li>
                        -->
                    @else
                        <li class="nav-item dropdown">
                            <a href="#"
                                class="nav-link dropdown-toggle"
                                data-toggle="dropdown"
                                role="button"
                                aria-haspopup="true"
                                aria-expanded="false">
                                {{ Auth::user()->name }} <span class="caret"></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                  <a class="dropdown-item" href="#"
                                      onClick="return window.GTrader.request(
                                          'password', 'change', null, 'GET', 'settings_content'
                                      )"
                                      data-toggle="modal"
                                      data-target=".bs-modal-lg">
                                      Change Password
                                  </a>
                                  <a class="dropdown-item"href="{{ url('/logout') }}"
                                      onclick="event.preventDefault();
                                               document.getElementById('logout-form').submit();">
                                      Logout
                                  </a>
                                  <form id="logout-form"
                                          action="{{ url('/logout') }}"
                                          method="POST"
                                          style="display: none;">
                                      {{ csrf_field() }}
                                  </form>
                            </div>
                        </li>
                    @endif
                </ul>

            </div>
        </nav>





        @yield('content')
    </div>

    <!-- Scripts -->
    @yield('scripts_bottom')
</body>
</html>
