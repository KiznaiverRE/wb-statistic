<?php


namespace App\Services\Excel\Strategies;


use App\Jobs\ProcessExcelFile;
use App\Services\Excel\ExcelParsingService;
use BCMathExtended\BC;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdvertisingDocumentStrategy implements DocumentProcessingStrategy
{
    public function process(array $data, int $userId): array
    {
        try {
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

            foreach ($newRows as $index => $item) {
                $article = $item['meta']['wb_article'];

                foreach ($data['rows'] as $key => $value) {
//                $unixTimestamp = ((int)$value['Дата списания'] - 25569) * 86400;
//                $date = date('d.m.Y', $unixTimestamp);

                    list($startDate, $endDate) = $this->getWeekRange($value['Дата списания']);
//                if (isset($item['reports']["$startDate-$endDate"])){
//                    $week = "$startDate-$endDate";
//                }else{
//                    continue;
//                }
                    $week = "$startDate-$endDate";




                    if ($item['meta']['wb_article'] == $value['арт']) {
//                    if (!isset($item['reports'][$week])) {
//                        $item['reports'][$week] = [];
//                    }
//                    if (!isset($item['reports'][$week]['data'])) {
//                        $item['reports'][$week]['data'] = [];
//                    }

                        if (isset($item['reports'][$week])){
                            $item['reports'][$week]['data']['ads'] = BC::add($item['reports'][$week]['data']['ads'], $value['Сумма'], 2);
                        }

                        // Обновляем элемент в $newRows
                        $newRows[$index] = $item;
                    }
                }
            }
            foreach ($newRows as $key => $item) {
                foreach ($item['reports'] as $k => $v) {
                    if ($v['data']['storage'] == 0) {
                        $newRows[$key]['reports'][$k]['data']['creditedToAccount'] = BC::sub($v['data']['creditedToAccount'], $v['data']['storage'], 2);
                        $newRows[$key]['reports'][$k]['data']['creditedToAccount'] = BC::sub($v['data']['creditedToAccount'], $v['data']['ads'], 2);
                    } else {
                        $newRows[$key]['reports'][$k]['data']['creditedToAccount'] = BC::sub($v['data']['creditedToAccount'], $v['data']['ads'], 2);
                    }

                    $newRows[$key]['reports'][$k]['data']['profit'] = BC::sub($newRows[$key]['reports'][$k]['data']['creditedToAccount'], $newRows[$key]['reports'][$k]['data']['batchCost'], 2);

                    if ($v['data']['profit'] <= 0) {
                        $newRows[$key]['reports'][$k]['data']['profitPercent'] = 0;
                    } else {
                        $newRows[$key]['reports'][$k]['data']['profitPercent'] = BC::div($newRows[$key]['reports'][$k]['data']['profit'], (BC::div($newRows[$key]['reports'][$k]['data']['transfers'], 100, 10)), 2);
                    }

                    if ($newRows[$key]['reports'][$k]['data']['creditedToAccount'] - $newRows[$key]['reports'][$k]['data']['batchCost'] <= 0) {
                        $newRows[$key]['reports'][$k]['data']['margin'] = 0;
                    } else {
                        $numerator = $newRows[$key]['reports'][$k]['data']['creditedToAccount'] - $newRows[$key]['reports'][$k]['data']['batchCost'];
                        $denominator = BC::mul($newRows[$key]['reports'][$k]['data']['batchCost'], 100, 10);

                        if ($numerator != 0 && $newRows[$key]['reports'][$k]['data']['batchCost'] != 0) {
                            $newRows[$key]['reports'][$k]['data']['margin'] = BC::mul(BC::div($numerator, $newRows[$key]['reports'][$k]['data']['batchCost'], 10), 100, 2);
                        } else {
                            $newRows[$key]['reports'][$k]['data']['margin'] = 0;
                        }
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


        } catch (\Exception $e) {
            Log::error('General error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }
}
