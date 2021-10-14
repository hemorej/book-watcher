<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/5.5.3/css/foundation.css">
        <link href="/css/app.css" rel="stylesheet">
	<title>{{ config('app.name', 'Laravel') }}</title>
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
                    <a class="navbar-brand" href="{{ url('/') }}">
                        {{ config('app.name', 'Laravel') }}
                    </a>
                </div>

                <div>
                    <ul class="nav navbar-nav navbar-right">
                        <!-- Authentication Links -->
                        @if (Auth::guest())
                            <li><a href="{{ url('/login') }}">Login</a></li>
                            <li><a href="{{ url('/register') }}">Register</a></li>
                        @else
                            <li>
                                <a href="{{ url('/logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                                    <form id="logout-form" action="{{ url('/logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>
    </div>
    
    @if(\Session::has('error'))
        <div class="container alert alert-danger" role="alert">
            <h5>{!! \Session::get('error') !!}</h5>
        </div>
    @endif

	<div class="container">
	   <h2 class="mb-5">Watch list</h2>

    	<div class="row">
    		<div class="col-md-3"><h3>Author</h3></div>
    		<div class="col-md-3"><h3>Title</h3></div>
    		<div class="col-md-3"><h3>Availability</h3></div>
    	</div>

    	@unless(empty($books))
    		@foreach ($books as $book)
    		  <div class="row">
    			  <div class="col-md"><a href="{{ $book->url }}">{{$book->author}}</a></div>
    			  <div class="col-md"><a href="{{ $book->url }}">{{$book->title}}</a></div>
    			  	@if($book->available)
    				  <div class="col-md green">Available :D</div>
    			  	@else
    		  		  <div class="col-md red">Not available :(</div>
    			  	@endif
    			  <div class="col-md">
    				  <form style="display:inline-block;" action="/delete" method="post">
    			          @csrf
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
    			@csrf
    			<input type="submit" name="op" id="check-now" value="Check now" class="button success form-submit"/>
    		</form>
    	</div>

    	<div class="row">
    		<h2>Add books</h2>
        </div>
        <div class="row">
    		<form action="/urls/create" accept-charset="UTF-8" method="post">
    		@csrf
    		<textarea cols="80" rows="10" name="urls" placeholder="author:title:url"></textarea> 
    		<input type="submit" name="op" id="edit-submit" value="Submit" class="button success form-submit" />
    		</form>
    	</div>
    </div>
</body>
<script src="/js/app.js"></script>
</html>
