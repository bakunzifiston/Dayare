<?php

namespace App\Services\Farmer;

use App\DataTransferObjects\RwandaMovementPermitExtraction;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Smalot\PdfParser\Parser;

class RwandaMovementPermitPdfExtractor
{
    public function __construct(
        private readonly Parser $parser = new Parser,
    ) {}

    public function extractFromFile(UploadedFile|string $file): RwandaMovementPermitExtraction
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        if (! is_string($path) || ! is_readable($path)) {
            throw ValidationException::withMessages([
                'permit_pdf' => __('The uploaded permit PDF could not be read.'),
            ]);
        }

        try {
            $text = $this->parser->parseFile($path)->getText();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'permit_pdf' => __('The uploaded file is not a readable PDF permit.'),
            ]);
        }

        return $this->extractFromText($text);
    }

    public function extractFromText(string $text): RwandaMovementPermitExtraction
    {
        $normalized = $this->normalizeText($text);

        if (! str_contains($normalized, 'URUHUSHYA RWO KWIMURA AMATUNGO')) {
            throw ValidationException::withMessages([
                'permit_pdf' => __('This PDF does not appear to be a Rwanda animal movement permit (URUHUSHYA RWO KWIMURA AMATUNGO).'),
            ]);
        }

        $lines = array_values(array_filter(
            array_map(static fn (string $line) => trim($line), explode("\n", $normalized)),
            static fn (string $line) => $line !== '',
        ));

        $permitNumber = $this->extractPermitNumber($normalized, $text);
        $ownerName = null;
        $ownerNationalId = null;
        $ownerIndex = $this->indexOfLine($lines, 'Amazina');
        if ($ownerIndex !== null && isset($lines[$ownerIndex + 1])) {
            $ownerLine = $lines[$ownerIndex + 1];
            if (preg_match('/^(.+?)\t+(\d{13,20})$/u', $ownerLine, $match)) {
                $ownerName = trim($match[1]);
                $ownerNationalId = trim($match[2]);
            } elseif (preg_match('/^(.+?)\s+(\d{13,20})$/u', $ownerLine, $match)) {
                $ownerName = trim($match[1]);
                $ownerNationalId = trim($match[2]);
            } else {
                $ownerName = $ownerLine;
            }
        }

        $movementReason = $this->valueOnNextLine($lines, 'Impamvu y\'iyimuka')
            ?? $this->valueOnNextLine($lines, 'Impamvu y’iyimuka');
        $transportMode = $this->valueOnNextLine($lines, 'Uburyo bwo kugenda');
        $vehiclePlate = $this->plateAfterTransportMode($lines);
        $transportNotes = $this->valueOnNextLine($lines, 'Ibisobanuro birambuye by\'ubwikorezi')
            ?? $this->valueOnNextLine($lines, 'Ibisobanuro birambuye by’ubwikorezi');

        [$issueDate, $expiryDate] = $this->extractValidityDates($normalized);

        $origin = $this->extractAdministrativeBlock($lines, 'Ahantu aturuka:', 'Aho yerekeza:');
        $destination = $this->extractAdministrativeBlock($lines, 'Aho yerekeza:', 'Rutanzwe ku wa:');
        if ($destination === []) {
            $destination = $this->extractAdministrativeBlock($lines, 'Aho yerekeza:', 'Nomero y\'icyemezo:');
        }

        $issuingOfficer = $this->extractIssuingOfficer($lines);
        $species = $this->cleanSpecies($this->valueOnNextLine($lines, 'Ubwoko'));
        $animals = $this->extractAnimals($lines, $species);

        if ($permitNumber === null) {
            throw ValidationException::withMessages([
                'permit_pdf' => __('Could not find the permit number (Nomero y\'icyemezo) in the PDF.'),
            ]);
        }

        if ($animals === []) {
            throw ValidationException::withMessages([
                'permit_pdf' => __('No animals were found in the permit PDF. Check that ear tag rows are present.'),
            ]);
        }

        return new RwandaMovementPermitExtraction(
            permitNumber: $permitNumber,
            ownerName: $ownerName,
            ownerNationalId: $ownerNationalId,
            movementReason: $movementReason,
            transportMode: $transportMode,
            vehiclePlate: $vehiclePlate,
            transportNotes: $transportNotes,
            issueDate: $issueDate,
            expiryDate: $expiryDate,
            originDistrict: $origin['district'] ?? null,
            originSector: $origin['sector'] ?? null,
            originCell: $origin['cell'] ?? null,
            originVillage: $origin['village'] ?? null,
            destinationDistrict: $destination['district'] ?? null,
            destinationSector: $destination['sector'] ?? null,
            destinationCell: $destination['cell'] ?? null,
            destinationVillage: $destination['village'] ?? null,
            issuingOfficer: $issuingOfficer,
            species: $species,
            animals: $animals,
            rawText: $text,
        );
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function extractPermitNumber(string $normalized, string $raw): ?string
    {
        if (preg_match('/Nomero y\'icyemezo:\s*\n?\s*([A-Z0-9]{10,24})/iu', $normalized, $match)) {
            return strtoupper(trim($match[1]));
        }

        if (preg_match('/\b(B\d{12,20}[A-Z]{0,6})\b/i', $normalized, $match)) {
            return strtoupper(trim($match[1]));
        }

        if (preg_match('/_([A-Z0-9]{10,24})\.pdf$/i', $raw, $match)) {
            return strtoupper(trim($match[1]));
        }

        return null;
    }

    /** @return array{0: ?string, 1: ?string} */
    private function extractValidityDates(string $normalized): array
    {
        if (preg_match('/(\d{1,2}\/\d{1,2}\/\d{4})\s+(\d{1,2}\/\d{1,2}\/\d{4})/', $normalized, $match)) {
            return [
                $this->normalizeDate($match[1]),
                $this->normalizeDate($match[2]),
            ];
        }

        return [null, null];
    }

    private function normalizeDate(string $value): ?string
    {
        try {
            return Carbon::createFromFormat('d/m/Y', trim($value))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /** @param  list<string>  $lines */
    private function plateAfterTransportMode(array $lines): ?string
    {
        $index = $this->indexOfLine($lines, 'Uburyo bwo kugenda');
        if ($index === null) {
            return null;
        }

        $mode = $lines[$index + 1] ?? null;
        $candidate = $lines[$index + 2] ?? null;
        if ($candidate === null || $candidate === 'Nomero ya pulaki') {
            return null;
        }

        if ($mode !== null && strcasecmp($mode, 'Imodoka') === 0 && preg_match('/^[A-Z0-9]{3,12}$/i', $candidate)) {
            return strtoupper($candidate);
        }

        return null;
    }

    /** @param  list<string>  $lines */
    private function extractIssuingOfficer(array $lines): ?string
    {
        $index = $this->indexOfLineContaining($lines, 'Umuyobozi Ushinzwe');
        if ($index !== null && $index > 0) {
            $candidate = $lines[$index - 1];
            if (preg_match('/^[A-Z][A-Za-zÀ-ÿ\'\s]{4,}$/u', $candidate)) {
                return $candidate;
            }
        }

        return $this->valueOnNextLine($lines, 'Rwatanzwe na:');
    }

    /**
     * @param  list<string>  $lines
     * @return array{district: ?string, sector: ?string, cell: ?string, village: ?string}
     */
    private function extractAdministrativeBlock(array $lines, string $startLabel, string $endLabel): array
    {
        $start = $this->indexOfLine($lines, $startLabel);
        $end = $this->indexOfLine($lines, $endLabel);
        if ($start === null) {
            return [];
        }

        $slice = array_slice($lines, $start + 1, $end !== null ? max(0, $end - $start - 1) : null);
        $result = ['district' => null, 'sector' => null, 'cell' => null, 'village' => null];

        for ($i = 0; $i < count($slice); $i++) {
            $line = $slice[$i];
            $value = $slice[$i + 1] ?? null;

            if (str_starts_with($line, 'Akarere:') && $value !== null && ! str_contains($value, ':')) {
                $result['district'] = $this->cleanAdminValue($value);
                $i++;
            } elseif (str_contains($line, 'Umurenge:') && str_contains($line, 'Akagari:')) {
                $pairLine = $slice[$i + 1] ?? null;
                if ($pairLine !== null && ! str_contains($pairLine, ':')) {
                    $pairParts = preg_split('/\s+/', $pairLine) ?: [];
                    $result['sector'] = $this->cleanAdminValue($pairParts[0] ?? null);
                    if (isset($pairParts[1])) {
                        $result['cell'] = $this->cleanAdminValue($pairParts[1]);
                    }
                    $i++;
                }
            } elseif (str_starts_with($line, 'Umurenge:')) {
                [$sector, $cell] = $this->splitInlineAdminPair($line, $value);
                $result['sector'] = $sector;
                if ($cell !== null) {
                    $result['cell'] = $cell;
                }
                if ($value !== null && ! str_contains($value, ':')) {
                    $i++;
                }
            } elseif ($line === 'Umurenge:' && $value !== null) {
                $result['sector'] = $this->cleanAdminValue($value);
                $i++;
            } elseif (str_starts_with($line, 'Akagari:') && $value !== null && ! str_contains($value, ':')) {
                $result['cell'] = $this->cleanAdminValue($value);
                $i++;
            } elseif ($line === 'Akagari:' && $value !== null) {
                $result['cell'] = $this->cleanAdminValue($value);
                $i++;
            } elseif (str_starts_with($line, 'Umudugudu:')) {
                [$village, $cell] = $this->splitInlineAdminPair($line, $value);
                $result['village'] = $village;
                if ($cell !== null && $result['cell'] === null) {
                    $result['cell'] = $cell;
                }
                if ($value !== null && ! str_contains($value, ':')) {
                    $i++;
                }
            } elseif ($line === 'Umudugudu:' && $value !== null) {
                $parts = preg_split("/\t+/", $value) ?: [$value];
                $result['village'] = $this->cleanAdminValue($parts[0]);
                if (isset($parts[1]) && $result['cell'] === null) {
                    $result['cell'] = $this->cleanAdminValue($parts[1]);
                }
                $i++;
            } elseif ($line === 'Umurenge:' && ($value === 'Akagari:' || $value === null)) {
                $pairLine = $slice[$i + 1] ?? null;
                if ($pairLine !== null && ! str_contains($pairLine, ':')) {
                    $pairParts = preg_split('/\s+/', $pairLine) ?: [];
                    $result['sector'] = $this->cleanAdminValue($pairParts[0] ?? null);
                    if (isset($pairParts[1]) && $result['cell'] === null) {
                        $result['cell'] = $this->cleanAdminValue($pairParts[1]);
                    }
                    $i++;
                }
            }
        }

        $result['district'] = $this->cleanAdminValue($result['district']);
        $result['sector'] = $this->cleanAdminValue($result['sector']);
        $result['cell'] = $this->cleanAdminValue($result['cell']);
        $result['village'] = $this->cleanAdminValue($result['village']);

        return $result;
    }

    private function cleanSpecies(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $parts = preg_split("/\t+/", $value) ?: [$value];

        return trim($parts[0]);
    }

    /** @return array{0: ?string, 1: ?string} */
    private function splitInlineAdminPair(string $labelLine, ?string $nextLine): array
    {
        $inline = trim(substr($labelLine, (int) strpos($labelLine, ':') + 1));
        if ($inline !== '') {
            $parts = preg_split("/\t+/", $inline) ?: [$inline];

            return [$this->cleanAdminValue($parts[0]), $this->cleanAdminValue($parts[1] ?? null)];
        }

        if ($nextLine === null) {
            return [null, null];
        }

        $parts = preg_split("/\t+/", $nextLine) ?: [$nextLine];

        return [$this->cleanAdminValue($parts[0]), $this->cleanAdminValue($parts[1] ?? null)];
    }

    private function cleanAdminValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || str_ends_with($value, ':') || preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
            return null;
        }

        if (preg_match('/^[A-Z]{2,}\s+[A-Z][a-zÀ-ÿ\'-]+$/u', $value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param  list<string>  $lines
     * @return list<array{
     *     ear_tag: string,
     *     name: ?string,
     *     sex: ?string,
     *     quantity: int,
     *     breed: ?string,
     *     color_mark: ?string,
     *     description: ?string,
     *     species: ?string,
     * }>
     */
    private function extractAnimals(array $lines, ?string $species): array
    {
        $animals = [];
        $start = $this->indexOfLineContaining($lines, 'Nomero y\'iherena') ?? $this->indexOfLine($lines, 'Igitsina');

        foreach ($lines as $index => $line) {
            if ($start !== null && $index <= $start) {
                continue;
            }

            if (! preg_match('/^(\d{5,15})\s+(MALE|FEMALE|GABO|GORE)\s+(\d+)\s+(.+)$/iu', $line, $match)) {
                continue;
            }

            $tail = $match[4];
            $breed = null;
            $colorMark = null;
            $description = null;

            if (str_contains($tail, "\t")) {
                $parts = explode("\t", $tail);
                $left = trim($parts[0]);
                $description = trim($parts[1] ?? '');
                $leftParts = preg_split('/\s+/', $left) ?: [];
                $breed = trim($leftParts[0] ?? '');
                $colorMark = trim(implode(' ', array_slice($leftParts, 1)));
            } else {
                $parts = preg_split('/\s+/', trim($tail)) ?: [];
                $breed = trim($parts[0] ?? '');
                $colorMark = trim($parts[1] ?? '');
                $description = trim(implode(' ', array_slice($parts, 2)));
            }

            $animals[] = [
                'ear_tag' => trim($match[1]),
                'name' => null,
                'sex' => strtolower(trim($match[2])),
                'quantity' => max(1, (int) $match[3]),
                'breed' => $breed !== '' ? $breed : null,
                'color_mark' => $colorMark !== '' ? $colorMark : null,
                'description' => $description !== '' ? $description : null,
                'species' => $species,
            ];
        }

        return $animals;
    }

    /** @param  list<string>  $lines */
    private function valueOnNextLine(array $lines, string $label): ?string
    {
        $index = $this->indexOfLine($lines, $label);
        if ($index === null) {
            return null;
        }

        return $lines[$index + 1] ?? null;
    }

    /** @param  list<string>  $lines */
    private function indexOfLine(array $lines, string $label): ?int
    {
        foreach ($lines as $index => $line) {
            if ($line === $label) {
                return $index;
            }
        }

        return null;
    }

    /** @param  list<string>  $lines */
    private function indexOfLineContaining(array $lines, string $needle): ?int
    {
        foreach ($lines as $index => $line) {
            if (str_contains($line, $needle)) {
                return $index;
            }
        }

        return null;
    }
}
