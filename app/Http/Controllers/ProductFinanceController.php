<?php


namespace App\Http\Controllers;


use App\Exports\BaseExport;
use App\Models\CategoryFinalReport;
use App\Models\FinancialMetaData;
use App\Models\FinancialReport;
use App\Models\Header;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\Date\DateFilterService;
use App\Services\DateFormatService;
use App\Services\Excel\ExcelHeaderValidatorService;
use App\Services\Excel\ExcelParsingService;
use App\Services\Report\FinalReportService;
use App\Support\MathHelper;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use BCMathExtended\BC;
use Maatwebsite\Excel\Facades\Excel;
use PhpParser\Node\Stmt\If_;
use function Symfony\Component\Routing\Loader\load;

class ProductFinanceController extends Controller
{
    protected $dateFormatService;
    protected $dateFilterService;
    protected $bcmath;
    protected ExcelHeaderValidatorService $headerValidator;

    public function __construct(DateFormatService $dateFormatService, DateFilterService $dateFilterService, ExcelHeaderValidatorService $headerValidator){
        $this->dateFormatService = $dateFormatService;
        $this->dateFilterService = $dateFilterService;
        $this->headerValidator = $headerValidator;
    }

    public function uploadStat(Request $request) {
        try {
            $user = Auth::user();

            if (!$request->hasFile('file')) {
                return response()->json(['error' => 'No file uploaded'], 400);
            }

            $file = $request->file('file');
            $data = ExcelParsingService::getDataFromExcel($file);

            $missingHeaders = $this->headerValidator->validateHeaders($data['headers'], 'finance');

            if ($missingHeaders !== true){
                return response()->json(['error', 'Failed to process the spreadsheet file.'], 500);
            }

            $groupedData = [];
            $finances = [];


            Log::info('======================================================');



            foreach ($data['rows'] as $key => $row) {
                $item = $row['Артикул поставщика'];
                $product = Product::with(['sellerArticles', 'costPrices'])
                    ->where('user_id', $user->id)
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
                        if ($weekData['data']['logistic'] <= 0){
                            Log::info('Logistic: '.$weekData['data']['logistic'] . '|' . 'article: ' . $article);
                        }
                        if ($transfers <= 0){
                            Log::info('$transfers: '.$transfers . '|' . 'article: ' . $article);
                        }

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

                        Log::info('logisticPercent: '.$arr[$article]['reports'][$week]['data']['logisticPercent']);
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


            return response()->json($arr);

        } catch (\Exception $e) {
            Log::error('General error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    public function exportStatData(Request $request){
        $data = $request->all();

        function decode($payload) {
            $decoded = json_decode('["'.$payload.'"]');
            return array_pop($decoded);
        }

        $values = [
            ['к перечеслению', $data['transfers']],
            ['количество заказов', $data['orders_count']],
            ['средний чек', $data['average_check']],
            ['закупочня цена', $data['purchase_cost']],
            ['логистика', $data['logistic_cost']],
            ['логистика %', $data['logistic_percent']],
            ['хранение', $data['storage_cost']],
            ['реклама', $data['advertising_cost']],
            ['ДРР %', $data['ddr_percent']],
            ['придет на счет', $data['credited_to_account']],
            ['себес партии', $data['batch_cost']],
            ['прибыль', $data['profit']],
            ['прибыль %', $data['profit_percent']],
            ['наценка после расходов', $data['margin_after_expenses']],
            ['количество возвратов', $data['returns_count']],
        ];



        $customHeading = [' ', $data['report_date']];

//        $arr = array_combine($values2, $data);
//        array_unshift($data, $values);


        return Excel::download(new BaseExport($values, $customHeading), "fin-stat " . rand(10, 20) . ".xlsx");
    }

    public function uploadAds(Request $request){
        try {
        $user = Auth::user();

        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');
        $data = ExcelParsingService::getDataFromExcel($file);

        $missingHeaders = $this->headerValidator->validateHeaders($data['headers'], 'ads');

        if ($missingHeaders !== true){
            return response()->json(['error', 'Failed to process the spreadsheet filesssss.'], 500);
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
                    $newRows[$key]['reports'][$k]['data']['profitPercent'] = BC::div($newRows[$key]['reports'][$k]['data']['profit'], (BC::div($newRows[$key]['reports'][$k]['data']['transfers'], 100, 2)), 2);
                }

                if ($newRows[$key]['reports'][$k]['data']['creditedToAccount'] - $newRows[$key]['reports'][$k]['data']['batchCost'] <= 0) {
                    $newRows[$key]['reports'][$k]['data']['margin'] = 0;
                } else {
                    $numerator = $newRows[$key]['reports'][$k]['data']['creditedToAccount'] - $newRows[$key]['reports'][$k]['data']['batchCost'];
                    $denominator = BC::mul($newRows[$key]['reports'][$k]['data']['batchCost'], 100, 2);

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

    public function uploadStorage(Request $request){
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
                    $newRows[$key]['reports'][$k]['data']['profitPercent'] = BC::div($newRows[$key]['reports'][$k]['data']['profit'], (BC::div($newRows[$key]['reports'][$k]['data']['transfers'], 100, 2)), 2);
                }



                    $numerator = $newRows[$key]['reports'][$k]['data']['creditedToAccount'] - $newRows[$key]['reports'][$k]['data']['batchCost'];
                    $denominator = BC::mul($newRows[$key]['reports'][$k]['data']['batchCost'], 100, 2);

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

    public function getStatData(Request $request){
        $user = Auth::user();
        $page = $request->input('page');
        $search = $request->input('search', '');

        $query = FinancialMetaData::with('financialReports')
            ->where('user_id', $user->id);

        if (!empty($search)){
            $query->where('seller_article', 'like', '%' . $search . '%')
                ->orWhere('name', 'like', '%' . $search . '%');
        }

        $data = $query->paginate(30, ['*'], 'page', $page);


        return response()->json([
            'data' => $data,
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'next_page_url' => $data->nextPageUrl(),
                'prev_page_url' => $data->previousPageUrl(),
            ]
        ], 200);
    }

    public function saveStatData(Request $request){
        $user = Auth::user();
        $data = $request->all();

        DB::beginTransaction();

        try {
            foreach ($data as $key => $value){
                $finMetaData = FinancialMetaData::updateOrCreate([
                    'seller_article' => $value['meta']['article']
                ], [
                    'user_id' => $user->id,
                    'category' => $value['meta']['category'],
                    'name' => $value['meta']['name'],
                    'wb_article' => $value['meta']['wb_article'],
                    'seller_article' => $value['meta']['article']
                ]);

                foreach ($value['reports'] as $k => $v){
                    $financeReport = FinancialReport::updateOrCreate([
                        'meta_data_id' => $finMetaData->id,
                        'report_date' => $k
                    ], [
                        'user_id' => $user->id,
                        'meta_data_id' => $finMetaData->id,
                        'report_date' => $k,
                        'transfers' => $v['data']['transfers'],
                        'orders_count' => $v['data']['ordersCount'],
                        'average_check' => $v['data']['averageCheck'],
                        'purchase_cost' => $v['data']['cost'],
                        'logistic_cost' => $v['data']['logistic'],
                        'logistic_percent' => $v['data']['logisticPercent'],
                        'storage_cost' => $v['data']['storage'],
                        'advertising_cost' => $v['data']['ads'],
                        'ddr_percent' => $v['data']['ddr'],
                        'fine' => $v['data']['fines'],
                        'credited_to_account' => $v['data']['creditedToAccount'],
                        'batch_cost' => $v['data']['batchCost'],
                        'profit' => $v['data']['profit'],
                        'profit_percent' => $v['data']['profitPercent'],
                        'margin_after_expenses' => $v['data']['margin'],
                        'returns_count' => $v['data']['returns'],
                    ]);

                }
            }
            $bc = new BC();
            $finalReportService = new FinalReportService($bc);
            FinalReportController::categoryReport($finalReportService);

            DB::commit();
            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('General error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    public function deleteStatData(Request $request){
        $user = Auth::user();
        $data = $request->all();

        try {
            DB::beginTransaction();
            foreach ($data as $key => $value){

                $metaId = $value['id'];
                FinancialReport::where('meta_data_id', $metaId)->chunk(300, function ($reports){
                    foreach ($reports as $report){
                        $report->delete();
                    }
                });
                FinancialMetaData::findOrFail($metaId)->delete();


            }

//            CategoryFinalReport::truncate();

            CategoryFinalReport::all()->each(function ($report) {
                // Выполните здесь дополнительные действия перед удалением, если необходимо
                $report->delete();
            });

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Log::error('Deleting error: ' . $e->getMessage(), [
                'line' => $e->getLine(),
            ]);

            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
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

    protected function saveReport(Request $request){
        $reportData = $request->all();

        $report = FinancialReport::where('id', $reportData['id'])->first();

        $report->advertising_cost = $reportData['advertising_cost'];


        //Придёт на счёт
        $report->credited_to_account =
            BC::sub($report->transfers, $report->logistic_cost, 2);
        $report->credited_to_account =
            BC::sub($report->credited_to_account, $report->storage_cost, 2);
        $report->credited_to_account =
            BC::sub($report->credited_to_account, $report->advertising_cost, 2);

        //Прибыль
        $report->profit =
            BC::sub($report->credited_to_account, $report->batch_cost, 2);


        //Процент прибыли
        if ($report->profit <= 0){
            $report->profit_percent = 0;
        }else{
            $report->profit_percent =
                BC::div($report->profit, (BC::div($report->transfers, 100, 2)), 2);
        }

        $creditedToAccount = $report->credited_to_account;
        $batchCost = $report->batch_cost;


        $numerator = $creditedToAccount - $batchCost;
        $denominator = BC::mul($batchCost, 100, 2);

        //Наценка после расходов
        if ($batchCost != 0){
            $report->margin_after_expenses = BC::mul(
                BC::div($numerator, $batchCost, 10), 100, 2);
        }else{
            $report->margin_after_expenses = 0;
        }




        $report->save();

        return response()->json($report, 200);
    }
}
