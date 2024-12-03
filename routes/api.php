<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/excel-data/{fileHash}', function ($fileHash){
    $data = Redis::get("excel_data:{$fileHash}");

    if ($data){
        return response()->json(json_decode($data));
    }

    return response()->json(['message' => 'Data not found'], 404); // Если данных нет

});
