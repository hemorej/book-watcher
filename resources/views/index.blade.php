<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://unpkg.com/tachyons@4.12.0/css/tachyons.min.css"/>
        <title>{{ config('app.name', 'Laravel') }}</title>
    </head>

<body class="bg-washed-green">
    <header class="mt5 mw8 center ph3-ns">
        <nav class="cf ph2-ns">
            <div class="fl w-100 w-90-ns pa2">
                <a class="f3 f3-m f3-ns gold di-ns db pv2-l ph2 mr3" href="{{ url('/') }}" title="Home">{{ config('app.name', 'Laravel') }}</a>
            </div>

            <div class="fl w-100 w-10-ns pa2">
            <!-- Authentication Links -->
                @if (Auth::guest())
                    <a href="{{ url('/login') }}">Login</a>
                    <a href="{{ url('/register') }}">Register</a>
                @else
                    <a class="f5 f5-m f5-ns black di-ns db pv2-l ph2 mr3 i" href="{{ url('/logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">logout</a>
                    <form id="logout-form" action="{{ url('/logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                @endif
            </div>
        </nav>
    </header>

    @if(\Session::has('error'))
        <div class="mw8 center ph3-ns">
            <nav class="cf ph2-ns">
                <div class="fl w-100 w-90-ns pa2">
                    <div class="f4 f4-m f4-ns black-70 bg-light-red di-ns db pv2-l ph2 mr3">
                        {!! \Session::get('error') !!}
                    </div>
                </div>
            </nav>
        </div>
    @endif

    <div class="mt3 mw8 center ph3-ns">
        <div class="fl w-100 ph2"><div class="f3 f3-m f3-ns black-70 db mr3 ph3">Watch list</div></div>
        <div class="fl w-25 ph2"><div class="f4 f4-m f4-ns black-70 db mr3 pv2 ph3">Author</div></div>
        <div class="fl w-50 ph2"><div class="f4 f4-m f4-ns black-70 db mr3 pv2 ph3">Title</div></div>
        <div class="fl w-25 ph2"><div class="f4 f4-m f4-ns black-70 db mr3 pv2 ph3">Availability</div></div>
    </div>

    @unless(empty($books))
        <div class="cf ph2-ns"></div>
        <div class="mw8 center ph3-ns mt3">
    		@foreach ($books as $book)
                <div class="fl w-25 ph3"><a class="link black-80 hover-white hover-bg-green pa1 ph2" target="_blank" href="{{ $book->url }}">{{$book->author}}</a></div>
                <div class="fl w-50 ph3"><a class="link black-80 hover-white hover-bg-green pa1 ph2" target="_blank" href="{{ $book->url }}">{{$book->title}}</a></div>
                @if($book->available)
                    <div class="fl w-15 ph2 green"><cite class="pa3">Available :D</cite></div>
                @else
                    <div class="fl w-15 ph2 gold"><cite class="pa3">Not ready :(</cite></div>
                @endif

                <div class="fl w-10 ph2">
                    <form style="display:inline-block;" action="/delete" method="post">
                        @csrf
                        <input type="hidden" name="tid" value="{{$book->id}}">
                        <input type="submit" name="op" id="edit-submit" value="del" class="f6 link dim br2 pa1 dib white bg-gold i"/>
                    </form>
                </div>
    		@endforeach
    	</div>
	@endunless

    <div class="cf ph2-ns"></div>
	<div class="mw8 center ph4 mt4">
		<form action="/check" accept-charset="UTF-8" method="post" onSubmit="document.getElementById('check-now').disabled=true;">
			@csrf
			<input type="submit" name="op" id="check-now" value="check now" class="f4 link dim br2 ph3 pv2 mb2 dib white bg-dark-green"/>
		</form>
	</div>

    <div class="cf ph2-ns"></div>
    <div class="mw8 center ph4 mt4">
    	<h2>Add books <span class="f5 black-90 i">author:title:url</span></h2>
    </div>

    <div class="mw8 center ph4 mt4">
		<form action="/urls/create" accept-charset="UTF-8" method="post">
    		@csrf
	       	<textarea cols="80" rows="10" name="urls" placeholder="author:title:url"></textarea> 
    		<input type="submit" name="op" id="edit-submit" value="save" class="f4 link br2 ph3 pv2 mb2 db white bg-dark-green " />
    	</form>
    </div>

</body>
</html>
