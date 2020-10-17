<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
   // Scheme is ['name', 'barcode', 'business_id', 'type', 'applicable', 'product_id', 'brand_id', 'value', 'details', 'barcode_type', 'created_by', 'start_date', 'expiry_date', 'isActive', 'isUsed', 'product_description']

	protected $fillable = [
        'name', 'barcode', 'business_id', 'type', 'applicable', 'value', 'details', 'barcode_type', 'created_by', 'start_date', 'expiry_date', 'isActive', 'isUsed' 
    ];

	/**
     * Get the business that owns the user.
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }


	/**
     * Get the business that owns the user.
     */
    public function created_by()
    {
        return $this->belongsTo(\App\User::class);
    }

    /**
     * Get the brand associated with the product.
     */
    public function brand()
    {
        return $this->belongsTo(\App\Brands::class);
    }

    public static function createGiftCard($details)
    {
    	$userId = request()->session()->get('user.id');
        $GiftCard = GiftCard::create([
                    'name' => $details['name'],
                    'barcode' => $details['barcode'],
                    'business_id' => $details['business_id'],
                    'type' => $details['type'],
                    'applicable' => $details['applicable'],
                    'product_id' => ($details['applicable'] == "one") ? $details['product_id'] : NULL,
                    // 'brand_id' => ($details['applicable'] == "one") ? $details['brand_id'] : NULL,
                    'value' => $details['value'],
                    'details' => $details['details'],
                    'barcode_type' => $details['barcode_type'],
                    'created_by' => $userId,
                    'start_date' => $details['start_date'],
                    'expiry_date' => $details['expiry_date'],
                    'isActive' => $details['isActive'],
                    'isUsed' => '0'
                ]);

        return $GiftCard;
    }
}
