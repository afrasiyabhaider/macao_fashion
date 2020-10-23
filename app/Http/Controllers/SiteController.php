<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Category;
use App\Color;
use App\Product;
use App\ProductImages;
use App\ProductVariation;
use App\Size;
use App\SpecialCategoryProduct;
use App\VariationLocationDetails;
use App\WebsiteProducts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiteController extends Controller
{
    public function home()
    {
        $location_id = BusinessLocation::where('name','Web Shop')->orWhere('name','webshop')->orWhere('name','web shop')->orWhere('name','Website')->orWhere('name','website')->orWhere('name','MACAO WEBSHOP')->pluck('id');

        /**
         * Product is stored as many time as its size is selected 
         * e.g if a product have 4 sizes then it will be stored 
         * 4 times so we have to groupBy a product by its refference
         *  so we did
         * 
        */
        // $data = VariationLocationDetails::join('products as p','p.id','=','variation_location_details.product_id')->where('qty_available','>',0)->groupBy('p.refference')->orderBy('p.created_at','Desc')->get();
        $data = WebsiteProducts::join('products as p','p.refference','=','website_products.refference')->orderBy('p.created_at','Desc')->get();

        $featured = SpecialCategoryProduct::where('featured',"1")->get();
        $new_arrival = SpecialCategoryProduct::where('new_arrival',"1")->get();
        $sale = SpecialCategoryProduct::where('sale',"1")->get();
        // dd($featured);
        
        
        return view('site.home',compact('data','featured','new_arrival','sale'));
    }
    /**
     * Get Products of Specific Category 
     * 
     **/
    public function products_by_category($id)
    {
        $id = decrypt($id);
        $location_id = BusinessLocation::where('name', 'Web Shop')->orWhere('name', 'webshop')->orWhere('name', 'web shop')->orWhere('name', 'Website')->orWhere('name', 'website')->orWhere('name', 'MACAO WEBSHOP')->first()->id;
        // dd($id);

        $category = Category::find($id);

        $product_id = Product::where('category_id', $id)->orWhere('sub_category_id', $id)->groupBy('refference')->pluck('id');

        $products = WebsiteProducts::join('products as p', 'p.id', '=', 'website_products.product_id')->whereIn('p.id', $product_id)->orderBy('p.created_at', 'Desc')->paginate(12);

        return view('site.listings.category_listing', compact('category', 'products'));
    }
    /**
     *  Product Detail
     *
     **/
    public function detail($id)
    {   
        $id = decrypt($id);
        $location_id = BusinessLocation::where('name','Web Shop')->orWhere('name','webshop')->orWhere('name','web shop')->orWhere('name','Website')->orWhere('name','website')->orWhere('name','MACAO WEBSHOP')->first()->id;
        
        $product = Product::find($id);

        $special_category = SpecialCategoryProduct::where('refference',$product->refference)->first();

        $featured = SpecialCategoryProduct::where('featured',1)->latest()->get();
        $new_arrival = SpecialCategoryProduct::where('new_arrival',1)->latest()->get();
        
        $images = ProductImages::where('refference',$product->refference)->get();

        // dd($special_category);

        $color_ids = Product::where('refference',$product->refference)->distinct()->orderBy('color_id','asc')->pluck('color_id');
        
        $colors = Color::whereIn('id',$color_ids)->get();
        
        $size_ids = Product::where('refference',$product->refference)->distinct()->orderBy('color_id','asc')->pluck('sub_size_id');
        
        $sizes = Size::whereIn('id',$size_ids)->get();
        
        $product_ids = Product::where('refference',$product->refference)->pluck('id');
        
        $web_product = VariationLocationDetails::whereIn('product_id',$product_ids)->where('qty_available','>',0)->get();
        
        // dd($web_product-);

        $all_web_products = VariationLocationDetails::where('qty_available', '>', 0)->join('products as p','p.id','=','variation_location_details.product_id')->groupBy('p.refference')->orderBy('p.created_at','Desc')->get();

        // $social = Share::page('http://jorenvanhocht.be', 'Share title')
        //                 ->facebook()
        //                 ->twitter()
        //                 ->linkedin('Extra linkedin summary can be passed here')
        //                 ->whatsapp();
        // dd($social);   

        // dd($web_product);

        return view('site.detail',compact('product','special_category','images','colors','sizes','web_product','featured','new_arrival','all_web_products'));
    }

    public function get_color_sizes($refference,$color_id)
    {
         $location_id = BusinessLocation::where('name','Web Shop')->orWhere('name','webshop')->orWhere('name','web shop')->orWhere('name','Website')->orWhere('name','website')->orWhere('name','MACAO WEBSHOP')->first()->id;

        $products = Product::where('color_id',$color_id)
                                ->where('refference',$refference)
                                ->get();
        // dd($products);
        $product_ids = $products->pluck('id');

        $web_products = VariationLocationDetails::whereIn('product_id',$product_ids)->where('qty_available', '>', 0)->get();

        $qty = VariationLocationDetails::whereIn('product_id',$product_ids)->where('qty_available', '>', 0)->sum('qty_available');

        $size_ids = $products->pluck('sub_size_id');

        $sizes = Size::whereIn('id',$size_ids)->get();

        $data = [
            'sizes' => $sizes,
            'web_product' => $web_products,
            'qty' => $qty,
        ];

        return response()->json($data);
    }
    public function get_color_size_qty($refference,$color_id,$sub_size_id)
    {
         $location_id = BusinessLocation::where('name','Web Shop')->orWhere('name','webshop')->orWhere('name','web shop')->orWhere('name','Website')->orWhere('name','website')->orWhere('name','MACAO WEBSHOP')->first()->id;

        $products = Product::where('color_id',$color_id)
                                ->where('sub_size_id',$sub_size_id)
                                ->where('refference',$refference)
                                ->get();
        // dd($products);
        $product_ids = $products->pluck('id');

        $web_products = VariationLocationDetails::whereIn('product_id',$product_ids)->where('qty_available', '>', 0)->get();

        $qty = VariationLocationDetails::whereIn('product_id',$product_ids)->where('qty_available', '>', 0)->sum('qty_available');

        $size_ids = $products->pluck('sub_size_id');

        $sizes = Size::whereIn('id',$size_ids)->get();

        $data = [
            'sizes' => $sizes,
            'web_product' => $web_products,
            'qty' => $qty,
        ];

        return response()->json($data);
    }
    public function get_size_qty($refference,$sub_size_id)
    {
         $location_id = BusinessLocation::where('name','Web Shop')->orWhere('name','webshop')->orWhere('name','web shop')->orWhere('name','Website')->orWhere('name','website')->orWhere('name','MACAO WEBSHOP')->first()->id;

        $products = Product::where('sub_size_id',$sub_size_id)
                                ->where('refference',$refference)
                                ->get();
        // dd($products);
        $product_ids = $products->pluck('id');

        $web_products = VariationLocationDetails::whereIn('product_id',$product_ids)->where('qty_available', '>', 0)->get();

        $qty = VariationLocationDetails::whereIn('product_id',$product_ids)->where('qty_available', '>', 0)->sum('qty_available');

        $size_ids = $products->pluck('sub_size_id');

        $sizes = Size::whereIn('id',$size_ids)->get();

        $data = [
            'sizes' => $sizes,
            'web_product' => $web_products,
            'qty' => $qty,
        ];

        return response()->json($data);
    }

    public function all_products()
    {
        $location_id = BusinessLocation::where('name', 'Web Shop')->orWhere('name', 'webshop')->orWhere('name', 'web shop')->orWhere('name', 'Website')->orWhere('name', 'website')->orWhere('name', 'MACAO WEBSHOP')->first()->id;

        $products = VariationLocationDetails::where('qty_available', '>', 0)->join('products as p', 'p.id', '=', 'variation_location_details.product_id')->groupBy('p.refference')->orderBy('p.created_at', 'Desc')->paginate(12);
        
        return view('site.listings.all_products',compact('products'));
    }


    public function update_null_product_date($date)
    {
        $products = VariationLocationDetails::where('product_updated_at',null)->get();

        // dd($date,$products->pluck('product_updated_at'));
        foreach ($products as $product) {
            // dd($product);
            $vld = VariationLocationDetails::find($product->id);
            $vld->product_updated_at = $date;
            $vld->save();
        }
    }
}
