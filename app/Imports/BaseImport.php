<?php


namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class BaseImport extends DefaultValueBinder implements ToCollection, WithMapping, WithCustomValueBinder
{
    public $data;

    public function collection(Collection $collection)
    {
        $this->data = $collection;
    }

    // Метод для получения данных в виде массива
    public function getData()
    {
        return $this->data->toArray();
    }

    public function map($row): array
    {
//        return array_map('strval', is_array($row) ? $row : $row->toArray());
        return $row;
    }

    public function convertNumToDate(){

    }
}
