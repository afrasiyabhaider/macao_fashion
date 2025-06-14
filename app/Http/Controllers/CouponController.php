<?php

namespace App\Http\Controllers;

use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\GiftCard;
use App\Coupon;
use App\ProductVariation;
use App\PurchaseLine;
use App\SellingPriceGroup;
use App\TaxRate;
use App\Unit;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Variation;
use App\VariationGroupPrice;

use App\VariationLocationDetails;
use App\VariationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;


class CouponController extends Controller
{
    /**
     * All Utils instance. 
     gift.reports
gift.create
gift.view
gift.update
gift.delete

lastreloadDate ...expiry date is 1year above 
coupon ki 3 month expire date

     *
     */
    protected $productUtil;

    private $barcode_types;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;

        //barcode types
        $this->barcode_types = $this->productUtil->barcode_types();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('coupon.create') && !auth()->user()->can('coupon.views')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');

        if (request()->ajax()) {
            $products = Coupon::leftJoin('gift_cards', 'coupons.gift_card_id', '=', 'gift_cards.id')
                ->where('coupons.business_id', $business_id)
                ->where('coupons.created_by', $user_id)
                ->select(
                    'coupons.id',
                    'coupons.name',
                    'gift_cards.barcode as GiftCardBarcode', 
                    'coupons.value',
                    'coupons.orig_value',
                    'coupons.barcode',
                    'coupons.start_date', 
                    'coupons.isActive',
                    'coupons.details',
                    'coupons.isUsed' 
                )->groupBy('coupons.barcode');

            $type = request()->get('type', null);
            if (!empty($type)) {
                $products->where('coupons.isActive', $type);
            }else
            {
                $products->where('coupons.isActive', '!=', 'cancell');
            }

            $gift_card_id = request()->get('gift_cards', null);
            if (!empty($gift_card_id)) {
                $products->where('coupons.gift_card_id', $gift_card_id);
            }

            return Datatables::of($products)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html =
                        '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'. __("messages.actions") . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li><a href="' . action('LabelsController@show') . '?product_id=' . $row->id . '" data-toggle="tooltip" title="Print Barcode/Label"><i class="fa fa-barcode"></i> ' . __('barcode.labels') . '</a></li>';

                        if (auth()->user()->can('coupon.view')) {
                            $html .=
                            '<li><a href="' . action('CouponController@view', [$row->id]) . '" class="view-product"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                        }

                        if (auth()->user()->can('coupon.update')) {
                            $html .=
                            '<li><a href="' . action('CouponController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        }

                        if (auth()->user()->can('coupon.delete')) {
                            $html .=
                            '<li><a href="' . action('CouponController@destroy', [$row->id]) . '" class="delete-product"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        }

                        if ($row->isActive != "active") {
                            $html .=
                            '<li><a href="' . action('CouponController@activate', [$row->id]) . '" class="activate-product"><i class="fa fa-circle-o"></i> ' . __("lang_v1.reactivate") . '</a></li>';
                        }

                        $html .= '<li class="divider"></li>';

                        if (auth()->user()->can('coupon.create')) {
                            if ($row->enable_stock == 1) {
                                $html .=
                                '<li><a href="#" data-href="' . action('OpeningStockController@add', ['product_id' => $row->id]) . '" class="add-opening-stock"><i class="fa fa-database"></i> ' . __("lang_v1.add_edit_opening_stock") . '</a></li>';
                            }
            

                            $html .=
                                '<li><a href="' . action('CouponController@create', ["d" => $row->id]) . '"><i class="fa fa-copy"></i> ' . __("lang_v1.duplicate_product") . '</a></li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->editColumn('name', function ($row) {
                    return '<div style="display: flex;">' . $row->name . '</div>';
                     
                })
                ->editColumn('value', function ($row) {
                    return '<div style="display: flex;">' . $row->value . ' / ' . $row->orig_value . '</div>';
                })
                ->editColumn('barcode', function ($row) {
                    return '<div style="display: flex;">' . $row->barcode . '</div>';
                })
                ->editColumn('details', function ($row) {
                    return $row->details;
                })
                ->addColumn('mass_delete', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->id .'">' ;
                }) 
                ->editColumn('start_date', function ($row) {
                    return date("Y-m-d h:i A",strtotime($row->start_date));
                })
                ->editColumn('CouponExpiryDate', function ($row) {
                    return date("Y-m-d h:i A",strtotime($row->start_date ."+3 months"));
                })
                ->editColumn('GiftCardBarcode', function ($row) {
                    return $row->GiftCardBarcode;
                })
                ->editColumn('isActive', function ($row) {
                    return $row->isActive;
                })
                ->editColumn('isUsed', function ($row) {
                    return $row->isUsed;
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("coupon.view")) {
                            return  action('CouponController@view', [$row->id]) ;
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['action', 'name', 'details', 'value', 'mass_delete', 'barcode', 'start_date'])
                ->make(true);
        }

        $rack_enabled = (request()->session()->get('business.enable_racks') || request()->session()->get('business.enable_row') || request()->session()->get('business.enable_position'));

        return view('coupon.index')
            ->with(compact(
                'rack_enabled' 
            ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('coupon.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for products quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (!$this->moduleUtil->isQuotaAvailable('products', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('products', $business_id, action('ProductController@index'));
        }

        
        $brands = Brands::where('business_id', $business_id)
                            ->pluck('name', 'id');
       
        $barcode_types = $this->barcode_types;
        $barcode_default =  $this->productUtil->barcode_default();
        //Get all business locations
       
        $duplicate_product = null;
        $rack_details = null;
        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');
        $RandomId = $this->productUtil->RandomId();

        return view('coupon.create')
            ->with(compact(  'brands', 'barcode_types','barcode_default', 'module_form_parts', 'RandomId'));
    }

    /**
     * Store a newly created resource in storage.
     *['name', 'barcode', 'business_id', 'gift_card_id', 'value', 'barcode_type', 'isActive', 'transaction_id','start_date', 'created_by', 'isUsed']
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('coupon.create')) {
            abort(403, 'Unauthorized action.');
        } 

        try {
            $business_id = $request->session()->get('user.business_id');
            $form_fields =['name', 'barcode', 'business_id', 'gift_card_id', 'value', 'barcode_type', 'isActive', 'transaction_id','start_date', 'created_by', 'isUsed'];

            $module_form_fields = $this->moduleUtil->getModuleFormField('product_form_fields');
            if (!empty($module_form_fields)) {
                $form_fields = array_merge($form_fields, $module_form_fields);
            }
            // dd($form_fields);
            
            $objDetails = $request->only($form_fields);
            $objDetails['business_id'] = $business_id;
            $objDetails['orig_value'] = $objDetails['value'];
            $objDetails['created_by'] = $request->session()->get('user.id');
 
            $objDetails['isActive'] = 'inactive' ;
            $objDetails['isUsed'] = '0' ;
            // $objDetails['barcode'] = rand(11111,99999);
            $objDetails['barcode'] = $objDetails['barcode'];
            // dd($objDetails);
            // if(empty($objDetails['barcode'])){
            //     $objDetails['barcode'] = 1;
            // }
            if(empty($objDetails['barcode'])){
                $objDetails['barcode'] = $this->generateUUID(6);
            }
            DB::beginTransaction();

            $GiftCard = Coupon::create($objDetails);
            if(!empty($objDetails['barcode']) && $GiftCard->barcode == 1 ){
                    $barcode= $this->productUtil->generateProductSku($GiftCard->id);
                    $GiftCard->barcode = $barcode;
                } 
            $GiftCard->save();
             
            DB::commit();
            $output = ['success' => 1,
                            'msg' => __('coupon.sucess')
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong"). $e->getMessage()
                        ];
            return redirect('coupon')->with('status', $output);
        }

        if ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                'CouponController@create'
            )->with('status', $output);
        }

        return redirect('coupon')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('coupon.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $details = $this->productUtil->getRackDetails($business_id, $id, true);

        return view('coupon.index')->with(compact('details'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('coupon.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
         
        $brands = GiftCard::where('business_id', $business_id)
                            ->pluck('name', 'id');
          
        $barcode_types = $this->barcode_types;
        
        $product = Coupon::where('business_id', $business_id)
                            ->where('id', $id)
                            ->first();
        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');

        return view('coupon.edit')
                ->with(compact('barcode_types', 'product', 'module_form_parts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('coupon.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $product_details = $request->only(['name', 'barcode', 'business_id', 'type', 'applicable', 'product_id', 'brand_id', 'value', 'details', 'barcode_type', 'created_by', 'start_date', 'expiry_date', 'isActive', 'isUsed', 'consume_date']);

            DB::beginTransaction();
            
            $product = Coupon::where('business_id', $business_id)
                                ->where('id', $id)
                                ->first();
            $product->name = $product_details['name'];
            $product->barcode = $product_details['barcode'];
            $product->value = $product_details['value'];
            $product->details = $product_details['details'];
            $product->barcode_type = $product_details['barcode_type'];
            $product->start_date = $product_details['start_date'];
            $product->isActive = $product_details['isActive'];
            $product->save();
            DB::commit();
            $output = ['success' => 1,
                            'msg' => __('coupon.updated')
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => $e->getMessage() . __("messages.something_went_wrong")
                        ];
        }


        if ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                'CouponController@create'
            )->with('status', $output);
        }

        return redirect('coupon')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('coupon.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $can_be_deleted = true;
                $error_msg = '';
                if ($can_be_deleted) {
                    $product = Coupon::where('id', $id)
                                ->where('business_id', $business_id)
                                ->first();
                    if (!empty($product)) {
                        DB::beginTransaction(); 
                        $product->delete();
                        DB::commit();
                    }

                    $output = ['success' => true,
                                'msg' => __("coupon.deleted")
                            ];
                } else {
                    $output = ['success' => false,
                                'msg' => $error_msg
                            ];
                }
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                
                $output = ['success' => false,
                                'msg' => __("messages.something_went_wrong")
                            ];
            }

            return $output;
        }
    }
    
    /**
     * Get subcategories list for a category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getSubCategories(Request $request)
    {
        if (!empty($request->input('cat_id'))) {
            $category_id = $request->input('cat_id');
            $business_id = $request->session()->get('user.business_id');
            $sub_categories = Category::where('business_id', $business_id)
                        ->where('parent_id', $category_id)
                        ->select(['name', 'id'])
                        ->get();
            $html = '<option value="">None</option>';
            if (!empty($sub_categories)) {
                foreach ($sub_categories as $sub_category) {
                    $html .= '<option value="' . $sub_category->id .'">' .$sub_category->name . '</option>';
                }
            }
            echo $html;
            exit;
        }
    }

    /**
     * Get product form parts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getProductVariationFormPart(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $business = Business::findorfail($business_id);
        $profit_percent = $business->default_profit_percent;

        $action = $request->input('action');
        if ($request->input('action') == "add") {
            if ($request->input('type') == 'single') {
                return view('product.partials.single_product_form_part')
                        ->with(['profit_percent' => $profit_percent]);
            } elseif ($request->input('type') == 'variable') {
                $variation_templates = VariationTemplate::where('business_id', $business_id)->pluck('name', 'id')->toArray();
                $variation_templates = [ "" => __('messages.please_select')] + $variation_templates;

                return view('product.partials.variable_product_form_part')
                        ->with(compact('variation_templates', 'profit_percent', 'action'));
            }
        } elseif ($request->input('action') == "edit" || $request->input('action') == "duplicate") {
            $product_id = $request->input('product_id');
            if ($request->input('type') == 'single') {
                $product_deatails = ProductVariation::where('product_id', $product_id)
                                                    ->with(['variations'])
                                                    ->first();
                
                return view('product.partials.edit_single_product_form_part')
                            ->with(compact('product_deatails'));
            } elseif ($request->input('type') == 'variable') {
                $product_variations = ProductVariation::where('product_id', $product_id)
                                                    ->with(['variations'])
                                                    ->get();
                return view('product.partials.variable_product_form_part')
                        ->with(compact('product_variations', 'profit_percent', 'action'));
            }
        }
    }
    
    /**
     * Get product form parts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getVariationValueRow(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $business = Business::findorfail($business_id);
        $profit_percent = $business->default_profit_percent;

        $variation_index = $request->input('variation_row_index');
        $value_index = $request->input('value_index') + 1;

        $row_type = $request->input('row_type', 'add');

        return view('product.partials.variation_value_row')
                ->with(compact('profit_percent', 'variation_index', 'value_index', 'row_type'));
    }

    /**
     * Get product form parts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getProductVariationRow(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $business = Business::findorfail($business_id);
        $profit_percent = $business->default_profit_percent;

        $variation_templates = VariationTemplate::where('business_id', $business_id)
                                                ->pluck('name', 'id')->toArray();
        $variation_templates = [ "" => __('messages.please_select')] + $variation_templates;

        $row_index = $request->input('row_index', 0);
        $action = $request->input('action');

        return view('product.partials.product_variation_row')
                    ->with(compact('variation_templates', 'row_index', 'action', 'profit_percent'));
    }

    /**
     * Get product form parts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getVariationTemplate(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $business = Business::findorfail($business_id);
        $profit_percent = $business->default_profit_percent;

        $template = VariationTemplate::where('id', $request->input('template_id'))
                                                ->with(['values'])
                                                ->first();
        $row_index = $request->input('row_index');

        return view('product.partials.product_variation_template')
                    ->with(compact('template', 'row_index', 'profit_percent'));
    }

    /**
     * Retrieves products list.
     *
     * @param  string  $q
     * @param  boolean  $check_qty
     *
     * @return JSON
     */
    public function getProducts()
    {
        if (request()->ajax()) {
            $term = request()->input('term', '');
            $location_id = request()->input('location_id', '');

            $check_qty = request()->input('check_qty', false);

            $price_group_id = request()->input('price_group', '');

            $business_id = request()->session()->get('user.business_id');

           
          
            $products->where('gift_cards.business_id', $business_id)
                ->where('gift_cards.isActive', '!=', 'no');

            //Include search
            if (!empty($term)) {
                $products->where(function ($query) use ($term) {
                    $query->where('gift_cards.name', 'like', '%' . $term .'%');
                    $query->orWhere('gift_cards.barcode', 'like', '%' . $term .'%');
                });
            }

            //Include check for quantity
             
            
            $products->select(
                'gift_cards.id as product_id',
                'gift_cards.name',
                'gift_cards.type',
                'gift_cards.value as enable_stock',
                'U.short_name as unit'
            );
            
            $result = $products->orderBy('gift_cards.id', 'desc')
                        ->get();
            return json_encode($result);
        }
    }

    /**
     * Retrieves products list without variation list
     *
     * @param  string  $q
     * @param  boolean  $check_qty
     *
     * @return JSON
     */
    public function getProductsWithoutVariations()
    {
        if (request()->ajax()) {
            $term = request()->input('term', '');
            //$location_id = request()->input('location_id', '');

            //$check_qty = request()->input('check_qty', false);

            $business_id = request()->session()->get('user.business_id');

            $products = Product::join('variations', 'products.id', '=', 'variations.product_id')
                ->where('products.business_id', $business_id)
                ->where('products.type', '!=', 'modifier');
                
            //Include search
            if (!empty($term)) {
                $products->where(function ($query) use ($term) {
                    $query->where('products.name', 'like', '%' . $term .'%');
                    $query->orWhere('sku', 'like', '%' . $term .'%');
                    $query->orWhere('sub_sku', 'like', '%' . $term .'%');
                });
            }

            //Include check for quantity
            // if($check_qty){
            //     $products->where('VLD.qty_available', '>', 0);
            // }
            
            $products = $products->groupBy('products.id')
                ->select(
                    'products.id as product_id',
                    'products.name',
                    'products.type',
                    'products.enable_stock',
                    'products.sku'
                )
                    ->orderBy('products.name')
                    ->get();
            return json_encode($products);
        }
    }

    /**
     * Checks if product sku already exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkProductSku(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $sku = $request->input('sku');
        $product_id = $request->input('product_id');

        //check in products table
        $query = Product::where('business_id', $business_id)
                        ->where('sku', $sku);
        if (!empty($product_id)) {
            $query->where('id', '!=', $product_id);
        }
        $count = $query->count();
        
        //check in variation table if $count = 0
        if ($count == 0) {
            $count = Variation::where('sub_sku', $sku)
                            ->join('products', 'variations.product_id', '=', 'products.id')
                            ->where('product_id', '!=', $product_id)
                            ->where('business_id', $business_id)
                            ->count();
        }
        if ($count == 0) {
            echo "true";
            exit;
        } else {
            echo "false";
            exit;
        }
    }

    /**
     * Loads quick add product modal.
     *
     * @return \Illuminate\Http\Response
     */
    public function quickAdd(Request $request)
    {
        // dd($request->all());
        if (!auth()->user()->can('coupon.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        
        $location_id = request()->location_id??1;
        $brands = Brands::where('business_id', $business_id)
                            ->pluck('name', 'id');
       
        $barcode_types = $this->barcode_types;
        $barcode_default =  $this->productUtil->barcode_default();
        //Get all business locations
       
        $duplicate_product = null;
        $rack_details = null;
        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');
        $RandomId = $this->productUtil->RandomId();
 
        return view('coupon.partials.quick_add')->with(compact(  'brands', 'barcode_types','barcode_default', 'module_form_parts','RandomId','location_id'));
    }


    public function generateUUID($length)
    {
        $characters = '0123456789';
        $uuid = '';

        for ($i = 0; $i < $length; $i++) {
            $uuid .= $characters[rand(0, 6)];
        }

        return $uuid;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    
    public function saveQuickProduct(Request $request)
    {
        if (!auth()->user()->can('coupon.create')) {
            abort(403, 'Unauthorized action.');
        } 
        
        $request->validate([
            'location_id' => 'required',
        ]);
        
        try {
            $business_id = $request->session()->get('user.business_id');
            $form_fields = ['name', 'barcode', 'business_id', 'gift_card_id', 'value', 'barcode_type', 'isActive', 'transaction_id','start_date', 'created_by', 'isUsed'];
        
            $module_form_fields = $this->moduleUtil->getModuleFormField('product_form_fields');
            if (!empty($module_form_fields)) {
                $form_fields = array_merge($form_fields, $module_form_fields);
            }
            
            $objDetails = $request->only($form_fields);
            $objDetails['business_id'] = $business_id;
            $objDetails['orig_value'] = $objDetails['value'];
            $objDetails['created_by'] = $request->session()->get('user.id');
            $objDetails['isActive'] = 'inactive';
            $objDetails['isUsed'] = '0';
            
            // FIX 1: Proper barcode generation logic
            if (empty($objDetails['barcode'])) {
                // Generate a temporary barcode first, will be replaced after creation
                $objDetails['barcode'] = 'TEMP_' . uniqid();
            }
            
            DB::beginTransaction();
            
            $GiftCard = Coupon::create($objDetails);
            
            // FIX 2: Always generate proper barcode after creation
            $barcode = $this->productUtil->generateProductSku($GiftCard->id);
            
            // FIX 3: Ensure barcode is never null or empty
            if (empty($barcode)) {
                $barcode = $this->generateUUID(6);
            }
            
            // FIX 4: Additional fallback
            if (empty($barcode)) {
                $barcode = 'COUPON_' . $GiftCard->id . '_' . time();
            }
            
            $GiftCard->barcode = $barcode;
            $GiftCard->save();
            
            //------ PRODUCT Creation Start
            $objProductDetails['name'] = "Coupon - " . $GiftCard->barcode;
            $objProductDetails['business_id'] = $request->session()->get('user.business_id');
            $objProductDetails['unit_id'] = 1;
            $objProductDetails['category_id'] = 1;
            $objProductDetails['barcode_type'] = 'C128';
            $objProductDetails['tax_type'] = 'exclusive';
            $objProductDetails['sku'] = $GiftCard->barcode;
            $objProductDetails['alert_quantity'] = '1';
            $objProductDetails['type'] = 'single';
            $objProductDetails['p_type'] = 'coupon';
            $objProductDetails['coupon'] = $GiftCard->id;
            $objProductDetails['created_by'] = $request->session()->get('user.id');

            $objProduct = Product::create($objProductDetails);

            $product_variation_data = [
                'name' => 'DUMMYCOUPON',
                'product_id' => $objProduct->id,
                'is_dummy' => 1 
            ];
            $product_variation = ProductVariation::create($product_variation_data);

            $objVariationDetails['name'] = 'DUMMY';
            $objVariationDetails['product_id'] = $objProduct->id;
            $objVariationDetails['sub_sku'] = $objProduct->sku;
            $objVariationDetails['product_variation_id'] = $product_variation->id;
            $objVariationDetails['default_purchase_price'] = 1.0;
            $objVariationDetails['dpp_inc_tax'] = 1.0;
            $objVariationDetails['profit_percent'] = 0;
            $objVariationDetails['default_sell_price'] = $GiftCard->value;
            $objVariationDetails['sell_price_inc_tax'] = $GiftCard->value;

            $objVariations = Variation::create($objVariationDetails);

            $objVariationLocationDetails['qty_available'] = '1';  
            $objVariationLocationDetails['location_id'] = $request->location_id;
            $objVariationLocationDetails['product_id'] = $objProduct->id;
            $objVariationLocationDetails['product_variation_id'] = $product_variation->id;
            $objVariationLocationDetails['variation_id'] = $objVariations->id;
            $objVariationLocationDetails['product_refefrence'] = $objProduct->refference;
            $objVariationLocationDetails['sell_price'] = $GiftCard->value;

            $objVariationsLocation = VariationLocationDetails::create($objVariationLocationDetails);
            //------ PRODUCT Creation Ends

            DB::commit();
            
            $output = [
                'success' => 1,
                'msg' => 'Coupon Added Successfully  
                            <script type="text/javascript"> 
                                $("#search_product").val("'.$objProduct->sku.'");
                                $("#search_product").autocomplete("search");
                            </script>'
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            // FIX 5: Check if this is an AJAX request
            if ($request->expectsJson() || $request->ajax()) {
                $output = [
                    'success' => 0,
                    'msg' => __("messages.something_went_wrong") . ': ' . $e->getMessage()
                ];
                return response()->json($output, 422);
            }
            
            // For regular form submissions
            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];
            return redirect('coupon')->with('status', $output);
        } 

        // FIX 6: Always return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($output);
        }
        
        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function view($id)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $product = Product::where('business_id', $business_id)
                            ->where('id', $id)
                            ->with(['brand', 'unit', 'category', 'sub_category', 'product_tax', 'variations', 'variations.product_variation', 'variations.group_prices'])
                            ->first();

        $price_groups = SellingPriceGroup::where('business_id', $business_id)->pluck('name', 'id');

        $allowed_group_prices = [];
        foreach ($price_groups as $key => $value) {
            if (auth()->user()->can('selling_price_group.' . $key)) {
                $allowed_group_prices[$key] = $value;
            }
        }

        $group_price_details = [];

        foreach ($product->variations as $variation) {
            foreach ($variation->group_prices as $group_price) {
                $group_price_details[$variation->id][$group_price->price_group_id] = $group_price->price_inc_tax;
            }
        }

        $rack_details = $this->productUtil->getRackDetails($business_id, $id, true);

        return view('coupon.view-modal')->with(compact(
            'product',
            'rack_details',
            'allowed_group_prices',
            'group_price_details'
        ));
    }

    /**
     * Mass deletes products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function massDestroy(Request $request)
    {
        if (!auth()->user()->can('coupon.delete')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $purchase_exist = false;
            $IsAlreadyUsed = "";

            if (!empty($request->input('selected_rows'))) {
                $business_id = $request->session()->get('user.business_id');

                $selected_rows = explode(',', $request->input('selected_rows'));

                $products = Coupon::where('business_id', $business_id)
                                    ->whereIn('id', $selected_rows)
                                    ->get();
                $deletable_products = [];


                DB::beginTransaction();

                foreach ($products as $product) {
                    //Delete if no its Not Used
                    if ($product->isUsed == 0) {
                        //Delete variation location details
                        $product->delete();
                    } else {
                        $purchase_exist = true;
                        $IsAlreadyUsed .= $product->name ." ,";
                    }
                }
                DB::commit();
            }

            if (!$purchase_exist) {
                $output = ['success' => 1,
                            'msg' => __('lang_v1.deleted_success')
                        ];
            } else {
                $output = ['success' => 0,
                            'msg' => 'Sorry These coupon '.$IsAlreadyUsed.' is Already Used So We Cannot Delete That All Other is Deleted'
                        ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong")
                        ];
        }

        return redirect()->back()->with(['status' => $output]);
    }
 
    /**
     * Mass deactivates products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function massDeactivate(Request $request)
    {
        if (!auth()->user()->can('coupon.update')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (!empty($request->input('selected_products'))) {
                $business_id = $request->session()->get('user.business_id');

                $selected_products = explode(',', $request->input('selected_products'));

                DB::beginTransaction();

                $products = Coupon::where('business_id', $business_id)
                                    ->whereIn('id', $selected_products)
                                    ->update(['isActive' => "cancell"]);

                DB::commit();
            }

            $output = ['success' => 1,
                            'msg' => __('lang_v1.products_deactivated_success')
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong")
                        ];
        }

        return redirect()->back()->with(['status' => $output]);
    }


    /**
     * Activates the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function activate($id)
    {
        if (!auth()->user()->can('coupon.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                $product = Coupon::where('id', $id)
                                ->where('business_id', $business_id)
                                ->update(['isActive' => "active"]);

                $output = ['success' => true,
                                'msg' => __("lang_v1.updated_success")
                            ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                
                $output = ['success' => false,
                                'msg' => __("messages.something_went_wrong")
                            ];
            }

            return $output;
        }
    }
 
}