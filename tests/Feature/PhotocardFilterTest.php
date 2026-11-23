<?php

use App\Models\Photocard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('filters by status', function () {
    $user = User::factory()->create();
    Photocard::factory()->for($user)->count(3)->create(['status' => 'owned']);
    Photocard::factory()->for($user)->count(2)->create(['status' => 'wishlist']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/photocards?status=wishlist')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect(collect($response->json('data'))->pluck('status')->unique()->all())
        ->toBe(['wishlist']);
});

it('filters by card type', function () {
    $user = User::factory()->create();
    Photocard::factory()->for($user)->count(2)->create(['card_type' => 'pob']);
    Photocard::factory()->for($user)->count(3)->create(['card_type' => 'album']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/photocards?card_type=pob')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters by group with a partial, case-insensitive match', function () {
    $user = User::factory()->create();
    Photocard::factory()->for($user)->count(2)->create(['group_name' => 'NewJeans']);
    Photocard::factory()->for($user)->create(['group_name' => 'IVE']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/photocards?group=newjeans')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters by member', function () {
    $user = User::factory()->create();
    Photocard::factory()->for($user)->create(['member_name' => 'Hanni']);
    Photocard::factory()->for($user)->create(['member_name' => 'Minji']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/photocards?member=han')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.member_name', 'Hanni');
});

it('searches member, album, and version with q', function () {
    $user = User::factory()->create();

    $base = ['group_name' => 'NewJeans', 'member_name' => 'Hanni', 'album' => 'Get Up', 'version' => 'Ver. A'];

    Photocard::factory()->for($user)->create(array_merge($base, ['member_name' => 'Summer Kim']));
    Photocard::factory()->for($user)->create(array_merge($base, ['album' => 'Summer Special']));
    Photocard::factory()->for($user)->create(array_merge($base, ['version' => 'Summer Pop-up']));
    Photocard::factory()->for($user)->create($base); // matches nothing

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/photocards?q=summer')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('combines several filters together', function () {
    $user = User::factory()->create();
    Photocard::factory()->for($user)->create(['group_name' => 'IVE', 'status' => 'wishlist']);
    Photocard::factory()->for($user)->create(['group_name' => 'IVE', 'status' => 'owned']);
    Photocard::factory()->for($user)->create(['group_name' => 'aespa', 'status' => 'wishlist']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/photocards?status=wishlist&group=ive')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.group_name', 'IVE');
});

it('never leaks other users cards through the search', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Photocard::factory()->for($user)->create([
        'member_name' => 'Hanni', 'status' => 'owned',
    ]);

    // Someone else's card that would match "hanni" through its album.
    Photocard::factory()->for($other)->create([
        'member_name' => 'Minji', 'album' => 'Hanni Era', 'status' => 'wishlist',
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/photocards?status=owned&q=hanni')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.member_name', 'Hanni');
});

it('returns an empty list when nothing matches', function () {
    $user = User::factory()->create();
    Photocard::factory()->for($user)->count(2)->create(['group_name' => 'IVE']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/photocards?group=blackpink')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('ignores blank filters', function () {
    $user = User::factory()->create();
    Photocard::factory()->for($user)->count(3)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/photocards?status=&q=')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('rejects an invalid status filter', function () {
    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson('/api/photocards?status=ownd')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('rejects an invalid card type filter', function () {
    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson('/api/photocards?card_type=hologram')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('card_type');
});

it('keeps the filters in the pagination links', function () {
    $user = User::factory()->create();
    Photocard::factory()->for($user)->count(13)->create(['status' => 'owned']);
    Photocard::factory()->for($user)->count(2)->create(['status' => 'wishlist']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/photocards?status=owned')
        ->assertOk()
        ->assertJsonCount(12, 'data')
        ->assertJsonPath('meta.total', 13);

    expect($response->json('links.next'))->toContain('status=owned');
});
