<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LocationTransferDetail extends Model
{
    //
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id', 'product_variation_id', 'variation_id', 'location_id', 'transfered_from','quantity','transfered_on' 
    ];
    

    public function transfered_from()
    {
        return $this->hasOne(\App\BusinessLocation::class, 'transfered_from');
    }

    public function product()
    {
        return $this->hasMany(App\Product::class,'product_id');
    }
}
