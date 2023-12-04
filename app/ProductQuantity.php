<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductQuantity extends Model
{
    protected $fillable = [
        'product_id', 'variation_id', 'refference', 'location_id', 'quantity',
    ];
}
