<?php

namespace App\Http\Controllers;

use App\Brands;
use App\Supplier;
use App\ProductNameCategory;
use App\Size;
use App\Color;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\LocationTransferDetail;
use App\Product;
use App\ProductVariation;
use App\ProductQuantity;
use App\PurchaseLine;
use App\SellingPriceGroup;
use App\TaxRate;
use App\TransactionSellLine;
use App\Unit;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Variation;
use App\VariationGroupPrice;

use App\VariationLocationDetails;
use App\VariationTemplate;
use App\WebsiteProducts;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    /**
     * All Utils instance.
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

    public function getProductId()
    {
        $objBuss = \App\Business::find(request()->session()->get('user.business_id'));
        return str_pad($objBuss->prod_refference, 4, '0', STR_PAD_LEFT);
    }
    public function updateProductId()
    {
        $objBuss = \App\Business::find(request()->session()->get('user.business_id'));
        $objBuss->prod_refference += 1;
        $number = str_pad($objBuss->prod_refference, 4, '0', STR_PAD_LEFT);
        $objBuss->save();
        return $number;
    }
    public function index()
    {
        // dd(auth()->user()->getRoleNameAttribute());
        if (!auth()->user()->can('product.view') && !auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        //Update USER SESSION
        $user_id = request()->session()->get('user.id');
        $user = \App\User::find($user_id);
        request()->session()->put('user', $user->toArray());
        $business_location_id = request()->session()->get('user.business_location_id');
        //Update USER SESSION
        $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);

        // dd($business_id);

        if (request()->ajax()) {
            // ->where('vld.location_id', $business_location_id)

            $products = Product::leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                ->join('units', 'products.unit_id', '=', 'units.id')
                ->leftJoin('categories as c1', 'products.category_id', '=', 'c1.id')
                ->leftJoin('categories as c2', 'products.sub_category_id', '=', 'c2.id')
                ->leftJoin('tax_rates', 'products.tax', '=', 'tax_rates.id')
                ->leftJoin('sizes', 'products.sub_size_id', '=', 'sizes.id')
                ->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                ->leftJoin('variation_location_details as vld', 'vld.product_id', '=', 'products.id')
                ->where('vld.location_id', 1)
                ->join('variations as v', 'v.product_id', '=', 'products.id')
                ->join('suppliers', 'suppliers.id', '=', 'products.supplier_id')
                ->where('products.business_id', $business_id)
                ->where('products.type', '!=', 'modifier')
                ->select(
                    'products.id',
                    'products.name as product',
                    'products.type',
                    'products.description',
                    'products.supplier_id',
                    'suppliers.name as supplier_name',
                    'c1.name as category',
                    'c2.name as sub_category',
                    'units.actual_name as unit',
                    'brands.name as brand',
                    'tax_rates.name as tax',
                    'products.sku',
                    'products.created_at',
                    'products.bulk_add',
                    'products.image',
                    'products.refference',
                    'products.enable_stock',
                    'products.is_inactive',
                    'products.updated_at',
                    'sizes.name as size',
                    'vld.product_updated_at as product_date',
                    'colors.name as color',
                    'v.dpp_inc_tax as purchase_price',
                    // 'v.sell_price_inc_tax as selling_price',
                    'vld.sell_price as selling_price',
                    'vld.qty_available as current_stock',
                    // DB::raw('SUM(vld.qty_available) as current_stock'),
                    DB::raw('MAX(v.sell_price_inc_tax) as max_price'),
                    DB::raw('MIN(v.sell_price_inc_tax) as min_price')
                )
                // ->orderBy('products.updated_at', 'DESC')
                // ->orderBy('vld.updated_at', 'DESC')
                ->orderBy('vld.updated_at', 'DESC')
                ->groupBy('products.id');

            // $type = request()->get('type', null);
            // if (!empty($type)) {
            //     $products->where('products.p_type', $type);
            // }

            $supplier_id = request()->input('supplier_id');
            if (!empty($supplier_id)) {
                $products->where('suppliers.id', '=', $supplier_id);
                // $products->where('products.supplier_id', '=', $supplier_id);
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
                $products->whereDate('vld.product_updated_at', '>=', $from_date)
                    ->whereDate('vld.product_updated_at', '<=', $to_date);
            }

            return Datatables::of($products)
                ->addIndexColumn()
                ->addColumn(
                    'action',
                    function ($row) use ($selling_price_group_count) {
                        $html =
                            '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false"> <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
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
                            if ($row->enable_stock == 1) {
                                $html .=
                                    '<li><a href="#" data-href="' . action('OpeningStockController@add', ['product_id' => $row->id]) . '" class="add-opening-stock"><i class="fa fa-database"></i> ' . __("lang_v1.add_edit_opening_stock") . '</a></li>';
                            }

                            if ($selling_price_group_count > 0) {
                                $html .=
                                    '<li><a href="' . action('ProductController@addSellingPrices', [$row->id]) . '"><i class="fa fa-money"></i> ' . __("lang_v1.add_selling_price_group_prices") . '</a></li>';
                            }

                            $html .=
                                '<li><a href="' . action('ProductController@create', ["d" => $row->id]) . '"><i class="fa fa-copy"></i> ' . __("lang_v1.duplicate_product") . '</a></li>';
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
                ->editColumn('product_date', function ($row) {
                    return Carbon::parse($row->product_date)->format('d-M-Y h:i:s A');
                })
                ->editColumn('bulk_add', function ($row) {
                    return $row->bulk_add;
                })
                ->editColumn('date', function ($row) {
                    return $row->created_at;
                })
                ->editColumn('type', '@lang("lang_v1." . $type)')
                ->editColumn('purchase_price', function ($row) {
                    if (auth()->user()->getRoleNameAttribute() != 'Admin' && auth()->user()->getRoleNameAttribute() != 'admin lalouviere' && auth()->user()->getRoleNameAttribute() != '	
                    ADMIN DOUAIRE' && auth()->user()->getRoleNameAttribute() != 'ADMIN BELLE ILE') {
                        return '-';
                    } else {
                        return $row->purchase_price;
                        // return '<span class="display_currency" data-currency_symbol="true">{{$row->purchase_price}}</span>';
                    }
                })
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
                ->rawColumns(['action', 'purchase_price', 'image', 'mass_delete', 'product', 'price'])
                ->make(true);
        }

        $rack_enabled = (request()->session()->get('business.enable_racks') || request()->session()->get('business.enable_row') || request()->session()->get('business.enable_position'));

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $suppliers = Supplier::forDropdown($business_id);
        $businessArr = Business::forDropdown($business_id);

        $brands = Brands::forDropdown($business_id);

        $units = Unit::forDropdown($business_id);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, false);
        $taxes = $tax_dropdown['tax_rates'];

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('product.index', compact(
            'rack_enabled',
            'categories',
            'brands',
            'units',
            'taxes',
            'businessArr',
            'business_locations',
            'suppliers'
        ));
        // }
    }



    public function transfer()
    {
        if (!auth()->user()->can('product.view') && !auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        //Update USER SESSION
        $user_id = request()->session()->get('user.id');
        $user = \App\User::find($user_id);
        request()->session()->put('user', $user->toArray());
        $business_location_id = request()->session()->get('user.business_location_id');

        //Update USER SESSION
        $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
        if (request()->ajax()) {
            $products = Product::leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                ->join('units', 'products.unit_id', '=', 'units.id')
                ->leftJoin('categories as c1', 'products.category_id', '=', 'c1.id')
                ->leftJoin('categories as c2', 'products.sub_category_id', '=', 'c2.id')
                ->leftJoin('tax_rates', 'products.tax', '=', 'tax_rates.id')
                ->leftJoin('variation_location_details as vld', 'vld.product_id', '=', 'products.id')
                ->join('variations as v', 'v.product_id', '=', 'products.id')
                ->leftJoin('sizes', 'products.sub_size_id', '=', 'sizes.id')
                ->leftJoin('colors', 'products.color_id', '=', 'colors.id')
                ->where('products.business_id', $business_id)
                ->where('vld.location_id', $business_location_id)
                ->join('suppliers', 'suppliers.id', '=', 'products.supplier_id')
                ->where('vld.qty_available', '>', '0')
                ->where('products.type', '!=', 'modifier')
                ->select(
                    'products.id',
                    'products.name as product',
                    'products.type',
                    'products.description',
                    'suppliers.name as supplier_name',
                    'c1.name as category',
                    'c2.name as sub_category',
                    'units.actual_name as unit',
                    'brands.name as brand',
                    'tax_rates.name as tax',
                    'products.sku',
                    'products.created_at',
                    'products.bulk_add',
                    'products.image',
                    'products.enable_stock',
                    'products.refference',
                    'products.is_inactive',
                    'sizes.name as size',
                    'colors.name as color',
                    'v.dpp_inc_tax as purchase_price',
                    // 'v.sell_price_inc_tax as selling_price',
                    'vld.sell_price as selling_price',
                    'vld.printing_qty as printing_qty',
                    DB::raw('SUM(vld.qty_available) as current_stock'),
                    DB::raw('MAX(v.sell_price_inc_tax) as max_price'),
                    DB::raw('MIN(v.sell_price_inc_tax) as min_price')
                )
                ->groupBy('products.id')
                ->orderBy('vld.updated_at', 'DESC');

            $type = request()->get('type', null);
            if (!empty($type)) {
                $products->where('products.p_type', $type);
            }

            $supplier_id = request()->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $products->where('products.supplier_id', '=', $supplier_id);
            }


            $category_id = request()->get('category_id', null);
            if (!empty($category_id)) {
                $products->where('products.category_id', $category_id);
                // dd($products->get());
            }

            $sub_category_id = request()->get('sub_category_id', null);
            if (!empty($sub_category_id)) {
                $products->where('products.sub_category_id', $sub_category_id);
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
                // dd($products->first());
                $products->whereDate('products.created_at', '<=', $from_date)->whereDate('products.created_at', '>=', $to_date);
            }


            return Datatables::of($products)
                ->addIndexColumn()
                ->addColumn(
                    'action',
                    function ($row) use ($selling_price_group_count) {
                        $html =
                            '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">' . __("messages.actions") . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
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
                            if ($row->enable_stock == 1) {
                                $html .=
                                    '<li><a href="#" data-href="' . action('OpeningStockController@add', ['product_id' => $row->id]) . '" class="add-opening-stock"><i class="fa fa-database"></i> ' . __("lang_v1.add_edit_opening_stock") . '</a></li>';
                            }

                            if ($selling_price_group_count > 0) {
                                $html .=
                                    '<li><a href="' . action('ProductController@addSellingPrices', [$row->id]) . '"><i class="fa fa-money"></i> ' . __("lang_v1.add_selling_price_group_prices") . '</a></li>';
                            }

                            $html .=
                                '<li><a href="' . action('ProductController@create', ["d" => $row->id]) . '"><i class="fa fa-copy"></i> ' . __("lang_v1.duplicate_product") . '</a></li>';
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
                ->editColumn('purchase_price', function ($row) {

                    if (auth()->user()->getRoleNameAttribute() != 'Admin' && auth()->user()->getRoleNameAttribute() != 'admin lalouviere' && auth()->user()->getRoleNameAttribute() != '	
                    ADMIN DOUAIRE' && auth()->user()->getRoleNameAttribute() != 'ADMIN BELLE ILE') {
                        return '-';
                    } else {
                        return $row->purchase_price;
                        // return '<span class="display_currency" data-currency_symbol="true">{{$purchase_price}}</span>';
                    }
                })
                ->addColumn('mass_delete', function ($row) {
                    if (number_format($row->current_stock) > 0)
                        return  '<input type="checkbox" class="row-select" value="' . $row->id . '"><input type="number" class="row-qty form-control" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="qty_' . $row->id . '">';
                })
                ->addColumn('printing_qty', function ($row) {
                    return  'Print: <input type="number" class="row-print-qty form-control disabled" value="' . $row->printing_qty . '" max="' . $row->printing_qty . '" style="width:70px;" id="printing_qty_' . $row->id . '">';
                })
                ->editColumn('current_stock', '@if($enable_stock == 1) {{@number_format($current_stock)}} @else -- @endif {{$unit}}')
                ->addColumn(
                    'price',
                    '<div style="white-space: nowrap;"><span class="display_currency" data-currency_symbol="true">{{$min_price}}</span> @if($max_price != $min_price && $type == "variable") -  <span class="display_currency" data-currency_symbol="true">{{$max_price}}</span>@endif </div>'
                )
                // ->setRowAttr([
                //     'data-href' => function ($row) {
                //         if (auth()->user()->can("product.view")) {
                //             return  action('ProductController@view', [$row->id]);
                //         } else {
                //             return '';
                //         }
                //     }
                // ])
                ->rawColumns(['printing_qty', 'purchase_price', 'action', 'image', 'mass_delete', 'product', 'price'])
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

        return view('product.index_transfer')
            ->with(compact(
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
    public function create()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for products quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (!$this->moduleUtil->isQuotaAvailable('products', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('products', $business_id, action('ProductController@index'));
        }

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $brands = Brands::where('business_id', $business_id)
            ->pluck('name', 'id');
        $units = Unit::forDropdown($business_id, true);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $barcode_types = $this->barcode_types;
        $barcode_default =  $this->productUtil->barcode_default();

        $default_profit_percent = Business::where('id', $business_id)->value('default_profit_percent');

        //Get all business locations
        $business_locations = BusinessLocation::forDropdown($business_id);

        //Duplicate product
        $duplicate_product = null;
        $rack_details = null;

        $sub_categories = [];
        if (!empty(request()->input('d'))) {
            $duplicate_product = Product::where('business_id', $business_id)->find(request()->input('d'));
            $duplicate_product->name .= ' (copy)';

            if (!empty($duplicate_product->category_id)) {
                $sub_categories = Category::where('business_id', $business_id)
                    ->where('parent_id', $duplicate_product->category_id)
                    ->pluck('name', 'id')
                    ->toArray();
            }

            //Rack details
            if (!empty($duplicate_product->id)) {
                $rack_details = $this->productUtil->getRackDetails($business_id, $duplicate_product->id);
            }
        }

        $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);

        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');

        return view('product.create')
            ->with(compact('categories', 'brands', 'units', 'taxes', 'barcode_types', 'default_profit_percent', 'tax_attributes', 'barcode_default', 'business_locations', 'duplicate_product', 'sub_categories', 'rack_details', 'selling_price_group_count', 'module_form_parts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
// dd($request);
        try {
            $business_id = $request->session()->get('user.business_id');
            $form_fields = ['name', 'brand_id', 'unit_id', 'category_id', 'tax', 'type', 'barcode_type', 'sku', 'alert_quantity', 'tax_type', 'weight', 'product_custom_field1', 'product_custom_field2', 'product_custom_field3', 'product_custom_field4', 'product_description'];

            $module_form_fields = $this->moduleUtil->getModuleFormField('product_form_fields');
            if (!empty($module_form_fields)) {
                $form_fields = array_merge($form_fields, $module_form_fields);
            }

            $product_details = $request->only($form_fields);
            $product_details['business_id'] = $business_id;
            $product_details['created_by'] = $request->session()->get('user.id');

            if (!empty($request->input('enable_stock')) &&  $request->input('enable_stock') == 1) {
                $product_details['enable_stock'] = 1;
            }

            if (!empty($request->input('sub_category_id'))) {
                $product_details['sub_category_id'] = $request->input('sub_category_id');
            }

            if (empty($product_details['sku'])) {
                $product_details['sku'] = ' ';
            }

            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (!empty($request->input('expiry_period_type')) && !empty($request->input('expiry_period')) && !empty($expiry_enabled) && ($product_details['enable_stock'] == 1)) {
                $product_details['expiry_period_type'] = $request->input('expiry_period_type');
                $product_details['expiry_period'] = $this->productUtil->num_uf($request->input('expiry_period'));
            }

            if (!empty($request->input('enable_sr_no')) &&  $request->input('enable_sr_no') == 1) {
                $product_details['enable_sr_no'] = 1;
            }

            //upload document
            $product_details['image'] = $this->productUtil->uploadFile($request, 'image', config('constants.product_img_path'));

            DB::beginTransaction();
            // dd($product_details);
            $product = Product::create($product_details);

            if (empty(trim($request->input('sku')))) {
                $sku = $this->productUtil->generateProductSku($product->id);
                $product->sku = $sku;
                $product->product_updated_at = Carbon::now();
                $product->save();
            }

            if ($product->type == 'single') {
                $this->productUtil->createSingleProductVariation($product->id, $product->sku, $request->input('single_dpp'), $request->input('single_dpp_inc_tax'), $request->input('profit_percent'), $request->input('single_dsp'), $request->input('single_dsp_inc_tax'));
            } elseif ($product->type == 'variable') {
                if (!empty($request->input('product_variation'))) {
                    $input_variations = $request->input('product_variation');
                    $this->productUtil->createVariableProductVariations($product->id, $input_variations);
                }
            }

            //Add product racks details.
            $product_racks = $request->get('product_racks', null);
            if (!empty($product_racks)) {
                $this->productUtil->addRackDetails($business_id, $product->id, $product_racks);
            }

            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('product.product_added_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];
            return redirect('products')->with('status', $output);
        }

        if ($request->input('submit_type') == 'submit_n_add_opening_stock') {
            return redirect()->action(
                'OpeningStockController@add',
                ['product_id' => $product->id]
            );
        } elseif ($request->input('submit_type') == 'submit_n_add_selling_prices') {
            return redirect()->action(
                'ProductController@addSellingPrices',
                [$product->id]
            );
        } elseif ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                'ProductController@create'
            )->with('status', $output);
        }

        return redirect('products')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $details = $this->productUtil->getRackDetails($business_id, $id, true);

        return view('product.show')->with(compact('details'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // dd($request->all());
     
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        $location_id = request()->session()->get('location_id');
        // $location_input = request()->input('location_id');
        // dd($location_id);
        
        //Check if subscribed or not, then check for products quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (!$this->moduleUtil->isQuotaAvailable('products', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('products', $business_id, action('ProductController@index'));
        }
        $product = Product::where('id', '=', $id)->where('business_id', '=', $business_id)->first();
        $location_id = $product->variation_location_details()->first()->location_id;
        // dd($location_id);
        // dd($product);
        $product_qty = Product::join('variation_location_details as vld', 'vld.product_id', 'products.id')
            ->join('colors as c', 'c.id', 'products.color_id')
            ->where('products.refference', $product->refference)
            ->select([
                DB::raw('SUM(vld.qty_available) as qty'),
                'c.name as color_name',
                'c.id as color_id',
                'products.name as product_name',
                'products.id'
            ])
            ->groupBy('color_name')
            ->get();
            
            $product_price = VariationLocationDetails::where('location_id' , $location_id)
            ->where('product_refference', $product->refference)
            ->where('product_id', $id)
            ->first();
            // dd($product_price,$location_id);
        $product_sizes = Product::where('products.refference', $product->refference)
            ->get();
        $get_product_size_unique = Product::with('sub_size')->where('products.refference', $product->refference)
            ->get()->unique('sub_size_id')->toArray();
        $total_main_qty = VariationLocationDetails::where('product_refference', $product->refference)
            ->get()->sum('qty_available');
        // $product_qty = VariationLocationDetails::where('product_id',$product->id)->get()->pluck('qty_available');
        // dd($get_product_size_unique);


        // dd($product->image);
        //If brands, category are enabled then send else false.
        $noRefferenceProducts = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id) : false;
        $suppliers = (request()->session()->get('business.enable_brand') == 1) ? Supplier::where('business_id', $business_id)
            ->orderBy('name', 'ASC')
            ->pluck('name', 'id')
            ->prepend(__('lang_v1.all_suppliers'), 'all') : false;
        $categories = Category::where('parent_id', 0)->pluck('name', 'id');
        $sizes = Size::where('business_id', $business_id)->where('parent_id', '!=', 0)
            ->select('name', 'id')->get();
        // where('parent_id', 0)
        // ->

        $brands = Brands::where('business_id', $business_id)->pluck('name', 'id');
        $ProductNameCategory = ProductNameCategory::where('business_id', $business_id)->pluck('name', 'id', 'row_no');
        $pnc = array();
        foreach ($ProductNameCategory as $key => $objPNC) {
            # code...
            $pnc[] = $key . "@" . $objPNC;
        }
        $pnc = json_encode($pnc);
        $objBuss = \App\Business::find(request()->session()->get('user.business_id'));
        $refferenceCount = str_pad($objBuss->prod_refference, 4, '0', STR_PAD_LEFT);

        $suppliers = Supplier::where('business_id', $business_id)
            ->orderBy('name', 'ASC')
            ->pluck('name', 'id');
        $colors = Color::where('business_id', $business_id)->whereNotIn('id', $product_sizes->pluck('color_id'))->pluck('name', 'id');
        $units = Unit::forDropdown($business_id, true);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $barcode_types = $this->barcode_types;
        $barcode_default =  $this->productUtil->barcode_default();

        $default_profit_percent = Business::where('id', $business_id)->value('default_profit_percent');

        //Get all business locations
        $business_locations = BusinessLocation::forDropdown($business_id);

        //Duplicate product
        $duplicate_product = null;
        $rack_details = null;

        $sub_categories = [];
        if (!empty(request()->input('d'))) {
            $duplicate_product = Product::where('business_id', $business_id)->find(request()->input('d'));
            $duplicate_product->name .= ' (copy)';

            if (!empty($duplicate_product->category_id)) {
                $sub_categories = Category::where('business_id', $business_id)
                    ->where('parent_id', $duplicate_product->category_id)
                    ->pluck('name', 'id')
                    ->toArray();
            }

            //Rack details
            if (!empty($duplicate_product->id)) {
                $rack_details = $this->productUtil->getRackDetails($business_id, $duplicate_product->id);
            }
        }

        $sub_sizes = [];
        if (!empty(request()->input('d'))) {
            $duplicate_product = Product::where('business_id', $business_id)->find(request()->input('d'));
            $duplicate_product->name .= ' (copy)';

            if (!empty($duplicate_product->size_id)) {
                $sub_sizes = Size::where('business_id', $business_id)
                    ->where('parent_id', $duplicate_product->size_id)
                    ->pluck('name', 'id')
                    ->toArray();
            }
            //Rack details
            if (!empty($duplicate_product->id)) {
                $rack_details = $this->productUtil->getRackDetails($business_id, $duplicate_product->id);
            }
        }

        $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);

        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');
        request()->session()->put('url_id',$id);
        request()->session()->put('refference',$product->refference);
        
        // dd($categories);
        // dd($product);
        // dd($product->variations()->get());
        // dd($product->variations()->get());
        // dd($product->sub_size()->get());
        // dd($product->size()->get());
        // dd($product->color()->get());
        // dd($product);
        // dd($product);
        return view('product.edit')
            ->with(compact('product','categories', 'get_product_size_unique', 'total_main_qty', 'product_sizes', 'suppliers', 'noRefferenceProducts', 'brands', 'refferenceCount', 'pnc', 'suppliers', 'sizes', 'sub_sizes', 'colors', 'units', 'taxes', 'barcode_types', 'default_profit_percent', 'tax_attributes', 'barcode_default', 'business_locations', 'duplicate_product', 'sub_categories', 'rack_details', 'selling_price_group_count', 'module_form_parts', 'product_qty' , 'product_price'));
    }
    public function old_edit($id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $brands = Brands::where('business_id', $business_id)
            ->pluck('name', 'id');
        $units = Unit::forDropdown($business_id, true);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $barcode_types = $this->barcode_types;

        $product = Product::where('business_id', $business_id)
            ->where('id', $id)
            ->first();

        $sub_categories = [];

        $sub_categories = Category::where('business_id', $business_id)
            ->where('parent_id', $product->category_id)
            ->pluck('name', 'id')
            ->toArray();

        $sub_categories = ["" => "None"] + $sub_categories;

        $default_profit_percent = Business::where('id', $business_id)->value('default_profit_percent');

        //Get all business locations
        $business_locations = BusinessLocation::forDropdown($business_id);
        //Rack details
        $rack_details = $this->productUtil->getRackDetails($business_id, $id);

        $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);

        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');


        return view('product.edit')
            ->with(compact('categories', 'brands', 'units', 'taxes', 'tax_attributes', 'barcode_types', 'product', 'sub_categories', 'default_profit_percent', 'business_locations', 'rack_details', 'selling_price_group_count', 'module_form_parts'));
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
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }


        try {
            $business_id = $request->session()->get('user.business_id');
            $product_details = $request->only(['name', 'brand_id', 'unit_id', 'category_id', 'tax', 'barcode_type', 'sku', 'alert_quantity', 'tax_type', 'weight', 'product_custom_field1', 'product_custom_field2', 'product_custom_field3', 'product_custom_field4', 'product_description']);

            DB::beginTransaction();

            $product = Product::where('business_id', $business_id)
                ->where('id', $id)
                ->with(['product_variations'])
                ->first();

            $module_form_fields = $this->moduleUtil->getModuleFormField('product_form_fields');
            if (!empty($module_form_fields)) {
                foreach ($module_form_fields as $column) {
                    $product->$column = $request->input($column);
                }
            }

            $product->name = $product_details['name'];
            $product->brand_id = $product_details['brand_id'];
            $product->unit_id = $product_details['unit_id'];
            $product->category_id = $product_details['category_id'];
            $product->tax = $product_details['tax'];
            $product->barcode_type = $product_details['barcode_type'];
            $product->sku = $product_details['sku'];
            $product->alert_quantity = $product_details['alert_quantity'];
            $product->tax_type = $product_details['tax_type'];
            $product->weight = $product_details['weight'];
            $product->product_custom_field1 = $product_details['product_custom_field1'];
            $product->product_custom_field2 = $product_details['product_custom_field2'];
            $product->product_custom_field3 = $product_details['product_custom_field3'];
            $product->product_custom_field4 = $product_details['product_custom_field4'];
            $product->product_description = $product_details['product_description'];

            if (!empty($request->input('enable_stock')) &&  $request->input('enable_stock') == 1) {
                $product->enable_stock = 1;
            } else {
                $product->enable_stock = 0;
            }
            if (!empty($request->input('sub_category_id'))) {
                $product->sub_category_id = $request->input('sub_category_id');
            } else {
                $product->sub_category_id = null;
            }

            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (!empty($expiry_enabled)) {
                if (!empty($request->input('expiry_period_type')) && !empty($request->input('expiry_period')) && ($product->enable_stock == 1)) {
                    $product->expiry_period_type = $request->input('expiry_period_type');
                    $product->expiry_period = $this->productUtil->num_uf($request->input('expiry_period'));
                } else {
                    $product->expiry_period_type = null;
                    $product->expiry_period = null;
                }
            }

            if (!empty($request->input('enable_sr_no')) &&  $request->input('enable_sr_no') == 1) {
                $product->enable_sr_no = 1;
            } else {
                $product->enable_sr_no = 0;
            }

            //upload document
            $file_name = $this->productUtil->uploadFile($request, 'image', config('constants.product_img_path'));
            if (!empty($file_name)) {
                $product->image = $file_name;
            }

            $product->save();

            if ($product->type == 'single') {
                $single_data = $request->only(['single_variation_id', 'single_dpp_inc_tax', 'single_dpp_inc_tax', 'single_dsp_inc_tax', 'profit_percent', 'single_dsp_inc_tax']);

                $variation = Variation::find($single_data['single_variation_id']);

                $variation->sub_sku = $product->sku;
                $variation->default_purchase_price = $this->productUtil->num_uf($single_data['single_dpp_inc_tax']);
                $variation->dpp_inc_tax = $this->productUtil->num_uf($single_data['single_dpp_inc_tax']);
                $variation->profit_percent = $this->productUtil->num_uf($single_data['profit_percent']);
                $variation->default_sell_price = $this->productUtil->num_uf($single_data['single_dsp_inc_tax']);
                $variation->sell_price_inc_tax = $this->productUtil->num_uf($single_data['single_dsp_inc_tax']);
                $variation->save();
            } elseif ($product->type == 'variable') {
                //Update existing variations
                $input_variations_edit = $request->get('product_variation_edit');
                if (!empty($input_variations_edit)) {
                    $this->productUtil->updateVariableProductVariations($product->id, $input_variations_edit);
                }

                //Add new variations created.
                $input_variations = $request->input('product_variation');
                if (!empty($input_variations)) {
                    $this->productUtil->createVariableProductVariations($product->id, $input_variations);
                }
            }

            //Add product racks details.
            $product_racks = $request->get('product_racks', null);
            if (!empty($product_racks)) {
                $this->productUtil->addRackDetails($business_id, $product->id, $product_racks);
            }

            $product_racks_update = $request->get('product_racks_update', null);
            if (!empty($product_racks_update)) {
                $this->productUtil->updateRackDetails($business_id, $product->id, $product_racks_update);
            }

            DB::commit();

            $this->update_on_website($product->name);

            $output = [
                'success' => 1,
                'msg' => __('product.product_updated_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        if ($request->input('submit_type') == 'update_n_edit_opening_stock') {
            return redirect()->action(
                'OpeningStockController@add',
                ['product_id' => $product->id]
            );
        } elseif ($request->input('submit_type') == 'submit_n_add_selling_prices') {
            return redirect()->action(
                'ProductController@addSellingPrices',
                [$product->id]
            );
        } elseif ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                'ProductController@create'
            )->with('status', $output);
        }

        return redirect('products')->with('status', $output);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function bulkUpdate(Request $request)
    {
        // dd($request->all());
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'supplier' => ['required', Rule::notIn(0)],
            'category' => ['required', Rule::notIn(0)],
            // 'sub_category' => [Rule::notIn(0)],
            'product_name' => 'required',
            'refference' => 'required',
            'unit_price' => 'required',
            'custom_price' => 'required',
            'sku' => 'required',
            // 'color' => ['required', Rule::notIn(0)],
            // 'quantity' => 'required',
            // 'size' => ['required', Rule::notIn(0)],
        ]);

        try {
            DB::beginTransaction();
            $product = Product::find($request->input('product_id'));

            $product_image = $product->image;
            $location_id = $request->input('location_id');
            // if ($request->hasFile('file')) {
            //     $file = $request->file();
            //     $file['file'];
            //     $product_image =  $this->productUtil->uploadFileArr($request, 'file', config('constants.product_img_path'), 0);
            // }

            if ($request->input('description')) {
                $product_all = Product::where('refference', $request->refference)->get();
                // dd($product_all);
                foreach ($product_all as $key => $disc) {
                    $disc->update([
                        'description' => $request->input('description'),
                    ]);
                }
            }
            if ($request->hasFile('file')) {
                $files = $request->file('file');
            
                $product_all = Product::where('refference', $request->refference)->get();
                if (!empty($files)) { 
                    foreach ($files as $uploadedFile) {
                        $image = rand(10, 100) . time() . '.' . $uploadedFile->getClientOriginalExtension();
                        $uploadedFile->storeAs(config('constants.product_img_path'), $image);
                        foreach ($product_all as $key => $all_product) {
                            $all_product->update([
                                'image' => $image,
                            ]);
                        }
                    }
                }
            }
            $location = 1;
            if ($request->input('location_id')) {
                $location = $request->input('location_id');
            }
            session()->put('location_id', $location);
            $size = Size::find($request->input('size'));
            // $product->name = $request->input('product_name');
            $product->image = $product_image;
            // if ($request->input('supplier_id') == 0) {
            //     $product->supplier_id = $request->input('supplier');
            // } else {
            //     $product->supplier_id = $request->input('supplier');
            // }
            $product->supplier_id = $request->input('supplier');
            $product->category_id = $request->input('category');
            if ($request->input('sub_category') != 0) {
                $product->sub_category_id = $request->input('sub_category');
            }
            $product->refference = $request->input('refference');
            // $product->color_id = $request->input('color');
            // $product->size_id = $size->parent_id;
            // $product->sub_size_id = $request->input('size');   //todo comment by hamza due to adding in 
            $product->sku = $request->input('sku');
            $product->print_price_check = $request->print_price_check ? true : false;
            $product->description = $request->input('description');
            $product->product_updated_at = Carbon::now();

            $product->save();

            $variation = Variation::where('product_id', '=', $request->input('product_id'))->first();
            // $unit = str_replace($request->input('unit_price'),'.',',');
            $variation->sub_sku = $product->sku;
           
            if ($request->allow_price_qty) {
                $variation->dpp_inc_tax = $this->productUtil->num_uf($request->input('unit_price'));
                $variation->old_sell_price_inc_tax= $this->productUtil->num_uf($request->old_price);
                $variation->sell_price_inc_tax = $this->productUtil->num_uf($request->input('custom_price'));
            }
            $variation->save();

            if($location_id === 'all'){
                $price_change = VariationLocationDetails::where('product_refference', $product->refference)->get();
                foreach ($price_change as $price_changes){
                    $price_changes->update([
                        'old_sell_price' => $this->productUtil->num_uf($request->old_price),
                        'sell_price' => $this->productUtil->num_uf($request->input('custom_price')),
                    ]);
                    } 
            }else{
            $price_change = VariationLocationDetails::where('product_refference', $product->refference)->where('location_id', $location_id)->get();
            foreach ($price_change as $price_changes){
            $price_changes->update([
                'old_sell_price' => $this->productUtil->num_uf($request->old_price),
                'sell_price' => $this->productUtil->num_uf($request->input('custom_price')),
            ]);
            }
        }
            
            // add the size while checking the colors
            // $product_colors = Product::join('variation_location_details as vld', 'vld.product_id', 'products.id')
            //     ->join('colors as c', 'c.id', 'products.color_id')
            //     ->where('products.refference', $product->refference)
            //     ->select([
            //         DB::raw('SUM(vld.qty_available) as qty'),
            //         'c.id as color_id',
            //         'c.name as color_name',
            //         'products.name as product_name',
            //         'products.id as product_id'
            //     ])
            //     ->groupBy('color_name')
            //     ->get();
            $product_sizes = Product::where('products.refference', $product->refference)
                ->get();
            $message = 'Product Updated Successfully. ';
            // update unit price and custom price same reference prodeucts
            foreach ($product_sizes as $product_size) {
                $record = Variation::where('product_id', '=', $product_size->id)->first();
                if ($request->allow_price_qty && isset($record)) {
                    $record->dpp_inc_tax = $this->productUtil->num_uf($request->input('unit_price'));
                    $record->old_sell_price_inc_tax = $this->productUtil->num_uf($request->old_price);
                    $record->sell_price_inc_tax = $this->productUtil->num_uf($request->input('custom_price'));
                    $record->save();
                }
            }
            // update multi color quantity  and image

            foreach ($request->productColor as $key => $updateColorArray) {
                $findColorArray = $product_sizes->where('id', $updateColorArray['product_id'])->first();
                if (!is_null($findColorArray)) {
                    $new_qty = !is_null($updateColorArray['new_qty']) ? $updateColorArray['new_qty'] : 0;
                    if ($request->allow_price_qty) {
                        // $purchase_line = VariationLocationDetails::where('product_id', '=', $findColorArray->id)->first();
                        $purchase_line = VariationLocationDetails::where('product_id', '=', $findColorArray->id)->where('location_id', 1)->first();
                       if($new_qty != 0){
                        $product_quantity_d = new ProductQuantity();
                        $product_quantity_d->product_id = $purchase_line['product_id'];
                        $product_quantity_d->variation_id = $purchase_line['variation_id'];
                        $product_quantity_d->refference = $product->refference;
                        $product_quantity_d->location_id = 1;
                        $product_quantity_d->quantity = $new_qty;
                        $product_quantity_d->created_at = Carbon::now();
                        $product_quantity_d->updated_at = Carbon::now();
                        $product_quantity_d->save();
                    }
                        if (!is_null($purchase_line)) {
                            $purchase_line->update([
                                'qty_available' => $updateColorArray['old_qty'] +  $new_qty,
                                'updated_at' => Carbon::now(),
                            ]);
                            $purchase_line->update([
                                'printing_qty' => $purchase_line->qty_available,
                            ]);
                          
                        } else {
                            $variation_location_d = new VariationLocationDetails();
                            $variation_location_d->variation_id = $variation->id;
                            $variation_location_d->product_refference = $product->refference;
                            $variation_location_d->product_id = $product->id;
                            $variation_location_d->location_id = 1;
                            $variation_location_d->product_variation_id = $variation->id;
                            $variation_location_d->qty_available = (float)$updateColorArray['old_qty'] + $new_qty;
                            $variation_location_d->product_updated_at = Carbon::now();
                            $variation_location_d->printing_qty = (float)$updateColorArray['old_qty'] + $new_qty;
                            $variation_location_d->updated_at = Carbon::now();
                            $variation_location_d->save();

                            $product_quantity_d = new product_quantity();
                            $product_quantity_d->product_id = $variation_location_d['product_id'];
                            $product_quantity_d->variation_id = $variation_location_d['variation_id'];
                            $product_quantity_d->refference = $product->refference;
                            $product_quantity_d->location_id = 1;
                            $product_quantity_d->quantity = $new_qty;
                            $product_quantity_d->created_at = Carbon::now();
                            $product_quantity_d->updated_at = Carbon::now();
                            $product_quantity_d->save();
                        }
                    }
                    if (isset($request->color_file)) {
                        foreach ($request->color_file as $key => $file_name) {
                            if ($key === $updateColorArray['color_name']) {
                                $uploaded_file_name = null;
                                if ($file_name->getSize() <= config('constants.document_size_limit')) {
                                    $new_file_name = time() . '_' . $file_name->getClientOriginalName();
                                    if ($file_name->storeAs(config('constants.product_img_path'), $new_file_name)) {
                                        $uploaded_file_name = $new_file_name;
                                    }
                                }
                                if (!is_null($uploaded_file_name)) {
                                    $findColorArray->update([
                                        'image' => $uploaded_file_name,
                                    ]);
                                }
                            }
                        }
                    }
                    $all_products = VariationLocationDetails::where('product_refference', $product->refference)->get();
                    foreach ($all_products as $all_product) {
                        $all_product->update([
                            'updated_at' => Carbon::now(),
                            'product_updated_at' => Carbon::now(),
                        ]);
                    }
                }
            }
            // add new multi color with quantity  
            if (!is_null($request->newColor)) {
                foreach ($request->newColor as $key => $new_color_array) {
                    $qty = 1;
                    if (!is_null($new_color_array['new_qty'])) {
                        $qty = $new_color_array['new_qty'];
                    }
                    $new_color = Color::where('name', $new_color_array['color_name'])->first();
                    $size = Size::find($new_color_array['size_id']);
                    $lastId = Product::orderBy('id', 'desc')->first();

                    $new_product = $product->replicate();
                    $new_product->color_id = $new_color->id;
                    $new_product->size_id = $size->parent_id;
                    $new_product->sub_size_id = $size->id;
                    // $new_product->sku = $sku;
                    $new_product->created_by = Auth::id();
                    $new_product->save();
                    $sku = $this->productUtil->generateProductSku($new_product->id);
                    $new_product->update([
                        'sku' => $sku,
                    ]);

                    $user_id = Auth::id();
                    if ($new_product->type == 'single') {
                        $variation = $product->variations->first();
                        $this->productUtil->createSingleProductVariation2($new_product, $new_product->sku, $variation->dpp_inc_tax, $variation->dpp_inc_tax, $variation->profit_percent, $variation->default_sell_price, $variation->sell_price_inc_tax);
                    }
                    if ($product->enable_stock == 1) {
                        $transaction_date = $request->session()->get("financial_year.start");
                        $transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();
                        $variatArr = array(
                            "1" => array(
                                "purchase_price" => $request->input('custom_price'),
                                "quantity" => $qty,
                                "exp_date" => "",
                                "lot_number" => ""
                            )
                        );
                        $this->productUtil->addSingleProductOpeningStock2(1, $new_product, $variatArr, $transaction_date, $user_id);
                    }
                }
            }

            DB::commit();
            $this->update_on_website($product->name);
            $output = [
                'success' => 1,
                'msg' => $message
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong" . ' ' . "File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage())
            ];
        }
        return redirect(url('products/' . $product->id . '/edit'))->with('status', $output)->with('location_id_set', $location);

        $business_id = $request->session()->get('user.business_id');
        $product_details = $request->only(['name', 'brand_id', 'unit_id', 'category_id', 'tax', 'barcode_type', 'sku', 'alert_quantity', 'tax_type', 'weight', 'product_custom_field1', 'product_custom_field2', 'product_custom_field3', 'product_custom_field4', 'product_description']);

        $product = Product::where('business_id', $business_id)
            ->where('id', $id)
            ->with(['product_variations'])
            ->first();
        try {
            DB::beginTransaction();

            $product = Product::where('business_id', $business_id)
                ->where('id', $id)
                ->with(['product_variations'])
                ->first();

            $module_form_fields = $this->moduleUtil->getModuleFormField('product_form_fields');
            if (!empty($module_form_fields)) {
                foreach ($module_form_fields as $column) {
                    $product->$column = $request->input($column);
                }
            }

            $product->name = $product_details['name'];
            $product->brand_id = $product_details['brand_id'];
            $product->unit_id = $product_details['unit_id'];
            $product->category_id = $product_details['category_id'];
            $product->tax = $product_details['tax'];
            $product->barcode_type = $product_details['barcode_type'];
            $product->sku = $product_details['sku'];
            $product->alert_quantity = $product_details['alert_quantity'];
            $product->tax_type = $product_details['tax_type'];
            $product->weight = $product_details['weight'];
            $product->product_custom_field1 = $product_details['product_custom_field1'];
            $product->product_custom_field2 = $product_details['product_custom_field2'];
            $product->product_custom_field3 = $product_details['product_custom_field3'];
            $product->product_custom_field4 = $product_details['product_custom_field4'];
            $product->product_description = $product_details['product_description'];

            if (!empty($request->input('enable_stock')) &&  $request->input('enable_stock') == 1) {
                $product->enable_stock = 1;
            } else {
                $product->enable_stock = 0;
            }
            if (!empty($request->input('sub_category_id'))) {
                $product->sub_category_id = $request->input('sub_category_id');
            } else {
                $product->sub_category_id = null;
            }

            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (!empty($expiry_enabled)) {
                if (!empty($request->input('expiry_period_type')) && !empty($request->input('expiry_period')) && ($product->enable_stock == 1)) {
                    $product->expiry_period_type = $request->input('expiry_period_type');
                    $product->expiry_period = $this->productUtil->num_uf($request->input('expiry_period'));
                } else {
                    $product->expiry_period_type = null;
                    $product->expiry_period = null;
                }
            }

            if (!empty($request->input('enable_sr_no')) &&  $request->input('enable_sr_no') == 1) {
                $product->enable_sr_no = 1;
            } else {
                $product->enable_sr_no = 0;
            }

            //upload document
            $file_name = $this->productUtil->uploadFile($request, 'image', config('constants.product_img_path'));
            if (!empty($file_name)) {
                $product->image = $file_name;
            }
            $product->product_updated_at = Carbon::now();

            $product->save();

            if ($product->type == 'single') {
                $single_data = $request->only(['single_variation_id', 'single_dpp', 'single_dpp_inc_tax', 'single_dsp_inc_tax', 'profit_percent', 'single_dsp_inc_tax']);
                $variation = Variation::find($single_data['single_variation_id']);

                $variation->sub_sku = $product->sku;
                $variation->default_purchase_price = $this->productUtil->num_uf($single_data['single_dpp_inc_tax']);
                $variation->dpp_inc_tax = $this->productUtil->num_uf($single_data['single_dpp_inc_tax']);
                $variation->profit_percent = $this->productUtil->num_uf($single_data['profit_percent']);
                $variation->default_sell_price = $this->productUtil->num_uf($single_data['single_dsp_inc_tax']);
                $variation->sell_price_inc_tax = $this->productUtil->num_uf($single_data['single_dsp_inc_tax']);
                $variation->save();
            } elseif ($product->type == 'variable') {
                //Update existing variations
                $input_variations_edit = $request->get('product_variation_edit');
                if (!empty($input_variations_edit)) {
                    $this->productUtil->updateVariableProductVariations($product->id, $input_variations_edit);
                }

                //Add new variations created.
                $input_variations = $request->input('product_variation');
                if (!empty($input_variations)) {
                    $this->productUtil->createVariableProductVariations($product->id, $input_variations);
                }
            }

            //Add product racks details.
            $product_racks = $request->get('product_racks', null);
            if (!empty($product_racks)) {
                $this->productUtil->addRackDetails($business_id, $product->id, $product_racks);
            }

            $product_racks_update = $request->get('product_racks_update', null);
            if (!empty($product_racks_update)) {
                $this->productUtil->updateRackDetails($business_id, $product->id, $product_racks_update);
            }

            DB::commit();
            $this->update_on_website($product->name);
            $output = [
                'success' => 1,
                'msg' => __('product.product_updated_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        if ($request->input('submit_type') == 'update_n_edit_opening_stock') {
            return redirect()->action(
                'OpeningStockController@add',
                ['product_id' => $product->id]
            );
        } elseif ($request->input('submit_type') == 'submit_n_add_selling_prices') {
            return redirect()->action(
                'ProductController@addSellingPrices',
                [$product->id]
            );
        } elseif ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                'ProductController@create'
            )->with('status', $output);
        }

        return redirect('products')->with('status', $output);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function bulkUpdateOld(Request $request)
    {
        dd($request->input());
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'supplier' => ['required', Rule::notIn(0)],
            'category' => ['required', Rule::notIn(0)],
            // 'sub_category' => [Rule::notIn(0)],
            'product_name' => 'required',
            'refference' => 'required',
            'unit_price' => 'required',
            'custom_price' => 'required',
            'sku' => 'required',
            'color' => ['required', Rule::notIn(0)],
            'quantity' => 'required',
            // 'size' => ['required', Rule::notIn(0)],
        ]);

        try {
            DB::beginTransaction();
            $product = Product::find($request->input('product_id'));

            $product_image = $product->image;

            if ($request->hasFile('file')) {
                $file = $request->file();
                $file['file'];
                $product_image =  $this->productUtil->uploadFileArr($request, 'file', config('constants.product_img_path'), 0);
            }
            $size = Size::find($request->input('size'));
            // $product->name = $request->input('product_name');
            $product->image = $product_image;
            // if ($request->input('supplier_id') == 0) {
            //     $product->supplier_id = $request->input('supplier');
            // } else {
            //     $product->supplier_id = $request->input('supplier');
            // }
            $product->supplier_id = $request->input('supplier');
            $product->category_id = $request->input('category');
            if ($request->input('sub_category') != 0) {
                $product->sub_category_id = $request->input('sub_category');
            }
            $product->refference = $request->input('refference');
            $product->color_id = $request->input('color');
            $product->size_id = $size->parent_id;
            $product->sub_size_id = $request->input('size');
            $product->sku = $request->input('sku');
            $product->print_price_check = $request->print_price_check ? true : false;
            $product->description = $request->input('description');
            $product->product_updated_at = Carbon::now();

            $product->save();

            $variation = Variation::where('product_id', '=', $request->input('product_id'))->first();
            // $unit = str_replace($request->input('unit_price'),'.',',');
            // dd($this->productUtil->num_uf($request->input('unit_price')));
            // dd($unit);
            $variation->sub_sku = $product->sku;
            if ($request->allow_price_qty) {
                $variation->dpp_inc_tax = $this->productUtil->num_uf($request->input('unit_price'));
                $variation->old_sell_price_inc_tax     = $variation->sell_price_inc_tax;
                $variation->sell_price_inc_tax = $this->productUtil->num_uf($request->input('custom_price'));
            }
            $variation->save();

            $location = 1;
            if ($request->input('location_id')) {
                $location = $request->input('location_id');
            }
            session()->put('location_id', $location);
            $purchase_line = VariationLocationDetails::where('product_id', '=', $request->input('product_id'))->where('location_id', $request->input('location_id'))->first();

            if ($request->input('new_quantity') && $request->allow_price_qty) {
                $purchase_line->qty_available = $request->input('quantity') + $request->input('new_quantity');
                $purchase_line->printing_qty = $request->input('new_quantity');
                $purchase_line->product_updated_at = Carbon::now();
            } else {
                $purchase_line->qty_available = $request->input('quantity');
            }

            $purchase_line->save();

            $message = 'Product Updated Successfully. ';
            // Adding Product In Purchase Line
            if ($request->input('new_quantity') && $purchase_line->location_id != 1) {
                $location_transfer_detail = new LocationTransferDetail();
                $location_transfer_detail->variation_id = $purchase_line->variation_id;
                $location_transfer_detail->product_id = $purchase_line->product_id;
                $location_transfer_detail->product_refference = $product->refference;
                $location_transfer_detail->location_id = $purchase_line->location_id;
                $location_transfer_detail->transfered_from = 1;

                $location_transfer_detail->product_variation_id = $purchase_line->product_variation_id;

                $location_transfer_detail->quantity = $purchase_line->quantity;
                $location_transfer_detail->transfered_on = Carbon::now();

                $location_transfer_detail->save();

                $message .= 'Product added into Purchase Table as well for Location: ' . $location_transfer_detail->location_id;
            }

            DB::commit();
            $this->update_on_website($product->name);
            $output = [
                'success' => 1,
                'msg' => $message
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            // dd($e->getMessage());
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong" . ' ' . "File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage())
            ];
        }
        // dd($location);
        return redirect(url('products/' . $product->id . '/edit'))->with('status', $output)->with('location_id_set', $location);

        $business_id = $request->session()->get('user.business_id');
        $product_details = $request->only(['name', 'brand_id', 'unit_id', 'category_id', 'tax', 'barcode_type', 'sku', 'alert_quantity', 'tax_type', 'weight', 'product_custom_field1', 'product_custom_field2', 'product_custom_field3', 'product_custom_field4', 'product_description']);

        $product = Product::where('business_id', $business_id)
            ->where('id', $id)
            ->with(['product_variations'])
            ->first();
        try {
            DB::beginTransaction();

            $product = Product::where('business_id', $business_id)
                ->where('id', $id)
                ->with(['product_variations'])
                ->first();

            $module_form_fields = $this->moduleUtil->getModuleFormField('product_form_fields');
            if (!empty($module_form_fields)) {
                foreach ($module_form_fields as $column) {
                    $product->$column = $request->input($column);
                }
            }

            $product->name = $product_details['name'];
            $product->brand_id = $product_details['brand_id'];
            $product->unit_id = $product_details['unit_id'];
            $product->category_id = $product_details['category_id'];
            $product->tax = $product_details['tax'];
            $product->barcode_type = $product_details['barcode_type'];
            $product->sku = $product_details['sku'];
            $product->alert_quantity = $product_details['alert_quantity'];
            $product->tax_type = $product_details['tax_type'];
            $product->weight = $product_details['weight'];
            $product->product_custom_field1 = $product_details['product_custom_field1'];
            $product->product_custom_field2 = $product_details['product_custom_field2'];
            $product->product_custom_field3 = $product_details['product_custom_field3'];
            $product->product_custom_field4 = $product_details['product_custom_field4'];
            $product->product_description = $product_details['product_description'];

            if (!empty($request->input('enable_stock')) &&  $request->input('enable_stock') == 1) {
                $product->enable_stock = 1;
            } else {
                $product->enable_stock = 0;
            }
            if (!empty($request->input('sub_category_id'))) {
                $product->sub_category_id = $request->input('sub_category_id');
            } else {
                $product->sub_category_id = null;
            }

            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (!empty($expiry_enabled)) {
                if (!empty($request->input('expiry_period_type')) && !empty($request->input('expiry_period')) && ($product->enable_stock == 1)) {
                    $product->expiry_period_type = $request->input('expiry_period_type');
                    $product->expiry_period = $this->productUtil->num_uf($request->input('expiry_period'));
                } else {
                    $product->expiry_period_type = null;
                    $product->expiry_period = null;
                }
            }

            if (!empty($request->input('enable_sr_no')) &&  $request->input('enable_sr_no') == 1) {
                $product->enable_sr_no = 1;
            } else {
                $product->enable_sr_no = 0;
            }

            //upload document
            $file_name = $this->productUtil->uploadFile($request, 'image', config('constants.product_img_path'));
            if (!empty($file_name)) {
                $product->image = $file_name;
            }
            $product->product_updated_at = Carbon::now();

            $product->save();

            if ($product->type == 'single') {
                $single_data = $request->only(['single_variation_id', 'single_dpp', 'single_dpp_inc_tax', 'single_dsp_inc_tax', 'profit_percent', 'single_dsp_inc_tax']);
                $variation = Variation::find($single_data['single_variation_id']);

                $variation->sub_sku = $product->sku;
                $variation->default_purchase_price = $this->productUtil->num_uf($single_data['single_dpp_inc_tax']);
                $variation->dpp_inc_tax = $this->productUtil->num_uf($single_data['single_dpp_inc_tax']);
                $variation->profit_percent = $this->productUtil->num_uf($single_data['profit_percent']);
                $variation->default_sell_price = $this->productUtil->num_uf($single_data['single_dsp_inc_tax']);
                $variation->sell_price_inc_tax = $this->productUtil->num_uf($single_data['single_dsp_inc_tax']);
                $variation->save();
            } elseif ($product->type == 'variable') {
                //Update existing variations
                $input_variations_edit = $request->get('product_variation_edit');
                if (!empty($input_variations_edit)) {
                    $this->productUtil->updateVariableProductVariations($product->id, $input_variations_edit);
                }

                //Add new variations created.
                $input_variations = $request->input('product_variation');
                if (!empty($input_variations)) {
                    $this->productUtil->createVariableProductVariations($product->id, $input_variations);
                }
            }

            //Add product racks details.
            $product_racks = $request->get('product_racks', null);
            if (!empty($product_racks)) {
                $this->productUtil->addRackDetails($business_id, $product->id, $product_racks);
            }

            $product_racks_update = $request->get('product_racks_update', null);
            if (!empty($product_racks_update)) {
                $this->productUtil->updateRackDetails($business_id, $product->id, $product_racks_update);
            }

            DB::commit();
            $this->update_on_website($product->name);
            $output = [
                'success' => 1,
                'msg' => __('product.product_updated_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        if ($request->input('submit_type') == 'update_n_edit_opening_stock') {
            return redirect()->action(
                'OpeningStockController@add',
                ['product_id' => $product->id]
            );
        } elseif ($request->input('submit_type') == 'submit_n_add_selling_prices') {
            return redirect()->action(
                'ProductController@addSellingPrices',
                [$product->id]
            );
        } elseif ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                'ProductController@create'
            )->with('status', $output);
        }

        return redirect('products')->with('status', $output);
    }
    /**
     * Update the All products of same name in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateAll(Request $request)
    {
        // dd($request->input());
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'supplier' => ['required', Rule::notIn(0)],
            'category' => ['required', Rule::notIn(0)],
            // 'sub_category' => [Rule::notIn(0)],
            'product_name' => 'required',
            'refference' => 'required',
            'unit_price' => 'required',
            'custom_price' => 'required',
            // 'sku' => 'required',
            'color' => ['required', Rule::notIn(0)],
            'quantity' => 'required',
            'size' => ['required', Rule::notIn(0)],
        ]);

        try {
            DB::beginTransaction();
            $product_id = Product::find($request->input('product_id'));
            $product = Product::where('name', $product_id->name)->get();

            for ($i = 0; $i < count($product); $i++) {
                $product_image = $product[$i]->image;

                if ($request->hasFile('file')) {
                    $file = $request->file();
                    $file['file'];
                    $product_image =  $this->productUtil->uploadFileArr($request, 'file', config('constants.product_img_path'), 0);
                }
                $size = Size::find($request->input('size'));
                // $product->name = $request->input('product_name');

                // $product[$i]->image = $product_image;

                // if ($request->input('supplier_id') == 0) {
                //     $product->supplier_id = $request->input('supplier');
                // } else {
                //     $product->supplier_id = $request->input('supplier');
                // }
                $product[$i]->supplier_id = $request->input('supplier');
                $product[$i]->category_id = $request->input('category');
                if ($request->input('sub_category') != 0) {
                    $product[$i]->sub_category_id = $request->input('sub_category');
                }
                $product[$i]->refference = $request->input('refference');

                // $product[$i]->color_id = $request->input('color');

                // $product[$i]->size_id = $size->parent_id;
                // $product[$i]->sub_size_id = $request->input('size');
                // $product[$i]->sku = $request->input('sku');
                $product[$i]->description = $request->input('description');
                $product[$i]->print_price_check = $request->print_price_check ? true : false;
                $product[$i]->product_updated_at = Carbon::now();

                $product[$i]->save();

                $variation = Variation::where('product_id', '=', $product[$i]->id)->first();
                // $unit = str_replace($request->input('unit_price'),'.',',');
                // dd($this->productUtil->num_uf($request->input('unit_price')));
                // dd($unit);
                // $variation->sub_sku = $product[$i]->sku;
                if ($request->allow_price_qty) {
                    $variation->dpp_inc_tax = $this->productUtil->num_uf($request->input('unit_price'));
                    $variation->old_sell_price_inc_tax     = $variation->sell_price_inc_tax;
                    $variation->sell_price_inc_tax = $this->productUtil->num_uf($request->input('custom_price'));
                }
                $variation->save();

                $location = 1;
                if ($request->input('location_id')) {
                    $location = $request->input('location_id');
                }
                session()->put('location_id', $location);
                $purchase_line = VariationLocationDetails::where('product_id', '=', $product[$i]->id)->where('location_id', $request->input('location_id'))->first();

                if ($request->input('new_quantity') && $request->allow_price_qty) {
                    $purchase_line->qty_available = $request->input('quantity') + $request->input('new_quantity');
                    $purchase_line->printing_qty = $request->input('new_quantity');
                    $purchase_line->product_updated_at = Carbon::now();
                } else {
                    $purchase_line->qty_available = $request->input('quantity');
                }

                $purchase_line->save();

                $message = count($product) . ' Products Updated Successfully. ';
                // Adding Product In Purchase Line
                if (($request->input('new_quantity') && $purchase_line->location_id != 1) && $request->allow_price_qty) {
                    $location_transfer_detail = new LocationTransferDetail();
                    $location_transfer_detail->variation_id = $purchase_line->variation_id;
                    $location_transfer_detail->product_id = $purchase_line->product_id;
                    $location_transfer_detail->product_refference = $product[$i]->refference;
                    $location_transfer_detail->location_id = $purchase_line->location_id;
                    $location_transfer_detail->transfered_from = 1;

                    $location_transfer_detail->product_variation_id = $purchase_line->product_variation_id;

                    $location_transfer_detail->quantity = $purchase_line->quantity;
                    $location_transfer_detail->transfered_on = Carbon::now();

                    $location_transfer_detail->save();

                    $message .= 'Product added into Purchase Table as well for Location: ' . $location_transfer_detail->location_id;
                }

                DB::commit();
            }
            $this->update_on_website($product_id->name);
            $output = [
                'success' => 1,
                'msg' => $message
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            // dd($e->getMessage());
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong" . ' ' . "File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage())
            ];
        }
        $location = 1;
        if ($request->input('location_id')) {
            $location = $request->input('location_id');
        }
        // dd($location);
        return redirect(url('products/' . $request->input('product_id') . '/edit'))->with('status', $output)->with('location_id_set', $location);

        $business_id = $request->session()->get('user.business_id');
        $product_details = $request->only(['name', 'brand_id', 'unit_id', 'category_id', 'tax', 'barcode_type', 'sku', 'alert_quantity', 'tax_type', 'weight', 'product_custom_field1', 'product_custom_field2', 'product_custom_field3', 'product_custom_field4', 'product_description']);

        $product = Product::where('business_id', $business_id)
            ->where('id', $id)
            ->with(['product_variations'])
            ->first();
        try {
            DB::beginTransaction();

            $product = Product::where('business_id', $business_id)
                ->where('id', $id)
                ->with(['product_variations'])
                ->first();

            $module_form_fields = $this->moduleUtil->getModuleFormField('product_form_fields');
            if (!empty($module_form_fields)) {
                foreach ($module_form_fields as $column) {
                    $product->$column = $request->input($column);
                }
            }

            $product->name = $product_details['name'];
            $product->brand_id = $product_details['brand_id'];
            $product->unit_id = $product_details['unit_id'];
            $product->category_id = $product_details['category_id'];
            $product->tax = $product_details['tax'];
            $product->barcode_type = $product_details['barcode_type'];
            $product->sku = $product_details['sku'];
            $product->alert_quantity = $product_details['alert_quantity'];
            $product->tax_type = $product_details['tax_type'];
            $product->weight = $product_details['weight'];
            $product->product_custom_field1 = $product_details['product_custom_field1'];
            $product->product_custom_field2 = $product_details['product_custom_field2'];
            $product->product_custom_field3 = $product_details['product_custom_field3'];
            $product->product_custom_field4 = $product_details['product_custom_field4'];
            $product->product_description = $product_details['product_description'];

            if (!empty($request->input('enable_stock')) &&  $request->input('enable_stock') == 1) {
                $product->enable_stock = 1;
            } else {
                $product->enable_stock = 0;
            }
            if (!empty($request->input('sub_category_id'))) {
                $product->sub_category_id = $request->input('sub_category_id');
            } else {
                $product->sub_category_id = null;
            }

            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (!empty($expiry_enabled)) {
                if (!empty($request->input('expiry_period_type')) && !empty($request->input('expiry_period')) && ($product->enable_stock == 1)) {
                    $product->expiry_period_type = $request->input('expiry_period_type');
                    $product->expiry_period = $this->productUtil->num_uf($request->input('expiry_period'));
                } else {
                    $product->expiry_period_type = null;
                    $product->expiry_period = null;
                }
            }

            if (!empty($request->input('enable_sr_no')) &&  $request->input('enable_sr_no') == 1) {
                $product->enable_sr_no = 1;
            } else {
                $product->enable_sr_no = 0;
            }

            //upload document
            $file_name = $this->productUtil->uploadFile($request, 'image', config('constants.product_img_path'));
            if (!empty($file_name)) {
                $product->image = $file_name;
            }
            $product->product_updated_at = Carbon::now();

            $product->save();

            if ($product->type == 'single') {
                $single_data = $request->only(['single_variation_id', 'single_dpp', 'single_dpp_inc_tax', 'single_dsp_inc_tax', 'profit_percent', 'single_dsp_inc_tax']);
                $variation = Variation::find($single_data['single_variation_id']);

                $variation->sub_sku = $product->sku;
                $variation->default_purchase_price = $this->productUtil->num_uf($single_data['single_dpp_inc_tax']);
                $variation->dpp_inc_tax = $this->productUtil->num_uf($single_data['single_dpp_inc_tax']);
                $variation->profit_percent = $this->productUtil->num_uf($single_data['profit_percent']);
                $variation->default_sell_price = $this->productUtil->num_uf($single_data['single_dsp_inc_tax']);
                $variation->sell_price_inc_tax = $this->productUtil->num_uf($single_data['single_dsp_inc_tax']);
                $variation->save();
            } elseif ($product->type == 'variable') {
                //Update existing variations
                $input_variations_edit = $request->get('product_variation_edit');
                if (!empty($input_variations_edit)) {
                    $this->productUtil->updateVariableProductVariations($product->id, $input_variations_edit);
                }

                //Add new variations created.
                $input_variations = $request->input('product_variation');
                if (!empty($input_variations)) {
                    $this->productUtil->createVariableProductVariations($product->id, $input_variations);
                }
            }

            //Add product racks details.
            $product_racks = $request->get('product_racks', null);
            if (!empty($product_racks)) {
                $this->productUtil->addRackDetails($business_id, $product->id, $product_racks);
            }

            $product_racks_update = $request->get('product_racks_update', null);
            if (!empty($product_racks_update)) {
                $this->productUtil->updateRackDetails($business_id, $product->id, $product_racks_update);
            }

            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('product.product_updated_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        if ($request->input('submit_type') == 'update_n_edit_opening_stock') {
            return redirect()->action(
                'OpeningStockController@add',
                ['product_id' => $product->id]
            );
        } elseif ($request->input('submit_type') == 'submit_n_add_selling_prices') {
            return redirect()->action(
                'ProductController@addSellingPrices',
                [$product->id]
            );
        } elseif ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                'ProductController@create'
            )->with('status', $output);
        }

        return redirect('products')->with('status', $output);
    }
    /**
     * Update the All products of same color and name in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateColor(Request $request)
    {
        // dd($request->input());
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'supplier' => ['required', Rule::notIn(0)],
            'category' => ['required', Rule::notIn(0)],
            // 'sub_category' => [Rule::notIn(0)],
            'product_name' => 'required',
            'refference' => 'required',
            'unit_price' => 'required',
            'custom_price' => 'required',
            // 'sku' => 'required',
            'color' => ['required', Rule::notIn(0)],
            'quantity' => 'required',
            'size' => ['required', Rule::notIn(0)],
        ]);
        $message = null;
        try {
            DB::beginTransaction();
            $product_id = Product::where('color_id', $request->input('color'))->where('name', $request->input('product_name'))->first();
            $product = Product::where('color_id', $request->input('color'))->where('name', $request->input('product_name'))->get();
            // dd($product, $product_id,$request->input());
            if (is_null($product_id) && $product->isEmpty()) {
                $product_id =  Product::where('name', $request->input('product_name'))->first();
                $product = Product::where('name', $request->input('product_name'))->get();
            }
            // dd($request->input());

            for ($i = 0; $i < count($product); $i++) {
                $product_image = $product[$i]->image;

                if ($request->hasFile('file')) {
                    $file = $request->file();
                    $file['file'];
                    $product_image =  $this->productUtil->uploadFileArr($request, 'file', config('constants.product_img_path'), 0);
                }
                $size = Size::find($request->input('size'));
                // $product->name = $request->input('product_name');
                $product[$i]->image = $product_image;
                // if ($request->input('supplier_id') == 0) {
                //     $product->supplier_id = $request->input('supplier');
                // } else {
                //     $product->supplier_id = $request->input('supplier');
                // }
                $product[$i]->supplier_id = $request->input('supplier');
                $product[$i]->category_id = $request->input('category');
                if ($request->input('sub_category') != 0) {
                    $product[$i]->sub_category_id = $request->input('sub_category');
                }
                $product[$i]->refference = $request->input('refference');

                $product[$i]->color_id = $request->input('color');
                // $product[$i]->size_id = $size->parent_id;
                // $product[$i]->sub_size_id = $request->input('size');
                // $product[$i]->sku = $request->input('sku');
                $product[$i]->description = $request->input('description');
                $product[$i]->print_price_check = $request->print_price_check ? true : false;
                $product[$i]->product_updated_at = Carbon::now();
                $product[$i]->save();
                // dd($product[$i]);

                $variation = Variation::where('product_id', '=', $product[$i]->id)->first();
                // $unit = str_replace($request->input('unit_price'),'.',',');
                // dd($this->productUtil->num_uf($request->input('unit_price')));
                // dd($unit);
                // $variation->sub_sku = $product[$i]->sku;
                if ($request->allow_price_qty) {
                    $variation->dpp_inc_tax = $this->productUtil->num_uf($request->input('unit_price'));
                    $variation->old_sell_price_inc_tax     = $variation->sell_price_inc_tax;
                    $variation->sell_price_inc_tax = $this->productUtil->num_uf($request->input('custom_price'));
                }
                $variation->save();

                $location = 1;
                if ($request->input('location_id')) {
                    $location = $request->input('location_id');
                }
                session()->put('location_id', $location);
                $purchase_line = VariationLocationDetails::where('product_id', '=', $product[$i]->id)->where('location_id', $request->input('location_id'))->first();

                if ($request->input('new_quantity') && $request->allow_price_qty) {
                    $purchase_line->qty_available = $request->input('quantity') + $request->input('new_quantity');
                    $purchase_line->printing_qty = $request->input('new_quantity');
                    $purchase_line->product_updated_at = Carbon::now();
                } else {
                    $purchase_line->qty_available = $request->input('quantity');
                }

                $purchase_line->save();

                $message = count($product) . ' Products Updated Successfully. ';
                // Adding Product In Purchase Line
                if (($request->input('new_quantity') && $purchase_line->location_id != 1) && $request->allow_price_qty) {
                    $location_transfer_detail = new LocationTransferDetail();
                    $location_transfer_detail->variation_id = $purchase_line->variation_id;
                    $location_transfer_detail->product_id = $purchase_line->product_id;
                    $location_transfer_detail->product_refference = $product[$i]->refference;
                    $location_transfer_detail->location_id = $purchase_line->location_id;
                    $location_transfer_detail->transfered_from = 1;

                    $location_transfer_detail->product_variation_id = $purchase_line->product_variation_id;

                    $location_transfer_detail->quantity = $purchase_line->quantity;
                    $location_transfer_detail->transfered_on = Carbon::now();

                    $location_transfer_detail->save();

                    $message .= 'Product added into Purchase Table as well for Location: ' . $location_transfer_detail->location_id;
                }

                DB::commit();
                $this->update_on_website($product_id->name);
            }
            $output = [
                'success' => 1,
                'msg' => $message
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            // dd($e->getMessage());
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong" . ' ' . "File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage())
            ];
        }
        $location = 1;
        if ($request->input('location_id')) {
            $location = $request->input('location_id');
        }
        // dd($location);
        return redirect(url('products/' . $request->input('product_id') . '/edit'))->with('status', $output)->with('location_id_set', $location);

        $business_id = $request->session()->get('user.business_id');
        $product_details = $request->only(['name', 'brand_id', 'unit_id', 'category_id', 'tax', 'barcode_type', 'sku', 'alert_quantity', 'tax_type', 'weight', 'product_custom_field1', 'product_custom_field2', 'product_custom_field3', 'product_custom_field4', 'product_description']);

        $product = Product::where('business_id', $business_id)
            ->where('id', $id)
            ->with(['product_variations'])
            ->first();
        try {
            DB::beginTransaction();

            $product = Product::where('business_id', $business_id)
                ->where('id', $id)
                ->with(['product_variations'])
                ->first();

            $module_form_fields = $this->moduleUtil->getModuleFormField('product_form_fields');
            if (!empty($module_form_fields)) {
                foreach ($module_form_fields as $column) {
                    $product->$column = $request->input($column);
                }
            }

            $product->name = $product_details['name'];
            $product->brand_id = $product_details['brand_id'];
            $product->unit_id = $product_details['unit_id'];
            $product->category_id = $product_details['category_id'];
            $product->tax = $product_details['tax'];
            $product->barcode_type = $product_details['barcode_type'];
            $product->sku = $product_details['sku'];
            $product->alert_quantity = $product_details['alert_quantity'];
            $product->tax_type = $product_details['tax_type'];
            $product->weight = $product_details['weight'];
            $product->product_custom_field1 = $product_details['product_custom_field1'];
            $product->product_custom_field2 = $product_details['product_custom_field2'];
            $product->product_custom_field3 = $product_details['product_custom_field3'];
            $product->product_custom_field4 = $product_details['product_custom_field4'];
            $product->product_description = $product_details['product_description'];

            if (!empty($request->input('enable_stock')) &&  $request->input('enable_stock') == 1) {
                $product->enable_stock = 1;
            } else {
                $product->enable_stock = 0;
            }
            if (!empty($request->input('sub_category_id'))) {
                $product->sub_category_id = $request->input('sub_category_id');
            } else {
                $product->sub_category_id = null;
            }

            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (!empty($expiry_enabled)) {
                if (!empty($request->input('expiry_period_type')) && !empty($request->input('expiry_period')) && ($product->enable_stock == 1)) {
                    $product->expiry_period_type = $request->input('expiry_period_type');
                    $product->expiry_period = $this->productUtil->num_uf($request->input('expiry_period'));
                } else {
                    $product->expiry_period_type = null;
                    $product->expiry_period = null;
                }
            }

            if (!empty($request->input('enable_sr_no')) &&  $request->input('enable_sr_no') == 1) {
                $product->enable_sr_no = 1;
            } else {
                $product->enable_sr_no = 0;
            }

            //upload document
            $file_name = $this->productUtil->uploadFile($request, 'image', config('constants.product_img_path'));
            if (!empty($file_name)) {
                $product->image = $file_name;
            }
            $product->product_updated_at = Carbon::now();

            $product->save();

            if ($product->type == 'single') {
                $single_data = $request->only(['single_variation_id', 'single_dpp', 'single_dpp_inc_tax', 'single_dsp_inc_tax', 'profit_percent', 'single_dsp_inc_tax']);
                $variation = Variation::find($single_data['single_variation_id']);

                $variation->sub_sku = $product->sku;
                $variation->default_purchase_price = $this->productUtil->num_uf($single_data['single_dpp_inc_tax']);
                $variation->dpp_inc_tax = $this->productUtil->num_uf($single_data['single_dpp_inc_tax']);
                $variation->profit_percent = $this->productUtil->num_uf($single_data['profit_percent']);
                $variation->default_sell_price = $this->productUtil->num_uf($single_data['single_dsp_inc_tax']);
                $variation->sell_price_inc_tax = $this->productUtil->num_uf($single_data['single_dsp_inc_tax']);
                $variation->save();
            } elseif ($product->type == 'variable') {
                //Update existing variations
                $input_variations_edit = $request->get('product_variation_edit');
                if (!empty($input_variations_edit)) {
                    $this->productUtil->updateVariableProductVariations($product->id, $input_variations_edit);
                }

                //Add new variations created.
                $input_variations = $request->input('product_variation');
                if (!empty($input_variations)) {
                    $this->productUtil->createVariableProductVariations($product->id, $input_variations);
                }
            }

            //Add product racks details.
            $product_racks = $request->get('product_racks', null);
            if (!empty($product_racks)) {
                $this->productUtil->addRackDetails($business_id, $product->id, $product_racks);
            }

            $product_racks_update = $request->get('product_racks_update', null);
            if (!empty($product_racks_update)) {
                $this->productUtil->updateRackDetails($business_id, $product->id, $product_racks_update);
            }

            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('product.product_updated_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        if ($request->input('submit_type') == 'update_n_edit_opening_stock') {
            return redirect()->action(
                'OpeningStockController@add',
                ['product_id' => $product->id]
            );
        } elseif ($request->input('submit_type') == 'submit_n_add_selling_prices') {
            return redirect()->action(
                'ProductController@addSellingPrices',
                [$product->id]
            );
        } elseif ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                'ProductController@create'
            )->with('status', $output);
        }

        return redirect('products')->with('status', $output);
    }
    /**
     * Update Products on website 
     * 
     **/
    public function update_on_website($product_name)
    {
        try {

            $products = WebsiteProducts::join('products as p', 'p.refference', '=', 'website_products.refference')
                ->where('p.name', $product_name)
                ->join('variation_location_details as vld', 'vld.product_id', '=', 'p.id')
                ->join('variations as v', 'p.id', '=', 'v.product_id')
                ->join('sizes as s', 'p.sub_size_id', '=', 's.id')
                ->join('colors as c', 'c.id', '=', 'p.color_id')
                ->join('categories as cat', 'cat.id', '=', 'p.category_id')
                ->join('categories as sub_cat', 'sub_cat.id', '=', 'p.sub_category_id')
                ->groupBy('id')
                ->get([
                    'p.id',
                    'p.name as name',
                    'p.sku as sku',
                    'cat.name as category_name',
                    'sub_cat.name as sub_category_name',
                    'c.name as color',
                    'c.color_code as color_code',
                    's.name as size',
                    'website_products.quantity as quantity',
                    'v.sell_price_inc_tax as price',
                    'p.image',
                    // DB::raw('(SELECT qty_available from variation_location_details) as qty'),
                    DB::raw('SUM(vld.qty_available) as qty'),
                    // 'vld.qty_available',
                ])
                ->groupBy('name')
                ->toArray();
            // $total = $connection->table('website_products')->count('id');
            // dd($products);
            DB::beginTransaction();
            $qurrey_count = 0;
            $all_product = 0;
            $product = 0;
            // dd($products);
            foreach ($products as $key => $value) {
                // for ($i=0; $i < count($products); $i++) {
                $connection = DB::connection('website');
                $qurrey_count++;
                $current_product = $value;
                // dd($current_product[0]);
                // dd($connection->table('categories')->where('name', $current_product[0]['category_name'])->first());
                $cat_id = NULL;
                $subcat_id = NULL;
                $child_id = NULL;
                if ($connection->table('categories')->where('name', $current_product[0]['category_name'])->first()) {
                    $cat_id = $connection->table('categories')->where('name', $current_product[0]['category_name'])->first()->id;
                }
                if ($connection->table('subcategories')->where('name', $current_product[0]['sub_category_name'])->first()) {
                    $sub_category = $connection->table('subcategories')->where('name', $current_product[0]['sub_category_name'])->first();
                    $subcat_id = $sub_category->id;
                    $cat_id = $sub_category->category_id;;
                }
                if ($connection->table('childcategories')->where('name', $current_product[0]['sub_category_name'])->first()) {
                    $child = $connection->table('childcategories')->where('name', $current_product[0]['sub_category_name'])->first();
                    $child_id = $child->id;
                    $subcat_id = $child->subcategory_id;
                    $cat_id = $connection->table('subcategories')->where('id', $subcat_id)->first()->category_id;
                }
                $size = [];
                $color = [];
                $quantity = [];
                $price = [];
                $total_qty = 0;
                for ($j = 0; $j < count($current_product); $j++) {
                    $size[$j] =  $current_product[$j]['size'];
                    if (($j > 0) && (isset($color[($j - 1)]) && ($color[($j - 1)] != $current_product[$j]['color']))) {
                        $color[$j] = $current_product[$j]['color'];
                    } elseif ($j == 0) {
                        $color[0] = $current_product[$j]['color'];
                    }
                    // $quantity[$j] = $current_product[$j]->quantity;
                    if ($current_product[$j]['qty']) {
                        $quantity[$j] = (int) $current_product[$j]['qty'];
                    } else {
                        $quantity[$j] = 0;
                    }
                    $price[$j] = (float)$current_product[0]['price'];
                    $all_product++;
                    $total_qty += (int) $current_product[$j]['qty'];
                }
                // dd($current_product);
                // Create Product here
                if ($connection->table('products')->where('name', $current_product[0]['name'])->first()) {
                    $data = $connection->table('products')->where('name', $current_product[0]['name']);
                    $input = [];
                    $input['name'] = $current_product[0]['name'];
                    $input['slug'] = strtolower($current_product[0]['name']);
                    $input['sku'] = $current_product[0]['sku'];
                    $input['photo'] = $current_product[0]['image'];
                    $input['thumbnail'] = $current_product[0]['image'];
                    $input['size'] = implode(",", $size);
                    $input['size_price'] = implode(",", $price);
                    $input['size_qty'] = implode(",", $quantity);
                    $input['stock'] = $total_qty;
                    // $input['stock'] = $current_product[0]['quantity'];
                    // $input['quantity'] = $current_product[0]['quantity'];
                    $input['color'] = implode(",", $color);
                    $input['price'] = (float)$current_product[0]['price'];
                    $input['category_id'] = $cat_id;
                    $input['subcategory_id'] = $subcat_id;
                    $input['childcategory_id'] = $child_id;
                    // dd($input);
                    $data->update($input); //save product
                    $product++;
                }
                // dd("Hello");
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            dd($ex->getMessage() . ' on Line: ' . $ex->getLine() . ' in file: ' . $ex->getFile());
        }
    }
    public function old_bulkUpdate(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $product_details = $request->only(['name', 'brand_id', 'unit_id', 'category_id', 'tax', 'barcode_type', 'sku', 'alert_quantity', 'tax_type', 'weight', 'product_custom_field1', 'product_custom_field2', 'product_custom_field3', 'product_custom_field4', 'product_description']);

            DB::beginTransaction();

            $product = Product::where('business_id', $business_id)
                ->where('id', $id)
                ->with(['product_variations'])
                ->first();

            $module_form_fields = $this->moduleUtil->getModuleFormField('product_form_fields');
            if (!empty($module_form_fields)) {
                foreach ($module_form_fields as $column) {
                    $product->$column = $request->input($column);
                }
            }

            $product->name = $product_details['name'];
            $product->brand_id = $product_details['brand_id'];
            $product->unit_id = $product_details['unit_id'];
            $product->category_id = $product_details['category_id'];
            $product->tax = $product_details['tax'];
            $product->barcode_type = $product_details['barcode_type'];
            $product->sku = $product_details['sku'];
            $product->alert_quantity = $product_details['alert_quantity'];
            $product->tax_type = $product_details['tax_type'];
            $product->weight = $product_details['weight'];
            $product->product_custom_field1 = $product_details['product_custom_field1'];
            $product->product_custom_field2 = $product_details['product_custom_field2'];
            $product->product_custom_field3 = $product_details['product_custom_field3'];
            $product->product_custom_field4 = $product_details['product_custom_field4'];
            $product->product_description = $product_details['product_description'];

            if (!empty($request->input('enable_stock')) &&  $request->input('enable_stock') == 1) {
                $product->enable_stock = 1;
            } else {
                $product->enable_stock = 0;
            }
            if (!empty($request->input('sub_category_id'))) {
                $product->sub_category_id = $request->input('sub_category_id');
            } else {
                $product->sub_category_id = null;
            }

            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (!empty($expiry_enabled)) {
                if (!empty($request->input('expiry_period_type')) && !empty($request->input('expiry_period')) && ($product->enable_stock == 1)) {
                    $product->expiry_period_type = $request->input('expiry_period_type');
                    $product->expiry_period = $this->productUtil->num_uf($request->input('expiry_period'));
                } else {
                    $product->expiry_period_type = null;
                    $product->expiry_period = null;
                }
            }

            if (!empty($request->input('enable_sr_no')) &&  $request->input('enable_sr_no') == 1) {
                $product->enable_sr_no = 1;
            } else {
                $product->enable_sr_no = 0;
            }

            //upload document
            $file_name = $this->productUtil->uploadFile($request, 'image', config('constants.product_img_path'));
            if (!empty($file_name)) {
                $product->image = $file_name;
            }

            $product->product_updated_at = Carbon::now();

            $product->save();

            if ($product->type == 'single') {
                $single_data = $request->only(['single_variation_id', 'single_dpp_inc_tax', 'single_dpp_inc_tax', 'single_dsp_inc_tax', 'profit_percent', 'single_dsp_inc_tax']);
                $variation = Variation::find($single_data['single_variation_id']);

                $variation->sub_sku = $product->sku;
                $variation->default_purchase_price = $this->productUtil->num_uf($single_data['single_dpp_inc_tax']);
                $variation->dpp_inc_tax = $this->productUtil->num_uf($single_data['single_dpp_inc_tax']);
                $variation->profit_percent = $this->productUtil->num_uf($single_data['profit_percent']);
                $variation->default_sell_price = $this->productUtil->num_uf($single_data['single_dsp_inc_tax']);
                $variation->sell_price_inc_tax = $this->productUtil->num_uf($single_data['single_dsp_inc_tax']);
                $variation->save();
            } elseif ($product->type == 'variable') {
                //Update existing variations
                $input_variations_edit = $request->get('product_variation_edit');
                if (!empty($input_variations_edit)) {
                    $this->productUtil->updateVariableProductVariations($product->id, $input_variations_edit);
                }

                //Add new variations created.
                $input_variations = $request->input('product_variation');
                if (!empty($input_variations)) {
                    $this->productUtil->createVariableProductVariations($product->id, $input_variations);
                }
            }

            //Add product racks details.
            $product_racks = $request->get('product_racks', null);
            if (!empty($product_racks)) {
                $this->productUtil->addRackDetails($business_id, $product->id, $product_racks);
            }

            $product_racks_update = $request->get('product_racks_update', null);
            if (!empty($product_racks_update)) {
                $this->productUtil->updateRackDetails($business_id, $product->id, $product_racks_update);
            }

            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('product.product_updated_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        if ($request->input('submit_type') == 'update_n_edit_opening_stock') {
            return redirect()->action(
                'OpeningStockController@add',
                ['product_id' => $product->id]
            );
        } elseif ($request->input('submit_type') == 'submit_n_add_selling_prices') {
            return redirect()->action(
                'ProductController@addSellingPrices',
                [$product->id]
            );
        } elseif ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                'ProductController@create'
            )->with('status', $output);
        }

        return redirect('products')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $can_be_deleted = true;
                $error_msg = '';

                //Check if any purchase or transfer exists
                $count = PurchaseLine::join(
                    'transactions as T',
                    'purchase_lines.transaction_id',
                    '=',
                    'T.id'
                )
                    ->whereIn('T.type', ['purchase'])
                    ->where('T.business_id', $business_id)
                    ->where('purchase_lines.product_id', $id)
                    ->count();
                if ($count > 0) {
                    $can_be_deleted = false;
                    $error_msg = __('lang_v1.purchase_already_exist');
                } else {
                    //Check if any opening stock sold
                    $count = PurchaseLine::join(
                        'transactions as T',
                        'purchase_lines.transaction_id',
                        '=',
                        'T.id'
                    )
                        ->where('T.type', 'opening_stock')
                        ->where('T.business_id', $business_id)
                        ->where('purchase_lines.product_id', $id)
                        ->where('purchase_lines.quantity_sold', '>', 0)
                        ->count();
                    if ($count > 0) {
                        $can_be_deleted = false;
                        $error_msg = __('lang_v1.opening_stock_sold');
                    } else {
                        //Check if any stock is adjusted
                        $count = PurchaseLine::join(
                            'transactions as T',
                            'purchase_lines.transaction_id',
                            '=',
                            'T.id'
                        )
                            ->where('T.business_id', $business_id)
                            ->where('purchase_lines.product_id', $id)
                            ->where('purchase_lines.quantity_adjusted', '>', 0)
                            ->count();
                        if ($count > 0) {
                            $can_be_deleted = false;
                            $error_msg = __('lang_v1.stock_adjusted');
                        }
                    }
                }

                if ($can_be_deleted) {
                    $product = Product::where('id', $id)
                        ->where('business_id', $business_id)
                        ->first();
                    if (!empty($product)) {
                        DB::beginTransaction();
                        //Delete variation location details
                        VariationLocationDetails::where('product_id', $id)
                            ->delete();
                        $product->delete();

                        DB::commit();
                    }

                    $output = [
                        'success' => true,
                        'msg' => __("lang_v1.product_delete_success")
                    ];
                } else {
                    $output = [
                        'success' => false,
                        'msg' => $error_msg
                    ];
                }
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
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
                ->orderBy('name', 'ASC')
                ->get();
            $html = '<option value="">None</option>';
            if (!empty($sub_categories)) {
                foreach ($sub_categories as $sub_category) {
                    $html .= '<option value="' . $sub_category->id . '">' . $sub_category->name . '</option>';
                }
            }
            echo $html;
            exit;
        }
    }

    public function getSubSizes(Request $request)
    {
        if (!empty($request->input('cat_id'))) {
            $category_id = $request->input('cat_id');
            $business_id = $request->session()->get('user.business_id');
            $sub_categories = Size::where('business_id', $business_id)
                ->where('parent_id', $category_id)
                ->select(['name', 'id'])
                ->get();
            $html = '<option value="">None</option>';
            if (!empty($sub_categories)) {
                foreach ($sub_categories as $sub_category) {
                    $html .= '<option value="' . $sub_category->id . '">' . $sub_category->name . '</option>';
                }
            }
            echo $html;
            exit;
        }
    }

    public function getSupplierDetails($id)
    {
        $sub_categories = Supplier::where('id', $id)
            ->select(['id', 'name', 'description'])
            ->first();

        return $sub_categories->description;
        exit;
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
                $variation_templates = ["" => __('messages.please_select')] + $variation_templates;

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
        $variation_templates = ["" => __('messages.please_select')] + $variation_templates;

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

            $products = Product::join('variations', 'products.id', '=', 'variations.product_id')
                ->active()
                ->whereNull('variations.deleted_at')
                ->leftjoin('units as U', 'products.unit_id', '=', 'U.id')
                ->leftjoin(
                    'variation_location_details AS VLD',
                    function ($join) use ($location_id) {
                        $join->on('variations.id', '=', 'VLD.variation_id');

                        //Include Location
                        if (!empty($location_id)) {
                            $join->where(function ($query) use ($location_id) {
                                $query->where('VLD.location_id', '=', $location_id);
                                //Check null to show products even if no quantity is available in a location.
                                //TODO: Maybe add a settings to show product not available at a location or not.
                                $query->orWhereNull('VLD.location_id');
                            });;
                        }
                    }
                );
            if (!empty($price_group_id)) {
                $products->leftjoin(
                    'variation_group_prices AS VGP',
                    function ($join) use ($price_group_id) {
                        $join->on('variations.id', '=', 'VGP.variation_id')
                            ->where('VGP.price_group_id', '=', $price_group_id);
                    }
                );
            }
            $products->where('products.business_id', $business_id)
                ->where('products.type', '!=', 'modifier');

            //Include search
            if (!empty($term)) {
                $products->where(function ($query) use ($term) {
                    $query->where('products.name', 'like', '%' . $term . '%');
                    $query->orWhere('sku', 'like', '%' . $term . '%');
                    $query->orWhere('sub_sku', 'like', '%' . $term . '%');
                });
            }

            //Include check for quantity
            if ($check_qty) {
                $products->where('VLD.qty_available', '>', 0);
            }

            $products->select(
                'products.id as product_id',
                'products.name',
                'products.type',
                'products.enable_stock',
                'variations.id as variation_id',
                'variations.name as variation',
                'VLD.qty_available',
                'variations.sell_price_inc_tax as selling_price',
                'variations.sub_sku',
                'U.short_name as unit'
            );
            if (!empty($price_group_id)) {
                $products->addSelect('VGP.price_inc_tax as variation_group_price');
            }
            $result = $products->orderBy('products.id', 'desc')
                ->get();
            return json_encode($result);
        }
    }
    /**
     * Retrieves Reciept list.
     *
     * @param  string  $q
     * @param  boolean  $check_qty
     *
     * @return JSON
     */
    public function getReciept()
    {
        if (request()->ajax()) {
            $term = request()->input('term', '');
            // $location_id = request()->input('location_id', '');

            // $check_qty = request()->input('check_qty', false);

            // $price_group_id = request()->input('price_group', '');

            $business_id = request()->session()->get('user.business_id');

            $products = Product::join('variations', 'products.id', '=', 'variations.product_id')
                ->active()
                ->whereNull('variations.deleted_at')
                ->leftjoin('units as U', 'products.unit_id', '=', 'U.id')
                ->leftjoin(
                    'variation_location_details AS VLD',
                    function ($join) use ($location_id) {
                        $join->on('variations.id', '=', 'VLD.variation_id');

                        //Include Location
                        if (!empty($location_id)) {
                            $join->where(function ($query) use ($location_id) {
                                $query->where('VLD.location_id', '=', $location_id);
                                //Check null to show products even if no quantity is available in a location.
                                //TODO: Maybe add a settings to show product not available at a location or not.
                                $query->orWhereNull('VLD.location_id');
                            });;
                        }
                    }
                );
            if (!empty($price_group_id)) {
                $products->leftjoin(
                    'variation_group_prices AS VGP',
                    function ($join) use ($price_group_id) {
                        $join->on('variations.id', '=', 'VGP.variation_id')
                            ->where('VGP.price_group_id', '=', $price_group_id);
                    }
                );
            }
            $products->where('products.business_id', $business_id)
                ->where('products.type', '!=', 'modifier');

            //Include search
            if (!empty($term)) {
                $products->where(function ($query) use ($term) {
                    $query->where('products.name', 'like', '%' . $term . '%');
                    $query->orWhere('sku', 'like', '%' . $term . '%');
                    $query->orWhere('sub_sku', 'like', '%' . $term . '%');
                });
            }

            //Include check for quantity
            if ($check_qty) {
                $products->where('VLD.qty_available', '>', 0);
            }

            $products->select(
                'products.id as product_id',
                'products.name',
                'products.type',
                'products.enable_stock',
                'variations.id as variation_id',
                'variations.name as variation',
                'VLD.qty_available',
                'variations.sell_price_inc_tax as selling_price',
                'variations.sub_sku',
                'U.short_name as unit'
            );
            if (!empty($price_group_id)) {
                $products->addSelect('VGP.price_inc_tax as variation_group_price');
            }
            $result = $products->orderBy('products.id', 'desc')
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
                    $query->where('products.name', 'like', '%' . $term . '%');
                    $query->orWhere('sku', 'like', '%' . $term . '%');
                    $query->orWhere('sub_sku', 'like', '%' . $term . '%');
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
     * Loads quick add product modal. quickAddOnly
     *
     * @return \Illuminate\Http\Response
     */
    public function quickAdd()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $product_name = !empty(request()->input('product_name')) ? request()->input('product_name') : '';

        $product_for = !empty(request()->input('product_for')) ? request()->input('product_for') : null;


        $business_id = request()->session()->get('user.business_id');
        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $brands = Brands::where('business_id', $business_id)
            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $barcode_types = $this->barcode_types;

        $default_profit_percent = Business::where('id', $business_id)->value('default_profit_percent');

        $locations = BusinessLocation::forDropdown($business_id);

        $enable_expiry = request()->session()->get('business.enable_product_expiry');
        $enable_lot = request()->session()->get('business.enable_lot_number');

        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');

        return view('product.partials.quick_add_product')
            ->with(compact('categories', 'brands', 'units', 'taxes', 'barcode_types', 'default_profit_percent', 'tax_attributes', 'product_name', 'locations', 'product_for', 'enable_expiry', 'enable_lot', 'module_form_parts'));
    }

    /**
     * Loads quick add product modal. 
     *
     * @return \Illuminate\Http\Response
     */
    public function quickAddOnly()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $product_name = !empty(request()->input('product_name')) ? request()->input('product_name') : 'Product ';
        $autoBarcode =   $this->productUtil->generateProductSku("1");


        $product_for = !empty(request()->input('product_for')) ? request()->input('product_for') : null;


        $business_id = request()->session()->get('user.business_id');
        $user_location_id = request()->session()->get('user.business_location_id');

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $brands = Brands::where('business_id', $business_id)
            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $barcode_types = $this->barcode_types;

        $default_profit_percent = Business::where('id', $business_id)->value('default_profit_percent');

        $locations = BusinessLocation::forDropdown($business_id);

        $enable_expiry = request()->session()->get('business.enable_product_expiry');
        $enable_lot = request()->session()->get('business.enable_lot_number');

        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');

        return view('product.partials.quick_add_product_only')
            ->with(compact('categories', 'brands', 'units', 'taxes', 'barcode_types', 'default_profit_percent', 'tax_attributes', 'product_name', 'locations', 'product_for', 'enable_expiry', 'enable_lot', 'module_form_parts', 'autoBarcode'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveQuickProduct(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->session()->get('user.business_location_id');
            $form_fields = [
                'name', 'brand_id', 'unit_id', 'category_id', 'tax', 'barcode_type', 'tax_type', 'sku',
                'alert_quantity', 'type'
            ];

            $module_form_fields = $this->moduleUtil->getModuleData('product_form_fields');
            if (!empty($module_form_fields)) {
                foreach ($module_form_fields as $key => $value) {
                    if (!empty($value) && is_array($value)) {
                        $form_fields = array_merge($form_fields, $value);
                    }
                }
            }
            $product_details = $request->only($form_fields);

            $product_details['type'] = empty($product_details['type']) ? 'single' : $product_details['type'];
            $product_details['product_description'] = $request->input('product_description');
            $product_details['business_id'] = $business_id;
            $product_details['created_by'] = $request->session()->get('user.id');
            if (!empty($request->input('enable_stock')) &&  $request->input('enable_stock') == 1) {
                $product_details['enable_stock'] = 1;
                //TODO: Save total qty
                //$product_details['total_qty_available'] = 0;
            }
            if (empty($product_details['sku'])) {
                $product_details['sku'] = ' ';
            }

            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (!empty($request->input('expiry_period_type')) && !empty($request->input('expiry_period')) && !empty($expiry_enabled)) {
                $product_details['expiry_period_type'] = $request->input('expiry_period_type');
                $product_details['expiry_period'] = $this->productUtil->num_uf($request->input('expiry_period'));
            }

            if (!empty($request->input('enable_sr_no')) &&  $request->input('enable_sr_no') == 1) {
                $product_details['enable_sr_no'] = 1;
            }

            DB::beginTransaction();

            $product = Product::create($product_details);

            if (empty(trim($request->input('sku')))) {
                $sku = $this->productUtil->generateProductSku($product->id);
                $product->sku = $sku;
                $product->product_updated_at = Carbon::now();
                $product->save();
            }

            $this->productUtil->createSingleProductVariation(
                $product->id,
                $product->sku,
                $request->input('single_dpp'),
                $request->input('single_dpp_inc_tax'),
                $request->input('profit_percent'),
                $request->input('single_dsp'),
                $request->input('single_dsp_inc_tax')
            );

            if ($product->enable_stock == 1 && !empty($request->input('opening_stock'))) {
                $user_id = $request->session()->get('user.id');

                $transaction_date = $request->session()->get("financial_year.start");
                $transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();

                $this->productUtil->addSingleProductOpeningStock($business_id, $product, $request->input('opening_stock'), $transaction_date, $user_id, true);
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('product.product_added_success'),
                'product' => $product,
                'variation' => $product->variations->first()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong") . $e->getMessage()
            ];
        }

        return $output;
    }

    public function saveQuickProductOnly(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->session()->get('user.business_location_id');
            $form_fields = [
                'name', 'brand_id', 'unit_id', 'category_id', 'tax', 'barcode_type', 'tax_type', 'sku',
                'alert_quantity', 'type'
            ];

            $module_form_fields = $this->moduleUtil->getModuleData('product_form_fields');
            if (!empty($module_form_fields)) {
                foreach ($module_form_fields as $key => $value) {
                    if (!empty($value) && is_array($value)) {
                        $form_fields = array_merge($form_fields, $value);
                    }
                }
            }
            $product_details = $request->only($form_fields);

            $product_details['type'] = empty($product_details['type']) ? 'single' : $product_details['type'];
            $product_details['product_description'] = $request->input('product_description');
            $product_details['business_id'] = $business_id;
            $product_details['created_by'] = $request->session()->get('user.id');
            if (!empty($request->input('enable_stock')) &&  $request->input('enable_stock') == 1) {
                $product_details['enable_stock'] = 1;
                //TODO: Save total qty
                //$product_details['total_qty_available'] = 0;
            }
            $product_details['enable_stock'] = 1;
            if (empty($product_details['sku'])) {
                $product_details['sku'] = ' ';
            }

            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (!empty($request->input('expiry_period_type')) && !empty($request->input('expiry_period')) && !empty($expiry_enabled)) {
                $product_details['expiry_period_type'] = $request->input('expiry_period_type');
                $product_details['expiry_period'] = $this->productUtil->num_uf($request->input('expiry_period'));
            }

            if (!empty($request->input('enable_sr_no')) &&  $request->input('enable_sr_no') == 1) {
                $product_details['enable_sr_no'] = 1;
            }

            DB::beginTransaction();

            $product = Product::create($product_details);

            if (empty(trim($request->input('sku')))) {
                $sku = $this->productUtil->generateProductSku($product->id);
                $product->sku = $sku;
                $product->product_updated_at = Carbon::now();
                $product->save();
            }

            $this->productUtil->createSingleProductVariationForPOSUnkownBarCode(
                $product->id,
                $product->sku,
                1,
                1,
                0,
                $request->input('CustomPrice'),
                $request->input('CustomPrice')
            );

            if ($product->enable_stock == 1) {
                $user_id = $request->session()->get('user.id');

                $transaction_date = $request->session()->get("financial_year.start");
                $transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();
                $variatArr = array(
                    $location_id => array(
                        "purchase_price" => "1",
                        "quantity" => "1",
                        "exp_date" => "",
                        "lot_number" => ""
                    )
                );

                $this->productUtil->addSingleProductOpeningStock($business_id, $product, $variatArr, $transaction_date, $user_id);
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('product.product_added_success'),
                'product' => $product,
                'variation' => $product->variations->first()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong") . $e->getMessage()
            ];
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
            ->with([
                'color', 'brand', 'supplier', 'unit', 'category', 'sub_category', 'product_tax', 'variations', 'variations.product_variation', 'variations.group_prices'
            ])
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

        return view('product.view-modal')->with(compact(
            'product',
            'rack_details',
            'allowed_group_prices',
            'group_price_details'
        ));
    }
    /**
     * View Product Detail with Sales 
     * 
     **/
    /**
     * Shows product purchase report
     *
     * @return \Illuminate\Http\Response
     */
    public function viewProductDetailWithSale($id,$location_id)
    {
        
        if (!auth()->user()->can('purchase_n_sell_report.view') && !auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        // $business_id = $request->session()->get('user.business_id');
        // $variation_id = $request->get('variation_id', null);

        // $location_id = $request->get('location_id', null);

        $vld_str = '';
        // if (!empty($location_id)) {
        //     $vld_str = "AND vld.location_id=$location_id";
        // }

        $query = TransactionSellLine::join(
            'transactions as t',
            'transaction_sell_lines.transaction_id',
            '=',
            't.id'
        )
            ->join(
                'variations as v',
                'transaction_sell_lines.variation_id',
                '=',
                'v.id'
            )
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            // ->join('variation_location_details as vlds', 'pv.product_id', '=', 'vlds.product_id')
            // ->join('suppliers as s', 's.id','=','p.supplier_id')
            ->join('sizes as sub_size', 'p.sub_size_id', '=', 'sub_size.id')
            ->join('colors', 'p.color_id', '=', 'colors.id')
            ->leftjoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('p.id', $id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.image as image',
                'p.supplier_id as supplier_id',
                // 's.name as supplier',
                'p.refference as refference',
                'p.type as product_type',
                'p.sku as barcode',
                'sub_size.name as size',
                'colors.name as color',
                'pv.name as product_variation',
                'v.name as variation_name',
                'c.name as customer',
                't.id as transaction_id',
                't.invoice_no',
                't.transaction_date as transaction_date',
                'transaction_sell_lines.unit_price_before_discount as unit_price',
                'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                'p.product_updated_at as product_updated_at',
                'transaction_sell_lines.original_amount as original_amount',
                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as current_stock"),
                DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
                DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_id = p.id) as total_sold"),
                'transaction_sell_lines.line_discount_type as discount_type',
                'transaction_sell_lines.line_discount_amount as discount_amount',
                'transaction_sell_lines.item_tax',
                'tax_rates.name as tax',
                'u.short_name as unit',
                DB::raw("
                IF (p.print_price_check = 1 ,
                (transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.original_amount
                , 
                (transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax 
                ) as subtotal"),
                // DB::raw( "((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal")
            )
            ->orderBy('transaction_date', 'DESC')
            // ->orderBy('t.invoice_no','DESC')
            ->groupBy('transaction_sell_lines.id');
        // dd($query->first());
        // if (!empty($variation_id)) {
        //     $query->where('transaction_sell_lines.variation_id', $variation_id);
        // }

        // $start_date = $request->get('start_date');
        // $end_date = $request->get('end_date');
        // if (!empty($start_date) && !empty($end_date)) {
        //     $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
        // }

        // $purchase_start_date = $request->get('purchase_start_date');
        // $purchase_end_date = $request->get('purchase_end_date');

        // if (!empty($purchase_start_date) && !empty($purchase_end_date)) {
        //     $query->whereBetween(DB::raw('date(product_updated_at)'), [$purchase_start_date, $purchase_end_date]);
        // }

        // $permitted_locations = auth()->user()->permitted_locations();
        // if ($permitted_locations != 'all') {
        //     $query->whereIn('t.location_id', $permitted_locations);
        // }

        if (!empty($location_id)) {
            // dd(1);
            $query->where('t.location_id', $location_id);
        }

        // $customer_id = $request->get('customer_id', null);
        // if (!empty($customer_id)) {
        //     $query->where('t.contact_id', $customer_id);
        // }

        // $supplier_id = $request->get('supplier_id', null);
        // if (!empty($supplier_id)) {
        //     $query->where('p.supplier_id', $supplier_id);
        // }

        $query = $query->get();

        // $business_locations = BusinessLocation::forDropdown($business_id);
        // $customers = Contact::customersDropdown($business_id);
        // $suppliers = Supplier::forDropdown($business_id);

        // return view('report.product_sell_report')
        // ->with(compact('business_locations', 'customers', 'suppliers'));

        // if (!auth()->user()->can('product.view')) {
        //     abort(403, 'Unauthorized action.');
        // }


        $product = Product::where('business_id', $business_id)
            ->where('id', $id)
            ->with([
                'color', 'brand', 'supplier', 'unit', 'category', 'sub_category', 'product_tax', 'variations', 'variations.product_variation', 'variations.group_prices'
            ])
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

        return view('product.view-detailWithSale-modal')->with(compact(
            'product',
            'rack_details',
            'allowed_group_prices',
            'group_price_details',
            'query'
        ));
    }
    /**
     * Shows product report
     *
     * @return \Illuminate\Http\Response
     */
    public function viewProductRefDetailWithSale($refference)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view') && !auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        // $business_id = $request->session()->get('user.business_id');
        // $variation_id = $request->get('variation_id', null);

        // $location_id = request()->session()->get('location_id');
        // $location_id = request()->session()->get('location_id');
        // $location_id =  request()->get('location_id', null);
        $vld_str = '';
        // if (!empty($location_id)) {
        //     $vld_str = "AND vld.location_id=$location_id";
        // }

        $query = TransactionSellLine::join(
            'transactions as t',
            'transaction_sell_lines.transaction_id',
            '=',
            't.id'
        )
            ->join(
                'variations as v',
                'transaction_sell_lines.variation_id',
                '=',
                'v.id'
            )
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            // ->join('variation_location_details as vlds', 'pv.product_id', '=', 'vlds.product_id')
            // ->join('suppliers as s', 's.id','=','p.supplier_id')
            ->join('sizes as sub_size', 'p.sub_size_id', '=', 'sub_size.id')
            ->join('colors', 'p.color_id', '=', 'colors.id')
            ->leftjoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('p.refference', $refference)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.image as image',
                'p.supplier_id as supplier_id',
                // 's.name as supplier',
                'p.refference as refference',
                'p.type as product_type',
                'p.sku as barcode',
                'sub_size.name as size',
                'colors.name as color',
                'pv.name as product_variation',
                'v.name as variation_name',
                'c.name as customer',
                't.id as transaction_id',
                't.invoice_no',
                't.location_id',
                't.transaction_date as transaction_date',
                'transaction_sell_lines.unit_price_before_discount as unit_price',
                'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                'p.product_updated_at as product_updated_at',
                'transaction_sell_lines.original_amount as original_amount',
                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as current_stock"),
                DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
                DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_id = p.id) as total_sold"),
                'transaction_sell_lines.line_discount_type as discount_type',
                'transaction_sell_lines.line_discount_amount as discount_amount',
                'transaction_sell_lines.item_tax',
                'tax_rates.name as tax',
                'u.short_name as unit',
                DB::raw('((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
            )
            ->orderBy('transaction_date', 'DESC')
            // ->orderBy('t.invoice_no','DESC')
            ->groupBy('transaction_sell_lines.id');
        // dd($query->first());
        // if (!empty($variation_id)) {
        //     $query->where('transaction_sell_lines.variation_id', $variation_id);
        // }

        // $start_date = $request->get('start_date');
        // $end_date = $request->get('end_date');
        // if (!empty($start_date) && !empty($end_date)) {
        //     $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
        // }

        // $purchase_start_date = $request->get('purchase_start_date');
        // $purchase_end_date = $request->get('purchase_end_date');

        // if (!empty($purchase_start_date) && !empty($purchase_end_date)) {
        //     $query->whereBetween(DB::raw('date(product_updated_at)'), [$purchase_start_date, $purchase_end_date]);
        // }

        // $permitted_locations = auth()->user()->permitted_locations();
        // if ($permitted_locations != 'all') {
        //     $query->whereIn('t.location_id', $permitted_locations);
        // }

        $location_id = request()->get('location_id', null);
        if (!is_null($location_id)) {
            $query->where('t.location_id', $location_id);
        }

        // $customer_id = $request->get('customer_id', null);
        // if (!empty($customer_id)) {
        //     $query->where('t.contact_id', $customer_id);
        // }

        // $supplier_id = $request->get('supplier_id', null);
        // if (!empty($supplier_id)) {
        //     $query->where('p.supplier_id', $supplier_id);
        // }
        $query = $query->get();

        // $business_locations = BusinessLocation::forDropdown($business_id);
        // $customers = Contact::customersDropdown($business_id);
        // $suppliers = Supplier::forDropdown($business_id);

        // return view('report.product_sell_report')
        // ->with(compact('business_locations', 'customers', 'suppliers'));

        // if (!auth()->user()->can('product.view')) {
        //     abort(403, 'Unauthorized action.');
        // }


        $product = Product::where('business_id', $business_id)
            ->where('refference', $refference)
            ->with([
                'color', 'brand', 'supplier', 'unit', 'category', 'sub_category', 'product_tax', 'variations', 'variations.product_variation', 'variations.group_prices'
            ])
            ->first();

        $price_groups = SellingPriceGroup::where('business_id', $business_id)->pluck('name', 'id', 'refference');

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

        $rack_details = $this->productUtil->getRackDetails($business_id, $product->id, true);

        return view('product.view-detailWithSale-modal')->with(compact(
            'product',
            'rack_details',
            'allowed_group_prices',
            'group_price_details',
            'query'
        ));
    }
    public function generateSoldSubquery($location_filter, $dateInterval, $alias)
    {
        
       return DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
        AND TSL.product_id = p.id AND transactions.transaction_date > CURDATE() - INTERVAL $dateInterval day) as $alias");

    }
    public function generateSoldSubqueryforcolor($location_filter, $dateInterval, $alias)
    {
    
    return DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
        AND TSL.product_id = p.id AND transactions.transaction_date > CURDATE() - INTERVAL $dateInterval day GROUP BY p.color_id) as $alias");
    }
    /**
     * View Color Detail of Product 
     * 
     **/
    public function viewColorDetail($name, $from_date = null, $to_date = null,$refference = null)
    {
        $business_id = request()->session()->get('user.business_id');
        $location_id = request()->get('location_id', null);
        // $refference = request()->get('refference', null);
        // dd($refference);
        $vld_str = '';
        if (!empty($location_id)) {

            $vld_str = "AND vld.location_id=$location_id";
        }

        $location_filter = '';
        if (!empty($location_id)) {
            $location_filter = "AND transactions.location_id=$location_id";
        }
        $variation_id = request()->get('variation_id', null);
        $now = Carbon::now();

        // dd($now->today()->format('Y-m-d'));
        $sevenDaySoldSubquery = $this->generateSoldSubquery($location_filter, 6, 'seven_day_sold');
        $fifteenDaySoldSubquery = $this->generateSoldSubquery($location_filter, 14, 'fifteen_day_sold');
        $sevenDaySoldSubqueryforcolor = $this->generateSoldSubqueryforcolor($location_filter, 6, 'seven_day_sold');
        $fifteenDaySoldSubqueryforcolor = $this->generateSoldSubqueryforcolor($location_filter, 14, 'fifteen_day_sold');
        //product with color and sizes and 2nd table
        $today = $now->format("Y-m-d");
        $sevenDaysAgo = $now->copy()->subDays(7)->format("Y-m-d");
        $fifteenDaysAgo = $now->copy()->subDays(15)->format("Y-m-d");
        $thirtyDaysAgo = $now->copy()->subDays(30)->format("Y-m-d");

        $group_query = Product::from('products as p')
            ->join('variations as v', 'p.id', '=', 'v.product_id')
            ->leftjoin('transaction_sell_lines', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->leftjoin('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('colors as c', 'p.color_id', '=', 'c.id')
            ->join('sizes', 'p.size_id', '=', 'sizes.id')
            ->join('sizes as sub_size', 'p.sub_size_id', '=', 'sub_size.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.name', $name)
            ->where('p.refference', $refference)
            ->leftJoin(DB::raw("(SELECT
                tsl.product_id,
                SUM(CASE WHEN date(transactions.transaction_date) = '{$today}' THEN tsl.quantity ELSE 0 END) as today_sold,
                SUM(CASE WHEN date(transactions.transaction_date) BETWEEN '{$sevenDaysAgo}' AND '{$today}' THEN tsl.quantity  ELSE 0 END) as seven_day_sold,
                SUM(CASE WHEN date(transactions.transaction_date) BETWEEN '{$fifteenDaysAgo}' AND '{$today}' THEN tsl.quantity ELSE 0 END) as fifteen_day_sold
            FROM transaction_sell_lines tsl
            JOIN transactions ON tsl.transaction_id = transactions.id
            WHERE date(transactions.transaction_date) BETWEEN '{$thirtyDaysAgo}' AND '{$today}'
            $location_filter AND transactions.status='final' AND transactions.type='sell'
            GROUP BY tsl.product_id
            ) as sales_data"), 'p.id', '=', 'sales_data.product_id')
            ->leftJoin(DB::raw("(SELECT
                tsl.product_id,
                SUM(tsl.quantity - tsl.quantity_returned) as all_qty_sold
            FROM transaction_sell_lines tsl
            JOIN transactions ON tsl.transaction_id = transactions.id
            WHERE date(transactions.transaction_date) BETWEEN '{$from_date}' AND '{$to_date}'
            $location_filter AND transactions.status='final' AND transactions.type='sell'
            GROUP BY tsl.product_id
            ) as all_sales"), 'p.id', '=', 'all_sales.product_id')
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.image as image',
                'p.refference',
                'p.sku as barcode',
                'p.supplier_id as supplier',
                'p.enable_stock',
                'c.id as color_id',
                'c.name as color',
                'sub_size.name as size',
                'p.type as product_type',
                'pv.name as product_variation',
                'v.name as variation_name',
                't.id as transaction_id',
                't.transaction_date as transaction_date',
                DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                // DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) FROM transaction_sell_lines WHERE transaction_sell_lines.product_id = p.id) as all_time_sold"),
                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND TSL.product_id = p.id) as all_time_sold "),


                // DB::raw($sevenDaySoldSubquery),
                // DB::raw($fifteenDaySoldSubquery),
                // DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned)  FROM transaction_sell_lines WHERE transaction_sell_lines.product_id = p.id AND transaction_sell_lines.updated_at > now() - INTERVAL 15 day) as fifteen_day_sold"),
                // DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned)  FROM transaction_sell_lines WHERE transaction_sell_lines.product_id = p.id AND transaction_sell_lines.updated_at > now() - INTERVAL 7 day) as seven_day_sold"),
                DB::raw("sales_data.today_sold as today_sold"),
                DB::raw("sales_data.seven_day_sold as seven_day_sold"),
                DB::raw("sales_data.fifteen_day_sold as fifteen_day_sold"),
                // DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned)  FROM transaction_sell_lines WHERE transaction_sell_lines.product_id = p.id AND transaction_sell_lines.updated_at  >= DATE_FORMAT(now(), '%Y-%m-%d')  ) as today_sold"),
                // DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.product_refference=p.refference $vld_str GROUP BY p.color_id) as current_stock"),
                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as current_stock"),
                // DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.product_refference=p.refference  $vld_str) as current_stock"),

                DB::raw('all_sales.all_qty_sold as total_qty_sold'),
                DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_id = p.id) as total_sold"),
                DB::raw('DATE_FORMAT(p.product_updated_at, "%Y-%m-%d %H:%i:%s") as product_updated_at'),
                DB::raw('DATE_FORMAT(p.created_at, "%Y-%m-%d %H:%i:%s") as purchase_date'),
                DB::raw('DATE_FORMAT(transaction_sell_lines.updated_at, "%Y-%m-%d %H:%i:%s") as last_update_date'),
                // 'p.product_updated_at as product_updated_at',
                'u.short_name as unit',
                DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
            );

        $current_group_color = Product::from('products as p')
            ->join('variations as v', 'p.id', '=', 'v.product_id')
            ->leftjoin('transaction_sell_lines', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->leftjoin('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('colors as c', 'p.color_id', '=', 'c.id')
            ->join('sizes', 'p.size_id', '=', 'sizes.id')
            ->join('sizes as sub_size', 'p.sub_size_id', '=', 'sub_size.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.name', $name)
            ->where('p.refference', $refference)
            ->leftJoin(DB::raw("(SELECT
                tsl.product_id,
                SUM(CASE WHEN date(transactions.transaction_date) = '{$today}' THEN tsl.quantity ELSE 0 END) as today_sold,
                SUM(CASE WHEN date(transactions.transaction_date) BETWEEN '{$sevenDaysAgo}' AND '{$today}' THEN tsl.quantity  ELSE 0 END) as seven_day_sold,
                SUM(CASE WHEN date(transactions.transaction_date) BETWEEN '{$fifteenDaysAgo}' AND '{$today}' THEN tsl.quantity ELSE 0 END) as fifteen_day_sold
            FROM transaction_sell_lines tsl
            JOIN transactions ON tsl.transaction_id = transactions.id
            WHERE date(transactions.transaction_date) BETWEEN '{$thirtyDaysAgo}' AND '{$today}'
            $location_filter AND transactions.status='final' AND transactions.type='sell'
            GROUP BY tsl.product_id
            ) as sales_data"), 'p.id', '=', 'sales_data.product_id')
          ->leftJoin(DB::raw("(SELECT
                    tsl.product_id,
                    SUM(tsl.quantity - tsl.quantity_returned) as all_qty_sold
                FROM transaction_sell_lines tsl
                JOIN transactions ON tsl.transaction_id = transactions.id
                WHERE date(transactions.transaction_date) BETWEEN '{$from_date}' AND '{$to_date}'
                $location_filter AND transactions.status='final' AND transactions.type='sell'
                GROUP BY tsl.product_id
                ) as all_sales"), 'p.id', '=', 'all_sales.product_id')
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.image as image',
                'p.refference as refference',
                'p.sku as barcode',
                'p.supplier_id as supplier',
                'p.enable_stock',
                'c.id as color_id',
                'c.name as color',
                'p.type as product_type',
                'pv.name as product_variation',
                'v.name as variation_name',
                't.id as transaction_id',
                't.transaction_date as transaction_date',
                DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),

                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as current_stock"),

                DB::raw('all_sales.all_qty_sold as total_qty_sold'),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                    JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
                    WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                    AND TSL.product_id = p.id
                    AND p.color_id = c.id
                ) as all_time_sold"),
                DB::raw("sales_data.today_sold as today_sold"),
                DB::raw("sales_data.seven_day_sold as seven_day_sold"),
                DB::raw("sales_data.fifteen_day_sold as fifteen_day_sold"),
                // DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned)  FROM transaction_sell_lines WHERE transaction_sell_lines.product_id = p.id AND transaction_sell_lines.updated_at >= DATE_FORMAT(now(), '%Y-%m-%d') ) as today_sold"),

                DB::raw('DATE_FORMAT(p.product_updated_at, "%Y-%m-%d %H:%i:%s") as product_updated_at'),
                DB::raw('DATE_FORMAT(p.created_at, "%Y-%m-%d %H:%i:%s") as purchase_date'),
                DB::raw('DATE_FORMAT(transaction_sell_lines.updated_at, "%Y-%m-%d %H:%i:%s") as last_update_date'),

                DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal'),
            //     DB::raw('SUM(
            //     CASE
            //         WHEN t.transaction_date > CURDATE() - INTERVAL 6 DAY THEN transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned
            //         ELSE 0
            //     END
            // ) as seven_day_sold'),
            //     DB::raw('SUM(
            //     CASE
            //         WHEN t.transaction_date > CURDATE() - INTERVAL 14 DAY THEN transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned
            //         ELSE 0
            //     END
            // ) as fifteen_day_sold'),
            );

        // if (!empty($from_date) && !empty($to_date)) {
        //     $current_group_color->whereBetween(DB::raw('date(transaction_date)'), [$from_date, $to_date]);
        // }

        if (isset($location_id)) {
            $current_group_color->where('location_id', $location_id);
        }

        $current_group_color = $current_group_color
            // // ->groupBy('p.color_id')
            ->orderBy('color', 'DESC')
            ->groupBy('color')
            ->groupBy('refference')
            ->groupBy('p.id')
            ->get()
            ->groupBy('color');
        // Assuming $current_group_color_summed is the array containing the grouped colors
        $merged_summed_values = collect($current_group_color)->map(function ($items, $color) {
            // Sum up the values for each color
            $summed_values = collect($items)->reduce(function ($carry, $item) {
                return collect($item)->map(function ($value, $key) use ($carry) {
                    // Sum numeric values, keep non-numeric values from the first occurrence
                    return is_numeric($value) ? $carry->get($key, 0) + $value : $carry->get($key, $value);
                });
            }, collect());

            // Merge color key with summed values
            return $summed_values->prepend('color', $color);
        })->values()->all();

        // dd($merged_summed_values);

        $current_group = $group_query;
        // $current_group_color = $group_query_color;
        $history_group = $group_query->get();
        // if (!empty($from_date) && !empty($to_date)) {
        //     $current_group = $current_group->whereBetween(DB::raw('date(transaction_date)'), [$from_date, $to_date]);
        //     // $current_group_color = $current_group_color->whereBetween(DB::raw('date(transaction_date)'), [$from_date, $to_date]);
        // }
        if (isset($location_id)) {
            $current_group = $current_group->where('location_id', $location_id);
            // $current_group_color = $current_group_color->where('location_id', $location_id);
        }
        $current_group = $current_group
            ->orderBy('color', 'DESC')
            // ->groupBy('color')
            ->groupBy('product_id')
            ->get();
        // $current_group_color = $current_group_color
        //     ->orderBy('color', 'DESC')
        //     ->groupBy('color_id')
        //     ->get();
        // Log::info("Generated SQL Query: \n" . $current_group_color);

        $query = Product::from('products as p')
            ->join('variations as v', 'p.id', '=', 'v.product_id')
            ->leftjoin('transaction_sell_lines', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->leftjoin('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('colors as c', 'p.color_id', '=', 'c.id')
            // ->join('sizes', 'p.size_id', '=', 'sizes.id')
            ->join('sizes as sub_size', 'p.sub_size_id', '=', 'sub_size.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            // ->where('p.name', $name)
            ->leftjoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
            ->where('p.refference', $refference)
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.image as image',
                'p.supplier_id as supplier_id',
                // 's.name as supplier',
                'p.refference as refference',
                'sub_size.name as size',
                'p.type as product_type',
                'p.sku as barcode',
                'pv.name as product_variation',
                'v.name as variation_name',
                'c.name as color',
                't.id as transaction_id',
                't.invoice_no',
                't.transaction_date as transaction_date',
                'transaction_sell_lines.unit_price_before_discount as unit_price',
                'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                'p.product_updated_at as product_updated_at',
                'transaction_sell_lines.original_amount as original_amount',
                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.product_refference=p.refference $vld_str GROUP BY p.color_id) as current_stock"),
                DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
                DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_id = p.id) as total_sold"),
                'transaction_sell_lines.line_discount_type as discount_type',
                'transaction_sell_lines.line_discount_amount as discount_amount',
                'transaction_sell_lines.item_tax',
                'tax_rates.name as tax',
                'u.short_name as unit',
                DB::raw('((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
            )
            ->orderBy('color', 'DESC')
            // ->orderBy('transaction_date', 'DESC')
            // ->orderBy('t.invoice_no','DESC')
            ->groupBy('transaction_sell_lines.id');
        // ->groupBy('transaction_sell_lines.id');
        $current_detail = $query;
        $history_detail = $query->get();
        // dd(isset($location_id) && isset($location_id) != '0');
        // if (!empty($from_date)  && !empty($to_date)) {
        //     // dd(isset($location_id) && isset($location_id) != '0');
        //     $current_detail = $current_detail->whereBetween(DB::raw('date(transaction_date)'), [$from_date, $to_date]);

        //     // dd($current_detail->get());
        // }
        if (isset($location_id)) {
            $current_detail = $current_detail->where('location_id', $location_id);
        }
        $current_detail = $current_detail->orderBy('transaction_date', 'DESC')
            ->get();
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $select_date = date('d/m/y', strtotime($from_date)) . ' - ' . date('d/m/y', strtotime($to_date));

        if(request()->ajax()){
        return view('report.partials.filter_color_detail_by_name_dates', compact('current_group','merged_summed_values', 'select_date', 'name', 'business_locations', 'current_group_color', 'history_group', 'current_detail', 'history_detail', 'from_date', 'to_date','refference'));
        }
        // dd( $select_date);
        return view('report.partials.color_report_by_name_dates', compact('current_group', 'merged_summed_values', 'select_date', 'name', 'business_locations', 'current_group_color', 'history_group', 'current_detail', 'history_detail', 'from_date', 'to_date','refference'));
        // return view('product.view-product-color-detail', compact('current_group', 'current_group_color', 'history_group', 'current_detail', 'history_detail', 'from_date', 'to_date'));
    }
    public function viewColorDetailByFilter($name, $from_date = null, $to_date = null, $refference = null)
    {
        $business_id = request()->session()->get('user.business_id');
        $location_id = request()->get('location_id', null);
        $vld_str = '';
        if (!empty($location_id)) {

            $vld_str = "AND vld.location_id=$location_id";
        }
        $location_filter = '';
        if (!empty($location_id)) {
            $location_filter = "AND transactions.location_id=$location_id";
            // $location_filter = "AND t.location_id=$location_id";
        }
        $variation_id = request()->get('variation_id', null);
        $now = Carbon::now();
        $sevenDaySoldSubquery = $this->generateSoldSubquery($location_filter, 6, 'seven_day_sold');
        $fifteenDaySoldSubquery = $this->generateSoldSubquery($location_filter, 14, 'fifteen_day_sold');
        $sevenDaySoldSubqueryforcolor = $this->generateSoldSubqueryforcolor($location_filter, 6, 'seven_day_sold');
        $fifteenDaySoldSubqueryforcolor = $this->generateSoldSubqueryforcolor($location_filter, 14, 'fifteen_day_sold');
        // dd($now->today()->format('Y-m-d'));
        //product with color and sizes and 2nd table
        $group_query =
            TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
            ->join(
                'variations as v',
                'transaction_sell_lines.variation_id',
                '=',
                'v.id'
            )
            // ->rightjoin('variation_location_details as vlds', 'v.id', '=', 'vlds.variation_id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            // ->join('suppliers as s', 'p.supplier_id', '=', 's.id')
            ->join('colors as c', 'p.color_id', '=', 'c.id')
            ->join('sizes', 'p.size_id', '=', 'sizes.id')
            ->join('sizes as sub_size', 'p.sub_size_id', '=', 'sub_size.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.name', $name)
            ->where('p.refference', $refference)

            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.image as image',
                'p.refference',
                'p.sku as barcode',
                'p.supplier_id as supplier',
                'p.enable_stock',
                'c.id as color_id',
                'c.name as color',
                'sub_size.name as size',
                'p.type as product_type',
                'pv.name as product_variation',
                'v.name as variation_name',
                't.id as transaction_id',
                't.transaction_date as transaction_date',
                DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                // DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) FROM transaction_sell_lines WHERE transaction_sell_lines.product_id = p.id) as all_time_sold"),
                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                    JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                    WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                    AND TSL.product_id = p.id) as all_time_sold "),


                DB::raw($sevenDaySoldSubquery),
                DB::raw($fifteenDaySoldSubquery),
                // DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned)  FROM transaction_sell_lines WHERE transaction_sell_lines.product_id = p.id AND transaction_sell_lines.updated_at > now() - INTERVAL 15 day) as fifteen_day_sold"),
                // DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned)  FROM transaction_sell_lines WHERE transaction_sell_lines.product_id = p.id AND transaction_sell_lines.updated_at > now() - INTERVAL 7 day) as seven_day_sold"),
                DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned)  FROM transaction_sell_lines WHERE transaction_sell_lines.product_id = p.id AND transaction_sell_lines.updated_at  >= DATE_FORMAT(now(), '%Y-%m-%d')  ) as today_sold"),
                // DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.product_refference=p.refference $vld_str GROUP BY p.color_id) as current_stock"),
                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as current_stock"),
                // DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.product_refference=p.refference  $vld_str) as current_stock"),

                DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold'),
                DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_id = p.id) as total_sold"),
                DB::raw('DATE_FORMAT(p.product_updated_at, "%Y-%m-%d %H:%i:%s") as product_updated_at'),
                DB::raw('DATE_FORMAT(p.created_at, "%Y-%m-%d %H:%i:%s") as purchase_date'),
                DB::raw('DATE_FORMAT(transaction_sell_lines.updated_at, "%Y-%m-%d %H:%i:%s") as last_update_date'),
                // 'p.product_updated_at as product_updated_at',
                'u.short_name as unit',
                DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
            );
        //product with color only and 1st table
      
        $group_query_color = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->join('colors as c', 'p.color_id', '=', 'c.id')
            ->join('sizes', 'p.size_id', '=', 'sizes.id')
            ->join('sizes as sub_size', 'p.sub_size_id', '=', 'sub_size.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.name', $name)
            ->where('p.refference', $refference)
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.image as image',
                'p.refference as refference',
                'p.sku as barcode',
                'p.supplier_id as supplier',
                'p.enable_stock',
                'c.id as color_id',
                'c.name as color',
                'p.type as product_type',
                'pv.name as product_variation',
                'v.name as variation_name',
                't.id as transaction_id',
                't.transaction_date as transaction_date',
                DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),

                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as current_stock"),

                DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold'),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
                WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                AND TSL.product_id = p.id
                AND p.color_id = c.id
            ) as all_time_sold"),

                DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned)  FROM transaction_sell_lines WHERE transaction_sell_lines.product_id = p.id AND transaction_sell_lines.updated_at >= DATE_FORMAT(now(), '%Y-%m-%d') ) as today_sold"),

                DB::raw('DATE_FORMAT(p.product_updated_at, "%Y-%m-%d %H:%i:%s") as product_updated_at'),
                DB::raw('DATE_FORMAT(p.created_at, "%Y-%m-%d %H:%i:%s") as purchase_date'),
                DB::raw('DATE_FORMAT(transaction_sell_lines.updated_at, "%Y-%m-%d %H:%i:%s") as last_update_date'),

                DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal'),
                DB::raw('SUM(
                CASE
                    WHEN t.transaction_date > CURDATE() - INTERVAL 6 DAY THEN transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned
                    ELSE 0
                END
            ) as seven_day_sold'),
                DB::raw('SUM(
                CASE
                    WHEN t.transaction_date > CURDATE() - INTERVAL 14 DAY THEN transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned
                    ELSE 0
                END
            ) as fifteen_day_sold'),
            );

        if (!empty($from_date) && !empty($to_date)) {
            $group_query_color->whereBetween(DB::raw('date(transaction_date)'), [$from_date, $to_date]);
        }

        if (isset($location_id)) {
            $group_query_color->where('location_id', $location_id);
        }

        $current_group_color = $group_query_color
            // // ->groupBy('p.color_id')
            ->orderBy('color', 'DESC')
            ->groupBy('color')
            ->groupBy('refference')
            ->groupBy('p.id')
            ->get()
            ->groupBy('color');
        // Assuming $current_group_color_summed is the array containing the grouped colors
        $merged_summed_values = collect($current_group_color)->map(function ($items, $color) {
            // Sum up the values for each color
            $summed_values = collect($items)->reduce(function ($carry, $item) {
                return collect($item)->map(function ($value, $key) use ($carry) {
                    // Sum numeric values, keep non-numeric values from the first occurrence
                    return is_numeric($value) ? $carry->get($key, 0) + $value : $carry->get($key, $value);
                });
            }, collect());

            // Merge color key with summed values
            return $summed_values->prepend('color', $color);
        })->values()->all();
        $current_group = $group_query;
        // $current_group_color = $group_query_color;
        $history_group = $group_query->get();
        if (!empty($from_date) && !empty($to_date)) {
            $current_group = $current_group->whereBetween(DB::raw('date(transaction_date)'), [$from_date, $to_date]);
            // $current_group_color = $current_group_color->whereBetween(DB::raw('date(transaction_date)'), [$from_date, $to_date]);
        }
        if (isset($location_id)) {
            $current_group = $current_group->where('location_id', $location_id);
            // $current_group_color = $current_group_color->where('location_id', $location_id);
        }
        $current_group = $current_group
            ->orderBy('color', 'DESC')
            // ->groupBy('color')
            ->groupBy('product_id')
            ->get();
        // $current_group_color = $current_group_color
        //     ->orderBy('color', 'DESC')
        //     ->groupBy('color_id')
        //     ->get();
        $query =
            TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
            ->join(
                'variations as v',
                'transaction_sell_lines.variation_id',
                '=',
                'v.id'
            )
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            // ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            // ->join('variation_location_details as vlds', 'pv.product_id', '=', 'vlds.product_id')
            // ->join('suppliers as s', 's.id','=','p.supplier_id')
            ->join('sizes as sub_size', 'p.sub_size_id', '=', 'sub_size.id')
            ->join('colors as c', 'p.color_id', '=', 'c.id')
            ->leftjoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.name', $name)
            ->where('p.refference', $refference)
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.image as image',
                'p.supplier_id as supplier_id',
                // 's.name as supplier',
                'p.refference as refference',
                'sub_size.name as size',
                'p.type as product_type',
                'p.sku as barcode',
                'pv.name as product_variation',
                'v.name as variation_name',
                'c.name as color',
                't.id as transaction_id',
                't.invoice_no',
                't.transaction_date as transaction_date',
                'transaction_sell_lines.unit_price_before_discount as unit_price',
                'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                'p.product_updated_at as product_updated_at',
                'transaction_sell_lines.original_amount as original_amount',
                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.product_refference=p.refference $vld_str GROUP BY p.color_id) as current_stock"),
                DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
                DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_id = p.id) as total_sold"),
                'transaction_sell_lines.line_discount_type as discount_type',
                'transaction_sell_lines.line_discount_amount as discount_amount',
                'transaction_sell_lines.item_tax',
                'tax_rates.name as tax',
                'u.short_name as unit',
                DB::raw('((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
            )
            ->orderBy('color', 'DESC')
            // ->orderBy('transaction_date', 'DESC')
            // ->orderBy('t.invoice_no','DESC')
            ->groupBy('t.id');
        // ->groupBy('transaction_sell_lines.id');
        $current_detail = $query;
        $history_detail = $query->get();
        // dd(isset($location_id) && isset($location_id) != '0');
        if (!empty($from_date)  && !empty($to_date)) {
            // dd(isset($location_id) && isset($location_id) != '0');
            $current_detail = $current_detail->whereBetween(DB::raw('date(transaction_date)'), [$from_date, $to_date]);

            // dd($current_detail->get());
        }
        if (isset($location_id)) {
            $current_detail = $current_detail->where('location_id', $location_id);
        }
        $current_detail = $current_detail->orderBy('transaction_date', 'DESC')
            ->get();
        // $current_detail = $current_detail
        //                     ->orderBy('transaction_date','DESC')
        //                     ->groupBy('t.id')
        //                     ->get();
        // ->toSql();
        // dd($current_group_color);
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $select_date = date('d/m/y', strtotime($from_date)) . ' - ' . date('d/m/y', strtotime($to_date));
        // dd( $select_date);
        return view('report.partials.filter_color_detail_by_name_dates', compact('current_group','merged_summed_values', 'select_date', 'name', 'business_locations', 'current_group_color', 'history_group', 'current_detail', 'history_detail', 'from_date', 'to_date','refference'));
        // return view('product.view-product-color-detail', compact('current_group', 'current_group_color', 'history_group', 'current_detail', 'history_detail', 'from_date', 'to_date'));
    }

    /**
     * View Color Detail of Product By Refrence Id
     * 
     **/
    public function viewColorDetailByRefrenceID($name, $id = null, $from_date = null, $to_date = null)
    {
        $business_id = request()->session()->get('user.business_id');
        $location_id = request()->get('location_id', null);

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }
        $variation_id = request()->get('variation_id', null);
        $group_query =
            TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
            ->join(
                'variations as v',
                'transaction_sell_lines.variation_id',
                '=',
                'v.id'
            )
            // ->rightjoin('variation_location_details as vlds', 'v.id', '=', 'vlds.variation_id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            // ->join('suppliers as s', 'p.supplier_id', '=', 's.id')
            ->join('colors as c', 'p.color_id', '=', 'c.id')
            ->join('sizes', 'p.size_id', '=', 'sizes.id')
            ->join('sizes as sub_size', 'p.sub_size_id', '=', 'sub_size.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.name', $name)
            ->where('p.refference', $id)
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.image as image',
                'p.refference',
                'p.sku as barcode',
                'p.supplier_id as supplier',
                'p.enable_stock',
                'c.id as color_id',
                'c.name as color',
                'sub_size.name as size',
                'p.type as product_type',
                'pv.name as product_variation',
                'v.name as variation_name',
                't.id as transaction_id',
                't.transaction_date as transaction_date',
                DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) FROM transaction_sell_lines WHERE transaction_sell_lines.product_id = p.id) as all_time_sold"),
                DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned)  FROM transaction_sell_lines WHERE transaction_sell_lines.product_id = p.id AND transaction_sell_lines.updated_at > now() - INTERVAL 15 day) as fifteen_day_sold"),
                // DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.product_refference=p.refference $vld_str GROUP BY p.color_id) as current_stock"),
                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as current_stock"),
                DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold'),
                DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_refference = p.refference) as total_sold"),
                DB::raw('DATE_FORMAT(p.product_updated_at, "%Y-%m-%d %H:%i:%s") as product_updated_at'),
                // 'p.product_updated_at as product_updated_at',
                'u.short_name as unit',
                DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
            );
        $group_query_color =
            TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
            ->join(
                'variations as v',
                'transaction_sell_lines.variation_id',
                '=',
                'v.id'
            )
            // ->rightjoin('variation_location_details as vlds', 'v.id', '=', 'vlds.variation_id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            // ->join('suppliers as s', 'p.supplier_id', '=', 's.id')
            ->join('colors as c', 'p.color_id', '=', 'c.id')
            // ->join('sizes', 'p.size_id', '=', 'sizes.id')
            // ->join('sizes as sub_size', 'p.sub_size_id', '=', 'sub_size.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.name', $name)
            // ->where('c.id', $id)
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.image as image',
                'p.refference as refference',
                'p.sku as barcode',
                'p.supplier_id as supplier',
                'p.enable_stock',
                'c.id as color_id',
                'c.name as color',
                // 'sub_size.name as size',
                'p.type as product_type',
                'pv.name as product_variation',
                'v.name as variation_name',
                't.id as transaction_id',
                't.transaction_date as transaction_date',
                DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                // DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.product_refference=p.refference $vld_str GROUP BY p.color_id) as current_stock"),
                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as current_stock"),
                DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold'),
                DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) FROM transaction_sell_lines WHERE transaction_sell_lines.product_refference = p.refference) as all_time_sold"),
                DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned)  FROM transaction_sell_lines WHERE transaction_sell_lines.product_refference = p.refference AND transaction_sell_lines.updated_at > now() - INTERVAL 15 day) as fifteen_day_sold"),
                DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_refference = p.refference) as total_sold"),
                DB::raw('DATE_FORMAT(p.product_updated_at, "%Y-%m-%d %H:%i:%s") as product_updated_at'),
                // 'p.product_updated_at as product_updated_at',
                'u.short_name as unit',
                DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
            );
        $current_group = $group_query;
        $current_group_color = $group_query_color;
        $history_group = $group_query->get();
        if (!empty($from_date) && !empty($to_date)) {
            $current_group = $current_group->whereBetween(DB::raw('date(transaction_date)'), [$from_date, $to_date]);
            $current_group_color = $current_group_color->whereBetween(DB::raw('date(transaction_date)'), [$from_date, $to_date]);
        }
        $current_group = $current_group
            ->orderBy('color', 'DESC')
            // ->groupBy('color')
            ->groupBy('product_id')
            ->get();
        $current_group_color = $current_group_color
            ->orderBy('color', 'DESC')
            ->groupBy('color_id')
            ->get();
        // dd($current_group_color);
        $query =
            TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
            ->join(
                'variations as v',
                'transaction_sell_lines.variation_id',
                '=',
                'v.id'
            )
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            // ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            // ->join('variation_location_details as vlds', 'pv.product_id', '=', 'vlds.product_id')
            // ->join('suppliers as s', 's.id','=','p.supplier_id')
            ->join('sizes as sub_size', 'p.sub_size_id', '=', 'sub_size.id')
            ->join('colors as c', 'p.color_id', '=', 'c.id')
            ->leftjoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.name', $name)
            // ->where('c.id', $id)
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.image as image',
                'p.supplier_id as supplier_id',
                // 's.name as supplier',
                'p.refference as refference',
                'sub_size.name as size',
                'p.type as product_type',
                'p.sku as barcode',
                'pv.name as product_variation',
                'v.name as variation_name',
                'c.id as color_id',
                'c.name as color',
                't.id as transaction_id',
                't.invoice_no',
                't.transaction_date as transaction_date',
                'transaction_sell_lines.unit_price_before_discount as unit_price',
                'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                'p.product_updated_at as product_updated_at',
                'transaction_sell_lines.original_amount as original_amount',
                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.product_refference=p.refference $vld_str GROUP BY p.color_id) as current_stock"),
                DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
                DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_id = p.id) as total_sold"),
                'transaction_sell_lines.line_discount_type as discount_type',
                'transaction_sell_lines.line_discount_amount as discount_amount',
                'transaction_sell_lines.item_tax',
                'tax_rates.name as tax',
                'u.short_name as unit',
                DB::raw('((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
            )
            ->orderBy('color', 'DESC')
            // ->orderBy('transaction_date', 'DESC')
            // ->orderBy('t.invoice_no','DESC')
            ->groupBy('t.id');
        // ->groupBy('transaction_sell_lines.id');
        $current_detail = $query;
        $history_detail = $query->get();
        if (!empty($from_date) && !empty($to_date)) {
            $current_detail = $current_detail->whereBetween(DB::raw('date(transaction_date)'), [$from_date, $to_date]);
            // dd($current_detail->get());
        }
        $current_detail = $current_detail->orderBy('transaction_date', 'DESC')
            ->get();
        // $current_detail = $current_detail
        //                     ->orderBy('transaction_date','DESC')
        //                     ->groupBy('t.id')
        //                     ->get();
        // ->toSql();
        // dd($current_group_color);
        return view('product.view-product-color-detail-by-id', compact('current_group', 'current_group_color', 'history_group', 'current_detail', 'history_detail', 'from_date', 'to_date'));
        // dd($query);
    }
    /**
     * View Color Detail of Product Stock Report
     * 
     **/
    public function viewColorDetailStock($name, $from_date = null, $to_date = null, $location_id = 0)
    {
        $business_id = request()->session()->get('user.business_id');
        // $location_id = request()->get('location_id', null);

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }
        $variation_id = request()->get('variation_id', null);
        $group_query =
            Variation::join('products as p', 'p.id', '=', 'variations.product_id')
            ->join('units', 'p.unit_id', '=', 'units.id')
            ->join('colors', 'p.color_id', '=', 'colors.id')
            ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
            ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
            ->join('categories', 'p.category_id', '=', 'categories.id')
            ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
            ->leftjoin('colors as c', 'p.color_id', '=', 'c.id')
            ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
            ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
            ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
            ->where('p.business_id', $business_id)
            ->where('p.name', $name)
            ->whereIn('p.type', ['single', 'variable']);
        // dd($location_id);
        if ($location_id) {
            $group_query = $group_query->where('vld.location_id', $location_id);
        }
        $group_query = $group_query->select(
            // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final'  AND
            //     transaction_sell_lines.product_id=products.id) as total_sold"),

            DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' 
                        AND TSL.variation_id=variations.id) as total_qty_sold"),
            DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' 
                        AND (TSL.variation_id=variations.id)) as total_transfered"),
            DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' 
                        AND (SAL.variation_id=variations.id)) as total_adjusted"),
            DB::raw("SUM(vld.qty_available) as stock"),
            'variations.sub_sku as sku',
            'p.id as product_id',
            'bl.name as location_name',
            'vld.location_id as location_id',
            'p.created_at',
            'p.name as product_name',
            'p.image as image',
            'p.description as description',
            'p.type',
            'p.refference',
            'colors.name as color',
            'suppliers.name as supplier_name',
            'categories.name as category_name',
            'sub_cat.name as sub_category_name',
            'sizes.name as size_name',
            'units.short_name as unit',
            'p.enable_stock as enable_stock',
            'variations.sell_price_inc_tax as unit_price',
            'pv.name as product_variation',
            'vld.product_updated_at as product_date',
            'vld.location_print_qty as printing_qty',
            'variations.name as variation_name',
            'vld.updated_at',
            // 'vld.qty_available as current_stock'
            DB::raw('SUM(vld.qty_available) as current_stock')
        )
            ->groupBy('color')
            ->orderBy('vld.product_updated_at', 'DESC');

        $current_group = $group_query;
        $history_group = $group_query->get();
        if (!empty($from_date) && !empty($to_date)) {
            $current_group = $current_group->whereBetween(DB::raw('date(vld.product_updated_at)'), [$from_date, $to_date]);
        }
        $current_group = $current_group->get();

        $query =
            Variation::join('products as p', 'p.id', '=', 'variations.product_id')
            ->join('units', 'p.unit_id', '=', 'units.id')
            ->join('colors', 'p.color_id', '=', 'colors.id')
            ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
            ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
            ->join('categories', 'p.category_id', '=', 'categories.id')
            ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
            ->leftjoin('colors as c', 'p.color_id', '=', 'c.id')
            ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
            ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
            ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
            ->where('p.business_id', $business_id)
            ->where('p.name', $name)
            ->whereIn('p.type', ['single', 'variable']);
        if ($location_id) {
            $query = $query->where('vld.location_id', $location_id);
        }
        $query = $query->select(
            // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' AND
            //     transaction_sell_lines.product_id=products.id) as total_sold"),

            DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell'  
                        AND TSL.variation_id=variations.id) as sell_qty"),
            DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer'  
                        AND (TSL.variation_id=variations.id)) as total_transfered"),
            DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment'  
                        AND (SAL.variation_id=variations.id)) as total_adjusted"),
            DB::raw("SUM(vld.qty_available) as stock"),
            'variations.sub_sku as sku',
            'p.id as product_id',
            'bl.name as location_name',
            'vld.location_id as location_id',
            'p.created_at',
            'p.show_pos as show_pos',
            'p.name as product_name',
            'p.image as image',
            'p.description as description',
            'p.type',
            'p.refference',
            'colors.name as color',
            'suppliers.name as supplier_name',
            'categories.name as category_name',
            'sub_cat.name as sub_category_name',
            'sizes.name as size_name',
            'units.short_name as unit',
            'p.enable_stock as enable_stock',
            'variations.sell_price_inc_tax as unit_price',
            'pv.name as product_variation',
            'vld.product_updated_at as product_date',
            'vld.location_print_qty as printing_qty',
            'variations.name as variation_name',
            'vld.updated_at',
            // 'vld.qty_available as current_stock'
            DB::raw('SUM(vld.qty_available) as current_stock')
        )->groupBy('variations.id')
            ->orderBy('vld.product_updated_at', 'DESC');

        $history_detail = $query->get();
        $current_detail = $query;
        if (!empty($from_date) && !empty($to_date)) {
            $current_detail = $current_detail->whereBetween(DB::raw('date(vld.product_updated_at)'), [$from_date, $to_date]);
        }
        $current_detail = $current_detail->get();
        return view('product.view-product-color-detail', compact('current_group', 'history_group', 'current_detail', 'history_detail'));
        // dd($query);
    }
    /**
     * Mass deletes products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function massDestroy(Request $request)
    {
        if (!auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $purchase_exist = false;

            if (!empty($request->input('selected_rows'))) {
                $business_id = $request->session()->get('user.business_id');

                $selected_rows = explode(',', $request->input('selected_rows'));

                $products = Product::where('business_id', $business_id)
                    ->whereIn('id', $selected_rows)
                    ->with('purchase_lines')
                    ->get();
                $deletable_products = [];

                DB::beginTransaction();

                foreach ($products as $product) {
                    //Delete if no purchase found
                    if (empty($product->purchase_lines->toArray())) {
                        //Delete variation location details
                        VariationLocationDetails::where('product_id', $product->id)
                            ->delete();
                        $product->delete();
                    } else {
                        $purchase_exist = true;
                    }
                }

                DB::commit();
            }

            if (!$purchase_exist) {
                $output = [
                    'success' => 1,
                    'msg' => __('lang_v1.deleted_success')
                ];
            } else {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.products_could_not_be_deleted')
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * Shows form to add selling price group prices for a product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addSellingPrices($id)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $product = Product::where('business_id', $business_id)
            ->with(['variations', 'variations.group_prices', 'variations.product_variation'])
            ->findOrFail($id);

        $price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $variation_prices = [];
        foreach ($product->variations as $variation) {
            foreach ($variation->group_prices as $group_price) {
                $variation_prices[$variation->id][$group_price->price_group_id] = $group_price->price_inc_tax;
            }
        }
        return view('product.add-selling-prices')->with(compact('product', 'price_groups', 'variation_prices'));
    }

    /**
     * Saves selling price group prices for a product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveSellingPrices(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $product = Product::where('business_id', $business_id)
                ->with(['variations'])
                ->findOrFail($request->input('product_id'));
            DB::beginTransaction();
            foreach ($product->variations as $variation) {
                $variation_group_prices = [];
                foreach ($request->input('group_prices') as $key => $value) {
                    if (isset($value[$variation->id])) {
                        $variation_group_price =
                            VariationGroupPrice::where('variation_id', $variation->id)
                            ->where('price_group_id', $key)
                            ->first();
                        if (empty($variation_group_price)) {
                            $variation_group_price = new VariationGroupPrice([
                                'variation_id' => $variation->id,
                                'price_group_id' => $key
                            ]);
                        }

                        $variation_group_price->price_inc_tax = $this->productUtil->num_uf($value[$variation->id]);
                        $variation_group_prices[] = $variation_group_price;
                    }
                }

                if (!empty($variation_group_prices)) {
                    $variation->group_prices()->saveMany($variation_group_prices);
                }
            }
            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __("lang_v1.updated_success")
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        if ($request->input('submit_type') == 'submit_n_add_opening_stock') {
            return redirect()->action(
                'OpeningStockController@add',
                ['product_id' => $product->id]
            );
        } elseif ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                'ProductController@create'
            )->with('status', $output);
        }

        return redirect('products')->with('status', $output);
    }

    public function viewGroupPrice($id)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $product = Product::where('business_id', $business_id)
            ->where('id', $id)
            ->with(['variations', 'variations.product_variation', 'variations.group_prices'])
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

        return view('product.view-product-group-prices')->with(compact('product', 'allowed_group_prices', 'group_price_details'));
    }

    /**
     * Mass deactivates products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function massDeactivate(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (!empty($request->input('selected_products'))) {
                $business_id = $request->session()->get('user.business_id');

                $selected_products = explode(',', $request->input('selected_products'));

                DB::beginTransaction();

                $products = Product::where('business_id', $business_id)
                    ->whereIn('id', $selected_products)
                    ->update(['is_inactive' => 1]);

                DB::commit();
            }

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.products_deactivated_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }


    public function selectedBulkPrint(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if (!empty($request->input('selected_products_bulkPrint'))) {
                $business_id = $request->session()->get('user.business_id');

                $selected_products = explode(',', $request->input('selected_products_bulkPrint'));
                // dd($selected_products);
                $product = [];
                foreach ($selected_products as $key => $objProduct) {

                    $arr = explode("@", $objProduct);
                    $productId = $arr[0];
                    $productQty = $arr[1];

                    $pro = Product::find($productId);
                    $vld = VariationLocationDetails::where('product_id', $productId)->first()->printing_qty;

                    for ($i = 0; $i < $productQty; $i++) {
                        $product[] = [
                            'name' => $pro->name,
                            'size' => $pro->sub_size()->first()->name,
                            'refference' => $pro->refference,
                            'color' => $pro->color()->first()->name,
                            'barcode' => $pro->sku,
                            'price' => $pro->variations()->first()->sell_price_inc_tax,
                            'supplier' => $pro->supplier()->first()->name,
                            'category' => $pro->sub_category()->first()->name,
                            'count' => $productQty,
                        ];
                    }
                    // dd($productQty);
                }

                // dd($product);


                return view('product.selectedBulkPrint')
                    ->with(compact('product'));
            }

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong") . "Message:" . $e->getMessage()
            ];
        }
        dd($output);
        die();
        // return redirect()->back()->with(['status' => $output]);
    }
    public function massBulkPrint(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (!empty($request->input('selected_products_bulkPrint'))) {
                $business_location = $request->input('printing_location_id');
                $location = $business_location;
                // $location = 'All Location';
                // dd($business_location);
                // if ($business_location) {
                //     $location = BusinessLocation::find($business_location)->name;
                //     // dd($location);
                // }
                // if($location === '0'){
                //     $location = '1';
                // }
                $business_id = $request->session()->get('user.business_id');
                // dd($location);
                $selected_products = explode(',', $request->input('selected_products_bulkPrint'));
                $selected_products_qty = explode(',', $request->input('selected_products_bulkPrint_qty'));
                // dd($location);
                for ($i = 0; $i < count($selected_products); $i++) {
                    $pro = Product::find($selected_products[$i]);
                    $Vld = VariationLocationDetails::where("product_id",$pro->id)->where("location_id",$location)->first();
                    // dd($Vld);
                    if(!is_null($Vld)){
                        $sell_price = $Vld->sell_price;
                    }else{
                        $sell_price = 0; 
                    }
                    if(!is_null($Vld)){
                        $old_sell_price = $Vld->old_sell_price;
                    }else{
                        $old_sell_price = 0; 
                    }
                    $product[] = [
                        'id' => $pro->id,
                        'name' => $pro->name,
                        'type' => $pro->type,
                        'size' => $pro->sub_size()->first()->name,
                        'supplier_id' => $pro->supplier_id,
                        'refference' => $pro->refference,
                        'ColorName' => $pro->color()->first()->name,
                        'sku' => $pro->sku,
                        // 'max_price' => $pro->variations()->first()->sell_price_inc_tax,
                        // 'min_price' => $pro->variations()->first()->sell_price_inc_tax,
                        // 'old_price' => $pro->variations()->first()->old_sell_price_inc_tax,
                         
                        'max_price' => $sell_price,
                        'min_price' => $sell_price,
                        'old_price' => $old_sell_price,
                        'supplier' => $pro->supplier()->first()->name,
                        'sub_category' => $pro->sub_category()->first()->name,
                        'count' => $selected_products_qty[$i],
                        'updated_at' => $pro->updated_at,
                    ];
                }

                $s_products = collect($selected_products);
                $qtys = $s_products->combine($selected_products_qty);

                $print_qtys = $selected_products_qty;
                $product = collect($product)->groupBy('refference');
                // $product = collect($product)->sortBy('name')->sortBy('refference')->sortBy('size')->sortBy('ColorName')->sortBy('supplier_id');
                // ->sortBy('ColorName');
                // $product = collect($product)->sortBy('refference')->sortBy('ColorName');
                // dd($product->sortBy('refference')->sortBy('ColorName'));
                // dd($location);
                // $print_qtys = $qtys->sortKeys()->values()->toArray();
                // $print_qtys = $qtys->sortKeysDesc()->values()->toArray();

                // dd($qtys,$s_products,$selected_products_qty,$print_qtys,$product->pluck('id'));

                return view('product.massBulkPrint')->with(compact('product', 'print_qtys', 'location'));
            }

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong") . "Message:" . $e->getMessage()
            ];
        }
        dd($output);
        die();
        // return redirect()->back()->with(['status' => $output]);
    }
    public function oldMassBulkPrint(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (!empty($request->input('selected_products_bulkPrint'))) {
                $business_location = $request->input('printing_location_id');
                $location = 'All Location';
                if ($business_location) {
                    $location = BusinessLocation::find($business_location)->name;
                }
                $business_id = $request->session()->get('user.business_id');

                $selected_products = explode(',', $request->input('selected_products_bulkPrint'));
                $selected_products_qty = explode(',', $request->input('selected_products_bulkPrint_qty'));

                $product = Product::where('business_id', $business_id)
                    ->whereIn('id', $selected_products)
                    ->get();
                $product = Product::leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                    ->join('units', 'products.unit_id', '=', 'units.id')
                    ->leftJoin('categories as c1', 'products.category_id', '=', 'c1.id')
                    ->leftJoin('categories as c2', 'products.sub_category_id', '=', 'c2.id')
                    ->leftJoin('sizes as s', 'products.size_id', '=', 's.id')
                    ->leftJoin('sizes as ss', 'products.sub_size_id', '=', 'ss.id')
                    ->leftJoin('colors as c', 'products.color_id', '=', 'c.id')
                    ->leftJoin('tax_rates', 'products.tax', '=', 'tax_rates.id')
                    ->leftJoin('variation_location_details as vld', 'vld.product_id', '=', 'products.id')
                    ->join('variations as v', 'v.product_id', '=', 'products.id')
                    ->where('products.business_id', $business_id)
                    ->where('products.type', '!=', 'modifier')
                    ->whereIn('products.id', $selected_products)
                    ->select(
                        'products.id',
                        'products.name as product',
                        'products.type',
                        'products.refference',
                        'c1.name as category',
                        'c2.name as sub_category',
                        's.name as SizeName',
                        'ss.name as SubSizeName',
                        'c.name as ColorName',
                        'units.actual_name as unit',
                        'brands.name as brand',
                        'tax_rates.name as tax',
                        'products.sku',
                        'products.created_at',
                        'products.bulk_add',
                        'products.image',
                        'products.enable_stock',
                        'products.is_inactive',
                        'vld.printing_qty as printing_qty',
                        'vld.product_updated_at',
                        DB::raw('SUM(vld.qty_available) as current_stock'),
                        DB::raw('MAX(v.sell_price_inc_tax) as max_price'),
                        DB::raw('MIN(v.sell_price_inc_tax) as min_price')
                    )->groupBy('products.id')
                    // ->orderBy('products.id','ASC')
                    // ->orderBy('products.refference','ASC')
                    ->orderBy('vld.product_updated_at', 'DESC')
                    ->get();

                // Below code is to arrange desired qtys as per products
                $s_products = collect($selected_products);
                $qtys = $s_products->combine($selected_products_qty);

                $print_qtys = $selected_products_qty;
                // dd($location);
                // $print_qtys = $qtys->sortKeys()->values()->toArray();
                // $print_qtys = $qtys->sortKeysDesc()->values()->toArray();

                // dd($qtys, $s_products, $selected_products_qty, $print_qtys, $product->pluck('id'));

                return view('product.massBulkPrint')
                    ->with(compact('product', 'print_qtys', 'location'));
            }

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong") . "Message:" . $e->getMessage()
            ];
        }
        dd($output);
        die();
        // return redirect()->back()->with(['status' => $output]);
    }

    public function massTransfer(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        // dd($request->input());
        try {
            if (!empty($request->input('selected_products_bulkTransfer'))) {
                $business_id = $request->session()->get('user.business_id');
                $user_location_id = $request->session()->get('user.business_location_id');
                if ($request->input('current_location')) {
                    if ($request->input('current_location') == 0) {
                        $output = [
                            'success' => 0,
                            'msg' => "All Location is Invalid Select Valid Location"
                        ];
                        return redirect()->back()->with('status', $output);
                    }
                    $user_location_id = $request->input('current_location');
                }
                $user_id = $request->session()->get('user.id');

                $selected_products = explode(',', $request->input('selected_products_bulkTransfer'));
                $business_location_id = $request->input('bussiness_bulkTransfer');
                $location_id = $business_location_id;
                // dd($user_location_id,$location_id,$request->input());
                // dd($selected_products);
                foreach ($selected_products as $key => $objProduct) {
                    # code...
                    $purchase_total = 0;
                    $arr = explode("@", $objProduct);
                    $productId = $arr[0];
                    $productQty = $arr[1];
                    $productOrignalQty = $arr[2];
                    $LeftQty = $productOrignalQty - $productQty;
                    if (strcmp($productQty, $productOrignalQty) == 0) {
                        // dd($arr,$location_id);
                        DB::beginTransaction();
                        $objOldPurchaseLine = PurchaseLine::join('transactions as t', 't.id', '=', 'purchase_lines.transaction_id')->where("purchase_lines.product_id", $productId)->first();
                        // dd($arr,$productQty,$productOrignalQty,$LeftQty);
                        // ->where("t.location_id", $business_location_id)->where

                        /**
                         * ---------------IMPORTANT------------------
                         * 
                         * If Uncommented below 'if()' and other comments of location
                         * Id then product will not be save as 0 qty
                         *
                         * */
                        if (false) {
                            // if (empty($objOldPurchaseLine)) {
                            $objPurchaseLine = PurchaseLine::join('transactions as t', 't.id', '=', 'purchase_lines.transaction_id')->where("t.location_id", $user_location_id)->where("purchase_lines.product_id", $productId)->first();

                            $objTransaction = \App\Transaction::where("id", $objPurchaseLine->transaction_id)->update(['location_id' => $business_location_id]);

                            $oldPurchaseLine = VariationLocationDetails::where("location_id", $user_location_id)->where("variation_id", $objPurchaseLine->variation_id)->where("product_id", $productId)->update(['location_id' => $business_location_id]);
                        } elseif (!empty($objOldPurchaseLine)) {
                            // dd($arr,$location_id);
                            // PL with new bussiness location id 
                            $objNewPurchaseLine = $objOldPurchaseLine;
                            $qtyForPurchaseLine = $productQty;
                            // $qtyForPurchaseLine = $productQty + $objNewPurchaseLine->quantity;
                            // PL with exisiting  location id 
                            $objOldPurchaseLine = PurchaseLine::join('transactions as t', 't.id', '=', 'purchase_lines.transaction_id')->where("purchase_lines.product_id", $productId)->first();
                            // ->where("t.location_id", $user_location_id)

                            // dd($objOldPurchaseLine);

                            // $product = Product::where('id', $productId)->update(['business_location_id' => $business_location_id]);
                            $oldPurchaseLine = PurchaseLine::where("transaction_id", $objOldPurchaseLine->transaction_id)->where("product_id", $objOldPurchaseLine->product_id)->where("variation_id", $objOldPurchaseLine->variation_id)->update(['quantity' => $LeftQty]); //Update OLD ONE AND THEN NEW ONE
                            $oldPurchaseLine = PurchaseLine::where("transaction_id", $objNewPurchaseLine->transaction_id)->where("product_id", $objNewPurchaseLine->product_id)->where("variation_id", $objNewPurchaseLine->variation_id)->update(['quantity' => $qtyForPurchaseLine]);

                            //Update Variation_location_details Qty Remaining 
                            $old_qty_of_product = VariationLocationDetails::where("location_id", $user_location_id)->where("variation_id", $objOldPurchaseLine->variation_id)->where("product_id", $objOldPurchaseLine->product_id);

                            // $new_transfer_Qty = $old_qty_of_product->first()->qty_available + $productQty;

                            // dd($new_transfer_Qty);
                            // $old_qty_of_product->update(['qty_available' => $new_transfer_Qty]);
                            $old_qty_of_product->update(['qty_available' => $LeftQty]);

                            // dd($user_location_id,$LeftQty,$oldPurchaseLine);

                            // Commented below for checking
                            $after_transfer = VariationLocationDetails::where("location_id", $business_location_id)->where("variation_id", $objNewPurchaseLine->variation_id)->where("product_id", $objNewPurchaseLine->product_id);

                            $before_transfer_qty = VariationLocationDetails::where("location_id", $business_location_id)->where("variation_id", $objNewPurchaseLine->variation_id)->where("product_id", $objNewPurchaseLine->product_id)->first();

                            $product_detail = Product::find($objNewPurchaseLine->product_id);
                            // dd($before_transfer_qty, $after_transfer);
                            
                            if (!is_null($before_transfer_qty)) {
                                $new_qty = $before_transfer_qty->qty_available + $productQty;

                                $before_transfer_qty->update(['location_print_qty' => $productQty]);
                                $before_transfer_qty->update(['qty_available' => $new_qty]);
                                $before_transfer_qty->update(['product_updated_at' => Carbon::now()]);
                            } else {
                                $variation_location_d = new VariationLocationDetails();
                                $variation_location_d->variation_id = $objNewPurchaseLine->variation_id;
                                $variation_location_d->product_refference = $product_detail->refference;
                                $variation_location_d->product_id = $product_detail->id;
                                $variation_location_d->location_id = $business_location_id;
                                $variation_location_d->product_variation_id = $objNewPurchaseLine->variation_id;
                                $variation_location_d->qty_available = $productQty;
                                $variation_location_d->location_print_qty = $productQty;
                                $variation_location_d->product_updated_at = Carbon::now();
                                $variation_location_d->save();
                            }
                            // dd(1);
                        }
                        // dd(2);
                        // dd($location_id);
                        $ref = Product::find($objOldPurchaseLine->product_id)->refference;
                        $all_products = VariationLocationDetails::where('product_refference', $ref)->where("product_id", $objOldPurchaseLine->product_id)->where("location_id", $location_id)->get();
                        foreach ($all_products as $all_product) {
                            $all_product->update([
                                'updated_at' => Carbon::now(),
                                'product_updated_at' => Carbon::now(),
                            ]);
                        }
                        $all_products = VariationLocationDetails::where('product_refference', $ref)->where("product_id", $objOldPurchaseLine->product_id)->where("location_id", $user_location_id)->get();
                        foreach ($all_products as $all_product) {
                            $all_product->update([
                                'updated_at' => Carbon::now(),
                                'product_updated_at' => Carbon::now(),
                            ]);
                        }
                        $location_transfer_detail = new LocationTransferDetail();
                        $location_transfer_detail->variation_id = $objOldPurchaseLine->variation_id;
                        $location_transfer_detail->product_id = $objOldPurchaseLine->product_id;
                        $location_transfer_detail->product_refference = $ref;
                        $location_transfer_detail->transfered_from = $user_location_id;
                        // transfer to
                        $location_transfer_detail->location_id = $location_id;

                        $location_transfer_detail->product_variation_id = $objOldPurchaseLine->variation_id;

                        $location_transfer_detail->quantity = (float)$productQty;

                        $location_transfer_detail->transfered_on = Carbon::now();

                        $location_transfer_detail->save();

                        DB::commit();
                    } else {
                        DB::beginTransaction();
                        $tempProduct = Product::with(['variations', 'purchase_lines', 'product_tax'])->findOrFail($productId);
                        $oldTranscationLine = $tempProduct->purchase_lines;

                        // $user_location_id;
                        $location_main = 1;
                        $objOrignalPurchaseLine = PurchaseLine::join('transactions as t', 't.id', '=', 'purchase_lines.transaction_id')->where("t.location_id", $location_main)->where("purchase_lines.product_id", $productId)->first();

                        // dd($objOrignalPurchaseLine);

                        $objOrignalPurchaseLine = PurchaseLine::where(
                            "transaction_id",
                            $objOrignalPurchaseLine->transaction_id
                        )->where("product_id", $objOrignalPurchaseLine->product_id)->first();

                        $objOldPurchaseLine = PurchaseLine::join('transactions as t', 't.id', '=', 'purchase_lines.transaction_id')->where("t.location_id", $business_location_id)->where("purchase_lines.product_id", $productId)->first();

                        $isPurchaseLineExist = false;
                        if (!empty($objOldPurchaseLine)) {
                            $isPurchaseLineExist = true;
                            $ExisitngPurchaseLine = $objOldPurchaseLine;
                        }

                        // $ExisitngPurchaseLine = NULL;
                        // foreach ($oldTranscationLine as $key => $PurchaseLine ) {
                        //     # code...
                        //     if($PurchaseLine ->transaction->location_id == $business_location_id && $PurchaseLine->product_id == $productId)
                        //     {
                        //       $isPurchaseLineExist = true;
                        //       $ExisitngPurchaseLine = $PurchaseLine;
                        //     }
                        // }

                        $ProductVariation = $tempProduct->variations[count($tempProduct->variations) - 1];
                        $product = $tempProduct;

                        $tax_percent = !empty($product->product_tax->amount) ? $product->product_tax->amount : 0;
                        $tax_id = !empty($product->product_tax->id) ? $product->product_tax->id : null;
                        $product_details = $tempProduct->toArray();
                        $product_details['id'] = NULL;
                        $product_details['business_location_id'] = $business_location_id;

                        // $product = Product::create($product_details);
                        $objVariation = $ProductVariation;
                        $k = $objVariation->id;
                        $purchase_price = $this->productUtil->num_uf(trim($objVariation->default_purchase_price));
                        $item_tax = $this->productUtil->calc_percentage($objVariation->default_purchase_price, 0);
                        $purchase_price_inc_tax = $purchase_price + $item_tax;

                        $qty_remaining = $this->productUtil->num_uf(trim($productQty));

                        $exp_date = null;
                        $lot_number = null;
                        $purchase_line = null;

                        if ($isPurchaseLineExist) {
                            $purchase_line = PurchaseLine::where("transaction_id", $ExisitngPurchaseLine->transaction_id)->where("product_id", $ExisitngPurchaseLine->product_id)->first();
                            //Quantity = remaining + used
                            $qty_remaining = $qty_remaining + $purchase_line->quantity_used;

                            // if ($qty_remaining != 0) {
                            //Calculate transaction total
                            $purchase_total += ($purchase_price_inc_tax * $qty_remaining);
                            $old_qty = $purchase_line->quantity;

                            // }
                        } else {
                            if ($qty_remaining != 0) {
                                //create newly added purchase lines
                                $purchase_line = new PurchaseLine();
                                $purchase_line->product_id = $product->id;
                                $purchase_line->variation_id = $k;

                                // dd($qty_remaining);

                                // $this->productUtil->updateProductQuantity($location_id, $product->id, $k, $qty_remaining, 0, null, false);

                                //Calculate transaction total
                                $purchase_total += ($purchase_price_inc_tax * $qty_remaining);
                            }
                        }
                        if (!is_null($purchase_line)) {
                            $purchase_line->item_tax = $item_tax;
                            $purchase_line->tax_id = $tax_id;
                            $purchase_line->quantity = $qty_remaining;
                            $purchase_line->pp_without_discount = $purchase_price;
                            $purchase_line->purchase_price = $purchase_price;
                            $purchase_line->purchase_price_inc_tax = $purchase_price_inc_tax;
                            $purchase_line->exp_date = $exp_date;
                            $purchase_line->lot_number = $lot_number;
                        }

                        //create transaction & purchase lines
                        $transaction_date = request()->session()->get("financial_year.start");
                        $transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();
                        $is_new_transaction = false;

                        if ($isPurchaseLineExist) {
                            $transaction = \App\Transaction::findOrFail($ExisitngPurchaseLine->transaction->id);
                            if (!empty($transaction)) {
                                $transaction->total_before_tax = $purchase_total;
                                $transaction->final_total = $purchase_total;
                                $transaction->update();
                            }
                            $transaction_id = $transaction->id;

                            $old_available_qty = VariationLocationDetails::where("location_id", $business_location_id)->where("variation_id", $objOldPurchaseLine->variation_id)->where("product_id", $product->id)->first();
                            $qtyForPurchaseLine = $productQty;
                            // $qtyForPurchaseLine = $productQty + $purchase_line->quantity;
                            // dd($qtyForPurchaseLine);
                        } else {
                            $old_available_qty = VariationLocationDetails::where("location_id", $location_id)->where("product_id", $product->id)->first();
                            // dd((float)$old_available_qty->qty_available,$location_id);
                            // $old_available_qty = VariationLocationDetails::where("location_id", $business_location_id)->where("variation_id", $objOldPurchaseLine->variation_id)->where("product_id", $product->id)->first();
                            $qtyForPurchaseLine = (float)$productQty;

                            // dd($location_id,$old_available_qty->qty_available,$qtyForPurchaseLine,$LeftQty);

                            $transaction = \App\Transaction::where('type', 'opening_stock')
                                ->where('business_id', $business_id)
                                ->where('opening_stock_product_id', $product->id)
                                ->where('location_id', $business_location_id)
                                ->first();
                            if (!empty($transaction)) {
                                $transaction->total_before_tax = $purchase_total;
                                $transaction->final_total = $purchase_total;
                                $transaction->update();
                            } else {
                                $is_new_transaction = true;
                                $transaction = \App\Transaction::create(
                                    [
                                        'type' => 'opening_stock',
                                        'opening_stock_product_id' => $product->id,
                                        'status' => 'received',
                                        'business_id' => $business_id,
                                        'transaction_date' => $transaction_date,
                                        'total_before_tax' => $purchase_total,
                                        'document' => "Transfer At " . date("Y-m-d"),
                                        'location_id' => $business_location_id,
                                        'final_total' => $purchase_total,
                                        'payment_status' => 'paid',
                                        'created_by' => $user_id
                                    ]
                                );
                            }
                            $transaction_id = $transaction->id;
                            $purchase_line->transaction_id = $transaction->id;
                            $objOldPurchaseLine = $purchase_line;
                        }
                        $purchase_line->quantity = $qtyForPurchaseLine;
                        // dd($purchase_line); 
                        // dd("Hello");
                        $purchase_line->save();
                        //Adjust stock over selling if found
                        // $this->productUtil->adjustStockOverSelling($transaction);

                        //adjust it 
                        if ($isPurchaseLineExist) {
                            $oldPurchaseLine = PurchaseLine::where("id", $objOrignalPurchaseLine->id)->update(['quantity' => $LeftQty]);
                            $oldPurchaseLine = PurchaseLine::where("transaction_id", $objOldPurchaseLine->transaction_id)->where("product_id", $product->id)->update(['quantity' => $qtyForPurchaseLine]);
                        } else {
                            // dd($oldPurchaseLine);
                            $oldPurchaseLine = PurchaseLine::where("id", $objOrignalPurchaseLine->id)->update(['quantity' => $LeftQty]); //Update OLD ONE AND THEN NEW ONE
                            $oldPurchaseLine = PurchaseLine::where("transaction_id", $objOldPurchaseLine->transaction_id)->where("product_id", $product->id)->update(['quantity' => $qtyForPurchaseLine]);
                        }
                        // dd($business_location_id,$LeftQty,$purchase_line,$qty_remaining,$qtyForPurchaseLine);
                        //Update Variation_location_details Qty Remaining 
                        // dd("Hello");
                        // dd($LeftQty);
                        /**
                         * Qty for Location is Updating here 
                         * 
                         **/
                        $oldPurchaseLine = VariationLocationDetails::where("location_id", $user_location_id)->where("variation_id", $objOrignalPurchaseLine->variation_id)->where("product_id", $product->id)->update(['qty_available' => $LeftQty]);

                        $transfer_to_location = VariationLocationDetails::where("location_id", $business_location_id)->where("variation_id", $objOldPurchaseLine->variation_id)->where("product_id", $product->id)->first();
                        // dd(!is_null($transfer_to_location));
                        if (!is_null($transfer_to_location)) {
                            $new_qty = (float)$transfer_to_location->qty_available + (float)$qtyForPurchaseLine;

                            // dd($transfer_to_location,(float)$transfer_to_location->qty_available,(float)$qtyForPurchaseLine,$new_qty);

                            $transfer_to_location->qty_available = $new_qty;
                            $transfer_to_location->location_print_qty = $productQty;
                            $transfer_to_location->product_updated_at = Carbon::now();
                            $transfer_to_location->save();
                        } else {
                            // dd($qtyForPurchaseLine);

                            $variation_location_d = new VariationLocationDetails();
                            $variation_location_d->variation_id = $objOldPurchaseLine->variation_id;
                            $variation_location_d->product_refference = $product->refference;
                            $variation_location_d->product_id = $product->id;
                            $variation_location_d->location_id = $business_location_id;
                            $variation_location_d->location_print_qty = $productQty;
                            $variation_location_d->product_variation_id = $objOldPurchaseLine->variation_id;
                            $variation_location_d->qty_available = $productQty;
                            $variation_location_d->product_updated_at = Carbon::now();
                            $variation_location_d->save();
                        }



                        $product->product_updated_at = Carbon::now();
                        $product->save();


                        // dd($location_id);
                        // dd(3);
                        // New table for Purchase Report
                        $transfer_new_location = VariationLocationDetails::where("location_id", $business_location_id)->where("variation_id", $objOldPurchaseLine->variation_id)->where("product_id", $product->id)->first();
                        $ref = Product::find($objOldPurchaseLine->product_id)->refference;
                        $all_products = VariationLocationDetails::where('product_refference', $ref)->where("product_id", $product->id)->where("location_id", $location_id)->get();
                        foreach ($all_products as $all_product) {
                            $all_product->update([
                                'updated_at' => Carbon::now(),
                                'product_updated_at' => Carbon::now(),
                            ]);
                        } 
                        $all_products = VariationLocationDetails::where('product_refference', $ref)->where("product_id", $product->id)->where("location_id", $user_location_id)->get();
                        foreach ($all_products as $all_product) {
                            $all_product->update([
                                'updated_at' => Carbon::now(),
                                'product_updated_at' => Carbon::now(),
                            ]);
                        }
                        $location_transfer_detail = new LocationTransferDetail();
                        $location_transfer_detail->variation_id = $objOldPurchaseLine->variation_id;
                        $location_transfer_detail->product_id = $product->id;
                        $location_transfer_detail->product_refference = $ref;
                        $location_transfer_detail->transfered_from = $user_location_id;
                        // transfer to
                        $location_transfer_detail->location_id = $location_id;

                        $location_transfer_detail->product_variation_id = $transfer_new_location->product_variation_id;

                        $location_transfer_detail->quantity = (float)$qtyForPurchaseLine;
                        $location_transfer_detail->transfered_on = Carbon::now();

                        $location_transfer_detail->save();

                        //create transaction & purchase lines
                        // dd($location_transfer_detail);
                        DB::commit();
                    }
                }

                $output = [
                    'success' => 1,
                    'msg' => "TRANSFER SUCCESSFULY !"
                ];

                // return redirect('products')->with('status', $output);

                // return view('product.massBulkTransfer')->with(compact( 'product' ));

            }

            $output = [
                'success' => 1,
                'msg' => "TRANSFER SUCCESSFULY !"
            ];
            // $output = [
            //     'success' => 1,
            //     'msg' => __('lang_v1.products_deactivated_success')
            // ];
        } catch (\Exception $e) {
            DB::rollBack();
            // dd($e->getMessage());
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong") . "Message:" . $e->getMessage() . ' on Line: ' . $e->getLine() . ' of ' . $e->getFile()
            ];
        }
        // dd($output);
        // die();
        return redirect()->back()->with('status', $output);
        // return redirect()->back()->with(['status' => $output]);
    }

    public function posToMassTransfer(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $selected_products = collect(json_decode($request->products));
        try {
            if (!empty($selected_products)) {
                $business_id = $request->session()->get('user.business_id');
                $user_location_id = $request->session()->get('user.business_location_id');
                if ($request->input('current_location')) {
                    if ($request->input('current_location') == 0) {
                        $output = [
                            'success' => 0,
                            'msg' => "All Location is Invalid Select Valid Location"
                        ];
                        return redirect()->back()->with('status', $output);
                    }
                    $user_location_id = $request->input('current_location');
                }
                $user_id = $request->session()->get('user.id');

                $business_location_id = $request->input('bussiness_bulkTransfer');
                $location_id = $business_location_id;

                
                foreach ($selected_products as $key => $objProduct) {
                    # code...
                    $purchase_total = 0;
                    $productId = $objProduct->product_id;
                    $productQty = $objProduct->quantity;
                    $productOrignalQty = $objProduct->available_qty;
                    $LeftQty = (int)$productOrignalQty -  (int)$productQty;

                    if (strcmp($productQty, $productOrignalQty) == 0) {
                        // dd($arr,$location_id);
                        DB::beginTransaction();
                        $objOldPurchaseLine = PurchaseLine::join('transactions as t', 't.id', '=', 'purchase_lines.transaction_id')->where("purchase_lines.product_id", $productId)->first();

                        /**
                         * ---------------IMPORTANT------------------
                         * 
                         * If Uncommented below 'if()' and other comments of location
                         * Id then product will not be save as 0 qty
                         *
                         * */
                        if (false) {
                            // if (empty($objOldPurchaseLine)) {
                            $objPurchaseLine = PurchaseLine::join('transactions as t', 't.id', '=', 'purchase_lines.transaction_id')->where("t.location_id", $user_location_id)->where("purchase_lines.product_id", $productId)->first();

                            $objTransaction = \App\Transaction::where("id", $objPurchaseLine->transaction_id)->update(['location_id' => $business_location_id]);

                            $oldPurchaseLine = VariationLocationDetails::where("location_id", $user_location_id)->where("variation_id", $objPurchaseLine->variation_id)->where("product_id", $productId)->update(['location_id' => $business_location_id]);
                        } elseif (!empty($objOldPurchaseLine)) {
                            // dd($arr,$location_id);
                            // PL with new bussiness location id 
                            $objNewPurchaseLine = $objOldPurchaseLine;
                            $qtyForPurchaseLine = $productQty;
                            // $qtyForPurchaseLine = $productQty + $objNewPurchaseLine->quantity;
                            // PL with exisiting  location id 
                            $objOldPurchaseLine = PurchaseLine::join('transactions as t', 't.id', '=', 'purchase_lines.transaction_id')->where("purchase_lines.product_id", $productId)->first();
                            // ->where("t.location_id", $user_location_id)

                            $oldPurchaseLine = PurchaseLine::where("transaction_id", $objOldPurchaseLine->transaction_id)->where("product_id", $objOldPurchaseLine->product_id)->where("variation_id", $objOldPurchaseLine->variation_id)->update(['quantity' => $LeftQty]); //Update OLD ONE AND THEN NEW ONE
                            $oldPurchaseLine = PurchaseLine::where("transaction_id", $objNewPurchaseLine->transaction_id)->where("product_id", $objNewPurchaseLine->product_id)->where("variation_id", $objNewPurchaseLine->variation_id)->update(['quantity' => $qtyForPurchaseLine]);

                            //Update Variation_location_details Qty Remaining 
                            $old_qty_of_product = VariationLocationDetails::where("location_id", $user_location_id)->where("variation_id", $objOldPurchaseLine->variation_id)->where("product_id", $objOldPurchaseLine->product_id);
                            $old_qty_of_product->update(['qty_available' => $LeftQty]);

                            // Commented below for checking
                            $after_transfer = VariationLocationDetails::where("location_id", $business_location_id)->where("variation_id", $objNewPurchaseLine->variation_id)->where("product_id", $objNewPurchaseLine->product_id);

                            $before_transfer_qty = VariationLocationDetails::where("location_id", $business_location_id)->where("variation_id", $objNewPurchaseLine->variation_id)->where("product_id", $objNewPurchaseLine->product_id)->first();

                            $product_detail = Product::find($objNewPurchaseLine->product_id);
                            
                            if (!is_null($before_transfer_qty)) {
                                $new_qty = $before_transfer_qty->qty_available + $productQty;

                                $before_transfer_qty->update([
                                    'location_print_qty' => $productQty,
                                    'qty_available' => $new_qty,
                                    'product_updated_at' => Carbon::now()
                                ]);
                            } else {
                                $variation_location_d = new VariationLocationDetails();
                                $variation_location_d->variation_id = $objNewPurchaseLine->variation_id;
                                $variation_location_d->product_refference = $product_detail->refference;
                                $variation_location_d->product_id = $product_detail->id;
                                $variation_location_d->location_id = $business_location_id;
                                $variation_location_d->product_variation_id = $objNewPurchaseLine->variation_id;
                                $variation_location_d->qty_available = $productQty;
                                $variation_location_d->location_print_qty = $productQty;
                                $variation_location_d->product_updated_at = Carbon::now();
                                $variation_location_d->save();
                            }
                        }
                        $ref = Product::find($objOldPurchaseLine->product_id)->refference;
                        $all_products = VariationLocationDetails::where('product_refference', $ref)->where("product_id", $objOldPurchaseLine->product_id)->where("location_id", $location_id)->get();
                        foreach ($all_products as $all_product) {
                            $all_product->update([
                                'updated_at' => Carbon::now(),
                                'product_updated_at' => Carbon::now(),
                            ]);
                        }
                        $all_products = VariationLocationDetails::where('product_refference', $ref)->where("product_id", $objOldPurchaseLine->product_id)->where("location_id", $user_location_id)->get();
                        foreach ($all_products as $all_product) {
                            $all_product->update([
                                'updated_at' => Carbon::now(),
                                'product_updated_at' => Carbon::now(),
                            ]);
                        }
                        $location_transfer_detail = new LocationTransferDetail();
                        $location_transfer_detail->variation_id = $objOldPurchaseLine->variation_id;
                        $location_transfer_detail->product_id = $objOldPurchaseLine->product_id;
                        $location_transfer_detail->product_refference = $ref;
                        $location_transfer_detail->transfered_from = $user_location_id;
                        // transfer to
                        $location_transfer_detail->location_id = $location_id;

                        $location_transfer_detail->product_variation_id = $objOldPurchaseLine->variation_id;

                        $location_transfer_detail->quantity = (float)$productQty;

                        $location_transfer_detail->transfered_on = Carbon::now();

                        $location_transfer_detail->save();

                        DB::commit();
                    } else {
                        DB::beginTransaction();
                        $tempProduct = Product::with(['variations', 'purchase_lines', 'product_tax'])->findOrFail($productId);
                        $oldTranscationLine = $tempProduct->purchase_lines;

                        // $user_location_id;
                        $location_main = 1;
                        $objOrignalPurchaseLine = PurchaseLine::join('transactions as t', 't.id', '=', 'purchase_lines.transaction_id')->where("t.location_id", $location_main)->where("purchase_lines.product_id", $productId)->first();

                        $objOrignalPurchaseLine = PurchaseLine::where(
                            "transaction_id",
                            $objOrignalPurchaseLine->transaction_id
                        )->where("product_id", $objOrignalPurchaseLine->product_id)->first();

                        $objOldPurchaseLine = PurchaseLine::join('transactions as t', 't.id', '=', 'purchase_lines.transaction_id')->where("t.location_id", $business_location_id)->where("purchase_lines.product_id", $productId)->first();

                        $isPurchaseLineExist = false;
                        if (!empty($objOldPurchaseLine)) {
                            $isPurchaseLineExist = true;
                            $ExisitngPurchaseLine = $objOldPurchaseLine;
                        }

                        $ProductVariation = $tempProduct->variations[count($tempProduct->variations) - 1];
                        $product = $tempProduct;

                        $tax_percent = !empty($product->product_tax->amount) ? $product->product_tax->amount : 0;
                        $tax_id = !empty($product->product_tax->id) ? $product->product_tax->id : null;
                        $product_details = $tempProduct->toArray();
                        $product_details['id'] = NULL;
                        $product_details['business_location_id'] = $business_location_id;

                        $objVariation = $ProductVariation;
                        $k = $objVariation->id;
                        $purchase_price = $this->productUtil->num_uf(trim($objVariation->default_purchase_price));
                        $item_tax = $this->productUtil->calc_percentage($objVariation->default_purchase_price, 0);
                        $purchase_price_inc_tax = $purchase_price + $item_tax;

                        $qty_remaining = $this->productUtil->num_uf(trim($productQty));

                        $exp_date = null;
                        $lot_number = null;
                        $purchase_line = null;

                        if ($isPurchaseLineExist) {
                            $purchase_line = PurchaseLine::where("transaction_id", $ExisitngPurchaseLine->transaction_id)->where("product_id", $ExisitngPurchaseLine->product_id)->first();
                            //Quantity = remaining + used
                            $qty_remaining = $qty_remaining + $purchase_line->quantity_used;
                            $purchase_total += ($purchase_price_inc_tax * $qty_remaining);
                            $old_qty = $purchase_line->quantity;

                        } else {
                            if ($qty_remaining != 0) {
                                //create newly added purchase lines
                                $purchase_line = new PurchaseLine();
                                $purchase_line->product_id = $product->id;
                                $purchase_line->variation_id = $k;

                                //Calculate transaction total
                                $purchase_total += ($purchase_price_inc_tax * $qty_remaining);
                            }
                        }
                        if (!is_null($purchase_line)) {
                            $purchase_line->item_tax = $item_tax;
                            $purchase_line->tax_id = $tax_id;
                            $purchase_line->quantity = $qty_remaining;
                            $purchase_line->pp_without_discount = $purchase_price;
                            $purchase_line->purchase_price = $purchase_price;
                            $purchase_line->purchase_price_inc_tax = $purchase_price_inc_tax;
                            $purchase_line->exp_date = $exp_date;
                            $purchase_line->lot_number = $lot_number;
                        }

                        //create transaction & purchase lines
                        $transaction_date = request()->session()->get("financial_year.start");
                        $transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();
                        $is_new_transaction = false;

                        if ($isPurchaseLineExist) {
                            $transaction = \App\Transaction::findOrFail($ExisitngPurchaseLine->transaction->id);
                            if (!empty($transaction)) {
                                $transaction->total_before_tax = $purchase_total;
                                $transaction->final_total = $purchase_total;
                                $transaction->update();
                            }
                            $transaction_id = $transaction->id;
                            $old_available_qty = VariationLocationDetails::where("location_id", $business_location_id)->where("variation_id", $objOldPurchaseLine->variation_id)->where("product_id", $product->id)->first();
                            $qtyForPurchaseLine = $productQty;
  
                        } else {
                            $old_available_qty = VariationLocationDetails::where("location_id", $location_id)->where("product_id", $product->id)->first();
                            $qtyForPurchaseLine = (float)$productQty;
                            $transaction = \App\Transaction::where('type', 'opening_stock')
                                ->where('business_id', $business_id)
                                ->where('opening_stock_product_id', $product->id)
                                ->where('location_id', $business_location_id)
                                ->first();
                            if (!empty($transaction)) {
                                $transaction->total_before_tax = $purchase_total;
                                $transaction->final_total = $purchase_total;
                                $transaction->update();
                            } else {
                                $is_new_transaction = true;
                                $transaction = \App\Transaction::create(
                                    [
                                        'type' => 'opening_stock',
                                        'opening_stock_product_id' => $product->id,
                                        'status' => 'received',
                                        'business_id' => $business_id,
                                        'transaction_date' => $transaction_date,
                                        'total_before_tax' => $purchase_total,
                                        'document' => "Transfer At " . date("Y-m-d"),
                                        'location_id' => $business_location_id,
                                        'final_total' => $purchase_total,
                                        'payment_status' => 'paid',
                                        'created_by' => $user_id
                                    ]
                                );
                            }
                            $transaction_id = $transaction->id;
                            $purchase_line->transaction_id = $transaction->id;
                            $objOldPurchaseLine = $purchase_line;
                        }
                        $purchase_line->quantity = $qtyForPurchaseLine;
                        $purchase_line->save();
                        //Adjust stock over selling if found
                        //adjust it 
                        if ($isPurchaseLineExist) {
                            $oldPurchaseLine = PurchaseLine::where("id", $objOrignalPurchaseLine->id)->update(['quantity' => $LeftQty]);
                            $oldPurchaseLine = PurchaseLine::where("transaction_id", $objOldPurchaseLine->transaction_id)->where("product_id", $product->id)->update(['quantity' => $qtyForPurchaseLine]);
                        } else {
                            $oldPurchaseLine = PurchaseLine::where("id", $objOrignalPurchaseLine->id)->update(['quantity' => $LeftQty]); //Update OLD ONE AND THEN NEW ONE
                            $oldPurchaseLine = PurchaseLine::where("transaction_id", $objOldPurchaseLine->transaction_id)->where("product_id", $product->id)->update(['quantity' => $qtyForPurchaseLine]);
                        }
                
                        //Update Variation_location_details Qty Remaining 
               
                        /**
                         * Qty for Location is Updating here 
                         * 
                         **/
                        $oldPurchaseLine = VariationLocationDetails::where("location_id", $user_location_id)->where("variation_id", $objOrignalPurchaseLine->variation_id)->where("product_id", $product->id)->update(['qty_available' => $LeftQty]);

                        $transfer_to_location = VariationLocationDetails::where("location_id", $business_location_id)->where("variation_id", $objOldPurchaseLine->variation_id)->where("product_id", $product->id)->first();
                        if (!is_null($transfer_to_location)) {
                            $new_qty = (float)$transfer_to_location->qty_available + (float)$qtyForPurchaseLine;

                            $transfer_to_location->qty_available = $new_qty;
                            $transfer_to_location->location_print_qty = $productQty;
                            $transfer_to_location->product_updated_at = Carbon::now();
                            $transfer_to_location->save();
                        } else {
                            $variation_location_d = new VariationLocationDetails();
                            $variation_location_d->variation_id = $objOldPurchaseLine->variation_id;
                            $variation_location_d->product_refference = $product->refference;
                            $variation_location_d->product_id = $product->id;
                            $variation_location_d->location_id = $business_location_id;
                            $variation_location_d->location_print_qty = $productQty;
                            $variation_location_d->product_variation_id = $objOldPurchaseLine->variation_id;
                            $variation_location_d->qty_available = $productQty;
                            $variation_location_d->product_updated_at = Carbon::now();
                            $variation_location_d->save();
                        }

                        $product->product_updated_at = Carbon::now();
                        $product->save();

                        // New table for Purchase Report
                        $transfer_new_location = VariationLocationDetails::where("location_id", $business_location_id)->where("variation_id", $objOldPurchaseLine->variation_id)->where("product_id", $product->id)->first();
                        $ref = Product::find($objOldPurchaseLine->product_id)->refference;
                        $all_products = VariationLocationDetails::where('product_refference', $ref)->where("product_id", $product->id)->where("location_id", $location_id)->get();
                        foreach ($all_products as $all_product) {
                            $all_product->update([
                                'updated_at' => Carbon::now(),
                                'product_updated_at' => Carbon::now(),
                            ]);
                        } 
                        $all_products = VariationLocationDetails::where('product_refference', $ref)->where("product_id", $product->id)->where("location_id", $user_location_id)->get();
                        foreach ($all_products as $all_product) {
                            $all_product->update([
                                'updated_at' => Carbon::now(),
                                'product_updated_at' => Carbon::now(),
                            ]);
                        }
                        $location_transfer_detail = new LocationTransferDetail();
                        $location_transfer_detail->variation_id = $objOldPurchaseLine->variation_id;
                        $location_transfer_detail->product_id = $product->id;
                        $location_transfer_detail->product_refference = $ref;
                        $location_transfer_detail->transfered_from = $user_location_id;
                        // transfer to
                        $location_transfer_detail->location_id = $location_id;
                        $location_transfer_detail->product_variation_id = $transfer_new_location->product_variation_id;

                        $location_transfer_detail->quantity = (float)$qtyForPurchaseLine;
                        $location_transfer_detail->transfered_on = Carbon::now();
                        $location_transfer_detail->save();

                        //create transaction & purchase lines
                        DB::commit();
                    }
                }
                $output = [
                    'success' => 1,
                    'msg' => "TRANSFER SUCCESSFULY !"
                ];
            }

            $output = [
                'success' => 1,
                'msg' => "TRANSFER SUCCESSFULY !"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong") . "Message:" . $e->getMessage() . ' on Line: ' . $e->getLine() . ' of ' . $e->getFile()
            ];
        }

        return response()->json( $output);
    }
    /**
     * Activates the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function activate($id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                $product = Product::where('id', $id)
                    ->where('business_id', $business_id)
                    ->update(['is_inactive' => 0]);

                $output = [
                    'success' => true,
                    'msg' => __("lang_v1.updated_success")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }

    public function bulkAdd()
    {

        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for products quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (!$this->moduleUtil->isQuotaAvailable('products', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('products', $business_id, action('ProductController@index'));
        }
        // $noRefferenceProducts = Product::where('refference', '=', null)->get();
        //If brands, category are enabled then send else false.
        $noRefferenceProducts = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id) : false;
        $suppliers = (request()->session()->get('business.enable_brand') == 1) ? Supplier::where('business_id', $business_id)
            ->pluck('name', 'id')
            ->prepend(__('lang_v1.all_suppliers'), 'all') : false;
        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->orderBy('name', 'Asc')
            ->pluck('name', 'id');

        $dd_sizes = Size::where('parent_id', 0)->pluck('name', 'id');
        // dd($dd_sizes);
        $sizes = Size::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->select('name', 'id')->get();

        $brands = Brands::where('business_id', $business_id)->pluck('name', 'id');
        /**
         *Getting Names of products
         *  
         **/
        $ProductNameCategory = ProductNameCategory::where('business_id', $business_id)->pluck('name', 'id', 'row_no');
        $pnc = array();

        $temp = [];
        foreach ($ProductNameCategory as $key => $objPNC) {
            $temp[] = $key . "@" . $objPNC;
        }
        // Shuffling names of Products
        $pnc = collect($temp)->shuffle()->toArray();
        $pnc = json_encode($pnc);
        // dd($pnc);
        $objBuss = \App\Business::find(request()->session()->get('user.business_id'));
        $refferenceCount = str_pad($objBuss->prod_refference, 4, '0', STR_PAD_LEFT);

        // dd($refferenceCount);

        $suppliers = Supplier::where('business_id', $business_id)
            ->orderBy('name', 'Asc')->pluck('name', 'id');
        $colors = Color::where('business_id', $business_id)
            ->orderBy('name', 'Asc')->pluck('name', 'id');
        $units = Unit::forDropdown($business_id, true);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $barcode_types = $this->barcode_types;
        $barcode_default =  $this->productUtil->barcode_default();

        $default_profit_percent = Business::where('id', $business_id)->value('default_profit_percent');

        //Get all business locations
        $business_locations = BusinessLocation::forDropdown($business_id);

        //Duplicate product
        $duplicate_product = null;
        $rack_details = null;

        $sub_categories = [];
        if (!empty(request()->input('d'))) {
            $duplicate_product = Product::where('business_id', $business_id)->find(request()->input('d'));
            $duplicate_product->name .= ' (copy)';

            if (!empty($duplicate_product->category_id)) {
                $sub_categories = Category::where('business_id', $business_id)
                    ->where('parent_id', $duplicate_product->category_id)
                    ->orderBy('name', 'DESC')
                    ->pluck('name', 'id')
                    ->toArray();
            }

            //Rack details
            if (!empty($duplicate_product->id)) {
                $rack_details = $this->productUtil->getRackDetails($business_id, $duplicate_product->id);
            }
        }

        $sub_sizes = [];
        if (!empty(request()->input('d'))) {
            $duplicate_product = Product::where('business_id', $business_id)->find(request()->input('d'));
            $duplicate_product->name .= ' (copy)';

            if (!empty($duplicate_product->size_id)) {
                $sub_sizes = Size::where('business_id', $business_id)
                    ->where('parent_id', $duplicate_product->size_id)
                    ->pluck('name', 'id')
                    ->toArray();
            }

            //Rack details
            if (!empty($duplicate_product->id)) {
                $rack_details = $this->productUtil->getRackDetails($business_id, $duplicate_product->id);
            }
        }

        $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);

        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');

        // dd($duplicate_product);
        return view('product.bulkAdd')
            ->with(compact('categories', 'suppliers', 'noRefferenceProducts', 'brands', 'refferenceCount', 'pnc', 'suppliers', 'sizes', 'sub_sizes', 'colors', 'units', 'taxes', 'barcode_types', 'default_profit_percent', 'tax_attributes', 'barcode_default', 'business_locations', 'duplicate_product', 'sub_categories', 'rack_details', 'selling_price_group_count', 'module_form_parts', 'dd_sizes'));
    }
    /**
     * Add Color by Product Id 
     * 
     **/
    public function addColor($id)
    {

        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for products quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (!$this->moduleUtil->isQuotaAvailable('products', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('products', $business_id, action('ProductController@index'));
        }
        $product = Product::find($id);
        // $noRefferenceProducts = Product::where('refference', '=', null)->get();
        //If brands, category are enabled then send else false.
        $noRefferenceProducts = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id) : false;
        $suppliers = (request()->session()->get('business.enable_brand') == 1) ? Supplier::where('business_id', $business_id)
            ->pluck('name', 'id')
            ->prepend(__('lang_v1.all_suppliers'), 'all') : false;
        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->orderBy('name', 'Asc')
            ->pluck('name', 'id');

        $dd_sizes = Size::where('parent_id', 0)->pluck('name', 'id');
        // dd($dd_sizes);
        $sizes = Size::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->select('name', 'id')->get();

        $brands = Brands::where('business_id', $business_id)->pluck('name', 'id');
        /**
         *Getting Names of products
         *  
         **/
        $ProductNameCategory = ProductNameCategory::where('business_id', $business_id)->pluck('name', 'id', 'row_no');
        $pnc = array();

        $temp = [];
        foreach ($ProductNameCategory as $key => $objPNC) {
            $temp[] = $key . "@" . $objPNC;
        }
        // Shuffling names of Products
        $pnc = collect($temp)->shuffle()->toArray();
        $pnc = json_encode($pnc);
        // dd($pnc);
        $objBuss = \App\Business::find(request()->session()->get('user.business_id'));
        $refferenceCount = str_pad($objBuss->prod_refference, 4, '0', STR_PAD_LEFT);

        // dd($refferenceCount);

        $suppliers = Supplier::where('business_id', $business_id)
            ->orderBy('name', 'Asc')->pluck('name', 'id');
        $colors = Color::where('business_id', $business_id)
            ->orderBy('name', 'Asc')->pluck('name', 'id');
        $units = Unit::forDropdown($business_id, true);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $barcode_types = $this->barcode_types;
        $barcode_default =  $this->productUtil->barcode_default();

        $default_profit_percent = Business::where('id', $business_id)->value('default_profit_percent');

        //Get all business locations
        $business_locations = BusinessLocation::forDropdown($business_id);

        //Duplicate product
        $duplicate_product = null;
        $rack_details = null;

        $sub_categories = [];
        if (!empty(request()->input('d'))) {
            $duplicate_product = Product::where('business_id', $business_id)->find(request()->input('d'));
            $duplicate_product->name .= ' (copy)';

            if (!empty($duplicate_product->category_id)) {
                $sub_categories = Category::where('business_id', $business_id)
                    ->where('parent_id', $duplicate_product->category_id)
                    ->orderBy('name', 'DESC')
                    ->pluck('name', 'id')
                    ->toArray();
            }

            //Rack details
            if (!empty($duplicate_product->id)) {
                $rack_details = $this->productUtil->getRackDetails($business_id, $duplicate_product->id);
            }
        }

        $sub_sizes = [];
        if (!empty(request()->input('d'))) {
            $duplicate_product = Product::where('business_id', $business_id)->find(request()->input('d'));
            $duplicate_product->name .= ' (copy)';

            if (!empty($duplicate_product->size_id)) {
                $sub_sizes = Size::where('business_id', $business_id)
                    ->where('parent_id', $duplicate_product->size_id)
                    ->pluck('name', 'id')
                    ->toArray();
            }

            //Rack details
            if (!empty($duplicate_product->id)) {
                $rack_details = $this->productUtil->getRackDetails($business_id, $duplicate_product->id);
            }
        }

        $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);

        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');

        return view('product.addColor')
            ->with(compact('categories', 'suppliers', 'noRefferenceProducts', 'brands', 'refferenceCount', 'pnc', 'suppliers', 'sizes', 'sub_sizes', 'colors', 'units', 'taxes', 'barcode_types', 'default_profit_percent', 'tax_attributes', 'barcode_default', 'business_locations', 'duplicate_product', 'sub_categories', 'rack_details', 'selling_price_group_count', 'module_form_parts', 'dd_sizes', 'product'));
    }

    public function bulkAddStore(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        // dd($request->file());
        try {
            $business_id = $request->session()->get('user.business_id');
            $form_fields = [
                'supplier_id',
                'brand_id',
                'category_id',
                'sub_category_id',
                'name',
                'name_id',
                'custom_price',
                'size_id',
                'sub_size_id',
                'qty',
                'refference_id',
                'sku',
                'auto_sku',
                'color_id',
                'single_dpp',
                'single_dpp_inc_tax',
                'profit_percent',
                'single_dsp',
                'single_dsp_inc_tax',
                'file',
                'ref_description'
            ];
            // dd($form_fields);
            // dd($request->only($form_fields));
            $objInputs = $request->only($form_fields);
            $requesst = $request->file('file');
            $bulkAddCode = "BCD" . $this->productUtil->getBulkSerial();
            $tempReff = 0;
            $objBuss = \App\Business::find(request()->session()->get('user.business_id'));
            $tempReff = (int) $objBuss->prod_refference;
            $tRef = "";
            DB::beginTransaction();
            // $productNames = $objInputs['name'];
            // $existingProducts = Product::whereIn('name', $productNames)->get();
            // if ($existingProducts->isNotEmpty()) {
            //     $output = [
            //         'success' => 0,
            //         'msg' => "Bulk " . __('This product names are already available'. $existingProducts->pluck('name')->implode(', '))
            //     ];
            // }
            for ($i = 0; $i < count($objInputs['name']); $i++) {
                // $existingProduct = Product::where('name', $objInputs['name'][$i])->first();
                // if ($existingProduct) {
                //     continue;
                // }
                $deleteNameSeriesId = 0;
                if ($objInputs['name_id'][$i] != 0) {
                    $deleteNameSeriesId = $objInputs['name_id'][$i];
                }
                $product_details = array();
                $product_details['supplier_id'] = $objInputs['supplier_id'][$i];
                // dd($objInputs);
                $product_details['brand_id'] = isset($objInputs['brand_id'][$i]) ? $objInputs['brand_id'][$i] : null;
                // $product_details['brand_id'] = $objInputs['brand_id'][$i];
                $product_details['category_id'] = $objInputs['category_id'][$i];
                $product_details['name'] = $objInputs['name'][$i];
                $product_details['color_id'] = $objInputs['color_id'][$i];
                $product_details['size_id'] = $objInputs['size_id'][$i];
                $product_details['sub_size_id'] = $objInputs['sub_size_id'][$i];
                $product_details['refference']  = $cRef = $objInputs['refference_id'][$i];



                $product_details['type'] = "single";
                $product_details['barcode_type'] = "C128";
                $product_details['alert_quantity'] = "50";
                $product_details['tax_type'] = "exclusive";
                $product_details['enable_stock'] = 1;
                $product_details['unit_id'] = 1;
                $product_details['bulk_add'] = $bulkAddCode;

                $product_details['business_id'] = $business_id;
                $product_details['created_by'] = $request->session()->get('user.id');


                if (!empty($objInputs['sub_category_id'][$i])) {
                    $product_details['sub_category_id'] = $objInputs['sub_category_id'][$i];
                }

                $expiry_enabled = $request->session()->get('business.enable_product_expiry');
                if (!empty($request->input('expiry_period_type')) && !empty($request->input('expiry_period')) && !empty($expiry_enabled) && ($product_details['enable_stock'] == 1)) {
                    $product_details['expiry_period_type'] = $request->input('expiry_period_type');
                    $product_details['expiry_period'] = $this->productUtil->num_uf($request->input('expiry_period'));
                }

                if (!empty($request->input('enable_sr_no')) &&  $request->input('enable_sr_no') == 1) {
                    $product_details['enable_sr_no'] = 1;
                }

                //upload document
                if (!empty($objInputs['file'][$i])) {
                    $product_details['image'] =  $this->productUtil->uploadFileArr($request, 'file', config('constants.product_img_path'), $i);
                } else {
                    $product_details['image'] = 'default.png';
                }

                $product = Product::create($product_details);

                if ($deleteNameSeriesId != '0') {
                    ProductNameCategory::where('id', $deleteNameSeriesId)->delete();
                }

                $sku = $request->input('sku');

                if (isset($request->input('sku')[$i]) && !empty($request->input('sku')[$i])) {
                    $product->custom_barcode = 1;
                    $product->sku = $request->input('sku')[$i];
                } else {
                    $sku = $this->productUtil->generateProductSku($product->id);
                    $product->sku = $sku;
                }
                if (isset($request->input('ref_description')[$i]) && !empty($request->input('ref_description')[$i])) {
                    $product->description = $request->input('ref_description')[$i];
                }
                $product->product_updated_at = Carbon::now();

                $product->save();

                if ($product->type == 'single') {
                    $this->productUtil->createSingleProductVariation($product->id, $product->sku, $objInputs['single_dpp_inc_tax'][$i], $objInputs['single_dpp_inc_tax'][$i], $objInputs['profit_percent'][$i], $objInputs['single_dsp_inc_tax'][$i], $objInputs['single_dsp_inc_tax'][$i]);
                }

                if ($product->enable_stock == 1) {
                    $user_id = $request->session()->get('user.id');

                    $transaction_date = $request->session()->get("financial_year.start");
                    $transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();
                    $variatArr = array(
                        "1" => array(
                            "purchase_price" => $objInputs['custom_price'][$i],
                            "quantity" => $objInputs['qty'][$i],
                            "exp_date" => "",
                            "lot_number" => ""
                        )
                    );

                    $this->productUtil->addSingleProductOpeningStock($business_id, $product, $variatArr, $transaction_date, $user_id);
                }
                //  elseif ($product->type == 'variable') {
                //     if (!empty($request->input('product_variation'))) {
                //         $input_variations = $request->input('product_variation');
                //         $this->productUtil->createVariableProductVariations($product->id, $input_variations);
                //     }
                // }

                //Add product racks details.
                // $product_racks = $request->get('product_racks', null);
                // if (!empty($product_racks)) {
                //     $this->productUtil->addRackDetails($business_id, $product->id, $product_racks);
                // } 
                if ($cRef != $tRef) {
                    $tempReff++;
                    $tRef = $cRef;
                }
            }

            // $reffCountArr = explode("0",$tempReff);
            // $reffCount = (int)$reffCountArr[count($reffCountArr)-1];

            $number = str_pad($tempReff, 4, '0', STR_PAD_LEFT);
            $objBuss->prod_refference = $number;
            $objBuss->save();
            DB::commit();



            $output = [
                'success' => 1,
                'msg' => "Bulk " . __('product.product_added_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong") .  "Line:" . $e->getLine() . "Message:" . $e->getMessage()
            ];
            // dd($output);
            return redirect('products/bulk_add')->with('status', $output);
        }


        // return redirect()->back()->with('status', $output);
        return redirect('products/bulk_add')->with('status', $output);
    }

    public function viewBulkPackage($id)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $BulkId = $id;

        $product = Product::where('business_id', $business_id)
            ->where('bulk_add', $id)
            ->with(['color', 'brand', 'unit', 'category', 'sub_category', 'product_tax', 'variations'])
            ->get();

        return view('product.bulk_package')->with(compact(
            'product',
            'BulkId'
        ));
    }
    /**
     *  Add Product in VLD (Variation Location Detail) for specific location 
     *  from specific product's barcode
     *
     * */
    // public function addProductZeroQtyInLocation($barcode,$location_id)
    public function addProductZeroQtyInLocation()
    {
        try {
            // $product_id = Product::where('sku',$barcode)->pluck('id');
            // $products = Product::where('id','>=',$product_id)->pluck('id');
            $products = Product::pluck('id');
            $count = 0;
            // dd($products);
            // $id = VariationLocationDetails::pluck('id');
            for ($i = 0; $i < count($products); $i++) {
                if ($products[$i]) {
                    DB::beginTransaction();
                    $vld = new VariationLocationDetails();
                    $p_variation_id = ProductVariation::where('product_id', $products[$i])->first()->id;
                    $variation_id = Variation::where('product_id', $products[$i])->first()->id;

                    $vld->product_id = $products[$i];
                    $vld->product_variation_id = $p_variation_id;
                    $vld->variation_id = $variation_id;
                    $vld->location_id = 5;
                    // $vld->location_id = $location_id;
                    $vld->qty_available = 0;
                    $vld->product_updated_at = '2020-07-10 00:00:00';
                    // $vld->product_updated_at = Carbon::now();
                    $vld->save();
                    DB::commit();
                    $count++;
                }
            }

            dd($count . ' products are saved into VLD with location id ' . $location_id);
        } catch (\Exception $ex) {
            DB::rollback();
            dd($ex->getMessage() . ' in file: ' . $ex->getFile() . ' on Line ' . $ex->getLine());
        }
    }
    /**
     *  Add Date on Null in VLD
     * 
     **/
    public function addDateinNull()
    {
        $products = VariationLocationDetails::where('product_updated_at', null)->get();
        // dd($products);
        $count = 0;
        for ($i = 0; $i < count($products); $i++) {
            $vld = VariationLocationDetails::find($products[$i]->id);
            $vld->product_updated_at = '2020-07-01 00:00:00';
            $vld->save();
            $count++;
        }

        dd($count . ' product\'s updated at date is saved into VLD');
    }
    /**
     * Show on Top Of POS 
     * 
     **/
    public function showPos(Request $request)
    {
        // dd($request->all());
        try {
            DB::beginTransaction();
            $product = explode(",", $request->input('product_id'));
            $location_id =$request->input('top_location_id');
            $show_pos = Product::orderBy('show_pos', 'DESC')->first()->show_pos + 1;

            foreach ($product as $key => $value) {
                $product = Product::find($value);
                // $product_ids = Product::where('name', $product->name)->get();
                // // dd($product_ids);
                // foreach ($product_ids as $p_key => $p_value) {
                //     $p_value->update([
                //         'show_pos' => $show_pos,
                //     ]);
                //     // $p_value->show_pos = 1;
                //     // $p_value->save();
                // }

                $product->update([
                    'show_pos' => $show_pos,
                    'business_location_id' => is_null($location_id) ? null : $location_id,
                ]);
            }

        
            $output = [
                'success' => 1,
                'msg' => "Product will show on top on POS"
            ];
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong") . "Message:" . $ex->getMessage() . ' on Line: ' . $ex->getLine() . ' of ' . $ex->getFile()
            ];
        }
        return redirect()->back()->with('status', $output);
    }

    public function removeToPOS(Request $request)
    {
        // dd($request);
        // $validator = $request->validate([
        //     'reffernce'
        // ]);
        try {
            DB::beginTransaction();
            $product = explode(",", $request->input('product_id'));
            $show_pos = Product::orderBy('show_pos', 'DESC')->first()->show_pos + 1;
            foreach ($product as $key => $value) {
                $product = Product::find($value);
                $product_ids = Product::where('name', $product->name)->get();
                // dd($show_pos);
                foreach ($product_ids as $p_key => $p_value) {
                    $p_value->update([
                        'show_pos' => 0,
                        // 'show_pos' => $show_pos,
                    ]);
                    // $p_value->show_pos = 1;
                    // $p_value->save();
                }
            }
            $output = [
                'success' => 1,
                'msg' => "Product will remove from top on POS"
            ];
            DB::commit();
        }  catch (\Exception $ex) {
            DB::rollback();
            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong") . "Message:" . $ex->getMessage() . ' on Line: ' . $ex->getLine() . ' of ' . $ex->getFile()
            ];
        }
        return redirect()->back()->with('status', $output);

        // dd(WebsiteProducts::get(),$product,$request->input());

    }
    /**
     * Show on Bottom Of POS 
     * 
     **/
    public function showBottomPos(Request $request)
    {
        // dd($request);
        try {
            DB::beginTransaction();
            $product = explode(",", $request->input('product_id'));
            foreach ($product as $key => $value) {
                $product = Product::find($value);
                $product_ids = Product::where('name', $product->name)->get();
                // dd($product_ids);
                foreach ($product_ids as $p_key => $p_value) {
                    $p_value->update([
                        'show_pos' => 0,
                    ]);
                    // $p_value->show_pos = 1;
                    // $p_value->save();
                }
            }
            $output = [
                'success' => 1,
                'msg' => "Product will show as normal on POS"
            ];
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong") . "Message:" . $ex->getMessage() . ' on Line: ' . $ex->getLine() . ' of ' . $ex->getFile()
            ];
        }
        return redirect()->back()->with('status', $output);
    }
    public function generateUniqueProductname($name)
    {
        // do {
        //     $randomName = $name . rand(10, 1000);
        // } while (ProductNameCategory::where('name', $randomName)->exists());

        $randomName = $this->generateUniqueMeaningfulName(2);
        return response()->json(['name' => $randomName]);
    }




    /**
     * Generate a unique, meaningful name.
     *
     * @param int $wordCount
     * @return string
     */
    function generateUniqueMeaningfulName($wordCount = 2)
    {
        $path = public_path('Product Name Categories.csv');
        $data = array_map('str_getcsv', file($path));

        // Extract the first column of words, skipping the header row
        $words = array_column(array_slice($data, 1), 0);

        // Cache existing product names
        $existingNames = DB::table('products')->pluck('name')->toArray();

        // Generate a unique name by splitting and combining parts of existing words
        $uniqueName = $this->createSplitAndCombinedName($words, $wordCount);

        // Ensure the generated name is unique
        while (in_array($uniqueName, $existingNames)) {
            $uniqueName = $this->createSplitAndCombinedName($words, $wordCount);
        }

        return $uniqueName;
    }

    /**
     * Create a new name by splitting and combining word parts.
     *
     * @param array $words
     * @param int $wordCount
     * @return string
     */
    function createSplitAndCombinedName($words, $wordCount)
    {
        $splitParts = [];

        // Split each word into two parts
        foreach ($words as $word) {
            $splitPoint = intdiv(strlen($word), 2); // Get the middle of the word
            $splitParts[] = substr($word, 0, $splitPoint);  // First half
            $splitParts[] = substr($word, $splitPoint);      // Second half
        }

        // Shuffle the split parts and take the required number of them
        $randomParts = collect($splitParts)->shuffle()->take($wordCount);

        // Join the random parts together to form the new name
        return $randomParts->join('');
    }
}
