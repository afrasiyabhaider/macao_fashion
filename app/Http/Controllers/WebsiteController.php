<?php

namespace App\Http\Controllers;

use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\ProductImages;
use App\SellingPriceGroup;
use App\SpecialCategoryProduct;
use App\Supplier;
use App\TaxRate;
use App\Unit;
use App\VariationLocationDetails;
use App\Utils\ProductUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class WebsiteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
         $business_location_id = BusinessLocation::where('name','Web Shop')->orWhere('name','webshop')->orWhere('name','web shop')->orWhere('name','Website')->orWhere('name','website')->orWhere('name','MACAO WEBSHOP')->pluck('id');

         
        if (!auth()->user()->can('product.view') && !auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        //Update USER SESSION
        $user_id = request()->session()->get('user.id');
        $user = \App\User::find($user_id);
        request()->session()->put('user', $user->toArray());
        //Update USER SESSION
        $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);

        if (request()->ajax()) {
            $products = Product::leftJoin('variation_location_details as vlds', 'products.id', '=', 'vlds.product_id')
                ->join('units', 'products.unit_id', '=', 'units.id')
                ->leftJoin('categories as c1', 'products.category_id', '=', 'c1.id')
                ->leftJoin('categories as c2', 'products.sub_category_id', '=', 'c2.id')
                ->leftJoin('tax_rates', 'products.tax', '=', 'tax_rates.id')
                ->leftJoin('sizes', 'products.sub_size_id', '=', 'sizes.id')
                ->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                ->leftJoin('variation_location_details as vld', 'vld.product_id', '=', 'products.id')
                ->join('variations as v', 'v.product_id', '=', 'products.id')->join('suppliers','suppliers.id','=','products.supplier_id')
                ->where('products.business_id', $business_id)
                ->where('vld.location_id', $business_location_id)
                ->where('products.type', '!=', 'modifier')
                ->select(
                    'products.id',
                    'products.name as product',
                    'products.type',
                    'products.supplier_id',
                    'products.description',
                    'suppliers.name as supplier_name',
                    'c1.name as category',
                    'c2.name as sub_category',
                    'units.actual_name as unit',
                    'tax_rates.name as tax',
                    'products.sku',
                    'products.created_at',
                    'products.bulk_add',
                    'products.image',
                    'products.refference',
                    'products.enable_stock',
                    'products.is_inactive',
                    'sizes.name as size',
                    'colors.name as color',
                    'v.dpp_inc_tax as purchase_price',
                    'v.sell_price_inc_tax as selling_price',
                    DB::raw('SUM(vld.qty_available) as current_stock'),
                    DB::raw('MAX(v.sell_price_inc_tax) as max_price'),
                    DB::raw('MIN(v.sell_price_inc_tax) as min_price')
                )->orderBy('created_at','asc')->groupBy('products.id');

            // $type = request()->get('type', null);
            // if (!empty($type)) {
            //     $products->where('products.p_type', $type);
            // }
            
            $supplier_id = request()->input('supplier_id');
            if(!empty($supplier_id)){
                $products->where('products.supplier_id', '=',$supplier_id);
            }

            $category_id = request()->get('category_id', null);
            if (!empty($category_id)) {
                $products->where('products.sub_category_id', $category_id);
            }

            $brand_id = request()->get('brand_id', null);
            if (!empty($brand_id)) {
                $products->where('products.brand_id', $brand_id);
            }

            $unit_id = request()->get('unit_id', null);
            if (!empty($unit_id)) {
                $products->where('products.unit_id', $unit_id);
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $products->where('products.tax', $tax_id);
            }
            $products->orderBy('products.id', 'DESC');

            $from_date = request()->get('from_date', null);

            $to_date = request()->get('to_date', null);

            if (!empty($to_date)) {
                $products->whereDate('products.created_at', '>=', $from_date)
                    ->whereDate('products.created_at', '<=', $to_date);
            }

            return Datatables::of($products)
                ->addColumn(
                    'action',
                    function ($row) use ($selling_price_group_count) {
                        $html =
                            '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">' . __("messages.actions") . ' <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li><a href="' . action('LabelsController@show') . '?product_id=' . $row->id . '" data-toggle="tooltip" title="Print Barcode/Label"><i class="fa fa-barcode"></i> ' . __('barcode.labels') . '</a></li>';

                        if (auth()->user()->can('product.view')) {
                            $html .=
                                '<li><a href="' . action('ProductController@view', [$row->id]) . '" class="view-product"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                        }

                        if (auth()->user()->can('product.update')) {
                            $html .=
                                '<li><a href="' . action('ProductController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        }

                        if (auth()->user()->can('product.delete')) {
                            $html .=
                                '<li><a href="' . action('ProductController@destroy', [$row->id]) . '" class="delete-product"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        }

                        if ($row->is_inactive == 1) {
                            $html .=
                                '<li><a href="' . action('ProductController@activate', [$row->id]) . '" class="activate-product"><i class="fa fa-circle-o"></i> ' . __("lang_v1.reactivate") . '</a></li>';
                        }

                        $html .= '<li class="divider"></li>';

                        if (auth()->user()->can('product.create')) {
                            // $url = url("website/product/".$row->id."/special_category");
                            $html .=
                                '<li>
                                    <a href="' . action('WebsiteController@specialCategoriesForm', [$row->id]) . '">
                                    <i class="fa fa-sign-in"></i> Move To Special Category </a></li>';
                            $html .=
                                '<li>
                                    <a href="' . action('WebsiteController@addImagesForm', [$row->id]) . '">
                                    <i class="fa fa-image"></i> Add Images </a></li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->editColumn('product', function ($row) {
                    $product = $row->is_inactive == 1 ? $row->product . ' <span class="label bg-gray">Inactive
                        </span>' : $row->product;
                    return $product;
                })
                ->editColumn('description', function ($row) {
                    $description = '-';
                    if ($row->description) {
                        $description = $row->description;
                    }
                    return $description;
                })
                ->editColumn('image', function ($row) {
                    return '<div style="display: flex;"><img src="' . $row->image_url . '" alt="Product image" class="product-thumbnail-small"></div>';
                })
                ->editColumn('bulk_add', function ($row) {
                    return $row->bulk_add;
                })
                ->editColumn('date', function ($row) {
                    return $row->created_at;
                })
                ->editColumn('type', '@lang("lang_v1." . $type)')
                ->addColumn('mass_delete', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->id . '">';
                })
                ->editColumn('current_stock', '@if($enable_stock == 1) {{@number_format($current_stock)}} @else -- @endif {{$unit}}')
                ->addColumn(
                    'price',
                    '<div style="white-space: nowrap;"><span class="display_currency" data-currency_symbol="true">{{$min_price}}</span> @if($max_price != $min_price && $type == "variable") -  <span class="display_currency" data-currency_symbol="true">{{$max_price}}</span>@endif </div>'
                )
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("product.view")) {
                            return  action('ProductController@view', [$row->id]);
                        } else {
                            return '';
                        }
                    }
                ])
                ->rawColumns(['action', 'image', 'mass_delete', 'product', 'price'])
                ->make(true);
        }

        $rack_enabled = (request()->session()->get('business.enable_racks') || request()->session()->get('business.enable_row') || request()->session()->get('business.enable_position'));

        $categories = Category::forDropdown($business_id);
        $suppliers = Supplier::forDropdown($business_id);
        $businessArr = Business::forDropdown($business_id);

        $brands = Brands::forDropdown($business_id);

        $units = Unit::forDropdown($business_id);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, false);
        $taxes = $tax_dropdown['tax_rates'];

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('website_products.index',compact(
                'rack_enabled',
                'categories',
                'brands',
                'units',
                'taxes',
                'businessArr',
                'business_locations',
                'suppliers'
            ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function specialCategoriesForm($id)
    {
        $product = Product::find($id);

        $special_product = SpecialCategoryProduct::where('refference',$product->refference)->first();

        // dd($special_product);

        return view('website_products.special_category',compact('product','special_product'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addspecialCategories(Request $request)
    {
        $request->validate([
            'description' => 'required|min:20'
        ]);
        $product = Product::find($request->input('p_id'));
        // dd($product->variations()->first()->dpp_inc_tax);
        if (!is_null($product->refference)) {
           
        // $special = SpecialCategoryProduct::where('product_id',$product->id)->first();
        // if (is_null($special)) {
        //     $special = new SpecialCategoryProduct();
        // }
        
        $special = SpecialCategoryProduct::firstOrNew(['refference'=>$product->refference]);
        
        // dd($special);
        if ($request->has('featured')) {
            $special->featured = '1';
        }else{
            $special->featured = '0';
        }
        
        if ($request->has('new_arrival')) {
            $special->new_arrival = '1';
        }else{
            $special->new_arrival = '0';
            
        }
        $product_price = $product->variations()->first()->dpp_inc_tax;
        if ($request->has('sale')) {
            
            // $percentage = $request->input('sale_percent')/100;
            // $discounted_price =  $product_price * $percentage;
            // $after_discount = $product_price - $discounted_price;
            $ut = new Util();
            $special->sale = '1';
            $special->after_discount = $ut->num_uf($request->input('after_discount'));
            // $special->sale_percentage = $request->input('sale_percent');
            // $special->discounted_price = $discounted_price;
        }else{
            $special->sale = '0';
            // $special->sale_percentage = null;
            // $special->discounted_price = null;
            $special->after_discount = null;
        }
        $special->product_id = $product->id;
        $special->refference = $product->refference;
        $special->description = $request->input('description');
        $special->price = $product->variations()->first()->dpp_inc_tax;


        $special->save();

        $output = [
                'success' => 1,
                'msg' => "'".$product->name."' added into spacial categories"
            ];
            // 'website/product/list'
        }else{
            $output = [
                    'success' => 0,
                    'msg' => "'".$product->name."' Refference Not found Please add refference of this Product in order to continue"
                ];
        }
        return redirect()->back()->with('status',$output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addImagesForm($id)
    {
        $product = Product::find($id);
        $product_images = ProductImages::where('refference',$product->refference)->get();

        $all = Product::where('refference',$product->refference)->get();

        // dd($all);
        return view('website_products.images',compact('product','product_images'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addImages(Request $request,$id)
    {
        $product = Product::find($id);
        
        try{
            $image_index[0] = null;
            $i=0;
            foreach ($request->file('image') as $key => $value) {
                $image_index[$i] = $key;
                $i++;
            }
            for($i = 0; $i< count($image_index); $i++){
                $product_images = new ProductImages();
                
                $file = $request->file('image')[$image_index[$i]];
                $name = time().'.'.$file->extension();
                $file->move(public_path().'/uploads/img/', $name);  
                $product_images->product_id = $product->id;
                $product_images->refference = $product->refference;
                $product_images->image = $name;

                $product_images->save();
            }
            $output = [
                    'success' => 1,
                    'msg' => "Images Saved"
                ];
        }
        catch(\Exception $ex){
                dd($ex->getMessage());
            $output = [
                'success' => 0,
                'msg' => "Image Could not be Saved"
            ];
        }
        return redirect()->back()->with('status',$output);

    }

    /**
     * Delete Image
     *
     * @param  int  $id
     */
    public function deleteImage( $id)
    {
        $product_image = ProductImages::find($id);
        try {
            $product_image->delete();
        $output = [
                    'success' => 1,
                    'msg' => "Image Deleted"
                ];
            // 'website/product/list'
        }
        catch(\Exception $ex){
            $output = [
                'success' => 0,
                'msg' => "Image Could not be deleted"
            ];
        }
        return redirect()->back()->with('status',$output); //throw $th;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
