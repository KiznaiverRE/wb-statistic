<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerArticle extends Model
{
    use HasFactory;

    use HasFactory;

    protected $fillable = [
        'product_id', 'article', // добавьте сюда все необходимые поля
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
