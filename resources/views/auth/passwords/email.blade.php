@extends('layouts.app')

@section('content')
<main class="pa4 black-80">
    <form method="POST" class="measure center" action="{{ route('password.email') }}">
        @csrf

    <fieldset id="sign_up" class="ba b--transparent ph0 mh0">
      <legend class="f4 fw6 ph0 mh0">{{ __('Reset Password') }}</legend>
      <div class="mt3">
        <label class="db fw6 lh-copy f6" for="email">Email</label>
        <input class="pa2 input-reset ba bg-transparent w-100" type="email" name="email" required autocomplete="email" autofocus id="email">

       @error('email')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
      </div>
    </fieldset>

    <div>
      <input class="b ph3 pv2 input-reset ba b--black bg-transparent hover-bg-dark-green pointer f6 dib" type="submit" value="{{ __('Send Password Reset Link') }}">
    </div>

  </form>
</main>
@endsection
