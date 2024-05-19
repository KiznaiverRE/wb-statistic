<?php

namespace App\Http\Controllers;

use App\Models\CostPrice;
use App\Models\Product;
use App\Models\WbArticle;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Метод для обработки данных из Excel и сохранения товара
    public function importAndStore(Request $request)
    {
        // Валидация данных из Excel
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'article' => 'required|string|max:255',
            'prices.*.price' => 'required|numeric',
            'prices.*.date' => 'required|date',
        ]);

        // Создание нового товара
        $product = Product::create([
            'name' => $validatedData['name'],
        ]);

        // Создание нового артикула WbArticle
        $wbArticle = WbArticle::create([
            'product_id' => $product->id,
            'article' => $validatedData['article'],
        ]);

        // Создание новых цен CostPrice
        foreach ($validatedData['prices'] as $priceData) {
            CostPrice::create([
                'product_id' => $product->id,
                'price' => $priceData['price'],
                'date' => strtotime($priceData['date']), // Преобразование даты в формат временной метки
            ]);
        }

        // Отправить успешный ответ
        return response()->json(['message' => 'Data imported successfully'], 200);
    }
}
