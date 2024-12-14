<?php

namespace App\Jobs;

use App\Events\ExcelProcessed;
use App\Models\Header;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\Excel\ExcelHeaderValidatorService;
use App\Services\Excel\ExcelParsingService;
use BCMathExtended\BC;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessExcelFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $filePath;
    protected $userId;
    protected $fileHash;
    protected $fileType;
    protected $newRows;
    public $timeout = 220;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($filePath, $userId, $fileHash, $fileType, $newRows = null)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->fileHash = $fileHash;
        $this->fileType = $fileType;
        $this->newRows = $newRows;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info('=== Начало обработки файла ===');
            $parsedData = ExcelParsingService::getDataFromExcel(storage_path('app/' . $this->filePath));



            $processedData = match ($this->fileType) {
                'finance' => $this->processFinData($parsedData),
                'ads' => $this->processAdsData($parsedData, $this->newRows),
                'storage' => $this->processStorageData($parsedData, $this->newRows),
            };

            $headerValidator = app(ExcelHeaderValidatorService::class);
            $missingHeaders = $headerValidator->validateHeaders($parsedData['headers'], $this->fileType);

            if ($missingHeaders !== true) {
                Log::error('Missing headers in the file', [
                    'missing_headers' => $missingHeaders,
                ]);
//                return;
            }
//            Log::info(json_encode($processedData));
            Redis::set("excel_data:{$this->fileHash}", json_encode($processedData));
            Log::info('=== Задача успешно завершена ===');
        } catch (\Exception $e) {
            Log::error('Error in ProcessExcelFile job: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        } finally {
            Log::info('Event broadcasting. Hash: '.$this->fileHash);
            broadcast(new ExcelProcessed($this->fileHash));
            Log::info('Event broadcasted successfully.');
        }
    }

    private function processFinData($data){
        $groupedData = [];
        $finances = [];

        foreach ($data['rows'] as $key => $row) {
            $item = $row['Артикул поставщика'];
            $product = Product::with(['sellerArticles', 'costPrices'])
                ->where('user_id', $this->userId)
                ->where('sellers_article', $item)
                ->first();


            if (isset($product)) {
                $date = $row['Дата продажи'];
                $weekRange = $this->getWeekRange($date);
                if ($weekRange){
                    $groupedData[$item][$key]['Предмет'] = $row['Предмет'];
                    $groupedData[$item][$key]['Артикул поставщика'] = $item;
                    $groupedData[$item][$key]['Код номенклатуры'] = $row['Код номенклатуры'];
                    list($startDate, $endDate) = $this->getWeekRange($date);
                    $groupedData[$item][$key]['Неделя'] = "$startDate-$endDate";

                    $groupedData[$item][$key]['Тип документа'] = $row['Тип документа'];
                    $groupedData[$item][$key]['Дата продажи'] = $date;
                    $groupedData[$item][$key]['К перечислению'] = str_replace(',', '.', $row['К перечислению Продавцу за реализованный Товар']);
//                    $groupedData[$item][$key]['К перечислению'] = floor((float)$groupedData[$item][$key]['К перечислению'] *100) / 100;
                    $groupedData[$item][$key]['Виды логистики, штрафов и доплат'] = $row['Виды логистики, штрафов и доплат'];
                    $groupedData[$item][$key]['Общая сумма штрафов'] = $row['Общая сумма штрафов'];
                    $groupedData[$item][$key]['Обоснование для оплаты'] = $row['Обоснование для оплаты'];

                    $groupedData[$item][$key]['Услуги по доставке товара покупателю'] =+
                    BC::div(BC::mul(str_replace(',', '.', $row['Услуги по доставке товара покупателю']), 100, 2), 100, 2)
                    ;

                    if ($product->category_id){
                        $category = ProductCategory::where('id', $product->category_id)->first();
                        $groupedData[$item][$key]['Категория'] = $category->title;
                    }else{
                        $groupedData[$item][$key]['Категория'] = NULL;
                    }

                    foreach ($product->costPrices as $costPrice) {
                        $header = Header::find($costPrice->header_id);
                        $groupedData[$item][$key]['costPrices'][$header->value] = $costPrice->price;
                    }

                    $dates = array_keys($groupedData[$item][$key]['costPrices']);
                    usort($dates, function($a, $b) {
                        return strtotime($a) - strtotime($b);
                    });

                    $closestDate = null;
                    foreach ($dates as $costDate) {
                        if (strtotime($costDate) <= strtotime($date)) {
                            $closestDate = $costDate;
                        } else {
                            break;
                        }
                    }

                    if ($closestDate === null && !empty($dates)) {
                        $closestDate = end($dates);
                    }

                    $cost = $closestDate ? $groupedData[$item][$key]['costPrices'][$closestDate] : null;
                    $groupedData[$item][$key]['Себестоимость товара'] = $cost;
                }
            }
        }

        foreach ($groupedData as $article => $row) {
            foreach ($row as $key => $value) {
                // Инициализация массива 'meta'
                $arr[$article]['meta'] = [
                    'name' => $value['Предмет'],
                    'article' => $value['Артикул поставщика'],
                    'wb_article' => $value['Код номенклатуры'],
                    'category' => $value['Категория'],
                ];

                // Инициализация массива 'reports' и 'rows'
                if (!isset($arr[$article]['reports'][$value['Неделя']])) {
                    $arr[$article]['reports'][$value['Неделя']] = [
                        'rows' => [],
                        'data' => [
                            'summLogistic' => 0,
                            'cost' => $value['Себестоимость товара'],
                            'returns' => 0,
                            'ordersCount' => 0,
                            'transfers' => 0,
                            'logisticPercent' => 0,
                            'batchCost' => 0,
                            'logistic' => 0,
                            'averageCheck' => 0,
                            'ads' => 0,
                            'ddr' => 0,
                            'fines' => 0,
                            'creditedToAccount' => 0,
                            'profit' => 0,
                            'profitPercent' => 0,
                            'margin' => 0,
                            'storage' => 0
                        ],
                    ];
                }

                // Добавление данных в массив 'rows'
                $arr[$article]['reports'][$value['Неделя']]['rows'][$key] = [
                    'documentType' => $value['Тип документа'],
                    'date' => $value['Дата продажи'],
                    'transfer' => $value['К перечислению'],
                    'return' => $value['Обоснование для оплаты'],
                    'logistic' => $value['Обоснование для оплаты'],
                    'fine' => $value['Общая сумма штрафов'],
                    'service' => $value['Услуги по доставке товара покупателю'],
                    'prices' => $value['costPrices'],
                ];
            }

            // Сбор данных за неделю
            foreach ($arr[$article]['reports'] as $week => $weekData) {
                foreach ($weekData['rows'] as $key => $data) {

                    if ($data['return'] === 'Продажа' || strpos($data['return'], 'Корректная')) {
                        $arr[$article]['reports'][$week]['data']['ordersCount'] += 1;
                        $arr[$article]['reports'][$week]['data']['logistic'] = number_format($arr[$article]['reports'][$week]['data']['logistic'], 2, '.');
                    }

                    if ($data['return'] === 'Продажа' || strpos($data['return'], 'Корректная') || $data['return'] === 'Корректировка эквайринга' || $data['return'] === 'Коррекция продаж'){
                        $arr[$article]['reports'][$week]['data']['transfers'] = BC::add(
                            $arr[$article]['reports'][$week]['data']['transfers'],
                            $data['transfer'],
                            2
                        );
                    }


                    if ($data['logistic'] === 'Логистика') {
                        $arr[$article]['reports'][$week]['data']['summLogistic'] += 1;
                    }

                    if (strpos($data['return'], 'Возврат') !== false || strpos($data['return'], 'Сторно') !== false){
                        $arr[$article]['reports'][$week]['data']['returns'] += 1;
                        $arr[$article]['reports'][$week]['data']['transfers'] = BC::sub(
                            $arr[$article]['reports'][$week]['data']['transfers'],
                            $data['transfer'],
                            2
                        );
                    }

                    //Штрафы
                    $arr[$article]['reports'][$week]['data']['fines'] = BC::add($arr[$article]['reports'][$week]['data']['fines'], $data['fine'], 2);

                    //Логистика
                    $arr[$article]['reports'][$week]['data']['logistic'] = BC::add($arr[$article]['reports'][$week]['data']['logistic'], $data['service'], 2);
                }
            }


            foreach ($arr[$article]['reports'] as $week => $weekData){
//                    $transfers = floatval($weekData['data']['transfers']);
                $transfers = $weekData['data']['transfers'];

                if ($transfers == 0) {
                    $arr[$article]['reports'][$week]['data']['logisticPercent'] = 0;
                    $arr[$article]['reports'][$week]['data']['averageCheck'] = 0;
                } else {
//                    if ($weekData['data']['logistic'] <= 0){
//                        Log::info('Logistic: '.$weekData['data']['logistic'] . '|' . 'article: ' . $article);
//                    }
//                    if ($transfers <= 0){
//                        Log::info('$transfers: '.$transfers . '|' . 'article: ' . $article);
//                    }

//                        Log::info('========================================');
//                        Log::info('article: ' . $article);
//                        Log::info('logistic: '.$weekData['data']['logistic']);
//                        Log::info('$transfers: '.$transfers);
//                        Log::info('$transfers\100: '.BC::div($transfers, 100, 10));
//                        Log::info('logistic($transfers\100): '.BC::div($weekData['data']['logistic'], BC::div($transfers, 100, 10)));
//                        Log::info('========================================');

                    $arr[$article]['reports'][$week]['data']['logisticPercent'] =
                        BC::div($weekData['data']['logistic'], BC::div($transfers, 100, 10), 2);

//                            BC::div($weekData['data']['logistic'], BC::div($transfers, 100, 2), 2);
                    if ($weekData['data']['ordersCount'] > 0){
                        $arr[$article]['reports'][$week]['data']['averageCheck'] = BC::div($transfers, $weekData['data']['ordersCount'], 2);
                    }else{
                        $arr[$article]['reports'][$week]['data']['averageCheck'] = 0;
                    }

//                    Log::info('logisticPercent: '.$arr[$article]['reports'][$week]['data']['logisticPercent']);
                }

                //Себестоимость партии
                $arr[$article]['reports'][$week]['data']['batchCost'] =
                    BC::mul((BC::sub($weekData['data']['ordersCount'], $weekData['data']['returns'], 2)), $weekData['data']['cost'], 2);




                //Придёт на счёт
                $arr[$article]['reports'][$week]['data']['creditedToAccount'] =
                    BC::sub($transfers, $weekData['data']['logistic'], 2);


                $arr[$article]['reports'][$week]['data']['creditedToAccount'] =
                    BC::sub($arr[$article]['reports'][$week]['data']['creditedToAccount'], $weekData['data']['storage'], 2);
                $arr[$article]['reports'][$week]['data']['creditedToAccount'] =
                    BC::sub($arr[$article]['reports'][$week]['data']['creditedToAccount'], $weekData['data']['ads'], 2);
                $arr[$article]['reports'][$week]['data']['creditedToAccount'] =
                    BC::sub($arr[$article]['reports'][$week]['data']['creditedToAccount'], $weekData['data']['fines'], 2);

                //Прибыль
                $arr[$article]['reports'][$week]['data']['profit'] =
                    BC::sub($arr[$article]['reports'][$week]['data']['creditedToAccount'], $arr[$article]['reports'][$week]['data']['batchCost'], 2);



                //Процент прибыли
                $profit = $arr[$article]['reports'][$week]['data']['profit'];
                if ($profit <= 0){
                    $arr[$article]['reports'][$week]['data']['profitPercent'] = 0;
                }else{
//                        Log::info($transfers . '$transfers\100 = ' . BC::div($transfers, 100, 2));
                    $arr[$article]['reports'][$week]['data']['profitPercent'] =
                        BC::div($profit, (BC::div($transfers, 100, 10)));
                }



                $creditedToAccount = $arr[$article]['reports'][$week]['data']['creditedToAccount'];
                $batchCost = $arr[$article]['reports'][$week]['data']['batchCost'];


                $numerator = $creditedToAccount - $batchCost;
                $denominator = BC::mul($batchCost, 100, 2);

                //Наценка после расходов
                if ($batchCost != 0){
                    $arr[$article]['reports'][$week]['data']['margin'] = BC::mul(
                        BC::div($numerator, $batchCost, 10), 100, 2);
                }else{
                    $arr[$article]['reports'][$week]['data']['margin'] = 0;
                }
            }
        }

        return $arr;
    }

    private function processAdsData($data, $newRows){
            // Проверяем, есть ли newRows и является ли он валидным JSON
//            if (!empty($newRows)) {
//                $newRows = json_decode($newRows, true);
//
//                // Проверяем, успешно ли распарсен JSON
//                if (json_last_error() !== JSON_ERROR_NONE) {
//                    return response()->json(['error' => 'Invalid JSON format for newRows'], 400);
//                }
//            } else {
//                $newRows = [];
//            }

//        Log::info(json_encode($newRows));

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




                    if ((int)$item['meta']['wb_article'] == (int)$value['арт']) {

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

//            Log::info($newRows);
//            Log::info($newRowsWithArticleKeys);

            return $newRowsWithArticleKeys;
    }

    private function processStorageData($data, $newRows){
        // Проверяем, есть ли newRows и является ли он валидным JSON
//        if (!empty($newRows)) {
//            $newRows = json_decode($newRows, true);
//
//            // Проверяем, успешно ли распарсен JSON
//            if (json_last_error() !== JSON_ERROR_NONE) {
//                return response()->json(['error' => 'Invalid JSON format for newRows'], 400);
//            }
//        } else {
//            $newRows = [];
//        }

//        return response()->json(['newRows' => $newRows], 200);
//        Log::info('1111++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++=');
//        Log::info($newRows);
//        Log::info('==================================================================================================================================================================================================================');

        foreach ($newRows as $index => $item) {
            $article = $item['meta']['article'];

            foreach ($data['rows'] as $key => $value) {
                list($startDate, $endDate) = $this->getWeekRange($value['Дата']);
                $week = "$startDate-$endDate";

                Log::info('$article - '.$article);
                Log::info('Артикул продавца - '.$value['Артикул продавца']);
                Log::info($week);
//                Log::info($item['reports'][$week]);
                if (array_key_exists($week, $item['reports'])) {
                    Log::info('Ключ существует!');
                }


                if (array_key_exists($week, $item['reports'])) {
                    Log::info(11111111);
                    if ((int)$article == (int)$value['Артикул продавца'] || (int)$item['meta']['wb_article'] == (int)$value['Артикул продавца']) {
                        Log::info(222222);
//                        if (!isset($item['reports'][$week])) {
//                            $item['reports'][$week] = [];
//                        }
//                        if (!isset($item['reports'][$week]['data'])) {
//                            $item['reports'][$week]['data'] = [];
//                        }
//
//                        if (!isset($item['reports'][$week]['data']['storage'])){
//                            $item['reports'][$week]['data']['storage'] = 0;
//                        }


                        $item['reports'][$week]['data']['storage'] = BC::add($item['reports'][$week]['data']['storage'], $value['Сумма хранения, руб'], 2);
                        Log::info('START-BC::add($item[storage]: '.$item['reports'][$week]['data']['storage']);
                        Log::info('BC::add($value[Сумма хранения, руб]: '.$value['Сумма хранения, руб']);
                        Log::info('END-BC::add($item[storage]: '.$item['reports'][$week]['data']['storage']);

                        // Обновляем элемент в $newRows
                        $newRows[$index] = $item;

                    }

                    if (!isset($newRows[$index]['reports'][$week]['data']['creditedToAccount'])){
                        $newRows[$index]['reports'][$week]['data']['creditedToAccount'] = 0;
                    }
                }

            }


        }


//        Log::info('222++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++=');
//        Log::info($newRows);
//        Log::info('==================================================================================================================================================================================================================');

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

        return $newRowsWithArticleKeys;
    }


    private function getWeekRange($dateOrNumber) {
        try {
            $unixTimestamp = null;

            $parts = explode(' ', $dateOrNumber);

            $dateOrNumber = $parts[0];

            if (is_numeric($dateOrNumber)) {
                $unixTimestamp = $this->convertExcelDateToUnixTimestamp($dateOrNumber);
            } else {
                $dateFormats = ['d.m.Y', 'Y-m-d'];
                foreach ($dateFormats as $format) {
                    if ($this->isValidDate($dateOrNumber, $format)) {
                        $unixTimestamp = strtotime($dateOrNumber);
                        if ($unixTimestamp === false) {
                            throw new \Exception("Invalid date format: $dateOrNumber");
                        }
                        break;
                    }
                }
                if ($unixTimestamp === null) {
                    throw new \Exception("Invalid date format: $dateOrNumber");
                }
            }

            $date = date('d.m.Y', $unixTimestamp);

            $dateTime = new DateTime($date);
            $dayOfWeek = $dateTime->format('N');
            $startOfWeek = clone $dateTime;
            $startOfWeek->modify('-' . ($dayOfWeek - 1) . ' days');
            $endOfWeek = clone $startOfWeek;
            $endOfWeek->modify('+6 days');

            $startDate = $startOfWeek->format('d.m.Y');
            $endDate = $endOfWeek->format('d.m.Y');

            return [$startDate, $endDate];
        }catch (\Exception $e){
            // Логирование ошибки или вывод сообщения об ошибке
            error_log($e->getMessage());
            // Возврат значения по умолчанию или null, если это допустимо
            return null;
        }

    }
    private function isValidDate($date, $format = 'd.m.Y') {
        $dateTime = DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }

    private function convertExcelDateToUnixTimestamp($excelDate) {
        return ((int)$excelDate - 25569) * 86400;
    }
}
