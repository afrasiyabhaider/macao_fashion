<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductNameCategory extends Model
{
    //
    	protected $fillable = [
        'name', 'row_no','business_id','created_by'
    ];
}
