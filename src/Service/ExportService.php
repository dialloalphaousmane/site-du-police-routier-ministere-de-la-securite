<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function generateCSV(array $data, string $filename): Response
    {
        $response = new StreamedResponse(function () use ($data) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($handle, "\xEF\xBB\xBF");

            if (!empty($data)) {
                // Write headers
                $headers = array_keys((array)$data[0]);
                fputcsv($handle, $headers, ';');

                // Write data
                foreach ($data as $row) {
                    fputcsv($handle, (array)$row, ';');
                }
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    public function generateJSON(array $data, string $filename): Response
    {
        $response = new Response(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

        return $response;
    }

    public function generateExcel(array $data, string $filename): Response
    {
        // This would require a library like PHPExcel or OpenSpout
        // For now, we'll return CSV which Excel can open
        return $this->generateCSV($data, str_replace('.xlsx', '.csv', $filename));
    }

    public function arrayToCSV(array $data, string $delimiter = ';'): string
    {
        if (empty($data)) {
            return '';
        }

        $f = fopen('php://memory', 'r+');
        fwrite($f, "\xEF\xBB\xBF"); // BOM UTF-8

        // Headers
        $headers = array_keys((array)$data[0]);
        fputcsv($f, $headers, $delimiter);

        // Data
        foreach ($data as $row) {
            fputcsv($f, (array)$row, $delimiter);
        }

        rewind($f);
        $csv = stream_get_contents($f);
        fclose($f);

        return $csv;
    }
}
