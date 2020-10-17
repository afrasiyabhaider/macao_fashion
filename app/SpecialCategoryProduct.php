<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SpecialCategoryProduct extends Model
{
    protected $fillable = ['refference'];
    public function products()
    {
        return $this->belongsTo(Product::class,'product_id','id');
    }
    
    public function spec_prod()
    {
        return $this->belongsTo(VariationLocationDetails::class,'refference','id');
    }
}
