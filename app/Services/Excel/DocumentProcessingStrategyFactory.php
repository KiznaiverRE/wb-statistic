<?php


namespace App\Services\Excel;


use App\Services\Excel\Strategies\DocumentProcessingStrategy;
use App\Services\Excel\Strategies\FinancialDocumentStrategy;
use App\Services\Excel\Strategies\AdvertisingDocumentStrategy;
use App\Services\Excel\Strategies\StorageDocumentStrategy;

class DocumentProcessingStrategyFactory
{
    public static function getStrategy(string $documentType): DocumentProcessingStrategy
    {
        return match ($documentType){
            'finance' => new FinancialDocumentStrategy(),
            'ads' => new AdvertisingDocumentStrategy(),
            'storage' => new StorageDocumentStrategy(),
            default => throw new \InvalidArgumentException("Unknown document type: $documentType"),
        };
    }
}
