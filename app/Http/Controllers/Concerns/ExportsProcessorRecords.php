<?php

namespace App\Http\Controllers\Concerns;

use App\Support\DomPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExportsProcessorRecords
{
    /**
     * @param  iterable<mixed>  $records
     * @param  array<string, callable|string>  $columns
     */
    protected function streamCsv(iterable $records, array $columns, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($records, $columns): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_keys($columns));

            foreach ($records as $record) {
                $row = [];
                foreach ($columns as $column) {
                    $row[] = is_callable($column) ? $column($record) : data_get($record, $column);
                }
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Excel-compatible export (UTF-8 CSV opened by Excel).
     *
     * @param  iterable<mixed>  $records
     * @param  array<string, callable|string>  $columns
     */
    protected function streamExcel(iterable $records, array $columns, string $basename): StreamedResponse
    {
        $filename = $basename.'-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($records, $columns): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_keys($columns), "\t");

            foreach ($records as $record) {
                $row = [];
                foreach ($columns as $column) {
                    $row[] = is_callable($column) ? $column($record) : data_get($record, $column);
                }
                fputcsv($handle, $row, "\t");
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    /**
     * @param  Collection<int, mixed>|iterable<mixed>  $records
     */
    protected function streamTripsListPdf(Collection|iterable $records, string $view, array $extra = []): Response
    {
        $fileName = 'transport-trips-'.now()->format('Ymd-His').'.pdf';
        $pdf = DomPdf::loadView($view, array_merge([
            'trips' => $records instanceof Collection ? $records : collect($records),
            'generatedAt' => now(),
            'generatedBy' => auth()->user()?->name,
        ], $extra))->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }

    protected function jsonExport(Collection $records): JsonResponse
    {
        return response()->json($records->values()->toArray());
    }
}
