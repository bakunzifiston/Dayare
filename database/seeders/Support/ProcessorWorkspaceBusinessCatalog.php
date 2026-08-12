<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use Illuminate\Support\Str;

/**
 * Canonical processor workspace demo businesses for {@see \Database\Seeders\ProcessorWorkspaceSeeder}.
 */
final class ProcessorWorkspaceBusinessCatalog
{
    /** @var list<array{name: string, province: string, team_size: int, email_slug: string}> */
    public const ENTRIES = [
        ['name' => "Societe d'Abattoir Nyabugogo (SABAN)", 'province' => 'City of Kigali', 'team_size' => 4, 'email_slug' => 'saban'],
        ['name' => 'NDARU FARM Ltd', 'province' => 'Eastern Province', 'team_size' => 3, 'email_slug' => 'ndaru-farm'],
        ['name' => 'PANIEL Meat Processing Ltd', 'province' => 'City of Kigali', 'team_size' => 4, 'email_slug' => 'paniel-meat'],
        ['name' => 'RUGANO MEAT SUPPLY Co. Ltd', 'province' => 'Southern Province', 'team_size' => 3, 'email_slug' => 'rugano-meat'],
        ['name' => 'AGRIHEALTH Ltd', 'province' => 'Northern Province', 'team_size' => 4, 'email_slug' => 'agrihealth'],
        ['name' => 'SANTRA Slaughterhouse', 'province' => 'Western Province', 'team_size' => 3, 'email_slug' => 'santra'],
        ['name' => 'KOADU Abattoir', 'province' => 'Eastern Province', 'team_size' => 4, 'email_slug' => 'koadu'],
        ['name' => 'CAMR Abattoir', 'province' => 'Southern Province', 'team_size' => 3, 'email_slug' => 'camr'],
        ['name' => 'RUGALI Meat Processing Company', 'province' => 'Western Province', 'team_size' => 4, 'email_slug' => 'rugali-meat'],
        ['name' => 'Gakenke Abattoir', 'province' => 'Northern Province', 'team_size' => 3, 'email_slug' => 'gakenke'],
        ['name' => 'Buranga Abattoir', 'province' => 'Southern Province', 'team_size' => 4, 'email_slug' => 'buranga'],
        ['name' => 'Kabuga Pig/Modern Abattoir', 'province' => 'City of Kigali', 'team_size' => 5, 'email_slug' => 'kabuga-abattoir'],
    ];

    public static function ownerEmail(string $slug): string
    {
        return "owner@{$slug}.rw";
    }

    public static function businessEmail(string $slug): string
    {
        return "info@{$slug}.rw";
    }

    public static function teamEmail(string $slug, int $index): string
    {
        return "team.{$index}@{$slug}.rw";
    }

    /**
     * @return list<string>
     */
    public static function emailSlugs(): array
    {
        return array_column(self::ENTRIES, 'email_slug');
    }

    public static function emailSlugFromBusinessName(string $name): string
    {
        if (preg_match('/\(([^)]+)\)\s*$/', $name, $matches) === 1) {
            return Str::slug(strtolower(trim($matches[1])));
        }

        $normalized = str_replace('/', '-', $name);

        return Str::slug($normalized);
    }
}
