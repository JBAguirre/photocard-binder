<?php

use App\Services\PhotocardImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(fn () => Storage::fake('s3'));

it('stores the image under the users folder on the s3 disk', function () {
    $path = (new PhotocardImageService)->store(UploadedFile::fake()->image('card.jpg'), 42);

    expect($path)->toStartWith('photocards/42/');
    Storage::disk('s3')->assertExists($path);
});

it('gives each upload a unique name, even with the same original filename', function () {
    $service = new PhotocardImageService;

    $first = $service->store(UploadedFile::fake()->image('card.jpg'), 1);
    $second = $service->store(UploadedFile::fake()->image('card.jpg'), 1);

    expect($first)->not->toBe($second);
    Storage::disk('s3')->assertExists($first);
    Storage::disk('s3')->assertExists($second);
});

it('deletes the image from the s3 disk', function () {
    $service = new PhotocardImageService;
    $path = $service->store(UploadedFile::fake()->image('card.jpg'), 1);

    $service->delete($path);

    Storage::disk('s3')->assertMissing($path);
});