<?php

namespace App\Http\Controllers;

use App\Models\CostPrice;
use App\Models\Product;
use App\Models\WbArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;


class ProductController extends Controller
{
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

                Log::info($data['headers']);

                // Получение данных
                $rows = $worksheet->toArray();
                array_shift($rows); // Удаление первой строки, так как это заголовки
                $data['rows'] = $rows;

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

    public function parseExcelData($file){


//        return $data;
    }

    public function getExcelData(){
        $user = Auth::user();

    }


    public function sortData(Request $request){
        // Валидация данных из Excel
//        $validatedData = $request->validate([
//            'name' => 'required|string|max:255',
//            'article' => 'required|string|max:255',
//            'prices.*.price' => 'required|numeric',
//            'prices.*.date' => 'required|date',
//        ]);

        $data = $request->all();

        Log::info($data);
    }
}
