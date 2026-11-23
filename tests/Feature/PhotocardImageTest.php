<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('s3'));

function imageCardData(array $overrides = []): array
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

it('creates a card with an image stored on the s3 disk', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/photocards', imageCardData([
            'image' => UploadedFile::fake()->image('hanni.jpg'),
        ]))
        ->assertCreated();

    $path = $response->json('data.image_path');

    expect($path)->toStartWith("photocards/{$user->id}/");
    Storage::disk('s3')->assertExists($path);
    $this->assertDatabaseHas('photocards', ['user_id' => $user->id, 'image_path' => $path]);
});

it('creates a card without an image', function () {
    $this->actingAs(User::factory()->create(), 'sanctum')
        ->postJson('/api/photocards', imageCardData())
        ->assertCreated()
        ->assertJsonPath('data.image_path', null);

    expect(Storage::disk('s3')->allFiles())->toBeEmpty();
});

it('rejects a PDF upload and stores nothing', function () {
    $this->actingAs(User::factory()->create(), 'sanctum')
        ->postJson('/api/photocards', imageCardData([
            'image' => UploadedFile::fake()->create('card.pdf', 100, 'application/pdf'),
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    expect(Storage::disk('s3')->allFiles())->toBeEmpty();
    $this->assertDatabaseCount('photocards', 0);
});

it('rejects an image over 2 MB', function () {
    $this->actingAs(User::factory()->create(), 'sanctum')
        ->postJson('/api/photocards', imageCardData([
            'image' => UploadedFile::fake()->image('big.jpg')->size(3000),
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    expect(Storage::disk('s3')->allFiles())->toBeEmpty();
});

it('replaces the image on update and removes the old file', function () {
    $user = User::factory()->create();
    $oldPath = "photocards/{$user->id}/old.jpg";
    Storage::disk('s3')->put($oldPath, 'old image');
    $card = $user->photocards()->create(imageCardData(['image_path' => $oldPath]));

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/photocards/{$card->id}", imageCardData([
            'image' => UploadedFile::fake()->image('new.jpg'),
        ]))
        ->assertOk();

    $newPath = $response->json('data.image_path');

    expect($newPath)->not->toBe($oldPath);
    Storage::disk('s3')->assertMissing($oldPath);
    Storage::disk('s3')->assertExists($newPath);
});

it('keeps the existing image when an update has no new image', function () {
    $user = User::factory()->create();
    $oldPath = "photocards/{$user->id}/old.jpg";
    Storage::disk('s3')->put($oldPath, 'old image');
    $card = $user->photocards()->create(imageCardData(['image_path' => $oldPath]));

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/photocards/{$card->id}", imageCardData(['notes' => 'Updated notes']))
        ->assertOk()
        ->assertJsonPath('data.image_path', $oldPath);

    Storage::disk('s3')->assertExists($oldPath);
});

it('deletes the image from storage when the card is deleted', function () {
    $user = User::factory()->create();
    $path = "photocards/{$user->id}/card.jpg";
    Storage::disk('s3')->put($path, 'image');
    $card = $user->photocards()->create(imageCardData(['image_path' => $path]));

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/photocards/{$card->id}")
        ->assertNoContent();

    Storage::disk('s3')->assertMissing($path);
});

it('does not delete another users image', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $path = "photocards/{$owner->id}/card.jpg";
    Storage::disk('s3')->put($path, 'image');
    $card = $owner->photocards()->create(imageCardData(['image_path' => $path]));

    $this->actingAs($intruder, 'sanctum')
        ->deleteJson("/api/photocards/{$card->id}")
        ->assertNotFound();

    Storage::disk('s3')->assertExists($path);
});
