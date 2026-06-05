<?php

use App\Support\RemovesLegacyBusinessNameUniqueIndexes;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        RemovesLegacyBusinessNameUniqueIndexes::remove();
    }

    public function down(): void
    {
        // Business display names are intentionally not globally unique.
    }
};
