<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\LocationTransferDetail;
use App\Product;
use App\TransactionSellLine;
use App\Variation;
use App\VariationLocationDetails;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DataMigrationController extends Controller
{
    public function location_transfer_detail_data()
    {
        try {
            DB::beginTransaction();
            $date = Carbon::create('2020-08-01');
            $now = Carbon::now();
            $vld = VariationLocationDetails::whereBetween('product_updated_at', [$date, $now])->where('location_id', '!=', 1)->get()->toArray();

            $chunk = array_chunk($vld, 500);
            // dd($chunk);

            for ($j = 0; $j < count($chunk); $j++) {
                for ($i = 0; $i < count($chunk[$j]); $i++) {
                    $location_transfer_detail = new LocationTransferDetail();
                    $location_transfer_detail->variation_id = $chunk[$j][$i]['variation_id'];
                    $location_transfer_detail->product_id = $chunk[$j][$i]['product_id'];
                    $location_transfer_detail->location_id = $chunk[$j][$i]['location_id'];
                    $location_transfer_detail->transfered_from = 1;

                    $location_transfer_detail->product_variation_id = $chunk[$j][$i]['product_variation_id'];

                    $location_transfer_detail->quantity = $chunk[$j][$i]['qty_available'];
                    $location_transfer_detail->transfered_on = $chunk[$j][$i]['product_updated_at'];

                    $location_transfer_detail->save();
                }
            }
            DB::commit();
            dd('Record Saved');
        } catch (\Exception $ex) {
            DB::rollback();
            dd('Error Occured : '.$ex->getMessage().' in File: '.$ex->getFile().' on Line: '.$ex->getLine());
        }
    }


    public function location_transfer_detail_product_data()
    {
        try {
            DB::beginTransaction();

            $ltd = LocationTransferDetail::get();
            foreach ($ltd as $key => $value) {
                $ref = Product::find($value->product_id)->refference;

                if ($ref != null) {
                    $value->product_refference = $ref;
                }else{
                    $value->product_refference = null;
                }
                $value->save();
            }

            DB::commit();
            dd('Record Saved');
        } catch (\Exception $ex) {
            DB::rollback();
            dd('Error Occured : '.$ex->getMessage().' in File: '.$ex->getFile().' on Line: '.$ex->getLine());
        }
    }

    /**
     *  Add Refference in transaction_sell_lines
     * 
     **/
    public function transaction_sell_lines_product_data()
    {
        try {
            DB::beginTransaction();

            $ltd = TransactionSellLine::get();
            foreach ($ltd as $key => $value) {
                $ref = Product::find($value->product_id)->refference;

                if ($ref != null) {
                    $value->product_refference = $ref;
                }else{
                    $value->product_refference = null;
                }
                $value->save();
            }

            DB::commit();
            dd('Record Saved');
        } catch (\Exception $ex) {
            DB::rollback();
            dd('Error Occured : '.$ex->getMessage().' in File: '.$ex->getFile().' on Line: '.$ex->getLine());
        }
    }
    /**
     *  Add Refference in transaction_sell_lines
     * 
     **/
    public function variation_location_details_product_data()
    {
        try {
            DB::beginTransaction();

            $ltd = VariationLocationDetails::get();
            foreach ($ltd as $key => $value) {
                $ref = Product::find($value->product_id)->refference;

                if ($ref != null) {
                    $value->product_refference = $ref;
                }else{
                    $value->product_refference = null;
                }
                $value->save();
            }

            DB::commit();
            dd('Record Saved');
        } catch (\Exception $ex) {
            DB::rollback();
            dd('Error Occured : '.$ex->getMessage().' in File: '.$ex->getFile().' on Line: '.$ex->getLine());
        }
    }
    /**
     *  Add Products to Web Shop in transaction_sell_lines
     * 
     **/
    public function variation_location_details_web_shop()
    {
        try {
            DB::beginTransaction();

            $i=0;
            $location_id = BusinessLocation::where('name', 'Web Shop')->orWhere('name', 'webshop')->orWhere('name', 'web shop')->orWhere('name', 'Website')->orWhere('name', 'website')->orWhere('name', 'MACAO WEBSHOP')->first()->id;
            $products = Product::get();
            foreach ($products as $key => $value) {
                $variation = Variation::where('product_id',$value->id)
                                        ->first();
                $variation_location_d = new VariationLocationDetails();
                $variation_location_d->variation_id = $variation->id;
                $variation_location_d->product_id = $value->id;
                $variation_location_d->product_refference = $value->refference;
                $variation_location_d->location_id = $location_id;
                $variation_location_d->product_variation_id = $variation->product_variation_id;
                $variation_location_d->qty_available = 0;
                $variation_location_d->printing_qty = 0;
                $variation_location_d->product_updated_at = Carbon::now();

                $variation_location_d->save();
                $i++;
            }

            DB::commit();
            dd($i.' of '.$products->count().' Record Saved');
        } catch (\Exception $ex) {
            DB::rollback();
            dd('Error Occured : '.$ex->getMessage().' in File: '.$ex->getFile().' on Line: '.$ex->getLine());
        }
    }
}
