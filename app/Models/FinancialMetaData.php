<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialMetaData extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'category',
        'seller_article',
        'wb_article',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function financialReports()
    {
        return $this->hasMany(FinancialReport::class, 'meta_data_id');
    }
}
