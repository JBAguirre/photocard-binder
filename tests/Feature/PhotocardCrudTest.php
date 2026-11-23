<?php

use App\Models\Photocard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function cardPayload(array $overrides = []): array
{
    return array_merge([
        'group_name' => 'NewJeans',
        'member_name' => 'Hanni',
        'album' => 'Get Up',
        'version' => 'Bunny Beach Bag',
        'card_type' => 'album',
        'status' => 'owned',
    ], $overrides);
}

it('blocks unauthenticated requests', function () {
    $this->getJson('/api/photocards')->assertUnauthorized();
});

it('creates a photocard for the logged-in user', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/photocards', cardPayload())
        ->assertCreated()
        ->assertJsonPath('data.member_name', 'Hanni')
        ->assertJsonPath('data.status', 'owned');

    $this->assertDatabaseHas('photocards', [
        'user_id' => $user->id,
        'member_name' => 'Hanni',
    ]);
});

it('rejects a missing group name', function () {
    $this->actingAs(User::factory()->create(), 'sanctum')
        ->postJson('/api/photocards', cardPayload(['group_name' => null]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('group_name');
});

it('rejects an invalid status', function () {
    $this->actingAs(User::factory()->create(), 'sanctum')
        ->postJson('/api/photocards', cardPayload(['status' => 'ownd']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('rejects an invalid card type', function () {
    $this->actingAs(User::factory()->create(), 'sanctum')
        ->postJson('/api/photocards', cardPayload(['card_type' => 'hologram']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('card_type');
});

it('rejects a duplicate card for the same user', function () {
    $user = User::factory()->create();
    $user->photocards()->create(cardPayload());

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/photocards', cardPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('group_name');

    expect($user->photocards()->count())->toBe(1);
});

it('allows the same card for a different user', function () {
    $first = User::factory()->create();
    $first->photocards()->create(cardPayload());

    $second = User::factory()->create();

    $this->actingAs($second, 'sanctum')
        ->postJson('/api/photocards', cardPayload())
        ->assertCreated();
});

it('lists only my cards, newest first, 12 per page', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Photocard::factory()->for($user)->count(15)->create();
    Photocard::factory()->for($other)->count(3)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/photocards')
        ->assertOk()
        ->assertJsonCount(12, 'data')
        ->assertJsonPath('meta.per_page', 12)
        ->assertJsonPath('meta.total', 15);
});

it('shows one of my cards', function () {
    $user = User::factory()->create();
    $card = Photocard::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/photocards/{$card->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $card->id);
});

it('hides other users cards on show, update, and delete', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $card = Photocard::factory()->for($owner)->create();

    $this->actingAs($intruder, 'sanctum');

    $this->getJson("/api/photocards/{$card->id}")->assertNotFound();
    $this->putJson("/api/photocards/{$card->id}", cardPayload())->assertNotFound();
    $this->deleteJson("/api/photocards/{$card->id}")->assertNotFound();

    $this->assertDatabaseHas('photocards', ['id' => $card->id]);
});

it('updates a card', function () {
    $user = User::factory()->create();
    $card = Photocard::factory()->for($user)->create(['status' => 'wishlist']);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/photocards/{$card->id}", cardPayload(['status' => 'owned']))
        ->assertOk()
        ->assertJsonPath('data.status', 'owned');

    expect($card->fresh()->status->value)->toBe('owned');
});

it('lets a card be saved again without counting as its own duplicate', function () {
    $user = User::factory()->create();
    $card = $user->photocards()->create(cardPayload());

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/photocards/{$card->id}", cardPayload(['notes' => 'Got it at a fansign!']))
        ->assertOk()
        ->assertJsonPath('data.notes', 'Got it at a fansign!');
});

it('deletes a card', function () {
    $user = User::factory()->create();
    $card = Photocard::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/photocards/{$card->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('photocards', ['id' => $card->id]);
});
