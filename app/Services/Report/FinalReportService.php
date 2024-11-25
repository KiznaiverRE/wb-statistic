<?php


namespace App\Services\Report;


use App\Models\FinancialMetaData;
use BCMathExtended\BC;
use Illuminate\Support\Facades\Auth;
use App\DTO\FinalReportDTO;
use Illuminate\Support\Facades\Log;

class FinalReportService
{
    protected $bc;

    public function __construct(BC $bc){
        $this->bc = $bc;
    }

    public function getUserFinancesData()
    {
        $user = Auth::user();
        return FinancialMetaData::with('financialReports')->where('user_id', $user->id)->get();
    }


    public function generateCategoryReport($financesData)
    {
        return $this->calculateFinancialData($financesData, 'category', 'date');
    }

    public function generateDateReport($financesData)
    {
        return $this->calculateFinancialData($financesData, 'date');
    }

    public function generateProductReport($financesData)
    {
        return $this->calculateFinancialData($financesData, 'product', 'date');
    }

    private function calculateFinancialData($financesData, $groupBy, $subGroupBy = null){
        Log::info('================');
        $finances = [];

        foreach ($financesData as $value){
            foreach ($value->financialReports as $report){
                $groupKey = $groupBy === 'category' ? $value->category : ($groupBy === 'date' ? $report->report_date : $value->seller_article);
                $subGroupKey = $subGroupBy === 'date' ? $report->report_date : null;

                if ($subGroupKey && !isset($finances[$groupKey][$subGroupKey])) {
                    $finances[$groupKey]['meta'] = [
                        'name' => $value->name,
                        'wb_article' => $value->wb_article,
                        'category' => $value->category
                    ];
                    if (!isset($finances[$groupKey]['reports'][$subGroupKey])){
                        $finances[$groupKey]['reports'][$subGroupKey] = new FinalReportDTO();
                    }
                } elseif (!$subGroupKey) {
                    if (!isset($finances['reports'][$groupKey])) {
                        $finances['reports'][$groupKey] = new FinalReportDTO();
                    }
                }


                if ($subGroupKey) {
                    $finances[$groupKey]['reports'][$subGroupKey] = $this->sumFinancialData($finances[$groupKey]['reports'][$subGroupKey], $report);
                } else {
                    $finances['reports'][$groupKey] = $this->sumFinancialData($finances['reports'][$groupKey], $report);
                }
            }
            foreach ($finances as $groupKey => $subGroups) {
                if (isset($subGroups['reports'])) {
                    foreach ($subGroups['reports'] as $subGroupKey => $report) {
                        $finances[$groupKey]['reports'][$subGroupKey] = $this->calculatePercentages($finances[$groupKey]['reports'][$subGroupKey]);
                    }
                } else {
                    if (isset($finances['reports'][$groupKey])) {
                        $finances['reports'][$groupKey] = $this->calculatePercentages($finances['reports'][$groupKey]);
                    }
                }
            }
        }

        return $finances;
    }

    private function sumFinancialData($finances, $report){
        foreach ($finances as $key => $property) {
            if (isset($report->$key)) {
                $finances->$key = $this->bc::add($finances->$key, $report->$key, 2);
            }
        }

        return $finances;
    }


    private function calculatePercentages($finances)
    {
        if ($finances->logistic_cost != 0 && $finances->transfers != 0) {
            $finances->logistic_percent =
                $this->bc::div($finances->logistic_cost,
                    ($this->bc::div($finances->transfers, 100, 10)), 2);
        }

        if ($finances->transfers != 0 && $finances->profit != 0) {
            $finances->profit_percent =
                $this->bc::div($finances->profit,
                    ($this->bc::div($finances->transfers, 100, 10)), 2);
        }

        return $finances;
    }
}
