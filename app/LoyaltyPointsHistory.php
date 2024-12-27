<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LoyaltyPointsHistory extends Model
{
    protected $fillable = ['contact_id', 'points', 'transaction_type', 'description'];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
