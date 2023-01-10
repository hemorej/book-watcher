@extends('layouts.app')

@section('content')
<main class="pa4 black-80">
  <form method="POST" action="{{ route('login') }}" class="measure center">
    @csrf
    <fieldset id="sign_up" class="ba b--transparent ph0 mh0">
      <legend class="f4 fw6 ph0 mh0">{{ __('Login') }}</legend>
      <div class="mt3">
        <label class="db fw6 lh-copy f6" for="email">Email</label>
        <input class="pa2 input-reset ba bg-transparent hover-bg-black w-100" type="email" name="email" required autocomplete="email" autofocus id="email">

       @error('email')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
      </div>
      <div class="mv3">
        <label class="db fw6 lh-copy f6" for="password">{{ __('Password') }}</label>
        <input class="b pa2 input-reset ba bg-transparent hover-bg-black w-100" type="password" name="password" required autocomplete="password" id="password">

        @error('password')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
      </div>
      <label class="pa02 ma0 lh-copy f6 pointer"><input class="mr2" type="checkbox">{{ __('Remember Me') }}</label>
    </fieldset>
    <div class="">
      <input class="b ph3 pv2 input-reset ba b--black bg-transparent hover-bg-dark-green pointer f6 dib" type="submit" value="Sign in">
    </div>

    @if (Route::has('password.request'))
        <div class="lh-copy mt3">
            <a class="f6 link dim black db hover-dark-green" href="{{ route('password.request') }}">
                {{ __('Forgot Your Password?') }}
            </a>
        </div>
    @endif

  </form>
</main>
@endsection
