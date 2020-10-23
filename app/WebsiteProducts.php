<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WebsiteProducts extends Model
{
    public function product()
    {
        return $this->belongsTo(Product::class,'product_id','id');
    }
    public function products()
    {
        return $this->belongsTo(Product::class,'refference','refference');
    }
    // public function products()
    // {
    //     return $this->belongsTo(Product::class, 'product_id', 'id');
    // }
}
