<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PhotocardImageService
{
    public function store(UploadedFile $file, int $userId): string
    {
        $path = $file->store("photocards/{$userId}", $this->disk());

        if ($path === false) {
            throw new RuntimeException('Could not store the photocard image.');
        }

        return $path;
    }

    public function delete(string $path): void
    {
        Storage::disk($this->disk())->delete($path);
    }

    private function disk(): string
    {
        return config('filesystems.photocard_disk', 's3');
    }
}
