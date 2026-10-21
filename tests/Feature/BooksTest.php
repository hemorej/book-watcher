<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get('/books');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the books page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/books');
    $response->assertStatus(200);
});
