<?php


namespace App\Services\Excel\Strategies;


interface DocumentProcessingStrategy
{
    public function process(array $data, int $userId): array;
}
