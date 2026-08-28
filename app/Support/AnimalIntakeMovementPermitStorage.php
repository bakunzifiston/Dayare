<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AnimalIntakeMovementPermitStorage
{
    public const PERMIT_DIRECTORY = 'animal-intake-movement-permits';

    public const RECEIPT_DIRECTORY = 'animal-intake-receipts';

    public static function store(UploadedFile $file, string $directory = self::PERMIT_DIRECTORY): string
    {
        return $file->store($directory, 'public');
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
