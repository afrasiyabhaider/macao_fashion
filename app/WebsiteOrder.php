<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WebsiteOrder extends Model
{
    /**
     * Getting Product details on the basis of product_id
     *  
     **/
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    /**
     * Getting Product details on the basis of refference
     *  
     **/
    public function products()
    {
        return $this->belongsTo(Product::class, 'refference', 'refference');
    }
}
