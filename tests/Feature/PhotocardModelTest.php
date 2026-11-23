<?php

use App\Enums\PhotocardStatus;
use App\Models\Photocard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a photocard from the factory', function () {
    $card = Photocard::factory()->create();

    expect($card->exists)->toBeTrue()
        ->and($card->user)->toBeInstanceOf(User::class);

    $this->assertDatabaseHas('photocards', ['id' => $card->id]);
});

it('casts status to an enum', function () {
    $card = Photocard::factory()->create(['status' => 'wishlist']);

    expect($card->fresh()->status)->toBe(PhotocardStatus::Wishlist);
});

it('belongs to a user who can have many cards', function () {
    $user = User::factory()->create();
    Photocard::factory()->for($user)->count(3)->create();

    expect($user->photocards)->toHaveCount(3);
});