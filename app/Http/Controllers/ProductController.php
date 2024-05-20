<?php

namespace App\Http\Controllers;

use App\Models\CostPrice;
use App\Models\Product;
use App\Models\WbArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    // Метод для обработки данных из Excel и сохранения товара
    public function uploadExcelData(Request $request)
    {

        $data = $this->sortData($request);

        // Создание нового товара
//        $product = Product::create([
//            'name' => $data['name'],
//        ]);
//
//        // Создание нового артикула WbArticle
//        $wbArticle = WbArticle::create([
//            'product_id' => $product->id,
//            'article' => $data['article'],
//        ]);
//
//        // Создание новых цен CostPrice
//        foreach ($data['prices'] as $priceData) {
//            CostPrice::create([
//                'product_id' => $product->id,
//                'price' => $priceData['price'],
//                'date' => strtotime($priceData['date']), // Преобразование даты в формат временной метки
//            ]);
//        }

        // Отправить успешный ответ
        return response()->json(['message' => 'Data imported successfully'], 200);
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
