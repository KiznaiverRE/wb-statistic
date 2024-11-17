<?php


namespace App\Services\Excel;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ExcelHeaderValidatorService
{
    protected array $templateHeaders;
    protected array $availableKeys;

    public function __construct()
    {
        $this->templateHeaders = $this->getAllHeaders();
        $this->availableKeys = array_keys($this->getAllHeaders());
    }

    protected function getAllHeaders(): array
    {
        return json_decode(Storage::disk('public')->get('template_headers.json'),JSON_UNESCAPED_UNICODE);
    }

    public function formatHeaders(array $headers): array
    {
        return array_map(fn($header) => mb_ucfirst(mb_strtolower($header)), $headers);
    }

    public function validateHeaders(array $excelHeaders, string $headerKey): bool|array
    {
        if (!in_array($headerKey, $this->availableKeys)){

            throw new Exception("Invalid header template key '{$headerKey}'. Available keys are:" . implode(',', $this->availableKeys));
        }

//        Log::info($excelHeaders);

        $formattedHeaders = $this->formatHeaders($excelHeaders);
        $templateHeaders = $this->formatHeaders($this->templateHeaders[$headerKey]);

        $missingHeaders = array_diff($templateHeaders, $formattedHeaders);

        return empty($missingHeaders) ? true : $missingHeaders;
    }
}
