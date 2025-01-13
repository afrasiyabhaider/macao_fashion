<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LoyaltyPointsHistory extends Model
{
    protected $fillable = ['contact_id', 'points', 'transaction_type', 'description','transaction_id'];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
