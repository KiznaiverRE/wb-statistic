<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class WbArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'article', 'header_id' // добавьте сюда все необходимые поля
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
