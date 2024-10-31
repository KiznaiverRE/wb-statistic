<?php

namespace App\Http\Controllers;

use App\Models\CostPrice;
use App\Models\Header;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SellerArticle;
use App\Models\WbArticle;
use App\Services\Date\DateFilterService;
use App\Services\DateFormatService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use Illuminate\Support\Facades\DB;


class ProductController extends Controller
{
    protected $dateFormatService;
    protected $dateFilterService;

    public function __construct(DateFormatService $dateFormatService, DateFilterService $dateFilterService){
        $this->dateFormatService = $dateFormatService;
        $this->dateFilterService = $dateFilterService;
    }

    // Метод для обработки данных из Excel и сохранения товара
    public function uploadExcelData(Request $request)
    {
        try {
            // Проверка наличия файла
            if (!$request->hasFile('file')) {
                return response()->json(['error' => 'No file uploaded'], 400);
            }

            // Получение загруженного файла
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();

            $data = [
                'headers' => [],
                'rows' => []
            ];

            // Обработка файлов
            if (in_array($extension, ['xlsx', 'xls', 'csv'])) {
                // Сохранение файла во временную директорию
                $filePath = $file->store('temp');

                // Преобразование содержимого файла в UTF-8
                $content = file_get_contents(storage_path('app/' . $filePath));
                $utf8Content = mb_convert_encoding($content, 'UTF-8', 'UTF-16');

                // Сохранение преобразованного содержимого во временный файл
                $utf8FilePath = storage_path('app/temp/converted_' . $file->getClientOriginalName());
                file_put_contents($utf8FilePath, $utf8Content);

                // Создание экземпляра объекта PhpSpreadsheet
                $reader = IOFactory::createReaderForFile($utf8FilePath);
                $reader->setReadDataOnly(true);

                // Получение данных
                $spreadsheet = $reader->load($utf8FilePath);
                $worksheet = $spreadsheet->getActiveSheet();

                // Получение заголовков
                $headings = $worksheet->toArray()[0];
                $data['headers'] = $headings;


                // Получение данных
                $rows = $worksheet->toArray();
                array_shift($rows); // Удаление первой строки, так как это заголовки

                $data['rows'] = array_map(function($row) use ($headings) {
                    return array_combine($headings, $row);
                }, $rows);;

                // Обработка данных: удаление управляющих символов и пробелов
                array_walk_recursive($data['rows'], function (&$item) {
                    if (is_string($item)) {
                        $item = trim(preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $item));
                    }
                });

                // Удаление временного файла
                unlink($utf8FilePath);
            } else {
                return response()->json(['error' => 'Invalid file type. Only xlsx, xls, and csv files are allowed.'], 400);
            }

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

        // Получение всех заголовков
        $headers = Header::all();

        $headerValues = $headers->pluck('value');
        $headersById = $headers->keyBy('id');

        // Получение всех продуктов с их категориями, WbArticle, SellerArticle и CostPrice
        $products = Product::with(['category', 'wbArticles', 'sellerArticles', 'costPrices'])->where('user_id', $user->id)->get();

        // Подготовка данных для отправки на фронтенд
        $data = [];
        $data['headers'] = $headerValues;
        foreach ($products as $key => $product) {
            $row = [
                $product->sellers_article,
                $product->wbArticles->article ?? null,
                $product->category->title ?? null,
                $product->title,
            ];

            // Инициализация ключа 'prices'
            $row['prices'] = [];

            // Добавление цен в массив
            foreach ($product->costPrices as $costPrice) {
                $header = $headersById->get($costPrice->header_id);
                if ($header) {
                    $row['prices'][$header->value] = $costPrice->price;
                }
            }

            // Инициализация массива $assocArr для каждого продукта
            $assocArr = [
                'meta' => [],
                'prices' => $row['prices']
            ];

            foreach ($row as $k => $v) {
                if ($k !== 'prices') {
                    $assocArr['meta'][$headerValues[$k]] = $v;
                }
            }

            $data['rows'][$product->id] = $assocArr;
        }

        return response()->json([
            'data' => $data
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
                $productData = [
                    'title' => $item['Название'] ?: NULL,
                    'sellers_article' => isset($item['Артикул продавца']) ? (int)$item['Артикул продавца'] : NULL,
                    'wb_article' => isset($item['Артикул продавца']) ? (int)$item['Артикул продавца'] : NULL,
                    'category_id' => isset($item['Категория']) ? ProductCategory::firstOrCreate(['title' => $item['Категория']])->id : NULL,
                    'user_id' => $user->id
                ];

                if (isset($item['Код номенклатуры']) && !empty($item['Код номенклатуры'])) {
                    $productData['wb_article'] = (int)$item['Код номенклатуры'];
                }

                $product = Product::updateOrCreate(
                    [
                        'title' => $productData['title'],
                        'sellers_article' => $productData['sellers_article'],
                    ],
                    $productData
                );

                if (isset($item['Код номенклатуры']) && !empty($item['Код номенклатуры'])){
                    WbArticle::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'article' => $item['Код номенклатуры'] ?: NULL,
                        ]
                    );
                }

                if (isset($item['Артикул продавца']) && !empty($item['Артикул продавца'])){
                    SellerArticle::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'article' => (int)$item['Артикул продавца'] ?: NULL,
                        ]
                    );
                }

                foreach (array_slice($item, 4) as $key => $value) {
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
            ->where('id', $data['id'])
            ->first();

//        $categoryData = $data['product'];
        $category = ProductCategory::updateOrCreate(
            ['title' => $data['product']['meta']['Категория']] // Уникальный ключ для поиска
        );
        $product->category()->associate($category);

        $product->sellers_article = $data['product']['meta']['Артикул продавца'];
        $product->wb_article = $data['product']['meta']['Код номенклатуры'];
        $product->title = $data['product']['meta']['Название'];

        $product->save();
//
//        // Обновите или создайте WB статьи
        if (isset($data['product']['meta']['Код номенклатуры'])) {
            $wbArticle = WbArticle::updateOrCreate(
                ['product_id' => $product->id], // Уникальный ключ для поиска
                ['article' => $data['product']['meta']['Код номенклатуры']] // Данные для обновления или создания
            );
        }

        // Обновите или создайте продавцовские статьи
        if (isset($data['product']['meta']['Артикул продавца'])) {
            $sellerArticle = SellerArticle::updateOrCreate(
                ['product_id' => $product->id], // Уникальный ключ для поиска
                ['article' => $data['product']['meta']['Артикул продавца']] // Данные для обновления или создания
            );
        }


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
