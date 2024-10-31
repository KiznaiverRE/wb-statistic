<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'meta_data_id',
        'report_date',
        'transfers',
        'orders_count',
        'average_check',
        'purchase_cost',
        'logistic_cost',
        'logistic_percent',
        'storage_cost',
        'advertising_cost',
        'ddr_percent',
        'fine',
        'credited_to_account',
        'batch_cost',
        'profit',
        'profit_percent',
        'margin_after_expenses',
        'returns_count',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function financialMetaData()
    {
        return $this->belongsTo(FinancialMetaData::class, 'meta_data_id');
    }
}
