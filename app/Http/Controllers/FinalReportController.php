<?php


namespace App\Http\Controllers;


use App\Models\CategoryFinalReport;
use App\Models\FinancialMetaData;
use App\Models\FinancialReport;
use App\Models\ProductCategory;
use App\Services\Report\FinalReportService;
use BCMathExtended\BC;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FinalReportController extends Controller
{
    protected $finalReportService;

    public function __construct(FinalReportService $finalReportService){
        $this->finalReportService = $finalReportService;
    }
    public static function categoryReport(FinalReportService $finalReportService)
    {
        // Логика для отчёта по категориям
        $finances = $finalReportService->getUserFinancesData();
        $report = $finalReportService->generateCategoryReport($finances);

        $user = Auth::user();

        foreach ($report as $key => $value){
            Log::info($value);
            $category = ProductCategory::where('title', $value['meta']['category'])->first();

            if ($category){
                foreach ($value['reports'] as $k => $v){
                    CategoryFinalReport::updateOrCreate([
                        'report_date' => $k,
                        'category_id' => $category->id
                    ], [
                        'user_id' => $user->id,
                        'report_date' => $k,
                        'category_id' => $category->id,
                        'transfers' => $v->transfers,
                        'orders_count' => $v->orders_count,
                        'average_check' => $v->average_check,
                        'purchase_cost' => $v->purchase_cost,
                        'logistic_cost' => $v->logistic_cost,
                        'logistic_percent' => $v->logistic_percent,
                        'storage_cost' => $v->storage_cost,
                        'advertising_cost' => $v->advertising_cost,
                        'ddr_percent' => $v->ddr_percent,
                        'fine' => $v->fine,
                        'credited_to_account' => $v->credited_to_account,
                        'batch_cost' => $v->batch_cost,
                        'profit' => $v->profit,
                        'profit_percent' => $v->profit_percent,
                        'margin_after_expenses' => $v->margin_after_expenses,
                        'returns_count' => $v->returns_count,
                    ]);
                }
            }

        }
    }


    public function getCategoryReports(){
        $reportData = CategoryFinalReport::all();
        $report = [];


        foreach ($reportData as $key => $value){
            $category = ProductCategory::where('id', $value['category_id'])->first();

            $categoryTitle = $category->title;
            $reportDate = $value->report_date;

            // Инициализация массива для категории, если ещё не инициализирован
            if (!isset($report[$categoryTitle])) {
                $report[$categoryTitle] = [
                    'meta' => [
                        'category' => $categoryTitle,
                        'category_id' => $category->id
                    ],
                    'reports' => [],
                ];
            }

            $report[$categoryTitle]['reports'][$reportDate] = $value;
        }

        return response()->json(['data' => $report], 200);
    }

    public function dateReport()
    {
        // Логика для отчёта по всем товарам
        $finances = $this->finalReportService->getUserFinancesData();
        $report = $this->finalReportService->generateDateReport($finances);

        return response()->json(['data' => $report], 200);

    }

    public function productReport()
    {
        $finances = $this->finalReportService->getUserFinancesData();
        $report = $this->finalReportService->generateProductReport($finances);

        return response()->json(['data' => $report], 200);
    }

    public function updateCategoryReport(Request $request)
    {
        $reportData = $request->input('report');
        $categoryId = $request->input('categoryId');

        // Найти отчет по category_id и report_date
        $report = CategoryFinalReport::where('category_id', $categoryId)
            ->where('report_date', $reportData['report_date'])
            ->first();

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        $creditedToAccount = (string) $report->credited_to_account;

        $ads1 = BC::add((string)$report->advertising_cost, 0, 2);
        $ads2 = BC::add((string)$reportData['advertising_cost'], 0, 2);

        if (BC::comp((string)$ads1, (string)$ads2, 2) === 0) {
            // Числа равны, выполните логирование или другую операцию
            Log::info('Числа равны: ' . $ads1 . ' и ' . $ads2);
        } else {
            // Числа не равны
            //Придёт на счёт
            $report->advertising_cost = $reportData['advertising_cost'];
//            $report->credited_to_account =
//                BC::sub($report->transfers, $report->logistic_cost, 2);
//            $report->credited_to_account =
//                BC::sub($report->credited_to_account, $report->storage_cost, 2);
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
        }

        // Обновление данных отчета
        $report->fine = $reportData['fine'];


        $fine = (string) $report->fine;
//        if (isset($reportData['oldFine'])){
//            $oldFine = (string) $reportData['oldFine'];
//
//            // Сначала добавляем старое значение fine
//            $creditedToAccount = BC::add($creditedToAccount, $oldFine, 2);
//
//            // Затем вычитаем новое значение fine
//            $creditedToAccount = BC::sub($creditedToAccount, $fine, 2);
//
//            // Присваиваем результат обратно в credited_to_account
//            $report->credited_to_account = $creditedToAccount;
//        }

        if (isset($reportData['oldFine'])) {
            $oldFine = (string) $reportData['oldFine'];

            // Сначала добавляем старое значение fine
            $report->credited_to_account = BC::add($report->credited_to_account, $oldFine, 2);

            // Затем вычитаем новое значение fine
            $report->credited_to_account = BC::sub($report->credited_to_account, $fine, 2);
        }

        // Попытка сохранить изменения
        try {
            $report->save();
            return response()->json(['success' => true, 'data' => $report], 200);
        } catch (\Exception $e) {
            Log::error('Save failed:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to save report'], 500);
        }
    }
}
