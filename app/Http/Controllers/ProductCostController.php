<?php

namespace App\Http\Controllers;

use App\Imports\BaseImport;
use App\Models\CostPrice;
use App\Models\Header;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SellerArticle;
use App\Models\WbArticle;
use App\Services\Date\DateFilterService;
use App\Services\DateFormatService;
use App\Services\Excel\ExcelParsingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Shared\Date as Date;
use Illuminate\Support\Facades\DB;


class ProductCostController extends Controller
{
    protected $dateFormatService;
    protected $dateFilterService;

    public function __construct(DateFormatService $dateFormatService, DateFilterService $dateFilterService){
        $this->dateFormatService = $dateFormatService;
        $this->dateFilterService = $dateFilterService;
    }

    // Метод для обработки данных из Excel
    public function uploadExcelData(Request $request)
    {
        try {
            // Проверка наличия файла
            if (!$request->hasFile('file')) {
                return response()->json(['error' => 'No file uploaded'], 400);
            }

            // Получение загруженного файла
            $file = $request->file('file');

            $excelData = ExcelParsingService::getDataFromExcel($file);


            $rows = [];

            foreach ($excelData['headers'] as $k => $v) {
                $data['headers'][$k] = mb_ucfirst(mb_strtolower($v));
            }

//            Log::info($data['headers']);

//            $data['headers'] = $excelData['headers'];


            foreach ($excelData['rows'] as $row) {
                $lowercaseRow = [];
                foreach ($row as $k => $v) {
                    $lowercaseRow[mb_ucfirst(mb_strtolower($k))] = $v;
                }

                $data['rows'][$lowercaseRow['Артикул продавца']]['meta'] = [
                    'sellers_article' => $lowercaseRow['Артикул продавца'],
                    'wb_article' => $lowercaseRow['Код номенклатуры'] ?? null,
                    'category' => $lowercaseRow['Категория'] ?? null,
                    'title' => $lowercaseRow['Название'] ?? null,
                ];

                $data['rows'][$lowercaseRow['Артикул продавца']]['prices'] = array_slice($lowercaseRow, 4);
//                if (count($lowercaseRow) > 4){
//
//                }else{
//                    $data['rows'][$lowercaseRow['артикул продавца']]['prices'] = [];
//                }

            }

//            Log::info(json_encode($data));


            return response()->json($data);

        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            Log::error('Spreadsheet error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process the spreadsheet file.'], 500);
        } catch (\Exception $e) {
            Log::error('General error: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    public function getExcelData(Request $request){
        $date = $request->input('date');
        $user = Auth::user();
        $page = $request->input('page');
        $search = $request->input('search', '');

        // Получение всех заголовков
        $headers = Header::all();

        $headerValues = $headers->pluck('value');
        $headersById = $headers->keyBy('id');

        $query = Product::with(['category', 'costPrices'])
            ->where('user_id', $user->id);



        if (!empty($search)){
            $query->where('sellers_article', 'like', '%' . $search . '%')
                ->orWhere('title', 'like', '%' . $search . '%')
                ->orWhereHas('category', function ($query) use ($search){
                   $query->where('title', 'like', '%' . $search . '%');
                });
        }

        // Получение всех продуктов с их категориями, WbArticle, SellerArticle и CostPrice
        $products = $query->paginate(30, ['*'], 'page', $page);



        // Подготовка данных для отправки на фронтенд
        $data = [];
        $data['headers'] = $headerValues;


        foreach ($products as $key => $product) {
            $row = [
                'sellers_article' => $product->sellers_article,
                'wb_article' => $product->wb_article ?? null,
                'category' => $product->category->title ?? null,
                'title' => $product->title ?? null,
            ];

            $assocArr = [
                'id' => $product->id,
                'meta' => $row,
                'prices' => []
            ];

            // Инициализация ключа 'prices'
            $row['prices'] = [];

            // Добавление цен в массив
            if (!$product->costPrices->isEmpty()){
                foreach ($product->costPrices as $costPrice) {
                    $header = $headersById->get($costPrice->header_id);

                    if ($header) {
                        $row['prices'][$header->value] = $costPrice->price;
                    }
                }
            }


            // Инициализация массива $assocArr для каждого продукта
            $assocArr['prices'] = $row['prices'];

            $data['rows'][$product->sellers_article] = $assocArr;
        }

        return response()->json([
            'data' => $data,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'next_page_url' => $products->nextPageUrl(),
                'prev_page_url' => $products->previousPageUrl(),
            ]
        ]);
    }

    public function saveExcelData(Request $request){
        $data = $request->all();
        $user = Auth::user();
        $items = [];



        DB::transaction(function () use ($data, $user, $items){
            if (isset($data['headers'])){
                foreach ($data['headers'] as $header) {
                    $headerRecord = Header::firstOrCreate(['value' => $header]);
                    $headerIds[] = $headerRecord->id;
                }
            }

            foreach ($data['rows'] as $key => $item){
                Log::info($item['meta']);
                $productData = [
                    'title' => $item['meta']['title'] ? $item['meta']['title'] : NULL,
                    'sellers_article' => (int)$item['meta']['sellers_article'],
                    'wb_article' => (int)$item['meta']['wb_article'] ? : NULL,
                    'category_id' => isset($item['meta']['category']) ? ProductCategory::firstOrCreate(['title' => $item['meta']['category']])->id : NULL,
                    'user_id' => $user->id
                ];

                if (isset($item['meta']['wb_article']) && !empty($item['meta']['wb_article'])) {
                    $productData['wb_article'] = (int)$item['meta']['wb_article'];
                }

                $product = Product::updateOrCreate(
                    [
                        'sellers_article' => $productData['sellers_article'],
                    ],
                    $productData
                );

                foreach ($item['prices'] as $key => $value) {
                    // Ищем запись в таблице Headers
                    $header = Header::where('value', $key)->first();

                    // Если запись не найдена, создаем новую запись
                    if (!$header) {
                        $header = Header::create(['value' => $key]);
                    }

                    // Преобразуем значение в число с плавающей точкой
                    $price = floatval($value);

                    // Обновляем или создаем запись в таблице CostPrices
                    CostPrice::updateOrCreate(
                        [
                            'product_id' => $product->id, // Атрибуты для поиска
                            'header_id' => $header->id    // Атрибуты для поиска
                        ],
                        [
                            'price' => $price             // Атрибуты для обновления/создания
                        ]
                    );

                    // Добавляем цену в массив items для дальнейшего использования
                    $items[$key][] = $price;
                }
            }
        });

        return response()->json(['message' => 'Данные сохранены.', 'prices' => $items]);
    }


    public function saveProduct(Request $request){
        $user = Auth::user();
        $data = $request->all();

        $product = Product::with(['category', 'wbArticles', 'sellerArticles', 'costPrices'])
            ->where('user_id', $user->id)
            ->where('id', $data['product']['id'])
            ->first();

//        $categoryData = $data['product'];
        $category = ProductCategory::updateOrCreate(
            ['title' => $data['product']['meta']['category']], // Уникальный ключ для поиска
        );
//        Log::info(json_encode($data));
//        Log::info(json_encode($product));
//        Log::info(json_encode($category));
        $product->category()->associate($category);

        $product->sellers_article = $data['product']['meta']['sellers_article'];
        $product->wb_article = $data['product']['meta']['wb_article'];
        $product->title = $data['product']['meta']['title'];

        $product->save();

        // Получаем даты из цен продукта
        $headerDates = array_keys($data['product']['prices']);

        // Получаем заголовки, у которых значение равно датам из цен продукта
        $headers = Header::whereIn('value', $headerDates)->get();

        // Создаем словарь, сопоставляющий каждой дате соответствующий заголовок
        $headerValuesByDate = $headers->pluck('value', 'id')->toArray();

        // Получаем все цены продукта сразу с помощью отношения
        $costPrices = $product->costPrices()->whereIn('header_id', $headers->pluck('id'))->get();

        // Обновляем цены на основе данных из $data['product']['prices']
        $costPrices->each(function ($costPrice) use ($data, $headerValuesByDate) {
            $headerValue = $headerValuesByDate[$costPrice->header_id];
            $costPrice->price = $data['product']['prices'][$headerValue];
            $costPrice->save();
        });



        return response()->json([$product]);
    }

    public function getFilteredData(Request $request){
        $data = $request->all();
        $prices = [];

        $headers = Header::all();

        $costPrices = CostPrice::where('product_id', $data['id'])->get();



        if ($data['date']){
            foreach ($costPrices as $value) {
                $header = $headers->where('id', $value->header_id)->first();
                if ($header) {
                    $prices[$header->value] = $value->price;
                }
            }

            $startDate = $this->dateFormatService->formatCustomDate($data['date'][0]);
            $endDate = $this->dateFormatService->formatCustomDate($data['date'][1]);

            $prices = $this->dateFilterService->filterCostPricesByDateRange($prices, $startDate, $endDate);

        }else{
            foreach ($costPrices as $value) {
                $header = $headers->where('id', $value->header_id)->first();
                if ($header) {
                    $prices[$header->value] = $value->price;
                }
            }
        }


        return response()->json(['prices' => $prices]);
    }
}
