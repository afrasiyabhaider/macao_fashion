<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VariationLocationDetails extends Model
{
    //
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id', 'product_variation_id', 'variation_id', 'location_id', 'qty_available' 
    ];

    public function products()
    {
        return $this->belongsTo(Product::class,'product_id','id');
    }
}
