<?php


namespace App\Services\Excel\Strategies;


use App\Services\Excel\ExcelParsingService;
use BCMathExtended\BC;
use Illuminate\Support\Facades\Auth;

class StorageDocumentStrategy implements DocumentProcessingStrategy
{
    public function process(array $data, int $userId): array
    {
        $user = Auth::user();

        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');
        $data = ExcelParsingService::getDataFromExcel($file);

        $missingHeaders = $this->headerValidator->validateHeaders($data['headers'], 'storage');

        if ($missingHeaders !== true){
            return response()->json(['error', 'Failed to process the spreadsheet file.'], 500);
        }

        $finData = $request->input('newRows');

        // Проверяем, есть ли newRows и является ли он валидным JSON
        if (!empty($finData)) {
            $newRows = json_decode($finData, true);

            // Проверяем, успешно ли распарсен JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'Invalid JSON format for newRows'], 400);
            }
        } else {
            $newRows = [];
        }

//        return response()->json(['newRows' => $newRows], 200);

        foreach ($newRows as $index => $item) {
            $article = $item['meta']['article'];

            foreach ($data['rows'] as $key => $value) {
//                $unixTimestamp = ((int)$value['Дата'] - 25569) * 86400;
//                $date = date('d.m.Y', $unixTimestamp);
                list($startDate, $endDate) = $this->getWeekRange($value['Дата']);
                $week = "$startDate-$endDate";

                if ($item['meta']['article'] == $value['Артикул продавца']) {
                    if (!isset($item['reports'][$week])) {
                        $item['reports'][$week] = [];
                    }
                    if (!isset($item['reports'][$week]['data'])) {
                        $item['reports'][$week]['data'] = [];
                    }

                    if (!isset($item['reports'][$week]['data']['storage'])){
                        $item['reports'][$week]['data']['storage'] = 0;
                    }


                    $item['reports'][$week]['data']['storage'] = BC::add($item['reports'][$week]['data']['storage'], $value['Сумма хранения, руб'], 2);

                    // Обновляем элемент в $newRows
                    $newRows[$index] = $item;

                }
            }

            if (!isset($newRows[$index]['reports'][$week]['data']['creditedToAccount'])){
                $newRows[$index]['reports'][$week]['data']['creditedToAccount'] = 0;
            }
        }

        // Создаем новый массив с ключами-артикулами
        foreach ($newRows as $key => $item) {
            foreach ($item['reports'] as $k => $v) {
                if ($v['data']['ads'] == 0) {
                    $newRows[$key]['reports'][$k]['data']['creditedToAccount'] = BC::sub($v['data']['creditedToAccount'], $v['data']['storage'], 2);
                    $newRows[$key]['reports'][$k]['data']['creditedToAccount'] = BC::sub($v['data']['creditedToAccount'], $v['data']['ads'], 2);
                } else {
                    $newRows[$key]['reports'][$k]['data']['creditedToAccount'] = BC::sub($v['data']['creditedToAccount'], $v['data']['storage'], 2);
                }


                $newRows[$key]['reports'][$k]['data']['profit'] = BC::sub($newRows[$key]['reports'][$k]['data']['creditedToAccount'], $newRows[$key]['reports'][$k]['data']['batchCost'], 2);

                if ($v['data']['profit'] <= 0) {
                    $newRows[$key]['reports'][$k]['data']['profitPercent'] = 0;
                } else {
                    $newRows[$key]['reports'][$k]['data']['profitPercent'] = BC::div($newRows[$key]['reports'][$k]['data']['profit'], (BC::div($newRows[$key]['reports'][$k]['data']['transfers'], 100, 10)), 2);
                }



                $numerator = $newRows[$key]['reports'][$k]['data']['creditedToAccount'] - $newRows[$key]['reports'][$k]['data']['batchCost'];
                $denominator = BC::mul($newRows[$key]['reports'][$k]['data']['batchCost'], 100, 10);

                if ($numerator != 0 && $newRows[$key]['reports'][$k]['data']['batchCost'] != 0) {
                    $newRows[$key]['reports'][$k]['data']['margin'] = BC::mul(BC::div($numerator, $newRows[$key]['reports'][$k]['data']['batchCost'], 10), 100, 2);
                } else {
                    $newRows[$key]['reports'][$k]['data']['margin'] = 0;
                }
            }
        }

        // Создаем новый массив с ключами-артикулами
        $newRowsWithArticleKeys = [];
        foreach ($newRows as $item) {
            $article = $item['meta']['article'];
            $newRowsWithArticleKeys[$article] = $item;
        }

        return response()->json(['data' => $newRowsWithArticleKeys], 200);
    }
}
