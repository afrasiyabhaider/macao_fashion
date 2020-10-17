<?php

namespace App\Observers;

use App\Variation;
use Illuminate\Support\Facades\Log;

class VariationObserver
{
    /**
     * Handle the variation "created" event.
     *
     * @param  \App\Variation  $variation
     * @return void
     */
    public function created(Variation $variation)
    {
        try {
            $variation = Variation::first();
            $variation->default_sell_price = $variation->sell_price_inc_tax;
            $variation->default_purchase_price = $variation->dpp_inc_tax;
            $variation->save();

            Log::info('Same Product price Saved for Product having ID: '.$variation->product_id.' and SKU/BarCode: '.$variation->sub_sku);

        } catch (\Exception $ex) {
            dd($ex->getMessage().' on Line: '.$ex->getLine().' in File: '.$ex->getFile());
        }
    }

    /**
     * Handle the variation "updated" event.
     *
     * @param  \App\Variation  $variation
     * @return void
     */
    public function updated(Variation $variation)
    {
        try {
            $variation = Variation::first();
            $variation->default_sell_price = $variation->sell_price_inc_tax;
            $variation->default_purchase_price = $variation->dpp_inc_tax;
            $variation->save();

            Log::info('Same Product price Updated for Product having ID: '.$variation->product_id.' and SKU/BarCode: '.$variation->sub_sku);

        } catch (\Exception $ex) {
            dd($ex->getMessage().' on Line: '.$ex->getLine().' in File: '.$ex->getFile());
        }
    }

    /**
     * Handle the variation "deleted" event.
     *
     * @param  \App\Variation  $variation
     * @return void
     */
    public function deleted(Variation $variation)
    {
        //
    }

    /**
     * Handle the variation "restored" event.
     *
     * @param  \App\Variation  $variation
     * @return void
     */
    public function restored(Variation $variation)
    {
        //
    }

    /**
     * Handle the variation "force deleted" event.
     *
     * @param  \App\Variation  $variation
     * @return void
     */
    public function forceDeleted(Variation $variation)
    {
        //
    }
}
