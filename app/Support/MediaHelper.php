<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaHelper
{
    public static function storeMemberPhoto(UploadedFile $file, ?string $oldPhoto = null): string
    {
        $publicRoot = storage_path('app/public');
        $isVercel = isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']);

        if (!$isVercel && is_dir($publicRoot) && is_writable($publicRoot)) {
            if ($oldPhoto && !str_starts_with($oldPhoto, 'data:') && Storage::disk('public')->exists($oldPhoto)) {
                Storage::disk('public')->delete($oldPhoto);
            }

            return $file->store('members', 'public');
        }

        // Serverless read-only filesystem fallback: store as compact data URI in database
        $mime = $file->getMimeType() ?: 'image/jpeg';
        $contents = file_get_contents($file->getRealPath());

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    public static function url(?string $photo): ?string
    {
        if (!$photo) {
            return null;
        }

        if (str_starts_with($photo, 'data:') || str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
            return $photo;
        }

        return asset('storage/' . $photo);
    }
}
