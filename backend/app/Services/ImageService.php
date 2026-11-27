<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    public const DISK = 'public';

    /**
     * Store an uploaded image under a server-generated name; the client filename is never used.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $extension = $file->guessExtension() ?: 'jpg';
        $name = Str::uuid()->toString().'.'.$extension;

        $path = Storage::disk(self::DISK)->putFileAs($directory, $file, $name);

        return $path;
    }

    /**
     * Delete a stored image. Only paths inside the allowed directory are touched.
     */
    public function delete(?string $path, string $directory): void
    {
        if (blank($path) || ! str_starts_with($path, trim($directory, '/').'/') || str_contains($path, '..')) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public function url(?string $path): ?string
    {
        return blank($path) ? null : Storage::disk(self::DISK)->url($path);
    }
}
