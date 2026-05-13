<?php

namespace App\Support;

/**
 * Entry point for barryvdh/laravel-dompdf via the container binding.
 * Prefer this over {@see \Barryvdh\DomPDF\Facade\Pdf} so PDF generation does not depend
 * on facade class autoloading (some production deploys omit or mis-cache facades).
 */
final class DomPdf
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $mergeData
     */
    public static function loadView(string $view, array $data = [], array $mergeData = [], ?string $encoding = null): mixed
    {
        return app('dompdf.wrapper')->loadView($view, $data, $mergeData, $encoding);
    }
}
