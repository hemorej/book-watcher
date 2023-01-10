<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://unpkg.com/tachyons@4.12.0/css/tachyons.min.css"/>
    <title>{{ config('app.name', 'Laravel') }}</title>
</head>

<body class="bg-washed-green">
    <nav class="flex justify-between bb b--black-10">
        <a class="link black-70 hover-black no-underline flex items-center pa3" href="{{ url('/') }}">
            {{ config('app.name', 'Laravel') }}
        </a>

        <div class="flex-grow pa3 flex items-center">
            @guest
                <a class="f6 link dib black dim mr3 mr4-ns" href="{{ route('login') }}">{{ __('Login') }}</a>
                @if (Route::has('register'))
                    <a class="f6 dib black bg-animate hover-bg-black hover-black no-underline pv2 ph4 br-pill ba b--black-20" href="{{ route('register') }}">{{ __('Register') }}</a>
                @endif
            @endguest
        </div>
    </nav>

    <main class="py-4">
        @yield('content')
    </main>
</body>
</html>
