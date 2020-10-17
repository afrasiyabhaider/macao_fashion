<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
     use SoftDeletes;
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Return list of Supplier for a business
     *
     * @param int $business_id
     * @param boolean $show_none = false
     *
     * @return array
     */
    public static function forDropdown($business_id, $show_none = false)
    {
        $Supplier = Supplier::where('business_id', $business_id)
                            ->orderBy('name','ASC')
                            ->pluck('name', 'id');

        if ($show_none) {
            $Supplier->prepend(__('lang_v1.none'), '');
        }

        return $Supplier;
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
