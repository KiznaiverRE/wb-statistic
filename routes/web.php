<?php

use App\Http\Controllers\ExcelDataController;
use App\Http\Controllers\FinalReportController;
use App\Http\Controllers\ProductCostController;
use App\Http\Controllers\ProductLinkController;
use App\Http\Controllers\ProductFinanceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/link-articles', function () {
    return Inertia::render('LinkArticles');
})->middleware(['auth', 'verified'])->name('link.articles');

Route::get('/finance', function () {
    return Inertia::render('Finance');
})->middleware(['auth', 'verified'])->name('fin.calculations');

Route::get('/reports', function () {
    return Inertia::render('FinalReports');
})->middleware(['auth', 'verified'])->name('report.reports');

Route::get('/reports/category' , function (){
    return Inertia::render('CategoryReports');
})->middleware(['auth', 'verified'])->name('report.category');

Route::get('/reports/date' , function (){
    return Inertia::render('DateReports');
})->middleware(['auth', 'verified'])->name('report.date');

Route::get('/reports/summary' , function (){
    return Inertia::render('SummaryReports');
})->middleware(['auth', 'verified'])->name('report.summary');




Route::middleware(['auth', 'verified'])->group(function () {
    //User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //General
//    Route::post('/download')

    //Cost
    Route::get('/excel-data', [ProductCostController::class, 'getExcelData'])->name('excel.data.get');
    Route::post('/upload-excel', [ProductCostController::class, 'uploadExcelData'])->name('excel.data.upload');
    Route::post('/save-excel', [ProductCostController::class, 'saveExcelData'])->name('excel.data.save');
    Route::post('/save-product', [ProductCostController::class, 'saveProduct'])->name('product.save');
    Route::post('/filtered-prices', [ProductCostController::class, 'getFilteredData'])->name('product.prices.filtered.get');


    //Link
    Route::post('/save-link', [ProductLinkController::class, 'saveLink'])->name('product.link.save');
    Route::post('/upload-links', [ProductLinkController::class, 'uploadLinksData'])->name('product.link.upload');

    //Finance
    Route::post('/upload-fin', [ProductFinanceController::class, 'uploadStat'])->name('fin.upload');
    Route::post('/upload-ads', [ProductFinanceController::class, 'uploadAds'])->name('ads.upload');
    Route::post('/upload-storage', [ProductFinanceController::class, 'uploadStorage'])->name('storage.upload');
    Route::post('/download-fin', [ProductFinanceController::class, 'exportStatData'])->name('fin.download');
    Route::get('/get-fin', [ProductFinanceController::class, 'getStatData'])->name('fin.get');
    Route::post('/save-fin', [ProductFinanceController::class, 'saveStatData'])->name('fin.save');
    Route::post('/saveReport', [ProductFinanceController::class, 'saveReport'])->name('fin.update');
    Route::post('/delete-fin-data', [ProductFinanceController::class, 'deleteStatData'])->name('fin.data.delete');

    Route::get('/reports/category/get', [FinalReportController::class, 'getCategoryReports'])->name('report.category.get');
    Route::post('/reports/category/update', [FinalReportController::class, 'updateCategoryReport'])->name('report.category.update');
    Route::get('/reports/date/get', [FinalReportController::class, 'dateReport'])->name('report.date.get');
    Route::get('/reports/product/get', [FinalReportController::class, 'productReport'])->name('report.product.get');

});

require __DIR__.'/auth.php';
