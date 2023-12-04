<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
   // Scheme is ['name', 'barcode', 'business_id', 'gift_card_id', 'value', 'orig_value' ,'barcode_type', 'isActive', 'transaction_id','start_date', 'created_by', 'isUsed']

// 	protected $fillable = [
//         'name', 'barcode', 'business_id', 'gift_card_id', 'coupon_id', 'details', 'value', 'orig_value', 'barcode_type', 'created_by', 'start_date', 'transaction_id', 'isActive', 'isUsed' 
//     ];
 protected $guarded = [];

	/**
     * Get the business that owns the user.
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }

	public function gift_card()
    {
        return $this->belongsTo(\App\GiftCard::class);
    }

    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class);
    }


	/**
     * Get the business that owns the user.
     */
    public function created_by()
    {
        return $this->belongsTo(\App\User::class);
    }

   

    public static function createCoupon($details)
    {
    	$userId = request()->session()->get('user.id');
        $GiftCard = Coupon::create([
                    'name' => $details['name'],
                    'barcode' => $details['barcode'],
                    'business_id' => $details['business_id'],
                    'gift_card_id' => $details['gift_card_id'],
                    'value' => $details['value'],
                    'transaction_id' => $details['transaction_id'],
                    'details' => $details['details'],
                    'barcode_type' => $details['barcode_type'],
                    'created_by' => $userId,
                    'start_date' => $details['start_date'],
                    'isActive' => $details['isActive'],
                    'isUsed' => '0'
                ]);

        return $GiftCard;
    }
}