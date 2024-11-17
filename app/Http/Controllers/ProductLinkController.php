<?php

namespace App\Http\Controllers;

use App\Models\CostPrice;
use App\Models\ProductCategory;
use App\Models\WbArticle;
use App\Services\Excel\ExcelHeaderValidatorService;
use App\Services\Excel\ExcelParsingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\SellerArticle;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductLinkController extends Controller
{
    protected ExcelHeaderValidatorService $headerValidator;

    public function __construct(ExcelHeaderValidatorService $headerValidator)
    {
        $this->headerValidator = $headerValidator;
    }

    public function saveLink(Request $request)
    {
        $products = $request->input('product');
        if ($request->has('key')){
            $hasId = $request->input('key');
        }

        if (!is_array($products)) {
            // Если пришел один продукт, обернем его в массив
            $products = [$products];
        }

        DB::beginTransaction();
        try {
            // Получение пользователя
            $user = Auth::user();

            foreach ($products as $key => $data) {


                $checkArr = isset($hasId) ? ['sellers_article' => $hasId] : ['title' => $data['meta']['title']];
                // Проверка и создание/обновление категории
                Log::info($checkArr);

                if (isset($data['meta']['category'])) {
                    $category = ProductCategory::updateOrCreate(
                        [
                            'title' => $data['meta']['category']
                        ],
                        [
                            'title' => $data['meta']['category']
                        ]
                    );
                }

                // Создание или обновление продукта
                Product::updateOrCreate(
                    $checkArr,
                    [
                        'title' => $data['meta']['title'],
                        'user_id' => $user->id,
                        'category_id' => $category->id ?? null,
                        'sellers_article' => $data['meta']['sellers_article'],
                        'wb_article' => $data['meta']['wb_article'] ?? null,
                    ]
                );
            }

            // Завершение транзакции
            DB::commit();

            return response()->json(['message' => 'Связи успешно созданы или обновлены.'], 201);
        } catch (\Exception $e) {
            // Откат транзакции в случае ошибки
            DB::rollBack();
            // Подробное логирование
            Log::error('Ошибка при создании или обновлении связи', [
                'message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'input_data' => $request->all()
            ]);
            return response()->json(['error' => 'Ошибка при создании или обновлении связи.', 'details' => $e->getMessage()], 500);
        }
    }


    public function uploadLinksData(Request $request){
        try {
            Log::info('===========================================================================================================================================');
            // Проверка наличия файла
            if (!$request->hasFile('file')) {
                return response()->json(['error' => 'No file uploaded'], 400);
            }

            // Получение загруженного файла
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();

            $data = ExcelParsingService::getDataFromExcel($file);

            $missingHeaders = $this->headerValidator->validateHeaders($data['headers'], 'link');

            Log::info($data['headers']);
            Log::info($missingHeaders);

            if ($missingHeaders !== true){
                return response()->json(['error' => 'Failed to process the spreadsheet file.'], 500);
            }

            $ids = [];

            foreach ($data['rows'] as $value){
                $ids[] = $value['Артикул 1С'];
            }

            $products = Product::whereIn('sellers_article', $ids)
                ->where('user_id', auth()->id())
                ->get();


            foreach ($data['rows'] as $value){
                $product = $products->firstWhere('sellers_article', $value['Артикул 1С']);
                try {
                    $productCategory = ProductCategory::where('title', $value['Категория'])->first();
                    if (!$productCategory){
                        $productCategory = ProductCategory::create([
                            'title' => $value['Категория']
                        ]);
                    }
                    if ($product) {
    //                    Log::info(json_encode($product->sellerArticles));
                            $product->title = $value['Наименование общее'];
                            $product->wb_article = $value['АРТ ВБ'];
                            $product->sellers_article = $value['Артикул 1С'];
                            $product->category_id = $productCategory->id;

                            $product->save();
                    }else{
                        Product::create([
                            'title' => $value['Наименование общее'],
                            'wb_article' => $value['АРТ ВБ'],
                            'sellers_article' => $value['Артикул 1С'],
                            'category_id' => $productCategory->id,
                            'user_id' => auth()->id()
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('General error: ' . $e->getMessage());
                    return response()->json(['error' => 'An unexpected error occurred.'], 500);
                }
            }

//            $data = [
//                'rows' => []
//            ];
//
//            // Обработка файлов
//            if (in_array($extension, ['xlsx', 'xls', 'csv'])) {
//                // Сохранение файла во временную директорию
//                $filePath = $file->store('temp');
//
//                // Преобразование содержимого файла в UTF-8
//                $content = file_get_contents(storage_path('app/' . $filePath));
//                $utf8Content = mb_convert_encoding($content, 'UTF-8', 'UTF-16');
//
//                // Сохранение преобразованного содержимого во временный файл
//                $utf8FilePath = storage_path('app/temp/converted_' . $file->getClientOriginalName());
//                file_put_contents($utf8FilePath, $utf8Content);
//
//                // Создание экземпляра объекта PhpSpreadsheet
//                $reader = IOFactory::createReaderForFile($utf8FilePath);
//                $reader->setReadDataOnly(true);
//
//                // Получение данных
//                $spreadsheet = $reader->load($utf8FilePath);
//                $worksheet = $spreadsheet->getActiveSheet();
//
//                // Получение заголовков
//                $headings = $worksheet->toArray()[0];
//                $data['headers'] = $headings;
//
//
//                // Получение данных
//                $rows = $worksheet->toArray();
//                array_shift($rows); // Удаление первой строки, так как это заголовки
//
//                $data['rows'] = array_map(function($row) use ($headings) {
//                    return ['meta' => array_combine($headings, $row)];
//                }, $rows);;
//
//                // Обработка данных: удаление управляющих символов и пробелов
//                array_walk_recursive($data['rows'], function (&$item) {
//                    if (is_string($item)) {
//                        $item = trim(preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $item));
//                    }
//                });
//
//                // Удаление временного файла
//                unlink($utf8FilePath);
//            } else {
//                return response()->json(['error' => 'Invalid file type. Only xlsx, xls, and csv files are allowed.'], 400);
//            }

            return response()->json(['message' => 'Данные успешно обновлены']);

        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
//            Log::error('Spreadsheet error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process the spreadsheet file.'], 500);
        } catch (\Exception $e) {
//            Log::error('General error: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }
}
