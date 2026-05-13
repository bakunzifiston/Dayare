<?php

namespace App\Support;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PdfQrCode
{
    public static function dataUri(string $payload, int $size = 140, int $margin = 1): string
    {
        $svg = (string) QrCode::format('svg')->size($size)->margin($margin)->generate($payload);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
