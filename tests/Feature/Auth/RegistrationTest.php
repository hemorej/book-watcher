<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('registration is disabled', function () {
    $this->get('/register')->assertNotFound();

    expect(Route::has('register'))->toBeFalse();
});

test('the login page does not offer to create an account', function () {
    $this->get('/login')->assertOk()->assertDontSee('Create an account');
});
