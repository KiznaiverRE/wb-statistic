<?php


namespace App\Services\Excel\Strategies;


use App\Models\Header;
use App\Models\Product;
use App\Models\ProductCategory;
use BCMathExtended\BC;

class FinancialDocumentStrategy implements DocumentProcessingStrategy
{
    public function process(array $data, int $userId): array
    {
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
}
