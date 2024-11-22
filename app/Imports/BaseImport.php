<?php


namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class BaseImport extends DefaultValueBinder implements WithCustomValueBinder, WithChunkReading, OnEachRow
{
//    public $data;
    public array $data = [];

//    public function collection(Collection $collection)
//    {
//        $this->data = $collection;
//    }

    public function onRow(Row $row)
    {
        $this->data[] = $row->toArray();
    }

//    public function array(array $array)
//    {
//
//        $this->data = array_merge($this->data, $array);
//    }

    public function chunkSize(): int
    {
        return 1000;
    }

    // Метод для получения данных в виде массива
    public function getData()
    {
        return $this->data;
    }

//    public function map($row): array
//    {
//        return $row;
//    }
}
