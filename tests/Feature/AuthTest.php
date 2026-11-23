<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a user and returns a token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Mika',
        'email' => 'mika@example.com',
        'password' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

    $this->assertDatabaseHas('users', ['email' => 'mika@example.com']);
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'mika@example.com']);

    $this->postJson('/api/register', [
        'name' => 'Mika Two',
        'email' => 'mika@example.com',
        'password' => 'password123',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('logs in with correct credentials and returns a token', function () {
    User::factory()->create(['email' => 'mika@example.com']);

    $this->postJson('/api/login', [
        'email' => 'mika@example.com',
        'password' => 'password',
    ])->assertOk()->assertJsonStructure(['token']);
});

it('rejects login with a wrong password', function () {
    User::factory()->create(['email' => 'mika@example.com']);

    $this->postJson('/api/login', [
        'email' => 'mika@example.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});