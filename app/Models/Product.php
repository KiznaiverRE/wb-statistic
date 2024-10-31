<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CostPrice;
use App\Models\WbArticle;
use App\Models\SellerArticle;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'sellers_article', 'wb_article', 'category_id', 'header_id', 'user_id'  // добавьте сюда все необходимые поля
    ];

    // Пример связи с другими моделями
    public function costPrices()
    {
        return $this->hasMany(CostPrice::class);
    }

    public function wbArticles()
    {
        return $this->hasOne(WbArticle::class);
    }

    public function sellerArticles()
    {
        return $this->hasOne(SellerArticle::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}
