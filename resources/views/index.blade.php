<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/5.5.3/css/foundation.css">
        <link href="/css/app.css" rel="stylesheet">
        <style>
        	.red{
        		color: red;
        		font-style: italic;
        	}        	
        	.green{
        		color: green;
        		font-style: italic;
        	}
        </style>
    </head>
<body>
	<div id="app">
        <nav class="navbar navbar-default navbar-static-top">
            <div class="container">
                <div class="navbar-header">

                    <!-- Collapsed Hamburger -->
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse">
                        <span class="sr-only">Toggle Navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>

                    <!-- Branding Image -->
                    <a class="navbar-brand" href="{{ url('/') }}">
                        {{ config('app.name', 'Laravel') }}
                    </a>
                </div>

                <div class="collapse navbar-collapse" id="app-navbar-collapse">
                    <!-- Left Side Of Navbar -->
                    <ul class="nav navbar-nav">
                        &nbsp;
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="nav navbar-nav navbar-right">
                        <!-- Authentication Links -->
                        @if (Auth::guest())
                            <li><a href="{{ url('/login') }}">Login</a></li>
                            <li><a href="{{ url('/register') }}">Register</a></li>
                        @else
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                    {{ Auth::user()->name }} <span class="caret"></span>
                                </a>

                                <ul class="dropdown-menu" role="menu">
                                    <li>
                                        <a href="{{ url('/logout') }}"
                                            onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                            Logout
                                        </a>

                                        <form id="logout-form" action="{{ url('/logout') }}" method="POST" style="display: none;">
                                            {{ csrf_field() }}
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>
    </div>

	<div class="row">
	<h2>Watch list</h2>
	</div>

	<div class="row">
		<div class="medium-4 columns"><h3>Author</h3></div>
		<div class="medium-4 columns"><h3>Title</h3></div>
		<div class="medium-4 columns"><h3>Availability</h3></div>
	</div>

	@unless(empty($books))
		@foreach ($books as $book)
		  <div class="row">
			  <div class="medium-4 columns"><a href="{{ $book->url }}">{{$book->author}}</a></div>
			  <div class="medium-4 columns"><a href="{{ $book->url }}">{{$book->title}}</a></div>
			  	@if($book->available)
				  <div class="medium-3 columns green text-center">Available :D</div>
			  	@else
		  		  <div class="medium-3 columns red text-center">Not available :(</div>
			  	@endif
			  <div class="medium-1 columns">
				  <form style="display:inline-block;" action="/delete" method="post">
			          {{ csrf_field() }}
			          <input type="hidden" name="tid" value="{{$book->id}}">
			          <input type="submit" name="op" id="edit-submit" value="del" class="form-submit"/>
				  </form>
		      </div>
		  </div>
		@endforeach
	@endunless

	<br />
	<div class="row">
		<form action="/check" accept-charset="UTF-8" method="post" onSubmit="document.getElementById('check-now').disabled=true;">
			{{ csrf_field() }}
			<input type="submit" name="op" id="check-now" value="Check now" class="button success form-submit"/>
		</form>
	</div>

	<div class="row">
		<h2>Add books</h2>
		<form action="/urls/create" accept-charset="UTF-8" method="post">
		{{ csrf_field() }}
		<textarea cols="50" rows="10" name="urls" placeholder="author:title:url"></textarea> 
		<input type="submit" name="op" id="edit-submit" value="Submit" class="button success form-submit" />
		</form>
	</div>
</body>
<script src="/js/app.js"></script>
</html>
