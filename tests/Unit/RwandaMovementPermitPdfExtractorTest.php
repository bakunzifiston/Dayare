<?php

namespace Tests\Unit;

use App\Services\Farmer\RwandaMovementPermitPdfExtractor;
use Tests\TestCase;

class RwandaMovementPermitPdfExtractorTest extends TestCase
{
    public function test_extracts_sample_rwanda_movement_permit_pdf(): void
    {
        $path = base_path('tests/fixtures/movement-permits/rwanda-sample.pdf');
        $this->assertFileExists($path);

        $extraction = (new RwandaMovementPermitPdfExtractor)->extractFromFile($path);

        $this->assertSame('B260210141531XLJK', $extraction->permitNumber);
        $this->assertSame('NDAHIMANA Justin', $extraction->ownerName);
        $this->assertSame('1198980187193037', $extraction->ownerNationalId);
        $this->assertSame('Ubworozi bw\'inka', $extraction->movementReason);
        $this->assertSame('Imodoka', $extraction->transportMode);
        $this->assertSame('RAC474Z', $extraction->vehiclePlate);
        $this->assertSame('2026-02-10', $extraction->issueDate);
        $this->assertSame('2026-02-13', $extraction->expiryDate);
        $this->assertSame('GASHIRABAKE Isidore', $extraction->issuingOfficer);
        $this->assertSame('Inka', $extraction->species);
        $this->assertStringContainsString('Gicumbi', (string) $extraction->originLocation());
        $this->assertStringContainsString('Rusororo', (string) $extraction->destinationLocation());
        $this->assertCount(2, $extraction->animals);
        $this->assertSame('2214946', $extraction->animals[0]['ear_tag']);
        $this->assertSame('2214901', $extraction->animals[1]['ear_tag']);
        $this->assertSame('female', $extraction->animals[0]['sex']);
        $this->assertSame('CROSS', $extraction->animals[0]['breed']);
        $this->assertSame('KORORA', $extraction->animals[0]['description']);
    }
}
