<?php


namespace App\Services\Excel;


use App\Imports\BaseImport;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as Date;

class ExcelParsingService
{
    public static function getDataFromExcel($filePath){
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        $data = [
            'headers' => [],
            'rows' => []
        ];



//        if (in_array($extension, ['xlsx', 'xls', 'csv'])) {
            ini_set('max_execution_time', 300); // Увеличиваем до 5 минут
            ini_set('memory_limit', '512M'); // Увеличиваем лимит памяти

            $import = new BaseImport;
            Excel::import($import, $filePath);


//            Log::info(json_encode($import));

            $importedData = $import->getData();

        Log::info('getDataFromExcel '. $extension);

            // Получение заголовков
            $headings = $importedData[0];
            $newHeaders = [];
            foreach ($headings as $heading) {
                if (is_numeric($heading)) {
                    $date = Date::excelToDateTimeObject($heading);
                    $newHeaders[] = $date->format('d.m.y'); // Преобразуем в строку в формате "дд.мм.гггг"
                } else {
                    $newHeaders[] = $heading;
                }
            }

            $newHeaders = self::normalizeRowData($newHeaders);
            $data['headers'] = $newHeaders;

//            Log::info('$data[headers]: '.$data['headers']);

            // Получение данных
            $rows = $importedData;
            array_shift($rows); // Удаление первой строки, так как это заголовки

            $data['rows'] = $rows;


            $data['rows'] = array_map(function($row) use ($newHeaders) {
                return array_combine($newHeaders, $row);
            }, $rows);
//        } else {
//            // Возвращаем сообщение об ошибке или пустой массив
//            $data = [
//                'error' => 'Unsupported file format',
//                'headers' => [],
//                'rows' => []
//            ];
//        }

        return $data;
    }

    private static function normalizeRowData($rows){
        foreach ($rows as $key => $row){
            if (is_string($row)){
                $rows[$key] = preg_replace('/(\d+)[^\d]+(\d+)[^\d]+(\d+)/', '$1.$2.$3', $row);
            }
        }

        return $rows;
    }
}
