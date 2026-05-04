<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AnimalIntakeMovementPermitStorage
{
    public static function store(UploadedFile $file): string
    {
        return $file->store('animal-intake-movement-permits', 'public');
    }

    public static function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
