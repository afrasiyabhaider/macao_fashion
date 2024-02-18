<?php

namespace App\Http\Controllers;

use App\Brands;
use App\BusinessLocation;
use App\CashRegister;
use App\Category;

use App\Contact;
use App\CustomerGroup;
use App\Coupon;
use App\Business;
use App\TaxRate;
use App\ExpenseCategory;
use App\LocationTransferDetail;
use App\Product;
use App\PurchaseLine;
use App\Restaurant\ResTable;
use App\SellingPriceGroup;
use App\Supplier;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\TransactionSellLinesPurchaseLines;
use App\Unit;
use App\User;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\VariationLocationDetails;
use App\WebsiteProducts;
use App\CashRegisterTransaction;
use Carbon\Carbon;
use Charts;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Stripe\Terminal\Location;
use Yajra\DataTables\Facades\DataTables as FacadesDataTables;

class ReportController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $transactionUtil;
    protected $productUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Shows profit\loss of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getProfitLoss(Request $request)
    {
        if (!auth()->user()->can('profit_loss_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $location_id = $request->get('location_id');

            //For Opening stock date should be 1 day before
            $day_before_start_date = \Carbon::createFromFormat('Y-m-d', $start_date)->subDay()->format('Y-m-d');
            //Get Opening stock
            $opening_stock = $this->transactionUtil->getOpeningClosingStock($business_id, $day_before_start_date, $location_id, true);

            //Get Closing stock
            $closing_stock = $this->transactionUtil->getOpeningClosingStock(
                $business_id,
                $end_date,
                $location_id
            );

            //Get Purchase details
            $purchase_details = $this->transactionUtil->getPurchaseTotals(
                $business_id,
                $start_date,
                $end_date,
                $location_id
            );

            //Get Sell details
            $sell_details = $this->transactionUtil->getSellTotals(
                $business_id,
                $start_date,
                $end_date,
                $location_id
            );

            $transaction_types = [
                'purchase_return', 'sell_return', 'expense', 'stock_adjustment', 'sell_transfer', 'purchase', 'sell'
            ];

            $transaction_totals = $this->transactionUtil->getTransactionTotals(
                $business_id,
                $transaction_types,
                $start_date,
                $end_date,
                $location_id
            );

            $gross_profit = $this->transactionUtil->getGrossProfit(
                $business_id,
                $start_date,
                $end_date,
                $location_id
            );

            $total_transfer_shipping_charges = $transaction_totals['total_transfer_shipping_charges'];

            //Add total sell shipping charges to $total_transfer_shipping_charges
            if (!empty($sell_details['total_shipping_charges'])) {
                $total_transfer_shipping_charges += $sell_details['total_shipping_charges'];
            }
            //Add total purchase shipping charges to $total_transfer_shipping_charges
            if (!empty($purchase_details['total_shipping_charges'])) {
                $total_transfer_shipping_charges += $purchase_details['total_shipping_charges'];
            }

            //Discounts
            $total_purchase_discount = $transaction_totals['total_purchase_discount'];
            $total_sell_discount = $transaction_totals['total_sell_discount'];

            $data['opening_stock'] = !empty($opening_stock) ? $opening_stock : 0;
            $data['closing_stock'] = !empty($closing_stock) ? $closing_stock : 0;
            $data['total_purchase'] = !empty($purchase_details['total_purchase_exc_tax']) ? $purchase_details['total_purchase_exc_tax'] : 0;
            $data['total_sell'] = !empty($sell_details['total_sell_exc_tax']) ? $sell_details['total_sell_exc_tax'] : 0;
            $data['total_expense'] =  $transaction_totals['total_expense'];

            $data['total_adjustment'] = $transaction_totals['total_adjustment'];

            $data['total_recovered'] = $transaction_totals['total_recovered'];

            $data['total_transfer_shipping_charges'] = $total_transfer_shipping_charges;

            $data['total_purchase_discount'] = !empty($total_purchase_discount) ? $total_purchase_discount : 0;
            $data['total_sell_discount'] = !empty($total_sell_discount) ? $total_sell_discount : 0;

            $data['total_purchase_return'] = $transaction_totals['total_purchase_return_exc_tax'];

            $data['total_sell_return'] = $transaction_totals['total_sell_return_exc_tax'];

            $data['net_profit'] = $data['total_sell'] + $data['closing_stock'] -
                $data['total_purchase'] - $data['total_sell_discount'] -
                $data['opening_stock'] - $data['total_expense'] -
                $data['total_adjustment'] + $data['total_recovered'] -
                $data['total_transfer_shipping_charges'] + $data['total_purchase_discount']
                + $data['total_purchase_return'] - $data['total_sell_return'];

            $data['gross_profit'] = $gross_profit;
            return $data;
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);
        return view('report.profit_loss', compact('business_locations'));
    }

    /**
     * Shows product report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getPurchaseSell(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $location_id = $request->get('location_id');

            $purchase_details = $this->transactionUtil->getPurchaseTotals($business_id, $start_date, $end_date, $location_id);

            $sell_details = $this->transactionUtil->getSellTotals(
                $business_id,
                $start_date,
                $end_date,
                $location_id
            );

            $transaction_types = [
                'purchase_return', 'sell_return'
            ];

            $transaction_totals = $this->transactionUtil->getTransactionTotals(
                $business_id,
                $transaction_types,
                $start_date,
                $end_date,
                $location_id
            );

            $total_purchase_return_inc_tax = $transaction_totals['total_purchase_return_inc_tax'];
            $total_sell_return_inc_tax = $transaction_totals['total_sell_return_inc_tax'];

            $difference = [
                'total' => $sell_details['total_sell_inc_tax'] + $total_sell_return_inc_tax - $purchase_details['total_purchase_inc_tax'] - $total_purchase_return_inc_tax,
                'due' => $sell_details['invoice_due'] - $purchase_details['purchase_due']
            ];

            return [
                'purchase' => $purchase_details,
                'sell' => $sell_details,
                'total_purchase_return' => $total_purchase_return_inc_tax,
                'total_sell_return' => $total_sell_return_inc_tax,
                'difference' => $difference
            ];
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.purchase_sell')
            ->with(compact('business_locations'));
    }

    /**
     * Shows report for Supplier
     *
     * @return \Illuminate\Http\Response
     */
    public function getCustomerSuppliers(Request $request)
    {
        if (!auth()->user()->can('contacts_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $contacts = Contact::where('contacts.business_id', $business_id)
                ->join('transactions AS t', 'contacts.id', '=', 't.contact_id')
                ->groupBy('contacts.id')
                ->select(
                    DB::raw("SUM(IF(t.type = 'purchase', final_total, 0)) as total_purchase"),
                    DB::raw("SUM(IF(t.type = 'purchase_return', final_total, 0)) as total_purchase_return"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                    DB::raw("SUM(IF(t.type = 'purchase', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_paid"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid"),
                    DB::raw("SUM(IF(t.type = 'purchase_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_return_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                    'contacts.supplier_business_name',
                    'contacts.name',
                    'contacts.id'
                );
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $contacts->whereIn('t.location_id', $permitted_locations);
            }
            return Datatables::of($contacts)
                ->editColumn('name', function ($row) {
                    $name = $row->name;
                    if (!empty($row->supplier_business_name)) {
                        $name .= ', ' . $row->supplier_business_name;
                    }
                    return '<a href="' . action('ContactController@show', [$row->id]) . '" target="_blank" class="no-print">' .
                        $name .
                        '</a><span class="print_section">' . $name . '</span>';
                })
                ->editColumn('total_purchase', function ($row) {
                    return '<span class="display_currency total_purchase" data-orig-value="' . $row->total_purchase . '" data-currency_symbol = true>' . $row->total_purchase . '</span>';
                })
                ->editColumn('total_purchase_return', function ($row) {
                    return '<span class="display_currency total_purchase_return" data-orig-value="' . $row->total_purchase_return . '" data-currency_symbol = true>' . $row->total_purchase_return . '</span>';
                })
                ->editColumn('total_sell_return', function ($row) {
                    return '<span class="display_currency total_sell_return" data-orig-value="' . $row->total_sell_return . '" data-currency_symbol = true>' . $row->total_sell_return . '</span>';
                })
                ->editColumn('total_invoice', function ($row) {
                    return '<span class="display_currency total_invoice" data-orig-value="' . $row->total_invoice . '" data-currency_symbol = true>' . $row->total_invoice . '</span>';
                })
                ->addColumn('due', function ($row) {
                    $due = ($row->total_invoice - $row->invoice_received - $row->total_sell_return + $row->sell_return_paid) - ($row->total_purchase - $row->total_purchase_return + $row->purchase_return_received - $row->purchase_paid) + ($row->opening_balance - $row->opening_balance_paid);

                    return '<span class="display_currency total_due" data-orig-value="' . $due . '" data-currency_symbol=true data-highlight=true>' . $due . '</span>';
                })
                ->addColumn(
                    'opening_balance_due',
                    '<span class="display_currency opening_balance_due" data-currency_symbol=true data-orig-value="{{$opening_balance - $opening_balance_paid}}">{{$opening_balance - $opening_balance_paid}}</span>'
                )
                ->removeColumn('supplier_business_name')
                ->removeColumn('invoice_received')
                ->removeColumn('purchase_paid')
                ->removeColumn('id')
                ->rawColumns(['total_purchase', 'total_invoice', 'due', 'name', 'total_purchase_return', 'total_sell_return', 'opening_balance_due'])
                ->make(true);
        }

        return view('report.contact');
    }

    /**
     * Supplier Report
     * 
     * @return \Illuminate\Http\Response
     * */
    public function supplier_report(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);

            $location_id = $request->get('location_id', null);

            $vld_str = '';
            if (!empty($location_id)) {
                $vld_str = "AND vld.location_id=$location_id";
            }

            // dd($products->get());
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->join('suppliers as sup', 'p.supplier_id', '=', 'sup.id')
                ->join('categories', 'p.category_id', '=', 'categories.id')
                ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');
                $query->where('vld.location_id', $location_id);
            }
            $from_date = request()->get('from_date', null);

            $to_date = request()->get('to_date', null);
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('sup.updated_at', '>=', $from_date)->whereDate('sup.updated_at', '<=', $to_date);
            }

            // $supplier_data = $query->select(
            //     'p.id as product_id',
            //     'sup.id as supplier_id',
            //     'sup.name as supplier_name',
            //     // DB::raw("SUM(vld.qty_available) as quantity_available"),
            //     // DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
            //     // DB::raw("SUM(transaction_sell_lines.quantity) as total_sold"),


            //     DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=variations.id $vld_str) as quantity_available"),
            //     DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as quantity_sold'),
            //     DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_id = p.id) as total"),
            // )->groupBy('supplier_id');

            $supplier_data = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND TSL.variation_id=variations.id) as quantity_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                        AND (TSL.variation_id=variations.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                        AND (SAL.variation_id=variations.id)) as total_adjusted"),
                DB::raw("SUM(vld.qty_available) as quantity_available"),
                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.name as product',
                'p.image as image',
                'p.description as description',
                'p.type',
                'p.refference',
                'colors.name as color_name',
                'sup.name as supplier_name',
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
            )->groupBy('p.supplier_id')
                ->orderBy('vld.product_updated_at', 'DESC');

            // dd($supplier_data->first());
            return DataTables::of($supplier_data)
                ->editColumn('quantity_sold', function ($row) {
                    $quantity_sold = 0;
                    if ($row->quantity_sold) {
                        $quantity_sold =  (float) $row->quantity_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $quantity_sold . '" data-unit="Pcs" >' . $quantity_sold . '</span> Pcs';
                })
                ->addColumn('sale_percent', function ($row) {
                    $quantity_available =  (float) $row->quantity_available;
                    $quantity_sold =  (float) $row->quantity_sold;
                    $sum = $quantity_available + $quantity_sold;
                    if ($quantity_available < 1) {
                        $quantity_available = 1;
                    }
                    if ($quantity_sold > $quantity_available) {
                        $quantity_sold = $quantity_available;
                    }
                    $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                    return $percent . '%';
                })
                ->editColumn('quantity_available', function ($row) {
                    $quantity_available = 0;
                    if ($row->quantity_available) {
                        $quantity_available =  (float) $row->quantity_available;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $quantity_available . '" data-unit="Pcs" >' . $quantity_available . '</span> Pcs';
                })
                ->editColumn('total', function ($row) {
                    $total = (int)($row->quantity_available) + (int)($row->quantity_sold);
                    // if ($row->total) {
                    //     $total =  (float) $row->total;
                    // }

                    return '<span data-is_quantity="true" class="display_currency total" data-currency_symbol=false data-orig-value="' . $total . '" data-unit="Pcs" >' . $total . '</span> Pcs';
                })
                ->editColumn('transfered', function ($row) {
                    // $transfered = 0;
                    // if ($row->transfered) {
                    $transfered =  (float) $row->transfered;
                    // }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $transfered . '" data-unit="Pcs" >' . $transfered . '</span> Pcs';
                })
                ->rawColumns(['quantity_sold', 'quantity_available', 'total', 'transfered'])
                ->make(true);
        }
        $business_id = $request->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.supplier_report')
            ->with(compact('business_locations'));
        // dd($query->orderBy('supplier_id', 'ASC')->get()[1]);
    }
    public function old_supplier_report(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);

            $location_id = $request->get('location_id', null);

            $vld_str = '';
            if (!empty($location_id)) {
                $vld_str = "AND vld.location_id=$location_id";
            }
            // $query = Product::join('suppliers as sup', 'products.supplier_id', '=', 'sup.id')
            //     ->join('purchase_lines as pl', 'products.id', '=', 'pl.product_id')
            //     ->join('variation_location_details as vld', 'vld.variation_id', '=', 'pl.variation_id')
            //     ->join('transaction_sell_lines as tsl', 'products.id', '=', 'tsl.product_id');
            // ->join('location_transfer_details as vld', 'vld.variation_id', '=', 'pl.variation_id');

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
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->join('variation_location_details as vld', 'p.id', '=', 'vld.product_id')
                ->join('suppliers as sup', 'p.supplier_id', '=', 'sup.id');

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);
            }
            $from_date = request()->get('from_date', null);

            $to_date = request()->get('to_date', null);
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('sup.updated_at', '>=', $from_date)->whereDate('sup.updated_at', '<=', $to_date);
            }

            $supplier_data = $query->select(
                'p.id as product_id',
                'sup.id as supplier_id',
                'sup.name as supplier_name',
                // DB::raw("SUM(vld.qty_available) as quantity_available"),
                // DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
                // DB::raw("SUM(transaction_sell_lines.quantity) as total_sold"),


                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as quantity_available"),
                DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as quantity_sold'),
                DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_id = p.id) as total"),
            )->groupBy('supplier_id');
            return DataTables::of($supplier_data)
                ->editColumn('quantity_sold', function ($row) {
                    $quantity_sold = 0;
                    if ($row->quantity_sold) {
                        $quantity_sold =  (float) $row->quantity_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $quantity_sold . '" data-unit="Pcs" >' . $quantity_sold . '</span> Pcs';
                })
                ->addColumn('sale_percent', function ($row) {
                    $quantity_available =  (float) $row->quantity_available;
                    $quantity_sold =  (float) $row->quantity_sold;
                    // if($quantity_sold < 1 && $quantity_available > 0){
                    //     $quantity_sold = $quantity_available;
                    // }elseif($quantity_sold < 1 && $quantity_available <1){
                    //     $quantity_available = 1;
                    // }
                    // $percent = number_format((($quantity_available/ $quantity_sold) * 100), 0);
                    // $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                    return '100%';
                })
                ->editColumn('quantity_available', function ($row) {
                    $quantity_available = 0;
                    if ($row->quantity_available) {
                        $quantity_available =  (float) $row->quantity_available;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $quantity_available . '" data-unit="Pcs" >' . $quantity_available . '</span> Pcs';
                })
                ->editColumn('total', function ($row) {
                    $total = 0;
                    if ($row->total) {
                        $total =  (float) $row->total;
                    }

                    return '<span data-is_quantity="true" class="display_currency total" data-currency_symbol=false data-orig-value="' . $total . '" data-unit="Pcs" >' . $total . '</span> Pcs';
                })
                ->editColumn('transfered', function ($row) {
                    // $transfered = 0;
                    // if ($row->transfered) {
                    $transfered =  (float) $row->transfered;
                    // }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $transfered . '" data-unit="Pcs" >' . $transfered . '</span> Pcs';
                })
                ->rawColumns(['quantity_sold', 'quantity_available', 'total', 'transfered'])
                ->make(true);
        }
        $business_id = $request->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.supplier_report')
            ->with(compact('business_locations'));
        // dd($query->orderBy('supplier_id', 'ASC')->get()[1]);
    }
    public function oldd_supplier_report(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }
        if ($request->ajax()) {
            $query = Product::join('suppliers as sup', 'products.supplier_id', '=', 'sup.id')
                ->join('purchase_lines as pl', 'products.id', '=', 'pl.product_id')
                ->join('variation_location_details as vld', 'vld.variation_id', '=', 'pl.variation_id');

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);
            }
            $from_date = request()->get('from_date', null);

            $to_date = request()->get('to_date', null);
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('sup.updated_at', '>=', $from_date)->whereDate('sup.updated_at', '<=', $to_date);
            }

            $supplier_data = $query->groupBy('sup.id')->select(
                'products.id as product_id',
                'sup.id as supplier_id',
                'sup.name as supplier_name',
                DB::raw("COUNT(products.id) as num_of_products"),
                DB::raw("SUM(pl.quantity_sold) as quantity_sold"),
                DB::raw("SUM(vld.qty_available) as quantity_available"),
                DB::raw("SUM(pl.quantity_sold)+SUM(vld.qty_available) as total"),
                DB::raw("COUNT(vld.id) as transfered"),
            );
            return DataTables::of($supplier_data)
                ->editColumn('quantity_sold', function ($row) {
                    $quantity_sold = 0;
                    if ($row->quantity_sold) {
                        $quantity_sold =  (float) $row->quantity_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $quantity_sold . '" data-unit="Pcs" >' . $quantity_sold . '</span> Pcs';
                })
                ->addColumn('sale_percent', function ($row) {
                    $quantity_available =  (float) $row->quantity_available;
                    $quantity_sold =  (float) $row->quantity_sold;
                    if ($quantity_available < 1) {
                        $quantity_available = 1;
                    }
                    $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                    return $percent . '%';
                })
                ->editColumn('quantity_available', function ($row) {
                    $quantity_available = 0;
                    if ($row->quantity_available) {
                        $quantity_available =  (float) $row->quantity_available;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $quantity_available . '" data-unit="Pcs" >' . $quantity_available . '</span> Pcs';
                })
                ->editColumn('total', function ($row) {
                    $total = 0;
                    if ($row->total) {
                        $total =  (float) $row->total;
                    }

                    return '<span data-is_quantity="true" class="display_currency total" data-currency_symbol=false data-orig-value="' . $total . '" data-unit="Pcs" >' . $total . '</span> Pcs';
                })
                ->editColumn('transfered', function ($row) {
                    $transfered = 0;
                    if ($row->transfered) {
                        $transfered =  (float) $row->transfered;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $transfered . '" data-unit="Pcs" >' . $transfered . '</span> Pcs';
                })
                ->rawColumns(['quantity_sold', 'quantity_available', 'total', 'transfered'])
                ->make(true);
        }
        $business_id = $request->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.supplier_report')
            ->with(compact('business_locations'));
        // dd($query->orderBy('supplier_id', 'ASC')->get()[1]);
    }
    /**
     * Sub-Category Report
     * 
     * @return \Illuminate\Http\Response
     * */

    public function sub_category_report(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = $request->session()->get('user.business_id');

        // $query = Product::join('categories as cat', 'products.sub_category_id', '=', 'cat.id')
        //         ->join('purchase_lines as pl', 'products.id', '=', 'pl.product_id')
        //         ->join('variation_location_details as vld', 'vld.variation_id', '=', 'pl.variation_id');

        // $data = $query->groupBy('cat.name')->select(
        //     'cat.name as id',
        //     DB::raw("COUNT(products.id) as num_of_products"),
        //     DB::raw("SUM(pl.quantity_sold) as quantity_sold"),
        //     DB::raw("SUM(vld.qty_available) as quantity_available"),
        //     DB::raw("SUM(pl.quantity_sold)+SUM(vld.qty_available) as total"),
        //     DB::raw("COUNT(vld.id) as transfered"),
        // )->get();

        // dd($data);
        if ($request->ajax()) {

            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join(
                    'units',
                    'p.unit_id',
                    '=',
                    'units.id'
                )
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join(
                    'sizes',
                    'p.sub_size_id',
                    '=',
                    'sizes.id'
                )
                ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->join('categories', 'p.category_id', '=', 'categories.id')
                ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->join('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);
            $location_filter = null;

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);
            }
            if (!empty($request->input('category_id'))) {
                $category_id = $request->input('category_id');
                // dd($category_id);
                $query->where('p.category_id', $category_id);
            }
            $from_date = request()->get('from_date', null);

            $to_date = request()->get('to_date', null);
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('categories.updated_at', '>=', $from_date)->whereDate('categories.updated_at', '<=', $to_date);
            }

            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                // transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions
                JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                WHERE transactions.status='final' AND transactions.type='sell' $location_filter
                AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions
                JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter
                AND (TSL.variation_id=variations.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions
                JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter
                AND (SAL.variation_id=variations.id)) as total_adjusted"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.sub_category_id',
                'p.name as product',
                'p.image as image',
                'p.description as description',
                'p.type',
                'p.refference',
                'colors.name as color_name',
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
            )->groupBy('p.sub_category_id')
                ->orderBy('vld.product_updated_at', 'DESC');

            // dd($products->get());

            // $query = Product::join('categories as cat', 'products.sub_category_id', '=', 'cat.id')
            //     ->join('purchase_lines as pl', 'products.id', '=', 'pl.product_id')
            //     ->join('variation_location_details as vld', 'vld.variation_id', '=', 'pl.variation_id');

            // if (!empty($request->input('location_id'))) {
            //     $location_id = $request->input('location_id');

            //     $query->where('vld.location_id', $location_id);
            // }
            // if (!empty($request->input('category_id'))) {
            //     $category_id = $request->input('category_id');

            //     $query->where('products.category_id', $category_id);
            // }
            // $from_date = request()->get('from_date', null);

            // $to_date = request()->get('to_date', null);
            // if (!empty($to_date)) {
            //     // dd($products->first());
            //     $query->whereDate('cat.updated_at', '>=', $from_date)->whereDate('cat.updated_at', '<=', $to_date);
            // }

            // $data = $query->groupBy('cat.name')->select(
            //     'cat.name as cat_name',
            //     DB::raw("COUNT(products.id) as num_of_products"),
            //     DB::raw("SUM(pl.quantity_sold) as quantity_sold"),
            //     DB::raw("SUM(vld.qty_available) as quantity_available"),
            //     DB::raw("SUM(pl.quantity_sold)+SUM(vld.qty_available) as total"),
            //     DB::raw("COUNT(vld.id) as transfered"),
            // )
            //     ->orderBy('cat.updated_at')
            //     ->get();
            return DataTables::of($products)
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float) $row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="Pcs" >' . $total_sold . '</span> Pcs';
                })
                ->editColumn('stock', function ($row) {
                    $stock = 0;
                    if ($row->stock) {
                        $stock =  (float) $row->stock;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $stock . '" data-unit="Pcs" >' . $stock . '</span> Pcs';
                })
                ->editColumn('total', function ($row) {
                    $total = (int)($row->stock) + (int)($row->total_sold);
                    // if ($row->total) {
                    //     $total =  (float) $row->total;
                    // }

                    return '<span data-is_quantity="true" class="display_currency total" data-currency_symbol=false data-orig-value="' . $total . '" data-unit="Pcs" >' . $total . '</span> Pcs';
                })
                // ->editColumn('transfered', function ($row) {
                //     $transfered = 0;
                //     if ($row->transfered) {
                //         $transfered =  (float) $row->transfered;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $transfered . '" data-unit="Pcs" >' . $transfered . '</span> Pcs';
                // })
                ->rawColumns(['total_sold', 'stock', 'total'])
                ->make(true);
        }
        $business_id = $request->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $categories = Category::where('parent_id', '0')->pluck('name', 'id');
        // $categories = Category::forDropdown($business_id);

        return view('report.sub_category_report')
            ->with(compact('business_locations', 'categories'));
        // dd($query->orderBy('supplier_id', 'ASC')->get()[1]);
    }
    /**
     * Product Report1
     * 
     * @return \Illuminate\Http\Response
     * */

    public function product_first_report(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        // $query = Product::join('purchase_lines as pl', 'products.id', '=', 'pl.product_id')
        //         ->join('variation_location_details as vld', 'vld.variation_id', '=', 'pl.variation_id');

        // $data = $query->groupBy('products.name')->select(
        //     'products.name',
        //     DB::raw("COUNT(products.id) as num_of_products"),
        //     DB::raw("SUM(pl.quantity_sold) as quantity_sold"),
        //     DB::raw("SUM(vld.qty_available) as quantity_available"),
        //     DB::raw("SUM(pl.quantity_sold)+SUM(vld.qty_available) as total"),
        //     DB::raw("COUNT(vld.id) as transfered"),
        // )->get();

        // dd($data);
        if ($request->ajax()) {

            $location_id = $request->get('location_id', null);

            $vld_str = '';
            if (!empty($location_id)) {
                $vld_str = "AND vld.location_id=$location_id";
            }

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
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->select(
                    'p.id as product_id',
                    'p.name as name',
                    'p.image as image',
                    'p.sub_size_id',
                    'p.refference',
                    // DB::raw('(COUNT(p.refference)) as refference'),
                    // DB::raw('(COUNT(p.sub_size_id)) as sizes'),
                    't.id as transaction_id',
                    't.transaction_date as transaction_date',
                    'transaction_sell_lines.unit_price_before_discount as unit_price',
                    'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                    'p.product_updated_at as product_updated_at',
                    'transaction_sell_lines.original_amount as original_amount',
                    DB::raw("(SELECT COUNT(pro.refference) FROM products as pro WHERE pro.name=p.name) as pro_refference"),
                    DB::raw("(SELECT COUNT(prod.sub_size_id) FROM products as prod WHERE prod.refference=p.refference) as sizes"),
                    DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as current_stock"),
                    DB::raw("(SELECT COUNT(vldd.quantity) FROM location_transfer_details as vldd WHERE p.refference=vldd.product_refference AND location_id != 1) as transfered"),
                    DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
                    DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_id = p.id) as total_sold"),
                    'transaction_sell_lines.line_discount_type as discount_type',
                    'transaction_sell_lines.line_discount_amount as discount_amount',
                    'transaction_sell_lines.item_tax',
                    'u.short_name as unit',
                    DB::raw('((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
                )
                ->orderBy('p.name', 'ASC')
                // ->orderBy('t.invoice_no','DESC')
                ->groupBy('p.sub_size_id');

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);
            }
            if (!empty($request->input('category_id'))) {
                $category_id = $request->input('category_id');

                $query->where('products.category_id', $category_id);
            }
            $from_date = request()->get('from_date', null);

            $to_date = request()->get('to_date', null);
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('products.updated_at', '>=', $from_date)->whereDate('products.updated_at', '<=', $to_date);
            }


            return DataTables::of($query)
                ->editColumn('image', function ($row) {
                    return '<div style="display: flex;"><img src="' . $row->image_url . '" alt="Product image" class="product-thumbnail-small"></div>';
                })
                ->editColumn('quantity_sold', function ($row) {
                    $quantity_sold = 0;
                    if ($row->quantity_sold) {
                        $quantity_sold =  (float) $row->quantity_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $quantity_sold . '" data-unit="Pcs" >' . $quantity_sold . '</span> Pcs';
                })
                ->editColumn('pro_refference', function ($row) {
                    return $row->pro_refference;
                })
                ->editColumn('quantity_available', function ($row) {
                    $quantity_available = 0;
                    if ($row->quantity_available) {
                        $quantity_available =  (float) $row->quantity_available;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $quantity_available . '" data-unit="Pcs" >' . $quantity_available . '</span> Pcs';
                })
                ->editColumn('total_sold', function ($row) {
                    $total = 0;
                    if ($row->total) {
                        $total =  (float) $row->total;
                    }

                    return '<span data-is_quantity="true" class="display_currency total" data-currency_symbol=false data-orig-value="' . $total . '" data-unit="Pcs" >' . $total . '</span> Pcs';
                })
                ->editColumn('transfered', function ($row) {
                    $transfered = 0;
                    if ($row->transfered) {
                        $transfered =  (float) $row->transfered;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $transfered . '" data-unit="Pcs" >' . $transfered . '</span> Pcs';
                })
                ->rawColumns(['image', 'quantity_sold', 'quantity_available', 'total_sold', 'transfered', 'sizes'])
                ->make(true);
        }
        $business_id = $request->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $categories = Category::forDropdown($business_id);

        return view('report.product_report1')
            ->with(compact('business_locations', 'categories'));
        // dd($query->orderBy('supplier_id', 'ASC')->get()[1]);
    }
    public function old_product_first_report(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        // $query = Product::join('purchase_lines as pl', 'products.id', '=', 'pl.product_id')
        //         ->join('variation_location_details as vld', 'vld.variation_id', '=', 'pl.variation_id');

        // $data = $query->groupBy('products.name')->select(
        //     'products.name',
        //     DB::raw("COUNT(products.id) as num_of_products"),
        //     DB::raw("SUM(pl.quantity_sold) as quantity_sold"),
        //     DB::raw("SUM(vld.qty_available) as quantity_available"),
        //     DB::raw("SUM(pl.quantity_sold)+SUM(vld.qty_available) as total"),
        //     DB::raw("COUNT(vld.id) as transfered"),
        // )->get();

        // dd($data);
        if ($request->ajax()) {
            $query = Product::join('purchase_lines as pl', 'products.id', '=', 'pl.product_id')
                ->join('variation_location_details as vld', 'vld.variation_id', '=', 'pl.variation_id');
            $location_id = $request->get('location_id', null);

            $vld_str = '';
            if (!empty($location_id)) {
                $vld_str = "AND vld.location_id=$location_id";
            }
            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);
            }
            if (!empty($request->input('category_id'))) {
                $category_id = $request->input('category_id');

                $query->where('products.category_id', $category_id);
            }
            $from_date = request()->get('from_date', null);

            $to_date = request()->get('to_date', null);
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('products.updated_at', '>=', $from_date)->whereDate('products.updated_at', '<=', $to_date);
            }

            $data = $query->select(
                'products.name',
                'products.image as image',
                DB::raw("(COUNT(products.id)) as num_of_products"),
                DB::raw("COUNT(products.refference) as num_of_refference"),
                DB::raw("SUM(pl.quantity_sold) as quantity_sold"),
                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=pl.variation_id $vld_str) as current_stock"),
                // DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
                DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_id = products.id) as total_sold"),
                DB::raw("SUM(vld.qty_available) as quantity_available"),
                DB::raw("(COUNT(products.sub_size_id)) as num_of_sub_sizes"),
                DB::raw("SUM(pl.quantity_sold)+SUM(vld.qty_available) as total"),
                DB::raw("COUNT(vld.id) as transfered"),
            )
                ->orderBy('products.updated_at')
                ->groupBy('products.refference')
                ->get();
            return DataTables::of($data)
                ->editColumn('image', function ($row) {
                    return '<div style="display: flex;"><img src="' . $row->image_url . '" alt="Product image" class="product-thumbnail-small"></div>';
                })
                ->editColumn('quantity_sold', function ($row) {
                    $quantity_sold = 0;
                    if ($row->quantity_sold) {
                        $quantity_sold =  (float) $row->quantity_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $quantity_sold . '" data-unit="Pcs" >' . $quantity_sold . '</span> Pcs';
                })
                ->editColumn('quantity_available', function ($row) {
                    $quantity_available = 0;
                    if ($row->quantity_available) {
                        $quantity_available =  (float) $row->quantity_available;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $quantity_available . '" data-unit="Pcs" >' . $quantity_available . '</span> Pcs';
                })
                ->editColumn('total_sold', function ($row) {
                    $total = 0;
                    if ($row->total) {
                        $total =  (float) $row->total;
                    }

                    return '<span data-is_quantity="true" class="display_currency total" data-currency_symbol=false data-orig-value="' . $total . '" data-unit="Pcs" >' . $total . '</span> Pcs';
                })
                ->editColumn('transfered', function ($row) {
                    $transfered = 0;
                    if ($row->transfered) {
                        $transfered =  (float) $row->transfered;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $transfered . '" data-unit="Pcs" >' . $transfered . '</span> Pcs';
                })
                ->rawColumns(['image', 'quantity_sold', 'quantity_available', 'total_sold', 'transfered'])
                ->make(true);
        }
        $business_id = $request->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $categories = Category::forDropdown($business_id);

        return view('report.product_report1')
            ->with(compact('business_locations', 'categories'));
        // dd($query->orderBy('supplier_id', 'ASC')->get()[1]);
    }
    /**
     * Shows product stock report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->join('categories', 'p.category_id', '=', 'categories.id')
                ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }

            if (!empty($request->input('supplier_id'))) {
                $query->where('p.supplier_id', $request->input('supplier_id'));
            }

            $from_date = request()->get('start_date', null);

            $to_date = request()->get('end_date', null);
            // dd($to_date);
            // if($to_date == 'no'){
            //     $to_date = Carbon::now();
            // }
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('p.created_at', '>=', $from_date)->whereDate('p.created_at', '<=', $to_date);
                // $query->where('vld.qty_available', '>', 0);
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }

            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN


            $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
            // $query->join('product.color_id','=','color.id');
            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                        AND (TSL.variation_id=variations.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                        AND (SAL.variation_id=variations.id)) as total_adjusted"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.show_pos as show_pos',
                'p.name as product',
                'p.image as image',
                'p.description as description',
                'p.type',
                'p.refference',
                'p.updated_at as product_updated',
                'colors.name as color_name',
                'suppliers.name as supplier_name',
                'categories.name as category_name',
                'sub_cat.name as sub_category_name',
                'sizes.name as size_name',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                // 'variations.sell_price_inc_tax as unit_price',
                'vld.sell_price as unit_price',
                'pv.name as product_variation',
                'vld.product_updated_at as product_date',
                'vld.location_print_qty as printing_qty',
                'variations.name as variation_name',
                'vld.updated_at',
                // 'vld.qty_available as current_stock'

                DB::raw('SUM(vld.qty_available) as current_stock')
            )->groupBy('variations.id')
                // ->having('current_stock', '>', 0)
                ->having('current_stock', '!=', 0)
                ->orderBy('vld.product_updated_at', 'DESC');



            // ->orderBy('vld.product_updated_at', 'DESC');
            // dd($products->first());
            // dd($products->first()->product()->first()->image_url);

            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('mass_delete', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"> <input type="number" class="row-print-qty form-control disabled" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="stock_qty_' . $row->product_id . '">';
                    // return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"><input type="number" class="row-qty form-control" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="qty_' . $row->product_id . '">';
                })
                ->editColumn('show_pos', function ($row) {
                    $web_items = WebsiteProducts::where('product_id', $row->product_id)->first();
                    $data = '';
                    if ($row->show_pos) {
                        $data .= '<span class="btn btn-xs btn-info">TOP</span>';
                    } else {
                        $data .= '<span class="btn btn-xs btn-danger">Normal</span>';
                    }
                    if ($web_items) {
                        $data .= '<span class="btn btn-xs btn-success">On Website</span>';
                    }
                    return $data;
                })
                ->editColumn('printing_qty', function ($row) {
                    if ($row->printing_qty < 1) {
                        $qty = $row->current_stock;
                    } else {
                        $qty = $row->printing_qty;
                    }
                    return  'Print: <input type="number" class="row-print-qty form-control" value="' . number_format($qty) . '" max="' . number_format($qty) . '" style="width:70px;" id="printing_qty_' . $row->product_id . '">';
                })
                ->editColumn('price_input', function ($row) {
                    $price = $row->unit_price;
                    return  '<input type="hidden" step="any" class="row-print-qty form-control" value="' . $price . '" style="width:70px;" id="vld_old_sell_price_' . $row->product_id . '">
                             <input type="number" step="any" class="row-print-qty form-control" value="' . $price . '" style="width:70px;" id="vld_sell_price_' . $row->product_id . '">';
                })
                // ->addColumn('color_id', function ($row) {
                //     // return  $row->first()->product()->first()->color()->first()->name;
                //     $product = Product::find($row->product_id);
                //     return  $product->color()->first()->id;
                // })
                // ->addColumn('supplier_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->supplier()->first()->name;
                // })
                // ->addColumn('category_id', function ($row) {
                //     $product = Product::find($row->product_id);

                //     return  $product->category()->first()->name;
                // })
                // ->addColumn('sub_category_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->sub_category()->first()->name;
                // })
                // ->addColumn('sub_size_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->sub_size()->first()->name;
                // })
                ->editColumn('image', function ($row) {
                    $product = Product::find($row->product_id);
                    $url = url("/products/view/") . '/';
                    if (!empty($product->image) && !is_null($product->image)) {
                        return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="' . $url . $row->product()->first()->id . '"></div>';
                        // return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    } else {
                        return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="data-href="{{url("/products/view/".$row->product()->first()->id)}}"></div>';
                        // return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    }
                })
                ->addColumn('sale_percent', function ($row) {
                    $quantity_sold =  (float) $row->total_sold;
                    $quantity_available =  (float) $row->stock  + $quantity_sold;
                    if ($quantity_available < 1) {
                        $quantity_available = 1;
                    }
                    $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                    return $percent . '%';
                })
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float) $stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float) $stock . '</span>' . ' ' . $row->unit;
                    } else {
                        return 'N/A';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float) $row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('total_transfered', function ($row) {
                    $total_transfered = 0;
                    if ($row->total_transfered) {
                        $total_transfered =  (float) $row->total_transfered;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                })
                ->editColumn('location_name', function ($row) {
                    // return '<span max="' . $row->location_id . '" id="location_' . $row->product_id . '">' . $row->location_name . '</span> ';
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                ->editColumn('total_adjusted', function ($row) {
                    $total_adjusted = 0;
                    if ($row->total_adjusted) {
                        $total_adjusted =  (float) $row->total_adjusted;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                })
                ->editColumn('description', function ($row) {
                    $description = '-';
                    if ($row->description) {
                        $description = $row->description;
                    }
                    return $description;
                })
                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                            . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) . '">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                ->addColumn(
                    'actions',
                    function ($row) use ($selling_price_group_count) {
                        $html =
                            '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false"> <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li><a href="' . action('LabelsController@show') . '?product_id=' . $row->product()->first()->id . '" data-toggle="tooltip" title="Print Barcode/Label"><i class="fa fa-barcode"></i> ' . __('barcode.labels') . '</a></li>';

                        if (auth()->user()->can('product.view')) {
                            $html .=
                                '<li><a href="' . action('ProductController@view', [$row->product()->first()->id]) . '" class="view-product"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                        }

                        if (auth()->user()->can('product.update')) {
                            $html .=
                                '<li><a href="' . action('ProductController@edit', [$row->product()->first()->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        }

                        if (auth()->user()->can('product.delete')) {
                            $html .=
                                '<li><a href="' . action('ProductController@destroy', [$row->product()->first()->id]) . '" class="delete-product"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        }

                        if ($row->is_inactive == 1) {
                            $html .=
                                '<li><a href="' . action('ProductController@activate', [$row->product()->first()->id]) . '" class="activate-product"><i class="fa fa-circle-o"></i> ' . __("lang_v1.reactivate") . '</a></li>';
                        }

                        $html .= '<li class="divider"></li>';

                        if (auth()->user()->can('product.create')) {
                            if ($row->enable_stock == 1) {
                                $html .=
                                    '<li><a href="#" data-href="' . action('OpeningStockController@add', ['product_id' => $row->product()->first()->id]) . '" class="add-opening-stock"><i class="fa fa-database"></i> ' . __("lang_v1.add_edit_opening_stock") . '</a></li>';
                            }

                            if ($selling_price_group_count > 0) {
                                $html .=
                                    '<li><a href="' . action('ProductController@addSellingPrices', [$row->product()->first()->id]) . '"><i class="fa fa-money"></i> ' . __("lang_v1.add_selling_price_group_prices") . '</a></li>';
                            }

                            $html .=
                                '<li><a href="' . action('ProductController@create', ["d" => $row->product()->first()->id]) . '"><i class="fa fa-copy"></i> ' . __("lang_v1.duplicate_product") . '</a></li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                // ->setRowAttr([
                //     'data-href' => function ($row) {
                //         if (auth()->user()->can("product.view")) {
                //             return  action('ProductController@view', [$row->product()->first()->id]);
                //         } else {
                //             return '';
                //         }
                //     }
                // ])
                // ->removeColumn('id')
                ->rawColumns(['mass_delete', 'printing_qty', 'unit_price', 'total_transfered', 'show_pos', 'location_name', 'total_sold', 'total_adjusted', 'stock', 'actions', 'image', 'price_input'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $suppliers = Supplier::orderBy('name', 'ASC')->pluck('name', 'id');

        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.stock_report')
            ->with(compact('categories', 'suppliers', 'units', 'business_locations'));
    }
    public function getcolorDetailReport(Request $request)
    {
        // if (!auth()->user()->can('stock_report.view')) {
        //     abort(403, 'Unauthorized action.');
        // }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->join('categories', 'p.category_id', '=', 'categories.id')
                ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);


            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }


            $from_date = request()->get('start_date', null);

            $to_date = request()->get('end_date', null);
            // dd($to_date);
            // if($to_date == 'no'){
            //     $to_date = Carbon::now();
            // }
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('p.created_at', '>=', $from_date)->whereDate('p.created_at', '<=', $to_date);
                $query->where('vld.qty_available', '>', 0);
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }

            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN


            $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
            // $query->join('product.color_id','=','color.id');
            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                        AND (TSL.variation_id=variations.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                        AND (SAL.variation_id=variations.id)) as total_adjusted"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.show_pos as show_pos',
                'p.name as product',
                'p.image as image',
                'p.description as description',
                'p.type',
                'p.refference',
                'p.updated_at as product_updated',
                'colors.id as color_id',
                'colors.name as color_name',
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
                ->orderBy('color_id', 'DESC');
            // dd($products->toArray());
            //     )->groupBy('variations.id')
            // ->orderBy('vld.product_updated_at', 'DESC');
            // ->orderBy('vld.product_updated_at', 'DESC');
            // dd($products->first());
            // dd($products->first()->product()->first()->image_url);

            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('mass_delete', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"> <input type="number" class="row-print-qty form-control disabled" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="stock_qty_' . $row->product_id . '">';
                    // return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"><input type="number" class="row-qty form-control" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="qty_' . $row->product_id . '">';
                })
                ->editColumn('show_pos', function ($row) {
                    $web_items = WebsiteProducts::where('product_id', $row->product_id)->first();
                    $data = '';
                    if ($row->show_pos) {
                        $data .= '<span class="btn btn-xs btn-info">TOP</span>';
                    } else {
                        $data .= '<span class="btn btn-xs btn-danger">Normal</span>';
                    }
                    if ($web_items) {
                        $data .= '<span class="btn btn-xs btn-success">On Website</span>';
                    }
                    return $data;
                })
                ->editColumn('printing_qty', function ($row) {
                    if ($row->printing_qty < 1) {
                        $qty = $row->current_stock;
                    } else {
                        $qty = $row->printing_qty;
                    }
                    return  'Print: <input type="number" class="row-print-qty form-control" value="' . number_format($qty) . '" max="' . number_format($qty) . '" style="width:70px;" id="printing_qty_' . $row->product_id . '">';
                })
                // ->addColumn('color_id', function ($row) {
                //     // return  $row->first()->product()->first()->color()->first()->name;
                //     $product = Product::find($row->product_id);
                //     return  $product->color()->first()->id;
                // })
                // ->addColumn('supplier_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->supplier()->first()->name;
                // })
                // ->addColumn('category_id', function ($row) {
                //     $product = Product::find($row->product_id);

                //     return  $product->category()->first()->name;
                // })
                // ->addColumn('sub_category_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->sub_category()->first()->name;
                // })
                // ->addColumn('sub_size_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->sub_size()->first()->name;
                // })
                ->editColumn('image', function ($row) {
                    $product = Product::find($row->product_id);
                    $url = url("/products/view/") . '/';
                    if (!empty($product->image) && !is_null($product->image)) {
                        return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="' . $url . $row->product()->first()->id . '"></div>';
                        // return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    } else {
                        return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="data-href="{{url("/products/view/".$row->product()->first()->id)}}"></div>';
                        // return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    }
                })
                ->addColumn('sale_percent', function ($row) {
                    $quantity_sold =  (float) $row->total_sold;
                    $quantity_available =  (float) $row->stock  + $quantity_sold;
                    if ($quantity_available < 1) {
                        $quantity_available = 1;
                    }
                    $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                    return $percent . '%';
                })
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float) $stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float) $stock . '</span>' . ' ' . $row->unit;
                    } else {
                        return 'N/A';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float) $row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('total_transfered', function ($row) {
                    $total_transfered = 0;
                    if ($row->total_transfered) {
                        $total_transfered =  (float) $row->total_transfered;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                })
                ->editColumn('location_name', function ($row) {
                    // return '<span max="' . $row->location_id . '" id="location_' . $row->product_id . '">' . $row->location_name . '</span> ';
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                ->editColumn('total_adjusted', function ($row) {
                    $total_adjusted = 0;
                    if ($row->total_adjusted) {
                        $total_adjusted =  (float) $row->total_adjusted;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                })
                ->editColumn('description', function ($row) {
                    $description = '-';
                    if ($row->description) {
                        $description = $row->description;
                    }
                    return $description;
                })
                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                            . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) . '">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                ->addColumn(
                    'actions',
                    function ($row) use ($selling_price_group_count) {
                        $html =
                            '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false"> <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li><a href="' . action('LabelsController@show') . '?product_id=' . $row->product()->first()->id . '" data-toggle="tooltip" title="Print Barcode/Label"><i class="fa fa-barcode"></i> ' . __('barcode.labels') . '</a></li>';

                        if (auth()->user()->can('product.view')) {
                            $html .=
                                '<li><a href="' . action('ProductController@view', [$row->product()->first()->id]) . '" class="view-product"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                        }

                        if (auth()->user()->can('product.update')) {
                            $html .=
                                '<li><a href="' . action('ProductController@edit', [$row->product()->first()->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        }

                        if (auth()->user()->can('product.delete')) {
                            $html .=
                                '<li><a href="' . action('ProductController@destroy', [$row->product()->first()->id]) . '" class="delete-product"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        }

                        if ($row->is_inactive == 1) {
                            $html .=
                                '<li><a href="' . action('ProductController@activate', [$row->product()->first()->id]) . '" class="activate-product"><i class="fa fa-circle-o"></i> ' . __("lang_v1.reactivate") . '</a></li>';
                        }

                        $html .= '<li class="divider"></li>';

                        if (auth()->user()->can('product.create')) {
                            if ($row->enable_stock == 1) {
                                $html .=
                                    '<li><a href="#" data-href="' . action('OpeningStockController@add', ['product_id' => $row->product()->first()->id]) . '" class="add-opening-stock"><i class="fa fa-database"></i> ' . __("lang_v1.add_edit_opening_stock") . '</a></li>';
                            }

                            if ($selling_price_group_count > 0) {
                                $html .=
                                    '<li><a href="' . action('ProductController@addSellingPrices', [$row->product()->first()->id]) . '"><i class="fa fa-money"></i> ' . __("lang_v1.add_selling_price_group_prices") . '</a></li>';
                            }

                            $html .=
                                '<li><a href="' . action('ProductController@create', ["d" => $row->product()->first()->id]) . '"><i class="fa fa-copy"></i> ' . __("lang_v1.duplicate_product") . '</a></li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                // ->setRowAttr([
                //     'data-href' => function ($row) {
                //         if (auth()->user()->can("product.view")) {
                //             return  action('ProductController@view', [$row->product()->first()->id]);
                //         } else {
                //             return '';
                //         }
                //     }
                // ])
                // ->removeColumn('id')
                ->rawColumns(['mass_delete', 'printing_qty', 'unit_price', 'total_transfered', 'show_pos', 'location_name', 'total_sold', 'total_adjusted', 'stock', 'actions', 'image'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $suppliers = Supplier::orderBy('name', 'ASC')->pluck('name', 'id');

        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.color_detail_report')
            ->with(compact('categories', 'suppliers', 'units', 'business_locations'));
    }
    public function getProductInTopStockReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->join('categories', 'p.category_id', '=', 'categories.id')
                ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }

            if (!empty($request->input('supplier_id'))) {
                $query->where('p.supplier_id', $request->input('supplier_id'));
            }

            $from_date = request()->get('start_date', null);

            $to_date = request()->get('end_date', null);
            // dd($to_date);
            // if($to_date == 'no'){
            //     $to_date = Carbon::now();
            // }
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('p.created_at', '>=', $from_date)->whereDate('p.created_at', '<=', $to_date);
                $query->where('vld.qty_available', '>', 0);
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }
            $query->where('show_pos', '!=', 0);
            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN


            $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
            // $query->join('product.color_id','=','color.id');
            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                        AND (TSL.variation_id=variations.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                        AND (SAL.variation_id=variations.id)) as total_adjusted"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.show_pos as show_pos',
                'p.name as product',
                'p.image as image',
                'p.description as description',
                'p.type',
                'p.refference',
                'p.updated_at as product_updated',
                'colors.name as color_name',
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
            // ->orderBy('vld.product_updated_at', 'DESC');
            // dd($products->first());
            // dd($products->first()->product()->first()->image_url);

            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('mass_delete', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"> <input type="number" class="row-print-qty form-control disabled" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="stock_qty_' . $row->product_id . '">';
                    // return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"><input type="number" class="row-qty form-control" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="qty_' . $row->product_id . '">';
                })
                ->editColumn('show_pos', function ($row) {
                    $data = '';
                    if ($row->show_pos) {
                        $data .= '<span class="btn btn-xs btn-info">TOP</span>';
                    }
                    return $data;
                })
                ->editColumn('printing_qty', function ($row) {
                    if ($row->printing_qty < 1) {
                        $qty = $row->current_stock;
                    } else {
                        $qty = $row->printing_qty;
                    }
                    return  'Print: <input type="number" class="row-print-qty form-control" value="' . number_format($qty) . '" max="' . number_format($qty) . '" style="width:70px;" id="printing_qty_' . $row->product_id . '">';
                })
                // ->addColumn('color_id', function ($row) {
                //     // return  $row->first()->product()->first()->color()->first()->name;
                //     $product = Product::find($row->product_id);
                //     return  $product->color()->first()->id;
                // })
                // ->addColumn('supplier_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->supplier()->first()->name;
                // })
                // ->addColumn('category_id', function ($row) {
                //     $product = Product::find($row->product_id);

                //     return  $product->category()->first()->name;
                // })
                // ->addColumn('sub_category_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->sub_category()->first()->name;
                // })
                // ->addColumn('sub_size_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->sub_size()->first()->name;
                // })
                ->editColumn('image', function ($row) {
                    $product = Product::find($row->product_id);
                    $url = url("/products/view/") . '/';
                    if (!empty($product->image) && !is_null($product->image)) {
                        return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="' . $url . $row->product()->first()->id . '"></div>';
                        // return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    } else {
                        return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="data-href="{{url("/products/view/".$row->product()->first()->id)}}"></div>';
                        // return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    }
                })
                ->addColumn('sale_percent', function ($row) {
                    $quantity_sold =  (float) $row->total_sold;
                    $quantity_available =  (float) $row->stock  + $quantity_sold;
                    if ($quantity_available < 1) {
                        $quantity_available = 1;
                    }
                    $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                    return $percent . '%';
                })
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float) $stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float) $stock . '</span>' . ' ' . $row->unit;
                    } else {
                        return 'N/A';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float) $row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('total_transfered', function ($row) {
                    $total_transfered = 0;
                    if ($row->total_transfered) {
                        $total_transfered =  (float) $row->total_transfered;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                })
                ->editColumn('location_name', function ($row) {
                    // return '<span max="' . $row->location_id . '" id="location_' . $row->product_id . '">' . $row->location_name . '</span> ';
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                ->editColumn('total_adjusted', function ($row) {
                    $total_adjusted = 0;
                    if ($row->total_adjusted) {
                        $total_adjusted =  (float) $row->total_adjusted;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                })
                ->editColumn('description', function ($row) {
                    $description = '-';
                    if ($row->description) {
                        $description = $row->description;
                    }
                    return $description;
                })
                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                            . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) . '">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                ->addColumn(
                    'actions',
                    function ($row) use ($selling_price_group_count) {
                        $html =
                            '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false"> <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li><a href="' . action('LabelsController@show') . '?product_id=' . $row->product()->first()->id . '" data-toggle="tooltip" title="Print Barcode/Label"><i class="fa fa-barcode"></i> ' . __('barcode.labels') . '</a></li>';

                        if (auth()->user()->can('product.view')) {
                            $html .=
                                '<li><a href="' . action('ProductController@view', [$row->product()->first()->id]) . '" class="view-product"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                        }

                        if (auth()->user()->can('product.update')) {
                            $html .=
                                '<li><a href="' . action('ProductController@edit', [$row->product()->first()->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        }

                        if (auth()->user()->can('product.delete')) {
                            $html .=
                                '<li><a href="' . action('ProductController@destroy', [$row->product()->first()->id]) . '" class="delete-product"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        }

                        if ($row->is_inactive == 1) {
                            $html .=
                                '<li><a href="' . action('ProductController@activate', [$row->product()->first()->id]) . '" class="activate-product"><i class="fa fa-circle-o"></i> ' . __("lang_v1.reactivate") . '</a></li>';
                        }

                        $html .= '<li class="divider"></li>';

                        if (auth()->user()->can('product.create')) {
                            if ($row->enable_stock == 1) {
                                $html .=
                                    '<li><a href="#" data-href="' . action('OpeningStockController@add', ['product_id' => $row->product()->first()->id]) . '" class="add-opening-stock"><i class="fa fa-database"></i> ' . __("lang_v1.add_edit_opening_stock") . '</a></li>';
                            }

                            if ($selling_price_group_count > 0) {
                                $html .=
                                    '<li><a href="' . action('ProductController@addSellingPrices', [$row->product()->first()->id]) . '"><i class="fa fa-money"></i> ' . __("lang_v1.add_selling_price_group_prices") . '</a></li>';
                            }

                            $html .=
                                '<li><a href="' . action('ProductController@create', ["d" => $row->product()->first()->id]) . '"><i class="fa fa-copy"></i> ' . __("lang_v1.duplicate_product") . '</a></li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                // ->setRowAttr([
                //     'data-href' => function ($row) {
                //         if (auth()->user()->can("product.view")) {
                //             return  action('ProductController@view', [$row->product()->first()->id]);
                //         } else {
                //             return '';
                //         }
                //     }
                // ])
                // ->removeColumn('id')
                ->rawColumns(['mass_delete', 'printing_qty', 'unit_price', 'total_transfered', 'show_pos', 'location_name', 'total_sold', 'total_adjusted', 'stock', 'actions', 'image'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $suppliers = Supplier::orderBy('name', 'ASC')->pluck('name', 'id');

        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        // return view('report.stock_report')
        //     ->with(compact('categories', 'suppliers', 'units', 'business_locations'));
    }
    public function getProductInPosStockReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('website_products', 'p.id', '=', 'website_products.product_id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->join('categories', 'p.category_id', '=', 'categories.id')
                ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }

            if (!empty($request->input('supplier_id'))) {
                $query->where('p.supplier_id', $request->input('supplier_id'));
            }

            $from_date = request()->get('start_date', null);

            $to_date = request()->get('end_date', null);
            // dd($to_date);
            // if($to_date == 'no'){
            //     $to_date = Carbon::now();
            // }
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('p.created_at', '>=', $from_date)->whereDate('p.created_at', '<=', $to_date);
                $query->where('vld.qty_available', '>', 0);
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }
            $query->where('show_pos', 0);
            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN


            $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
            // $query->join('product.color_id','=','color.id');
            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                        AND (TSL.variation_id=variations.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                        AND (SAL.variation_id=variations.id)) as total_adjusted"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.show_pos as show_pos',
                'p.name as product',
                'p.image as image',
                'p.description as description',
                'p.type',
                'p.refference',
                'p.updated_at as product_updated',
                'colors.name as color_name',
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
                // ->allowedSorts('colors.name')
                ->orderBy('vld.product_updated_at', 'DESC');
            // ->orderBy('vld.product_updated_at', 'DESC');
            // dd($products->first());
            // dd($products->first()->product()->first()->image_url);

            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('mass_delete', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"> <input type="number" class="row-print-qty form-control disabled" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="stock_qty_' . $row->product_id . '">';
                    // return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"><input type="number" class="row-qty form-control" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="qty_' . $row->product_id . '">';
                })
                ->editColumn('show_pos', function ($row) {
                    // $data = '';
                    // if(!$row->show_pos){
                    //     $data .= '<span class="btn btn-xs btn-danger">Normal</span>';
                    // }
                    // return $data;
                    $web_items = WebsiteProducts::where('product_id', $row->product_id)->first();
                    $data = '';
                    // if($row->show_pos){
                    //     $data .= '<span class="btn btn-xs btn-info">TOP</span>';
                    // }else{
                    //     $data .= '<span class="btn btn-xs btn-danger">Normal</span>';
                    // }
                    if ($web_items) {
                        $data .= '<span class="btn btn-xs btn-success">On Website</span>';
                    }
                    return $data;
                })
                ->editColumn('printing_qty', function ($row) {
                    if ($row->printing_qty < 1) {
                        $qty = $row->current_stock;
                    } else {
                        $qty = $row->printing_qty;
                    }
                    return  'Print: <input type="number" class="row-print-qty form-control" value="' . number_format($qty) . '" max="' . number_format($qty) . '" style="width:70px;" id="printing_qty_' . $row->product_id . '">';
                })
                // ->addColumn('color_id', function ($row) {
                //     // return  $row->first()->product()->first()->color()->first()->name;
                //     $product = Product::find($row->product_id);
                //     return  $product->color()->first()->id;
                // })
                // ->addColumn('supplier_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->supplier()->first()->name;
                // })
                // ->addColumn('category_id', function ($row) {
                //     $product = Product::find($row->product_id);

                //     return  $product->category()->first()->name;
                // })
                // ->addColumn('sub_category_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->sub_category()->first()->name;
                // })
                // ->addColumn('sub_size_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->sub_size()->first()->name;
                // })
                ->editColumn('image', function ($row) {
                    $product = Product::find($row->product_id);
                    $url = url("/products/view/") . '/';
                    if (!empty($product->image) && !is_null($product->image)) {
                        return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="' . $url . $row->product()->first()->id . '"></div>';
                        // return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    } else {
                        return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="data-href="{{url("/products/view/".$row->product()->first()->id)}}"></div>';
                        // return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    }
                })
                ->addColumn('sale_percent', function ($row) {
                    $quantity_sold =  (float) $row->total_sold;
                    $quantity_available =  (float) $row->stock  + $quantity_sold;
                    if ($quantity_available < 1) {
                        $quantity_available = 1;
                    }
                    $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                    return $percent . '%';
                })
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float) $stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float) $stock . '</span>' . ' ' . $row->unit;
                    } else {
                        return 'N/A';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float) $row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('total_transfered', function ($row) {
                    $total_transfered = 0;
                    if ($row->total_transfered) {
                        $total_transfered =  (float) $row->total_transfered;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                })
                ->editColumn('location_name', function ($row) {
                    // return '<span max="' . $row->location_id . '" id="location_' . $row->product_id . '">' . $row->location_name . '</span> ';
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                ->editColumn('total_adjusted', function ($row) {
                    $total_adjusted = 0;
                    if ($row->total_adjusted) {
                        $total_adjusted =  (float) $row->total_adjusted;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                })
                ->editColumn('description', function ($row) {
                    $description = '-';
                    if ($row->description) {
                        $description = $row->description;
                    }
                    return $description;
                })
                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                            . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) . '">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                ->addColumn(
                    'actions',
                    function ($row) use ($selling_price_group_count) {
                        $html =
                            '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false"> <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li><a href="' . action('LabelsController@show') . '?product_id=' . $row->product()->first()->id . '" data-toggle="tooltip" title="Print Barcode/Label"><i class="fa fa-barcode"></i> ' . __('barcode.labels') . '</a></li>';

                        if (auth()->user()->can('product.view')) {
                            $html .=
                                '<li><a href="' . action('ProductController@view', [$row->product()->first()->id]) . '" class="view-product"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                        }

                        if (auth()->user()->can('product.update')) {
                            $html .=
                                '<li><a href="' . action('ProductController@edit', [$row->product()->first()->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        }

                        if (auth()->user()->can('product.delete')) {
                            $html .=
                                '<li><a href="' . action('ProductController@destroy', [$row->product()->first()->id]) . '" class="delete-product"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        }

                        if ($row->is_inactive == 1) {
                            $html .=
                                '<li><a href="' . action('ProductController@activate', [$row->product()->first()->id]) . '" class="activate-product"><i class="fa fa-circle-o"></i> ' . __("lang_v1.reactivate") . '</a></li>';
                        }

                        $html .= '<li class="divider"></li>';

                        if (auth()->user()->can('product.create')) {
                            if ($row->enable_stock == 1) {
                                $html .=
                                    '<li><a href="#" data-href="' . action('OpeningStockController@add', ['product_id' => $row->product()->first()->id]) . '" class="add-opening-stock"><i class="fa fa-database"></i> ' . __("lang_v1.add_edit_opening_stock") . '</a></li>';
                            }

                            if ($selling_price_group_count > 0) {
                                $html .=
                                    '<li><a href="' . action('ProductController@addSellingPrices', [$row->product()->first()->id]) . '"><i class="fa fa-money"></i> ' . __("lang_v1.add_selling_price_group_prices") . '</a></li>';
                            }

                            $html .=
                                '<li><a href="' . action('ProductController@create', ["d" => $row->product()->first()->id]) . '"><i class="fa fa-copy"></i> ' . __("lang_v1.duplicate_product") . '</a></li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                // ->setRowAttr([
                //     'data-href' => function ($row) {
                //         if (auth()->user()->can("product.view")) {
                //             return  action('ProductController@view', [$row->product()->first()->id]);
                //         } else {
                //             return '';
                //         }
                //     }
                // ])
                // ->removeColumn('id')
                ->rawColumns(['mass_delete', 'printing_qty', 'unit_price', 'total_transfered', 'show_pos', 'location_name', 'total_sold', 'total_adjusted', 'stock', 'actions', 'image'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $suppliers = Supplier::orderBy('name', 'ASC')->pluck('name', 'id');

        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        // return view('report.stock_report')
        //     ->with(compact('categories', 'suppliers', 'units', 'business_locations'));
    }
    /**
     * Shows product stock report
     *
     * @return \Illuminate\Http\Response
     */
    public function getGroupedStockReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->join('categories', 'p.category_id', '=', 'categories.id')
                ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }

            if (!empty($request->input('supplier_id'))) {
                $query->where('p.supplier_id', $request->input('supplier_id'));
            }

            $from_date = request()->get('start_date', null);

            $to_date = request()->get('end_date', null);
            // dd($to_date);
            // if($to_date == 'no'){
            //     $to_date = Carbon::now();
            // }
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('p.created_at', '>=', $from_date)->whereDate('p.created_at', '<=', $to_date);
                $query->where('vld.qty_available', '>', 0);
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }

            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN


            $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
            // $query->join('product.color_id','=','color.id');
            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                        AND (TSL.variation_id=variations.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                        AND (SAL.variation_id=variations.id)) as total_adjusted"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.name as product',
                'p.image as image',
                'p.description as description',
                'p.type',
                'p.refference',
                'colors.name as color_name',
                'suppliers.name as supplier_name',
                'categories.name as category_name',
                'sub_cat.name as sub_category_name',
                'sizes.name as size_name',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                // 'variations.sell_price_inc_tax as unit_price',
                'vld.sell_price as unit_price',
                'pv.name as product_variation',
                'vld.product_updated_at as product_date',
                'vld.location_print_qty as printing_qty',
                'variations.name as variation_name',
                'vld.updated_at',
                // 'vld.qty_available as current_stock'
                DB::raw('SUM(vld.qty_available) as current_stock')
            )
                ->groupBy('p.name')
                ->orderBy('vld.product_updated_at', 'DESC');
            // dd($products->first());
            // dd($products->first()->product()->first()->image_url);

            return DataTables::of($products)
                ->addIndexColumn()
                ->editColumn('image', function ($row) {
                    $product = Product::find($row->product_id);
                    $url = url("/products/view/") . '/';
                    if (!empty($product->image) && !is_null($product->image)) {
                        return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="' . $url . $row->product()->first()->id . '"></div>';
                        // return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    } else {
                        return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="data-href="{{url("/products/view/".$row->product()->first()->id)}}"></div>';
                        // return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    }
                })
                ->addColumn('detail', function ($row) {
                    $location_id = 0;
                    if (!empty(request()->input('location_id'))) {
                        $location_id = request()->input('location_id');
                    }

                    $start_date = request()->get('start_date', 'null');
                    $end_date = request()->get('end_date', 'null');
                    if (isset($start_date) && isset($end_date)) {
                        return '<a  href="' . url("/product/color-detail/" . $row->product . '/' . $start_date . '/' . $end_date) . '" target="_blank" class="btn btn-primary btn-sm">Color Report <i class="fa fa-eye"></i></a>';
                    } else {
                        return '<a  href="#"  class="btn btn-primary btn-sm">Color Report <i class="fa fa-eye"></i></a>';
                    }
                    // return '<a id="color-detail-modal" href="' . url("/product/color-detail/" . $row->product . '/' . $start_date . '/' . $end_date) . '" data-product-name="' . $row->product . '" class="btn btn-primary btn-sm">Color Report <i class="fa fa-eye"></i></a>';
                })
                // ->addColumn('sale_percent', function ($row) {
                //     $quantity_sold =  (float) $row->total_sold;
                //     $quantity_available =  (float) $row->stock  + $quantity_sold;
                //     if ($quantity_available < 1) {
                //         $quantity_available = 1;
                //     }
                //     $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                //     return $percent . '%';
                // })
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float) $stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float) $stock . '</span>' . ' ' . $row->unit;
                    } else {
                        return 'N/A';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                // ->editColumn('total_sold', function ($row) {
                //     $total_sold = 0;
                //     if ($row->total_sold) {
                //         $total_sold =  (float) $row->total_sold;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                // })
                // ->editColumn('total_transfered', function ($row) {
                //     $total_transfered = 0;
                //     if ($row->total_transfered) {
                //         $total_transfered =  (float) $row->total_transfered;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                // })
                ->editColumn('location_name', function ($row) {
                    // return '<span max="' . $row->location_id . '" id="location_' . $row->product_id . '">' . $row->location_name . '</span> ';
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                // ->editColumn('total_adjusted', function ($row) {
                //     $total_adjusted = 0;
                //     if ($row->total_adjusted) {
                //         $total_adjusted =  (float) $row->total_adjusted;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                // })
                ->editColumn('description', function ($row) {
                    $description = '-';
                    if ($row->description) {
                        $description = $row->description;
                    }
                    return $description;
                })
                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                            . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) . '">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("product.view")) {
                            return  action('ProductController@viewProductDetailWithSale', [$row->product()->first()->id]);
                            // return  action('ProductController@view', [$row->product()->first()->id]);
                        } else {
                            return '';
                        }
                    }
                ])
                ->removeColumn('id')
                ->rawColumns(['mass_delete', 'printing_qty', 'unit_price', 'total_transfered', 'location_name', 'total_sold', 'total_adjusted', 'stock', 'actions', 'image', 'detail'])
                ->make(true);
        }

        // $categories = Category::where('business_id', $business_id)
        //     ->where('parent_id', 0)
        //     ->pluck('name', 'id');
        // $suppliers = Supplier::orderBy('name', 'ASC')->pluck('name', 'id');

        // $units = Unit::where('business_id', $business_id)
        //     ->pluck('short_name', 'id');
        // $business_locations = BusinessLocation::forDropdown($business_id, true);

        // return view('report.stock_report')
        //     ->with(compact('categories', 'suppliers', 'units', 'business_locations'));
    }

    /**
     * Show Product Color Report 
     * 
     **/
    public function getColorStockReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->join('categories', 'p.category_id', '=', 'categories.id')
                ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }

            if (!empty($request->input('supplier_id'))) {
                $query->where('p.supplier_id', $request->input('supplier_id'));
            }

            $from_date = request()->get('start_date', null);

            $to_date = request()->get('end_date', null);
            // dd($to_date);
            // if($to_date == 'no'){
            //     $to_date = Carbon::now();
            // }
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('p.created_at', '>=', $from_date)->whereDate('p.created_at', '<=', $to_date);
                $query->where('vld.qty_available', '>', 0);
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }

            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN


            $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
            // $query->join('product.color_id','=','color.id');
            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                        AND (TSL.variation_id=variations.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                        AND (SAL.variation_id=variations.id)) as total_adjusted"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.name as product',
                'p.image as image',
                'p.description as description',
                'p.type',
                'p.refference',
                'colors.id as color_id',
                'colors.name as color_name',
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
                ->distinct('p.id')
                // ->groupBy('color_id')
                ->orderBy('vld.product_updated_at', 'DESC');
            // dd($products->first());
            // dd($products->first()->product()->first()->image_url);

            return DataTables::of($products)
                ->addIndexColumn()
                ->editColumn('image', function ($row) {
                    $product = Product::find($row->product_id);
                    $url = url("/products/view/") . '/';
                    if (!empty($product->image) && !is_null($product->image)) {
                        return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="' . $url . $row->product()->first()->id . '"></div>';
                        // return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    } else {
                        return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="data-href="{{url("/products/view/".$row->product()->first()->id)}}"></div>';
                        // return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    }
                })
                ->addColumn('detail', function ($row) {
                    return $row->color_name;
                })
                // ->addColumn('sale_percent', function ($row) {
                //     $quantity_sold =  (float) $row->total_sold;
                //     $quantity_available =  (float) $row->stock  + $quantity_sold;
                //     if ($quantity_available < 1) {
                //         $quantity_available = 1;
                //     }
                //     $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                //     return $percent . '%';
                // })
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float) $stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float) $stock . '</span>' . ' ' . $row->unit;
                    } else {
                        return 'N/A';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                // ->editColumn('total_sold', function ($row) {
                //     $total_sold = 0;
                //     if ($row->total_sold) {
                //         $total_sold =  (float) $row->total_sold;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                // })
                // ->editColumn('total_transfered', function ($row) {
                //     $total_transfered = 0;
                //     if ($row->total_transfered) {
                //         $total_transfered =  (float) $row->total_transfered;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                // })
                ->editColumn('location_name', function ($row) {
                    // return '<span max="' . $row->location_id . '" id="location_' . $row->product_id . '">' . $row->location_name . '</span> ';
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                // ->editColumn('total_adjusted', function ($row) {
                //     $total_adjusted = 0;
                //     if ($row->total_adjusted) {
                //         $total_adjusted =  (float) $row->total_adjusted;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                // })
                ->editColumn('description', function ($row) {
                    $description = '-';
                    if ($row->description) {
                        $description = $row->description;
                    }
                    return $description;
                })
                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                            . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) . '">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("product.view")) {
                            return  action('ProductController@view', [$row->product()->first()->id]);
                        } else {
                            return '';
                        }
                    }
                ])
                ->removeColumn('id')
                ->rawColumns(['mass_delete', 'printing_qty', 'unit_price', 'total_transfered', 'location_name', 'total_sold', 'total_adjusted', 'stock', 'actions', 'image', 'detail'])
                ->make(true);
        }

        // $categories = Category::where('business_id', $business_id)
        //     ->where('parent_id', 0)
        //     ->pluck('name', 'id');
        // $suppliers = Supplier::orderBy('name', 'ASC')->pluck('name', 'id');

        // $units = Unit::where('business_id', $business_id)
        //     ->pluck('short_name', 'id');
        // $business_locations = BusinessLocation::forDropdown($business_id, true);

        // return view('report.stock_report')
        //     ->with(compact('categories', 'suppliers', 'units', 'business_locations'));
    }
    public function getGroupedColorDetailReport(Request $request)
    {
        // if (!auth()->user()->can('stock_report.view')) {
        //     abort(403, 'Unauthorized action.');
        // }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->join('categories', 'p.category_id', '=', 'categories.id')
                ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }

            if (!empty($request->input('supplier_id'))) {
                $query->where('p.supplier_id', $request->input('supplier_id'));
            }

            $from_date = request()->get('start_date', null);

            $to_date = request()->get('end_date', null);
            // dd($to_date);
            // if($to_date == 'no'){
            //     $to_date = Carbon::now();
            // }
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('p.created_at', '>=', $from_date)->whereDate('p.created_at', '<=', $to_date);
                $query->where('vld.qty_available', '>', 0);
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }

            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN


            $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
            // $query->join('product.color_id','=','color.id');
            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                        AND (TSL.variation_id=variations.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                        AND (SAL.variation_id=variations.id)) as total_adjusted"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.name as product',
                'p.image as image',
                'p.description as description',
                'p.type',
                'p.refference',
                'colors.id as color_id',
                'colors.name as color_name',
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
                ->groupBy('color_id')
                ->orderBy('vld.product_updated_at', 'DESC');
            // dd($products->first());
            // dd($products->first()->product()->first()->image_url);

            return DataTables::of($products)
                ->addIndexColumn()
                ->editColumn('image', function ($row) {
                    $product = Product::find($row->product_id);
                    $url = url("/products/view/") . '/';
                    if (!empty($product->image) && !is_null($product->image)) {
                        return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="' . $url . $row->product()->first()->id . '"></div>';
                        // return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    } else {
                        return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="#"></div>';
                        // return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    }
                })
                ->addColumn('detail', function ($row) {
                    // dd($row);
                    $location_id = 0;
                    if (!empty(request()->input('location_id'))) {
                        $location_id = request()->input('location_id');
                    }

                    $start_date = request()->get('start_date', 'null');
                    $end_date = request()->get('end_date', 'null');
                    return '<a id="color-detail-modal" href="' . url("/product/color-detail/" . $row->product . '/' . $row->refference) . '" data-product-name="' . $row->product . '" class="btn btn-primary btn-sm">Color Report <i class="fa fa-eye"></i></a>';
                })
                // ->addColumn('sale_percent', function ($row) {
                //     $quantity_sold =  (float) $row->total_sold;
                //     $quantity_available =  (float) $row->stock  + $quantity_sold;
                //     if ($quantity_available < 1) {
                //         $quantity_available = 1;
                //     }
                //     $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                //     return $percent . '%';
                // })
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float) $stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float) $stock . '</span>' . ' ' . $row->unit;
                    } else {
                        return 'N/A';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                // ->editColumn('total_sold', function ($row) {
                //     $total_sold = 0;
                //     if ($row->total_sold) {
                //         $total_sold =  (float) $row->total_sold;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                // })
                // ->editColumn('total_transfered', function ($row) {
                //     $total_transfered = 0;
                //     if ($row->total_transfered) {
                //         $total_transfered =  (float) $row->total_transfered;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                // })
                ->editColumn('location_name', function ($row) {
                    // return '<span max="' . $row->location_id . '" id="location_' . $row->product_id . '">' . $row->location_name . '</span> ';
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                // ->editColumn('total_adjusted', function ($row) {
                //     $total_adjusted = 0;
                //     if ($row->total_adjusted) {
                //         $total_adjusted =  (float) $row->total_adjusted;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                // })
                ->editColumn('description', function ($row) {
                    $description = '-';
                    if ($row->description) {
                        $description = $row->description;
                    }
                    return $description;
                })
                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                            . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) . '">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("product.view")) {
                            return  action('ProductController@viewProductDetailWithSale', [$row->product()->first()->id]);
                            // return  action('ProductController@view', [$row->product()->first()->id]);
                        } else {
                            return '';
                        }
                    }
                ])
                ->removeColumn('id')
                ->rawColumns(['mass_delete', 'printing_qty', 'unit_price', 'total_transfered', 'location_name', 'total_sold', 'total_adjusted', 'stock', 'actions', 'image', 'detail'])
                ->make(true);
        }

        // $categories = Category::where('business_id', $business_id)
        //     ->where('parent_id', 0)
        //     ->pluck('name', 'id');
        // $suppliers = Supplier::orderBy('name', 'ASC')->pluck('name', 'id');

        // $units = Unit::where('business_id', $business_id)
        //     ->pluck('short_name', 'id');
        // $business_locations = BusinessLocation::forDropdown($business_id, true);

        // return view('report.stock_report')
        //     ->with(compact('categories', 'suppliers', 'units', 'business_locations'));
    }
    /**
     * Shows product stock report
     *
     * @return \Illuminate\Http\Response
     */
    public function getOldStockReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->join('categories', 'p.category_id', '=', 'categories.id')
                ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }

            if (!empty($request->input('supplier_id'))) {
                $query->where('p.supplier_id', $request->input('supplier_id'));
            }

            $from_date = request()->get('from_date', null);

            $to_date = request()->get('to_date', null);
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('p.created_at', '>=', $from_date)->whereDate('p.created_at', '<=', $to_date);
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }

            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN


            $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
            // $query->join('product.color_id','=','color.id');
            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                        AND (TSL.variation_id=variations.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                        AND (SAL.variation_id=variations.id)) as total_adjusted"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.name as product',
                'p.image as image',
                'p.description as description',
                'p.type',
                'p.refference',
                'colors.name as color_name',
                'suppliers.name as supplier_name',
                'categories.name as category_name',
                'sub_cat.name as sub_category_name',
                'sizes.name as size_name',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                'variations.sell_price_inc_tax as unit_price',
                'pv.name as product_variation',
                'vld.product_updated_at as product_date',
                'variations.name as variation_name',
                'vld.updated_at',
                'vld.qty_available as current_stock'
                // DB::raw('SUM(vld.qty_available) as current_stock')
            )->groupBy('variations.id')
                ->orderBy('vld.product_updated_at', 'DESC');
            // dd($products->first());
            // dd($products->first()->product()->first()->image_url);
            return DataTables::of($products)
                ->addColumn('mass_delete', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"> <input type="number" class="row-print-qty form-control disabled" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="printing_qty_' . $row->product_id . '">';
                    // return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"><input type="number" class="row-qty form-control" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="qty_' . $row->product_id . '">';
                })
                // ->addColumn('color_id', function ($row) {
                //     // return  $row->first()->product()->first()->color()->first()->name;
                //     $product = Product::find($row->product_id);
                //     return  $product->color()->first()->id;
                // })
                // ->addColumn('supplier_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->supplier()->first()->name;
                // })
                // ->addColumn('category_id', function ($row) {
                //     $product = Product::find($row->product_id);

                //     return  $product->category()->first()->name;
                // })
                // ->addColumn('sub_category_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->sub_category()->first()->name;
                // })
                // ->addColumn('sub_size_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->sub_size()->first()->name;
                // })
                ->editColumn('image', function ($row) {
                    $product = Product::find($row->product_id);
                    if (!empty($product->image) && !is_null($product->image)) {
                        return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    } else {
                        return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    }
                })
                ->addColumn('sale_percent', function ($row) {
                    $quantity_sold =  (float) $row->total_sold;
                    $quantity_available =  (float) $row->stock  + $quantity_sold;
                    if ($quantity_available < 1) {
                        $quantity_available = 1;
                    }
                    $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                    return $percent . '%';
                })
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float) $stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float) $stock . '</span>' . ' ' . $row->unit;
                    } else {
                        return 'N/A';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float) $row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('total_transfered', function ($row) {
                    $total_transfered = 0;
                    if ($row->total_transfered) {
                        $total_transfered =  (float) $row->total_transfered;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                })
                ->editColumn('location_name', function ($row) {
                    return '<span max="' . $row->location_id . '" id="location_' . $row->product_id . '">' . $row->location_name . '</span> ';
                })
                ->editColumn('total_adjusted', function ($row) {
                    $total_adjusted = 0;
                    if ($row->total_adjusted) {
                        $total_adjusted =  (float) $row->total_adjusted;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                })
                ->editColumn('description', function ($row) {
                    $description = '-';
                    if ($row->description) {
                        $description = $row->description;
                    }
                    return $description;
                })
                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                            . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) . '">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                ->addColumn(
                    'actions',
                    function ($row) use ($selling_price_group_count) {
                        $html =
                            '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false"> <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li><a href="' . action('LabelsController@show') . '?product_id=' . $row->product()->first()->id . '" data-toggle="tooltip" title="Print Barcode/Label"><i class="fa fa-barcode"></i> ' . __('barcode.labels') . '</a></li>';

                        if (auth()->user()->can('product.view')) {
                            $html .=
                                '<li><a href="' . action('ProductController@view', [$row->product()->first()->id]) . '" class="view-product"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                        }

                        if (auth()->user()->can('product.update')) {
                            $html .=
                                '<li><a href="' . action('ProductController@edit', [$row->product()->first()->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        }

                        if (auth()->user()->can('product.delete')) {
                            $html .=
                                '<li><a href="' . action('ProductController@destroy', [$row->product()->first()->id]) . '" class="delete-product"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        }

                        if ($row->is_inactive == 1) {
                            $html .=
                                '<li><a href="' . action('ProductController@activate', [$row->product()->first()->id]) . '" class="activate-product"><i class="fa fa-circle-o"></i> ' . __("lang_v1.reactivate") . '</a></li>';
                        }

                        $html .= '<li class="divider"></li>';

                        if (auth()->user()->can('product.create')) {
                            if ($row->enable_stock == 1) {
                                $html .=
                                    '<li><a href="#" data-href="' . action('OpeningStockController@add', ['product_id' => $row->product()->first()->id]) . '" class="add-opening-stock"><i class="fa fa-database"></i> ' . __("lang_v1.add_edit_opening_stock") . '</a></li>';
                            }

                            if ($selling_price_group_count > 0) {
                                $html .=
                                    '<li><a href="' . action('ProductController@addSellingPrices', [$row->product()->first()->id]) . '"><i class="fa fa-money"></i> ' . __("lang_v1.add_selling_price_group_prices") . '</a></li>';
                            }

                            $html .=
                                '<li><a href="' . action('ProductController@create', ["d" => $row->product()->first()->id]) . '"><i class="fa fa-copy"></i> ' . __("lang_v1.duplicate_product") . '</a></li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                // ->setRowAttr([
                //     'data-href' => function ($row) {
                //         if (auth()->user()->can("product.view")) {
                //             return  action('ProductController@view', [$row->product()->first()->id]);
                //         } else {
                //             return '';
                //         }
                //     }
                // ])
                // ->removeColumn('id')
                ->rawColumns(['mass_delete', 'unit_price', 'total_transfered', 'location_name', 'total_sold', 'total_adjusted', 'stock', 'actions', 'image'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $suppliers = Supplier::orderBy('name', 'ASC')->pluck('name', 'id');

        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.stock_report')
            ->with(compact('categories', 'suppliers', 'units', 'business_locations'));
    }

    /**
     * Shows product stock details
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockDetails(Request $request)
    {
        //Return the details in ajax call
        if ($request->ajax()) {
            $business_id = $request->session()->get('user.business_id');
            $product_id = $request->input('product_id');
            $query = Product::leftjoin('units as u', 'products.unit_id', '=', 'u.id')
                ->join('variations as v', 'products.id', '=', 'v.product_id')
                ->join('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
                ->leftjoin('variation_location_details as vld', 'v.id', '=', 'vld.variation_id')
                ->where('products.business_id', $business_id)
                ->where('products.id', $product_id)
                ->whereNull('v.deleted_at');

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';
            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);
                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            $product_details =  $query->select(
                'products.name as product',
                'u.short_name as unit',
                'pv.name as product_variation',
                'v.name as variation',
                'v.sub_sku as sub_sku',
                'v.sell_price_inc_tax',
                DB::raw("SUM(vld.qty_available) as stock"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell', TSL.quantity - TSL.quantity_returned, -1* TPL.quantity) ) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id

                        LEFT JOIN purchase_lines AS TPL ON transactions.id=TPL.transaction_id

                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND (TSL.variation_id=v.id OR TPL.variation_id=v.id)) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                        AND (TSL.variation_id=v.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        LEFT JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                        AND (SAL.variation_id=v.id)) as total_adjusted")
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.variation_id=v.id) as total_sold")
            )
                ->groupBy('v.id')
                ->get();

            return view('report.stock_details')
                ->with(compact('product_details'));
        }
    }

    /**
     * Shows tax report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getTaxReport(Request $request)
    {
        if (!auth()->user()->can('tax_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $location_id = $request->get('location_id');

            $input_tax_details = $this->transactionUtil->getInputTax($business_id, $start_date, $end_date, $location_id);

            $input_tax = view('report.partials.tax_details')->with(['tax_details' => $input_tax_details])->render();

            $output_tax_details = $this->transactionUtil->getOutputTax($business_id, $start_date, $end_date, $location_id);

            $output_tax = view('report.partials.tax_details')->with(['tax_details' => $output_tax_details])->render();

            return [
                'input_tax' => $input_tax,
                'output_tax' => $output_tax,
                'tax_diff' => $output_tax_details['total_tax'] - $input_tax_details['total_tax']
            ];
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.tax_report')
            ->with(compact('business_locations'));
    }

    /**
     * Shows trending products
     *
     * @return \Illuminate\Http\Response
     */
    public function getTrendingProducts(Request $request)
    {
        if (!auth()->user()->can('trending_product_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $filters = $request->only(['category', 'sub_category', 'brand', 'unit', 'limit', 'location_id', 'supplier']);

        $date_range = $request->input('date_range');
        $purchase_date = $request->input('purchase_date');

        if (!empty($date_range)) {
            $date_range_array = explode(' - ', $date_range);
            // dd($date_range_array);
            $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
            $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));
        }

        if (!empty($purchase_date)) {
            $purchase_date_array = explode(' - ', $purchase_date);
            // dd($date_range_array);
            $filters['purchase_start_date'] = $this->transactionUtil->uf_date(trim($purchase_date_array[0]));
            $filters['purchse_end_date'] = $this->transactionUtil->uf_date(trim($purchase_date_array[1]));
        }

        $products = $this->productUtil->getTrendingProducts($business_id, $filters);

        $values = [];
        $labels = [];
        $product_id = [];
        foreach ($products as $product) {
            $values[] = $product->total_unit_sold;
            $labels[] = $product->product . ' (' . $product->unit . ')';
            $product_id[] = $product->product_id;
        }

        // dd($id);

        $chart = Charts::create('bar', 'highcharts')
            ->title(" ")
            ->dimensions(0, 400)
            ->template("material")
            ->values($values)
            ->labels($labels)
            ->elementLabel(__('report.total_unit_sold'));

        $categories = Category::where('business_id', $business_id)->where('parent_id', 0)->pluck('name', 'id');

        $brands = Brands::where('business_id', $business_id)->pluck('name', 'id');

        $suppliers = Supplier::forDropdown($business_id);
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        // dd($this->trendingProductDetail($request));
        $details = $this->trendingProductDetail($request, $product_id);

        return view('report.trending_products')
            ->with(compact('chart', 'categories', 'brands', 'business_locations', 'suppliers', 'details'));
    }

    public function trendingProductDetail(Request $request, $product_id)
    {
        $business_id = $request->session()->get('user.business_id');
        $variation_id = $request->get('variation_id', null);

        $location_id = $request->get('location_id', null);

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }


        $query = TransactionSellLine::join(
            'transactions as t',
            'transaction_sell_lines.transaction_id',
            '=',
            't.id'
        )
            // ->join(
            //     'variations as v',
            //     'transaction_sell_lines.variation_id',
            //     '=',
            //     'v.id'
            // )
            // ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            // ->join('contacts as c', 't.contact_id', '=', 'c.id')
            // ->join('products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            // ->join('variation_location_details as vlds', 'pv.product_id', '=', 'vlds.product_id')
            // ->join('suppliers as s', 's.id','=','p.supplier_id')
            // ->leftjoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
            // ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->join('products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            ->leftjoin('units as u', 'u.id', '=', 'p.unit_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereIn('p.id', $product_id)
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.image as image',
                'p.supplier_id as supplier_id',
                'p.category_id as category',
                'p.sub_category_id as sub_category',
                // 's.name as supplier',
                'p.refference as refference',
                'p.type as product_type',
                'p.sku as barcode',
                // 'pv.name as product_variation',
                // 'v.name as variation_name',
                // 'c.name as customer',
                't.id as transaction_id',
                't.invoice_no',
                't.transaction_date as transaction_date',
                'transaction_sell_lines.unit_price_before_discount as unit_price',
                'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=transaction_sell_lines.variation_id $vld_str) as current_stock"),
                'p.product_updated_at as product_updated_at',
                'transaction_sell_lines.original_amount as original_amount',
                DB::raw('(SUM(transaction_sell_lines.quantity) - SUM(transaction_sell_lines.quantity_returned)) as sell_qty'),
                'transaction_sell_lines.line_discount_type as discount_type',
                'transaction_sell_lines.line_discount_amount as discount_amount',
                'transaction_sell_lines.item_tax',
                // 'tax_rates.name as tax',
                'u.short_name as unit',
                DB::raw('((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
            )
            ->orderBy('sell_qty', 'DESC')
            ->groupBy('p.sku');
        // ->orderBy('p.name', 'ASC')
        // ->orderBy('t.invoice_no','DESC')
        // ->groupBy('transaction_sell_lines.product_id');
        // ->groupBy('transaction_sell_lines.id');
        // dd($query->first());
        if (!empty($variation_id)) {
            $query->where('transaction_sell_lines.variation_id', $variation_id);
        }

        $date_range = $request->input('date_range');

        if (!empty($date_range)) {
            $date_range_array = explode(' - ', $date_range);
            // dd($date_range_array);
            $start_date = $this->transactionUtil->uf_date(trim($date_range_array[0]));
            $end_date = $this->transactionUtil->uf_date(trim($date_range_array[1]));

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(t.transaction_date)'), [$start_date, $end_date]);
            }
        } else {
            $start_week = Carbon::now()->addDays(-6);
            $end_week = Carbon::now();
            $query->whereBetween(DB::raw('date(t.transaction_date)'), [$start_week, $end_week]);
        }

        $purchase_date = $request->input('purchase_date');
        if (!empty($purchase_date)) {
            $purchase_date_array = explode(' - ', $purchase_date);
            // dd($date_range_array);
            $purchase_start_date = $this->transactionUtil->uf_date(trim($purchase_date_array[0]));
            $purchase_end_date = $this->transactionUtil->uf_date(trim($purchase_date_array[1]));

            if (!empty($purchase_start_date) && !empty($purchase_end_date)) {
                $query->whereBetween(DB::raw('date(product_updated_at)'), [$purchase_start_date, $purchase_end_date]);
            }
        } else {
            $start_year = Carbon::now()->startOfYear();
            $now = Carbon::now();

            $query->whereBetween(DB::raw('date(product_updated_at)'), [
                $start_year,
                $now
            ]);
        }


        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('t.location_id', $permitted_locations);
        }

        $location_id = $request->get('location_id', null);
        if (!empty($location_id)) {
            $query->where('t.location_id', $location_id);
        }

        $customer_id = $request->get('customer_id', null);
        if (!empty($customer_id)) {
            $query->where('t.contact_id', $customer_id);
        }

        $supplier_id = $request->get('supplier_id', null);
        if (!empty($supplier_id)) {
            $query->where('p.supplier_id', $supplier_id);
        }

        $category = $request->get('category', null);
        if (!empty($category)) {
            $query->where('p.category_id', $category);
        }

        $sub_category = $request->get('sub_category', null);
        if (!empty($sub_category)) {
            $query->where('p.sub_category_id', $sub_category);
        }
        // $limit = $request->get('limit', 15);
        // if (!empty($limit)) {
        //     $query->where('p.sub_category_id', $sub_category);
        // }

        return $query->get();


        return Datatables::of($query)
            ->editColumn('product_name', function ($row) {
                $product_name = $row->product_name;
                if ($row->product_type == 'variable') {
                    $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                }

                return $product_name;
            })
            ->editColumn('product_updated_at', function ($row) {
                return Carbon::parse($row->product_updated_at)->format('d-M-Y H:i');
            })
            ->addColumn('size', function ($row) {
                return $row->product()->first()->sub_size()->first()['name'];
            })
            ->editColumn('invoice_no', function ($row) {
                return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                    . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
            })
            ->editColumn('image', function ($row) {
                return '<div style="display: flex;"><img src="' . $row->image_url . '" alt="Product image" class="product-thumbnail-small"></div>';
            })
            ->editColumn('refference', function ($row) {
                if ($row->refference) {
                    return $row->refference;
                } else {
                    return '<b class="text-center">-</b>';
                }
            })
            ->editColumn('supplier_id', function ($row) {
                if ($row->product()->first()->supplier()->first()) {
                    return $row->product()->first()->supplier()->first()['name'];
                } else {
                    return '-';
                }
            })
            // ->editColumn('product_updated_at', function($row){
            //     return Carbon::parse($row->product_updated_at)->format('d-M-Y H:i');
            // })
            ->editColumn('transaction_date', function ($row) {
                return Carbon::parse($row->transaction_date)->format('d-M-Y H:i');
            })
            ->editColumn('unit_sale_price', function ($row) {
                return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_sale_price . '</span>';
            })
            ->editColumn('sell_qty', function ($row) {
                return '<span  class="sell_qty" data-currency_symbol=false data-orig-value="' . (int)$row->sell_qty . '" data-unit="' . $row->unit . '" >' . (int) $row->sell_qty . '</span> ' . $row->unit;
            })
            ->editColumn('subtotal', function ($row) {
                return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
            })
            ->editColumn('unit_price', function ($row) {
                return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_price . '</span>';
            })
            ->editColumn('original_amount', function ($row) {
                if ($row->original_amount) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->original_amount . '</span>';
                } else {
                    return '-';
                }
            })
            ->editColumn('discount_amount', '
                    @if($discount_type == "percentage")
                        {{@number_format($discount_amount)}} %
                    @elseif($discount_type == "fixed")
                        {{@number_format($discount_amount)}}
                    @endif
                    ')
            ->editColumn('tax', function ($row) {
                return '<span class="display_currency" data-currency_symbol = true>' .
                    $row->item_tax .
                    '</span>' . '<br>' . '<span class="tax" data-orig-value="' . (float)$row->item_tax . '" data-unit="' . $row->tax . '"><small>(' . $row->tax . ')</small></span>';
            })
            ->editColumn('current_stock', function ($row) {
                // if ($row->enable_stock) {
                return '<span data-is_quantity="true" class="display_currency current_stock" data-currency_symbol=false data-orig-value="' . (int) $row->current_stock . '" data-unit="' . $row->unit . '" >' . (int) $row->current_stock . '</span> ' . $row->unit;
                // } else {
                //     return '';
                // }
            })
            ->setRowAttr([
                'data-href' => function ($row) {
                    if (auth()->user()->can("product.view")) {
                        return  action('ProductController@view', [$row->product_id]);
                    } else {
                        return '';
                    }
                }
            ])
            ->rawColumns(['original_amount', 'refference', 'image', 'invoice_no', 'unit_sale_price', 'subtotal', 'sell_qty', 'discount_amount', 'unit_price', 'tax', 'current_stock'])
            ->make(true);
    }

    /**
     * Shows expense report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getExpenseReport(Request $request)
    {
        if (!auth()->user()->can('expense_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $filters = $request->only(['category', 'location_id']);

        $date_range = $request->input('date_range');

        if (!empty($date_range)) {
            $date_range_array = explode('~', $date_range);
            $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
            $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));
        } else {
            $filters['start_date'] = \Carbon::now()->startOfMonth()->format('Y-m-d');
            $filters['end_date'] = \Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $expenses = $this->transactionUtil->getExpenseReport($business_id, $filters);

        $values = [];
        $labels = [];
        foreach ($expenses as $expense) {
            $values[] = $expense->total_expense;
            $labels[] = !empty($expense->category) ? $expense->category : __('report.others');
        }

        $chart = Charts::create('bar', 'highcharts')
            ->title(__('report.expense_report'))
            ->dimensions(0, 400)
            ->template("material")
            ->values($values)
            ->labels($labels)
            ->elementLabel(__('report.total_expense'));

        $categories = ExpenseCategory::where('business_id', $business_id)
            ->pluck('name', 'id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.expense_report')
            ->with(compact('chart', 'categories', 'business_locations'));
    }

    /**
     * Shows stock adjustment report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockAdjustmentReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $query =  Transaction::where('business_id', $business_id)
                ->where('type', 'stock_adjustment');

            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('location_id', $permitted_locations);
            }

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }
            $location_id = $request->get('location_id');
            if (!empty($location_id)) {
                $query->where('location_id', $location_id);
            }

            $stock_adjustment_details = $query->select(
                DB::raw("SUM(final_total) as total_amount"),
                DB::raw("SUM(total_amount_recovered) as total_recovered"),
                DB::raw("SUM(IF(adjustment_type = 'normal', final_total, 0)) as total_normal"),
                DB::raw("SUM(IF(adjustment_type = 'abnormal', final_total, 0)) as total_abnormal")
            )->first();
            return $stock_adjustment_details;
        }
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.stock_adjustment_report')
            ->with(compact('business_locations'));
    }

    /**
     * Shows register report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getRegisterReportorignal(Request $request)
    {
        // dd($request);
        if (!auth()->user()->can('register_report.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {

            // $registers = CashRegister::join(
            //     'cash_register_transactions as ct',
            //     'ct.cash_register_id',
            //     '=',
            //     'cash_registers.id'
            //     )
            //     ->join(
            //         'business_locations as bl',
            //         'bl.id',
            //         '=',
            //         'cash_registers.location_id'
            //     )

            //     ->join(
            //         'transaction_sell_lines as tsl',
            //         'tsl.transaction_id',
            //         '=',
            //         'ct.transaction_id'
            //     )
            //     ->join(
            //         'transaction_payments as tp',
            //         'tp.transaction_id',
            //         '=',
            //         'ct.transaction_id'
            //     )
            //     ->join(
            //         'transactions as t',
            //         't.id',
            //         '=',
            //         'ct.transaction_id'
            //     )
            // ->join(
            //     'business_locations AS bl',
            //     't.location_id',
            //     '=',
            //     'bl.id'
            // )
            $registers = CashRegister::join(
                'cash_register_transactions as ct',
                'ct.cash_register_id',
                '=',
                'cash_registers.id'
            )
                ->join(
                    'business_locations as bl',
                    'bl.id',
                    '=',
                    'cash_registers.location_id'
                )
                ->join(
                    'users as u',
                    'u.id',
                    '=',
                    'cash_registers.user_id'
                )
                ->join(
                    'transaction_sell_lines as t',
                    't.transaction_id',
                    '=',
                    'ct.transaction_id'
                )
                ->where('ct.transaction_type', 'sell');
            // $transaction_ids = $registers->distinct('ct.transaction_id')->pluck('ct.transaction_id');
            // dd($transaction_ids);
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $discount = TransactionSellLine::where('discounted_amount', '>', 0)->get()->sum('discounted_amount');

            $registers->select(
                'cash_registers.id as register_id',
                'cash_registers.created_at as created_at',
                'cash_registers.location_id as location_id',
                'bl.name as location_name',
                'cash_registers.statusss as status',
                // 'discount as discounted_amount',
                // DB::raw("discount as discounted_amount"),
                //Orignal one 
                // DB::raw("SUM(IF(ct.pay_method = 'cash' AND ct.amount > 0 ,tp.amount, 0)) as cash"),
                DB::raw("SUM(IF(ct.pay_method = 'cash' AND ct.amount > 0 , ct.amount, 0)) as cash"),
                //orignal one 
                // DB::raw("SUM(IF(tp.method = 'card' AND ct.amount > 0, t.final_total, 0)) as card"),
                DB::raw("SUM(IF(ct.pay_method = 'card' AND ct.amount > 0, ct.amount, 0)) as card"),

                // DB::raw("SUM(ct.pay_method = 'card') as card"),
                //orignal one
                // DB::raw("SUM(IF(tp.is_convert = 'gift_card' AND ct.amount > 0, t.final_total, 0)) as gift_card"),
                // DB::raw("SUM(IF(tp.is_convert = 'coupon' AND ct.amount > 0, t.final_total, 0)) as coupon"),
                //orignal one
                // DB::raw('SUM(IF(tp.is_convert="coupon",t.final_total,0)) as coupon'),
                // DB::raw("SUM(IF(ct.pay_method = 'gift_card', amount, 0)) as gift_card"),
                // DB::raw("SUM(IF(ct.pay_method = 'coupon', amount, 0)) as coupon"),
                //orignal one
                // DB::raw("SUM(IF(ct.amount > 0, tsl.discounted_amount, 0)) as discounted_amount"),

                DB::raw("COUNT(DISTINCT(ct.transaction_id)) as invoices"),
                // DB::raw("(SELECT COUNT(tr.invoice_no) FROM transactions as tr WHERE tr.id=t.transaction_id) as invoice"),
                //orignal one
                //  DB::raw("SUM(IF(ct.amount > 0, tsl.quantity, 0)) as items"),
                //  DB::raw("SUM(IF(ct.amount > 0, tsl.quantity, 0)) as items"),
                // DB::raw("(SELECT SUM(IF(ct.amount > 0, tsl.quantity, 0)) FROM cash_registers
                // JOIN cash_register_transactions AS ct ON cash_registers.id = ct.cash_register_id
                // JOIN business_locations AS bl ON bl.id = cash_registers.location_id
                // JOIN transaction_sell_lines AS tsl ON tsl.transaction_id = ct.transaction_id
                // JOIN transaction_payments AS tp ON tp.transaction_id = ct.transaction_id
                // JOIN transactions AS t ON t.id = ct.transaction_id
                // WHERE tsl.transaction_id = ct.transaction_id ) as items"),



                // DB::raw("SUM(tsl.quantity) as items"),
                // DB::raw("SUM(IF(DISTINCT(ct.transaction_id), t.quanity, 0)) as items"),
                // DB::raw('(SELECT SUM(cts.amount) FROM cash_register_transactions as cts WHERE cts.cash_register_id=cash_registers.id AND cts.pay_method="cash") as cash_payment')
            )
                ->orderBy('created_at', 'DESC')
                ->groupBy('register_id');

            // dd($registers->get()[195], $registers->get()[196], $registers->get()[197]);


            if (!empty($request->input('user_id'))) {
                $registers->where('cash_registers.user_id', $request->input('user_id'));
            }

            if (!empty($request->input('location_id'))) {
                $registers->where('cash_registers.location_id', $request->input('location_id'));
            }



            if (!empty($start_date) && !empty($end_date)) {
                $registers->whereBetween(DB::raw('date(cash_registers.created_at)'), [$start_date, $end_date]);
            }

            if (!empty($request->input('status'))) {
                $registers->where('cash_registers.status', $request->input('status'));
            }
            return Datatables::of($registers)
                ->editColumn('cash', function ($row) {
                    $cash = $row->cash;
                    // $cash = $row->cash - $row->card - $row->coupon - $row->gift_card;
                    return '<span class="display_currency cash_amount" data-currency_symbol="true"  data-orig-value="' . $cash  / 2 . '">' .
                        $cash . '</span>';
                })

                ->editColumn('card', function ($row) {
                    return '<span class="display_currency card_amount" data-currency_symbol="true"  data-orig-value="' . $row->card / 2 . '">' .
                        $row->card . '</span>';
                })
                ->editColumn('gift_card', function ($row) {
                    return '<span class="display_currency giftcard_amount" data-currency_symbol="true" data-orig-value="' . $row->gift_card . '">' .
                        $row->gift_card . '</span>';
                })
                ->editColumn('coupon', function ($row) {
                    return '<span class="display_currency coupon_amount" data-currency_symbol="true" data-orig-value="' . $row->coupon . '">' .
                        $row->coupon . '</span>';
                })
                ->editColumn('discounted_amount', function ($row) {
                    return '<span class="display_currency discounted_amount" data-currency_symbol="true" data-orig-value="' . $row->discounted_amount / 2 . '">' . $row->discounted_amount / 2 . '</span>';
                })
                ->addColumn('total_amount', function ($row) {
                    $total = $row->cash + $row->card + $row->gift_card + $row->coupon + $row->discounted_amount;
                    return '<span class="display_currency total_amount" data-currency_symbol="true" data-orig-value="' . $total . '">' . $total . '</span>';
                })
                ->editColumn('invoices', function ($row) {
                    return '<span class="display_currency invoices" data-currency_symbol="false" data-orig-value="' . $row->invoices . '">' . $row->invoices . '</span>';
                })
                ->editColumn('items', function ($row) {
                    return '<span class="display_currency items" data-currency_symbol="false" data-orig-value="' . $row->items / 2 . '">' . $row->items / 2 . '</span>';
                    // return '<span class="display_currency items" data-currency_symbol="false" data-orig-value="' . $row->items . '">' . $row->items . '</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d-M-Y H:i A');
                    // return $this->productUtil->format_date($row->created_at, true);
                })
                // ->editColumn('closing_amount', function ($row) {
                //     if ($row->status == 'close') {
                //         return '<span class="display_currency" data-currency_symbol="true">' .
                //             $row->closing_amount . '</span>';
                //     } else {
                //         return '';
                //     }
                // })
                ->addColumn('action', '<button type="button" data-href="{{action(\'CashRegisterController@show\', [$register_id])}}" class="btn btn-xs btn-info btn-modal" 
                    data-container=".view_register"><i class="fa fa-external-link" aria-hidden="true"></i> @lang("messages.view")</button>')
                ->rawColumns(['action', 'cash', 'card', 'gift_card', 'coupon', 'discounted_amount', 'total_amount', 'invoices', 'items'])
                ->make(true);
        }

        $users = User::forDropdown($business_id, false);
        $business = BusinessLocation::forDropdown($business_id);


        return view('report.register_report')
            ->with(compact('users', 'business'));
    }
    public function getRegisterReport(Request $request)
    {
        // dd($request);
        if (!auth()->user()->can('register_report.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $registers = CashRegister::join(
                'cash_register_transactions as ct',
                'ct.cash_register_id',
                '=',
                'cash_registers.id'
            )
                ->join(
                    'business_locations as bl',
                    'bl.id',
                    '=',
                    'cash_registers.location_id'
                )

                ->join(
                    'transaction_sell_lines as tsl',
                    'tsl.transaction_id',
                    '=',
                    'ct.transaction_id'
                )
                // ->join(
                //     'transaction_payments as tp',
                //     'tp.transaction_id',
                //     '=',
                //     'ct.transaction_id'
                // )
                ->join(
                    'transactions as t',
                    't.id',
                    '=',
                    'ct.transaction_id'
                )
                // $registers = CashRegister::join(
                //     'cash_register_transactions as ct',
                //     'ct.cash_register_id',
                //     '=',
                //     'cash_registers.id'
                // )
                // ->join(
                //     'business_locations as bl',
                //     'bl.id',
                //     '=',
                //     'cash_registers.location_id'
                // )
                // ->join(
                //     'transaction_payments as tp',
                //     'tp.transaction_id',
                //     '=',
                //     'ct.transaction_id'
                // )
                // ->leftJoin(
                //     'transaction_sell_lines as tsl_items',
                //     function ($join) {
                //         $join->on('tsl_items.transaction_id', '=', 'ct.transaction_id')
                //             ->where('ct.transaction_type', '=', 'sell');
                //     }
                // )
                // ->leftJoin(
                //     'transaction_sell_lines as tsl_discounted',
                //     function ($join) {
                //         $join->on('tsl_discounted.transaction_id', '=', 'ct.transaction_id')
                //             ->where('ct.transaction_type', '=', 'sell');
                //     }
                // )
                // ->where('ct.transaction_type', 'sell');
                // if ($request->ajax()) {
                //     $registers->join(
                //         'transaction_sell_lines as tsl',
                //         'tsl.transaction_id',
                //         '=',
                //         'ct.transaction_id'
                //     )
                //     ->join(
                //         'transaction_payments as tp',
                //         'tp.transaction_id',
                //         '=',
                //         'ct.transaction_id'
                //     );
                // }

                // ->join(
                //     'transaction_sell_lines as tsl',
                //     'tsl.transaction_id',
                //     '=',
                //     'ct.transaction_id'
                // )
                // ->join(
                //     'transaction_payments as tp',
                //     'tp.transaction_id',
                //     '=',
                //     'ct.transaction_id'
                // )

                ->where('ct.transaction_type', 'sell');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $registers->select(
                'cash_registers.id as register_id',
                'cash_registers.created_at as created_at',
                'cash_registers.location_id as location_id',
                'bl.name as location_name',
                'cash_registers.statusss as status',
                // 'discount as discounted_amount',
                // DB::raw("discount as discounted_amount"),
                //Orignal one 
                // DB::raw("SUM(IF(tp.method = 'cash' AND ct.amount > 0 ,tp.amount, 0)) as cash"),

                // DB::raw("SUM(ct.pay_method = 'card') as card"),
                // DB::raw("SUM(IF(ct.pay_method = 'cash' AND ct.amount > 0 , ct.amount, 0)) as cash"),
                // DB::raw("(SELECT SUM(IF(ct.pay_method = 'cash' AND ct.amount > 0, ct.amount, 0)) FROM cash_register_transactions as ct WHERE ct.cash_register_id = cash_registers.id AND ct.transaction_type = 'sell') as cash"),
                DB::raw("
                        (SELECT 
                            SUM(
                                IF(ct.pay_method = 'cash' AND ct.amount > 0, ct.amount, 0)
                            ) 
                            FROM cash_register_transactions as ct 
                            WHERE ct.cash_register_id = cash_registers.id 
                            AND ct.transaction_type = 'sell'
                        ) as cash
                    "),
                //orignal one 
                // DB::raw("SUM(IF(tp.method = 'card' AND ct.amount > 0, t.final_total, 0)) as card"),
                // DB::raw("SUM(IF(ct.pay_method = 'card' AND ct.amount > 0, ct.amount, 0)) as card"),
                DB::raw("
                        (SELECT 
                            SUM(
                                IF(ct.pay_method = 'card' AND ct.amount > 0, ct.amount, 0)
                            ) 
                            FROM cash_register_transactions as ct 
                            WHERE ct.cash_register_id = cash_registers.id 
                            AND ct.transaction_type = 'sell'
                        ) as card
                    "),
                DB::raw("
                        (SELECT 
                            SUM(
                                IF(ct.pay_method = 'coupon', ct.amount, 0)
                            ) 
                            FROM cash_register_transactions as ct 
                            WHERE ct.cash_register_id = cash_registers.id 
                            AND ct.transaction_type = 'sell'
                        ) as coupon
                    "),
                //orignal one
                // DB::raw("SUM(IF(tp.is_convert = 'gift_card' AND ct.amount > 0, t.final_total, 0)) as gift_card"),
                // DB::raw("SUM(IF(tp.is_convert = 'coupon' AND ct.amount > 0, t.final_total, 0)) as coupon"),
                //orignal one
                // DB::raw('SUM(IF(tp.is_convert="coupon",t.final_total,0)) as coupon'),
                // DB::raw("SUM(IF(ct.pay_method = 'gift_card', amount, 0)) as gift_card"),
                // DB::raw("SUM(IF(ct.pay_method = 'coupon', amount, 0)) as coupon"),


                DB::raw("COUNT(DISTINCT(ct.transaction_id)) as invoices"),
                // DB::raw("(SELECT COUNT(tr.invoice_no) FROM transactions as tr WHERE tr.id=t.transaction_id) as invoice"),
                //orignal one
                DB::raw("SUM(IF(ct.amount > 0, tsl.discounted_amount, 0)) as discounted_amount"),
                //orignal one
                DB::raw("SUM(IF(ct.amount > 0, tsl.quantity, 0)) as items"),

                // 'items' calculation
                // DB::raw('SUM(IF(ct.amount > 0, tsl_items.quantity, 0)) as items'),

                // 'discounted_amount' calculation
                // DB::raw('SUM(IF(ct.amount > 0, tsl_discounted.discounted_amount, 0)) as discounted_amount'),

                //    // 'items' calculation
                //     DB::raw('(SELECT SUM(IF(ct.amount > 0, tsl_items.quantity, 0)) FROM transaction_sell_lines as tsl_items WHERE tsl_items.transaction_id = ct.transaction_id) as items'),

                //     // 'discounted_amount' calculation
                //     DB::raw('(SELECT SUM(IF(ct.amount > 0, tsl_discounted.discounted_amount, 0)) FROM transaction_sell_lines as tsl_discounted WHERE tsl_discounted.transaction_id = ct.transaction_id) as discounted_amount'),


                // DB::raw("SUM(tsl.quantity) as items"),
                // DB::raw("SUM(IF(DISTINCT(ct.transaction_id), t.quanity, 0)) as items"),
                // DB::raw('(SELECT SUM(cts.amount) FROM cash_register_transactions as cts WHERE cts.cash_register_id=cash_registers.id AND cts.pay_method="cash") as cash_payment')
            )

                ->orderBy('created_at', 'DESC')
                ->groupBy('register_id');

            // dd($registers->get()[195], $registers->get()[196], $registers->get()[197]);


            if (!empty($request->input('user_id'))) {
                $registers->where('cash_registers.user_id', $request->input('user_id'));
            }

            if (!empty($request->input('location_id'))) {
                $registers->where('cash_registers.location_id', $request->input('location_id'));
            }



            if (!empty($start_date) && !empty($end_date)) {
                $registers->whereBetween(DB::raw('date(cash_registers.created_at)'), [$start_date, $end_date]);
            }

            if (!empty($request->input('status'))) {
                $registers->where('cash_registers.status', $request->input('status'));
            }
            return Datatables::of($registers)
                ->editColumn('cash', function ($row) {
                    $cash = $row->cash;
                    // $cash = $row->cash - $row->card - $row->coupon - $row->gift_card;
                    return '<span class="display_currency cash_amount" data-currency_symbol="true"  data-orig-value="' . $cash  / 2 . '">' .
                        $cash  . '</span>';
                })

                ->editColumn('card', function ($row) {
                    return '<span class="display_currency card_amount" data-currency_symbol="true"  data-orig-value="' . $row->card / 2 . '">' .
                        $row->card  . '</span>';
                })
                ->editColumn('gift_card', function ($row) {
                    return '<span class="display_currency giftcard_amount" data-currency_symbol="true" data-orig-value="' . $row->gift_card . '">' .
                        $row->gift_card . '</span>';
                })
                ->editColumn('coupon', function ($row) {
                    return '<span class="display_currency coupon_amount" data-currency_symbol="true" data-orig-value="' . $row->coupon . '">' .
                        $row->coupon . '</span>';
                })
                ->editColumn('discounted_amount', function ($row) {
                    return '<span class="display_currency discounted_amount" data-currency_symbol="true" data-orig-value="' . $row->discounted_amount / 2 . '">' . $row->discounted_amount  . '</span>';
                })
                ->addColumn('total_amount', function ($row) {
                    // $total = $row->cash + $row->card + $row->gift_card + $row->coupon + $row->discounted_amount;
                    $total = $row->cash + $row->card + $row->gift_card + $row->coupon;
                    return '<span class="display_currency total_amount" data-currency_symbol="true" data-orig-value="' . $total . '">' . $total . '</span>';
                })
                ->editColumn('invoices', function ($row) {
                    return '<span class="display_currency invoices" data-currency_symbol="false" data-orig-value="' . $row->invoices . '">' . $row->invoices . '</span>';
                })
                ->editColumn('items', function ($row) {
                    return '<span class="display_currency items" data-currency_symbol="false" data-orig-value="' . $row->items / 2 . '">' . $row->items  . '</span>';
                    // return '<span class="display_currency items" data-currency_symbol="false" data-orig-value="' . $row->items . '">' . $row->items . '</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d-M-Y H:i A');
                    // return $this->productUtil->format_date($row->created_at, true);
                })
                // ->editColumn('closing_amount', function ($row) {
                //     if ($row->status == 'close') {
                //         return '<span class="display_currency" data-currency_symbol="true">' .
                //             $row->closing_amount . '</span>';
                //     } else {
                //         return '';
                //     }
                // })
                ->addColumn('action', '<button type="button" data-href="{{action(\'CashRegisterController@show\', [$register_id])}}" class="btn btn-xs btn-info btn-modal" 
                    data-container=".view_register"><i class="fa fa-external-link" aria-hidden="true"></i> @lang("messages.view")</button>')
                ->rawColumns(['action', 'cash', 'card', 'gift_card', 'coupon', 'discounted_amount', 'total_amount', 'invoices', 'items'])
                ->make(true);
        }

        $users = User::forDropdown($business_id, false);
        $business = BusinessLocation::forDropdown($business_id);


        return view('report.register_report')
            ->with(compact('users', 'business'));
    }
    /**
     * Shows Old register report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getold_RegisterReport(Request $request)
    {
        if (!auth()->user()->can('register_report.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $registers = CashRegister::join(
                'users as u',
                'u.id',
                '=',
                'cash_registers.user_id'
            )
                ->where('cash_registers.business_id', $business_id)
                ->select(
                    'cash_registers.*',
                    DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), '<br>', COALESCE(email, '')) as user_name")
                );

            if (!empty($request->input('user_id'))) {
                $registers->where('cash_registers.user_id', $request->input('user_id'));
            }
            if (!empty($request->input('status'))) {
                $registers->where('cash_registers.status', $request->input('status'));
            }
            return Datatables::of($registers)
                ->editColumn('total_card_slips', function ($row) {
                    if ($row->status == 'close') {
                        return $row->total_card_slips;
                    } else {
                        return '';
                    }
                })
                ->editColumn('total_cheques', function ($row) {
                    if ($row->status == 'close') {
                        return $row->total_cheques;
                    } else {
                        return '';
                    }
                })
                ->editColumn('closed_at', function ($row) {
                    if ($row->status == 'close') {
                        return $this->productUtil->format_date($row->closed_at, true);
                    } else {
                        return '';
                    }
                })
                ->editColumn('created_at', function ($row) {
                    return $this->productUtil->format_date($row->created_at, true);
                })
                ->editColumn('closing_amount', function ($row) {
                    if ($row->status == 'close') {
                        return '<span class="display_currency" data-currency_symbol="true">' .
                            $row->closing_amount . '</span>';
                    } else {
                        return '';
                    }
                })
                ->addColumn('action', '<button type="button" data-href="{{action(\'CashRegisterController@show\', [$id])}}" class="btn btn-xs btn-info btn-modal" 
                    data-container=".view_register"><i class="fa fa-external-link" aria-hidden="true"></i> @lang("messages.view")</button>')
                ->filterColumn('user_name', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), '<br>', COALESCE(email, '')) like ?", ["%{$keyword}%"]);
                })
                ->rawColumns(['action', 'user_name', 'closing_amount'])
                ->make(true);
        }

        $users = User::forDropdown($business_id, false);

        return view('report.register_report')
            ->with(compact('users'));
    }

    /**
     * Shows sales representative report
     *
     * @return \Illuminate\Http\Response
     */
    public function getSalesRepresentativeReport(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $users = User::allUsersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.sales_representative')
            ->with(compact('users', 'business_locations'));
    }

    /**
     * Shows sales representative total expense
     *
     * @return json
     */
    public function getSalesRepresentativeTotalExpense(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->ajax()) {
            $business_id = $request->session()->get('user.business_id');

            $filters = $request->only(['expense_for', 'location_id', 'start_date', 'end_date']);

            $total_expense = $this->transactionUtil->getExpenseReport($business_id, $filters, 'total');

            return $total_expense;
        }
    }

    /**
     * Shows sales representative total sales
     *
     * @return json
     */
    public function getSalesRepresentativeTotalSell(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $location_id = $request->get('location_id');
            $created_by = $request->get('created_by');

            $sell_details = $this->transactionUtil->getSellTotals($business_id, $start_date, $end_date, $location_id, $created_by);

            //Get Sell Return details
            $transaction_types = [
                'sell_return'
            ];
            $sell_return_details = $this->transactionUtil->getTransactionTotals(
                $business_id,
                $transaction_types,
                $start_date,
                $end_date,
                $location_id,
                $created_by
            );

            $total_sell_return = !empty($sell_return_details['total_sell_return_exc_tax']) ? $sell_return_details['total_sell_return_exc_tax'] : 0;
            $total_sell = $sell_details['total_sell_exc_tax'] - $total_sell_return;

            return [
                'total_sell_exc_tax' => $sell_details['total_sell_exc_tax'],
                'total_sell_return_exc_tax' => $total_sell_return,
                'total_sell' => $total_sell
            ];
        }
    }

    /**
     * Shows sales representative total commission
     *
     * @return json
     */
    public function getSalesRepresentativeTotalCommission(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $location_id = $request->get('location_id');
            $commission_agent = $request->get('commission_agent');

            $sell_details = $this->transactionUtil->getTotalSellCommission($business_id, $start_date, $end_date, $location_id, $commission_agent);

            //Get Commision
            $commission_percentage = User::find($commission_agent)->cmmsn_percent;
            $total_commission = $commission_percentage * $sell_details['total_sales_with_commission'] / 100;

            return [
                'total_sales_with_commission' =>
                $sell_details['total_sales_with_commission'],
                'total_commission' => $total_commission,
                'commission_percentage' => $commission_percentage
            ];
        }
    }

    /**
     * Shows product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockExpiryReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //TODO:: Need to display reference number and edit expiry date button

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = PurchaseLine::leftjoin(
                'transactions as t',
                'purchase_lines.transaction_id',
                '=',
                't.id'
            )
                ->leftjoin(
                    'products as p',
                    'purchase_lines.product_id',
                    '=',
                    'p.id'
                )
                ->leftjoin(
                    'variations as v',
                    'purchase_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->leftjoin(
                    'product_variations as pv',
                    'v.product_variation_id',
                    '=',
                    'pv.id'
                )
                ->leftjoin('business_locations as l', 't.location_id', '=', 'l.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                //->whereNotNull('p.expiry_period')
                //->whereNotNull('p.expiry_period_type')
                //->whereNotNull('exp_date')
                ->where('p.enable_stock', 1);
            // ->whereRaw('purchase_lines.quantity > purchase_lines.quantity_sold + quantity_adjusted + quantity_returned');

            $permitted_locations = auth()->user()->permitted_locations();

            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');
                $query->where('t.location_id', $location_id);
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }
            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }
            if (!empty($request->input('exp_date_filter'))) {
                $query->whereDate('exp_date', '<=', $request->input('exp_date_filter'));
            }

            $report = $query->select(
                'p.name as product',
                'p.sku',
                'p.type as product_type',
                'v.name as variation',
                'pv.name as product_variation',
                'l.name as location',
                'mfg_date',
                'exp_date',
                'u.short_name as unit',
                DB::raw("SUM(COALESCE(quantity, 0) - COALESCE(quantity_sold, 0) - COALESCE(quantity_adjusted, 0) - COALESCE(quantity_returned, 0)) as stock_left"),
                't.ref_no',
                't.id as transaction_id',
                'purchase_lines.id as purchase_line_id',
                'purchase_lines.lot_number'
            )
                ->groupBy('purchase_lines.exp_date')
                ->groupBy('purchase_lines.lot_number');

            return Datatables::of($report)
                ->editColumn('name', function ($row) {
                    if ($row->product_type == 'variable') {
                        return $row->product . ' - ' .
                            $row->product_variation . ' - ' . $row->variation;
                    } else {
                        return $row->product;
                    }
                })
                ->editColumn('mfg_date', function ($row) {
                    if (!empty($row->mfg_date)) {
                        return $this->productUtil->format_date($row->mfg_date);
                    } else {
                        return '--';
                    }
                })
                // ->editColumn('exp_date', function ($row) {
                //     if (!empty($row->exp_date)) {
                //         $carbon_exp = \Carbon::createFromFormat('Y-m-d', $row->exp_date);
                //         $carbon_now = \Carbon::now();
                //         if ($carbon_now->diffInDays($carbon_exp, false) >= 0) {
                //             return $this->productUtil->format_date($row->exp_date) . '<br><small>( <span class="time-to-now">' . $row->exp_date . '</span> )</small>';
                //         } else {
                //             return $this->productUtil->format_date($row->exp_date) . ' &nbsp; <span class="label label-danger no-print">' . __('report.expired') . '</span><span class="print_section">' . __('report.expired') . '</span><br><small>( <span class="time-from-now">' . $row->exp_date . '</span> )</small>';
                //         }
                //     } else {
                //         return '--';
                //     }
                // })
                ->editColumn('ref_no', function ($row) {
                    return '<button type="button" data-href="' . action('PurchaseController@show', [$row->transaction_id])
                        . '" class="btn btn-link btn-modal" data-container=".view_modal"  >' . $row->ref_no . '</button>';
                })
                ->editColumn('stock_left', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency stock_left" data-currency_symbol=false data-orig-value="' . $row->stock_left . '" data-unit="' . $row->unit . '" >' . $row->stock_left . '</span> ' . $row->unit;
                })
                ->addColumn('edit', function ($row) {
                    $html =  '<button type="button" class="btn btn-primary btn-xs stock_expiry_edit_btn" data-transaction_id="' . $row->transaction_id . '" data-purchase_line_id="' . $row->purchase_line_id . '"> <i class="fa fa-edit"></i> ' . __("messages.edit") .
                        '</button>';

                    if (!empty($row->exp_date)) {
                        $carbon_exp = \Carbon::createFromFormat('Y-m-d', $row->exp_date);
                        $carbon_now = \Carbon::now();
                        if ($carbon_now->diffInDays($carbon_exp, false) < 0) {
                            $html .=  ' <button type="button" class="btn btn-warning btn-xs remove_from_stock_btn" data-href="' . action('StockAdjustmentController@removeExpiredStock', [$row->purchase_line_id]) . '"> <i class="fa fa-trash"></i> ' . __("lang_v1.remove_from_stock") .
                                '</button>';
                        }
                    }

                    return $html;
                })
                ->rawColumns(['exp_date', 'ref_no', 'edit', 'stock_left'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $brands = Brands::where('business_id', $business_id)
            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $view_stock_filter = [
            \Carbon::now()->subDay()->format('Y-m-d') => __('report.expired'),
            \Carbon::now()->addWeek()->format('Y-m-d') => __('report.expiring_in_1_week'),
            \Carbon::now()->addDays(15)->format('Y-m-d') => __('report.expiring_in_15_days'),
            \Carbon::now()->addMonth()->format('Y-m-d') => __('report.expiring_in_1_month'),
            \Carbon::now()->addMonths(3)->format('Y-m-d') => __('report.expiring_in_3_months'),
            \Carbon::now()->addMonths(6)->format('Y-m-d') => __('report.expiring_in_6_months'),
            \Carbon::now()->addYear()->format('Y-m-d') => __('report.expiring_in_1_year')
        ];

        return view('report.stock_expiry_report')
            ->with(compact('categories', 'brands', 'units', 'business_locations', 'view_stock_filter'));
    }

    /**
     * Shows product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockExpiryReportEditModal(Request $request, $purchase_line_id)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $purchase_line = PurchaseLine::join(
                'transactions as t',
                'purchase_lines.transaction_id',
                '=',
                't.id'
            )
                ->join(
                    'products as p',
                    'purchase_lines.product_id',
                    '=',
                    'p.id'
                )
                ->where('purchase_lines.id', $purchase_line_id)
                ->where('t.business_id', $business_id)
                ->select(['purchase_lines.*', 'p.name', 't.ref_no'])
                ->first();

            if (!empty($purchase_line)) {
                if (!empty($purchase_line->exp_date)) {
                    $purchase_line->exp_date = date('m/d/Y', strtotime($purchase_line->exp_date));
                }
            }

            return view('report.partials.stock_expiry_edit_modal')
                ->with(compact('purchase_line'));
        }
    }

    /**
     * Update product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function updateStockExpiryReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            //Return the details in ajax call
            if ($request->ajax()) {
                DB::beginTransaction();

                $input = $request->only(['purchase_line_id', 'exp_date']);

                $purchase_line = PurchaseLine::join(
                    'transactions as t',
                    'purchase_lines.transaction_id',
                    '=',
                    't.id'
                )
                    ->join(
                        'products as p',
                        'purchase_lines.product_id',
                        '=',
                        'p.id'
                    )
                    ->where('purchase_lines.id', $input['purchase_line_id'])
                    ->where('t.business_id', $business_id)
                    ->select(['purchase_lines.*', 'p.name', 't.ref_no'])
                    ->first();

                if (!empty($purchase_line) && !empty($input['exp_date'])) {
                    $purchase_line->exp_date = $this->productUtil->uf_date($input['exp_date']);
                    $purchase_line->save();
                }

                DB::commit();

                $output = [
                    'success' => 1,
                    'msg' => __('lang_v1.updated_succesfully')
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }

    /**
     * Shows product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function getCustomerGroup(Request $request)
    {
        if (!auth()->user()->can('contacts_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = Transaction::leftjoin('customer_groups AS CG', 'transactions.customer_group_id', '=', 'CG.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->groupBy('transactions.customer_group_id')
                ->select(DB::raw("SUM(final_total) as total_sell"), 'CG.name');

            $group_id = $request->get('customer_group_id', null);
            if (!empty($group_id)) {
                $query->where('transactions.customer_group_id', $group_id);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }


            return Datatables::of($query)
                ->editColumn('total_sell', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->total_sell . '</span>';
                })
                ->rawColumns(['total_sell'])
                ->make(true);
        }

        $customer_group = CustomerGroup::forDropdown($business_id, false, true);
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.customer_group')
            ->with(compact('customer_group', 'business_locations'));
    }

    /**
     * Shows product purchase report
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductPurchaseReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);

            $location_id = $request->get('location_id', null);

            $vld_str = '';
            if (!empty($location_id)) {
                $vld_str .= "AND vldd.location_id=$location_id ";
            }

            $start = $request->get('start_date');
            $end = $request->get('end_date');
            if (!empty($start) && !empty($end)) {
                $vld_str .= "AND Date(vldd.transfered_on)>='$start' AND Date(vldd.transfered_on)<='$end'";
                // dd($vld_str,$start,$end);
            }
            $query = $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                // ->join('sizes as s', 'p.sub_size_id', '=', 's.id')
                ->leftjoin('suppliers as c', 'p.supplier_id', '=', 'c.id')
                ->join('location_transfer_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('purchase_lines', 'p.id', '=', 'purchase_lines.product_id')
                ->join('business_locations as bl', 'vld.location_id', '=', 'bl.id')
                ->select(
                    'p.id as product_id',
                    'p.name as product_name',
                    'p.image as image',
                    'p.type as product_type',
                    'c.name as supplier',
                    // 's.name as size',
                    // 't.id as transaction_id',
                    'p.refference as ref_no',
                    'vld.transfered_on as transaction_date',
                    'vld.transfered_from as transfered_from_id',
                    'bl.name as location_name',
                    'purchase_lines.purchase_price_inc_tax as unit_purchase_price',
                    // 'vld.quantity as purchase_qty',
                    DB::raw("(SELECT bls.name FROM business_locations as bls WHERE vld.transfered_from=bls.id) as transfered_from"),
                    // DB::raw("(SUM(vld.quantity)) as purchase_qty"),
                    DB::raw("(SELECT SUM(vldd.quantity)  FROM location_transfer_details as vldd WHERE vldd.product_refference=p.refference $vld_str) as purchase_qty"),
                    // DB::raw('(SUM(vld.quantity) - purchase_lines.quantity_returned) as purchase_qty'),
                    'variations.default_purchase_price as purchase_price',
                    'purchase_lines.quantity_adjusted',
                    'variations.default_purchase_price as subtotal'
                    // DB::raw('( SUM(vld.quantity) * variations.default_purchase_price) as subtotal')
                )
                // ->distinct('p.refference') 
                ->orderBy('vld.transfered_on', 'DESC')
                ->distinct('ref_no')
                // ->groupBy('p.refference');
                ->groupBy('vld.product_refference');
            // ->groupBy('purchase_lines.product_id');


            if (!empty($variation_id)) {
                $query->where('purchase_lines.variation_id', $variation_id);
            }
            $start = $request->get('start_date');
            $end = $request->get('end_date');
            if (!empty($start) && !empty($end)) {
                // dd($start,$end);
                // $query->whereDate('vld.transfered_on', '>=', $start)
                // ->whereDate('vld.transfered_on', '<=', $end);
                $query->whereBetween(DB::raw('date(vld.transfered_on)'), [$start, $end]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('vld.location_id', $location_id);
            }
            $transfered_from = $request->get('transfered_from', null);
            if (!empty($transfered_from)) {
                $query->where('vld.transfered_from', $transfered_from);
            }

            $supplier_id = $request->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $query->where('p.supplier_id', $supplier_id);
            }

            // dd($query->toSql());
            return Datatables::of($query)
                ->addIndexColumn()
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('ref_no', function ($row) {
                    return $row->ref_no;

                    //Below is for MODAL Popup
                    // return '<a data-href="' . action('PurchaseController@show', [$row->product_id])
                    //     . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->ref_no . '</a>';
                })
                ->editColumn('image', function ($row) {
                    $product = Product::find($row->product_id);
                    $url = url("/products/view/") . '/';
                    if (!empty($product->image) && !is_null($product->image)) {
                        return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="' . $url . $row->product()->first()->id . '"></div>';
                        // return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    } else {
                        return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="data-href="{{url("/products/view/".$row->product()->first()->id)}}"></div>';
                        // return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    }
                })
                ->editColumn('purchase_qty', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency quantity_adjusted" data-currency_symbol=false data-orig-value="' . (float) $row->purchase_qty . '" data-unit="' . $row->unit . '" >' . (float) $row->purchase_qty . '</span> ' . $row->unit;
                })
                ->editColumn('quantity_adjusted', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency quantity_adjusted" data-currency_symbol=false data-orig-value="' . (float) $row->quantity_adjusted . '" data-unit="' . $row->unit . '" >' . (float) $row->quantity_adjusted . '</span> ' . $row->unit;
                })
                // ->editColumn('transfered_from',function($row){
                //     return $row->location_transfer_detail()->first();
                // })
                ->editColumn('subtotal', function ($row) {
                    return '<span class="display_currency row_subtotal" data-currency_symbol=true data-orig-value="' . $row->purchase_qty * $row->subtotal . '">' . $row->purchase_qty * $row->subtotal . '</span>';
                })
                ->editColumn('purchase_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol=true data-orig-value="' . $row->purchase_price . '">' . $row->purchase_price . '</span>';
                })
                ->editColumn('transaction_date', function ($row) {
                    return  Carbon::parse($row->transaction_date)->format('d-m-Y H:i');
                })
                ->editColumn('location_name', function ($row) {
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                ->editColumn('unit_purchase_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_purchase_price . '</span>';
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        // if (auth()->user()->can("product.view")) {
                        return  action('ProductController@view', [$row->product_id]);
                        // } else {
                        //     return '';
                        // }
                    }
                ])
                ->rawColumns(['purchase_price', 'ref_no', 'unit_purchase_price', 'subtotal', 'purchase_qty', 'quantity_adjusted', 'image'])
                ->make(true);
        }

        $business_locations = BusinessLocation::notMainForDropdown($business_id);
        $business_locations_all = BusinessLocation::forDropdown($business_id);
        $suppliers = Supplier::forDropdown($business_id);
        // $suppliers = Contact::suppliersDropdown($business_id);

        return view('report.product_purchase_report')
            ->with(compact('business_locations', 'suppliers', 'business_locations_all'));
    }
    public function working_getproductPurchaseReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);

            $location_id = $request->get('location_id', null);

            $vld_str = '';
            if (!empty($location_id)) {
                $vld_str = "AND vldd.location_id=$location_id";
            }

            // $start = $request->get('start_date');
            // $end = $request->get('end_date');
            // if (!empty($start) && !empty($end)) {
            //     $vld_str .= "AND Date(vldd.transfered_on)>='$start' AND Date(vldd.transfered_on)<='$end'";
            //     // dd($vld_str,$start,$end);
            // }
            $query = $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join('sizes as s', 'p.sub_size_id', '=', 's.id')
                ->leftjoin('suppliers as c', 'p.supplier_id', '=', 'c.id')
                // ->join('categories', 'p.category_id', '=', 'categories.id')
                // ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->join('location_transfer_details as vld', 'variations.id', '=', 'vld.variation_id')
                // ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                // ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->join('purchase_lines', 'p.id', '=', 'purchase_lines.product_id')
                ->join('business_locations as bl', 'vld.location_id', '=', 'bl.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable'])
                ->select(
                    'p.id as product_id',
                    'p.name as product_name',
                    'p.image as image',
                    'p.type as product_type',
                    'c.name as supplier',
                    's.name as size',
                    // 't.id as transaction_id',
                    'p.refference as ref_no',
                    'vld.transfered_on as transaction_date',
                    'vld.transfered_from as transfered_from_id',
                    'bl.name as location_name',
                    'purchase_lines.purchase_price_inc_tax as unit_purchase_price',
                    // 'vld.quantity as purchase_qty',
                    DB::raw("(SELECT bls.name FROM business_locations as bls WHERE vld.transfered_from=bls.id) as transfered_from"),
                    // DB::raw("(SUM(vld.quantity)) as purchase_qty"),
                    DB::raw("(SELECT SUM(vldd.quantity)  FROM location_transfer_details as vldd WHERE vldd.product_id=vld.product_id $vld_str) as purchase_qty"),
                    // DB::raw('(SUM(vld.quantity) - purchase_lines.quantity_returned) as purchase_qty'),
                    'variations.default_purchase_price as purchase_price',
                    'purchase_lines.quantity_adjusted',
                    'variations.default_purchase_price as subtotal'
                    // DB::raw('( SUM(vld.quantity) * variations.default_purchase_price) as subtotal')
                )
                // ->distinct('p.refference') 
                ->orderBy('vld.transfered_on', 'DESC')
                // ->distinct('ref_no')
                // ->groupBy('p.refference');
                ->groupBy('vld.product_id');
            // ->groupBy('purchase_lines.product_id');


            if (!empty($variation_id)) {
                $query->where('purchase_lines.variation_id', $variation_id);
            }
            $start = $request->get('start_date');
            $end = $request->get('end_date');
            if (!empty($start) && !empty($end)) {
                // dd($start,$end);
                // $query->whereDate('vld.transfered_on', '>=', $start)
                // ->whereDate('vld.transfered_on', '<=', $end);
                $query->whereBetween(DB::raw('date(vld.transfered_on)'), [$start, $end]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('vld.location_id', $location_id);
            }
            $transfered_from = $request->get('transfered_from', null);
            if (!empty($transfered_from)) {
                $query->where('vld.transfered_from', $transfered_from);
            }

            $supplier_id = $request->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $query->where('p.supplier_id', $supplier_id);
            }

            return Datatables::of($query)
                ->addIndexColumn()
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('ref_no', function ($row) {
                    return $row->ref_no;

                    //Below is for MODAL Popup
                    // return '<a data-href="' . action('PurchaseController@show', [$row->product_id])
                    //     . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->ref_no . '</a>';
                })
                ->editColumn('image', function ($row) {
                    $product = Product::find($row->product_id);
                    $url = url("/products/view/") . '/';
                    if (!empty($product->image) && !is_null($product->image)) {
                        return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="' . $url . $row->product()->first()->id . '"></div>';
                        // return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    } else {
                        return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="data-href="{{url("/products/view/".$row->product()->first()->id)}}"></div>';
                        // return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    }
                })
                ->editColumn('purchase_qty', function ($row) {
                    return '<span data-is_quantity="true" class=" purchase_qty" data-currency_symbol=false>' . (int) $row->purchase_qty . '</span> ' . $row->unit;
                })
                ->editColumn('quantity_adjusted', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency quantity_adjusted" data-currency_symbol=false data-orig-value="' . (float) $row->quantity_adjusted . '" data-unit="' . $row->unit . '" >' . (float) $row->quantity_adjusted . '</span> ' . $row->unit;
                })
                // ->editColumn('transfered_from',function($row){
                //     return $row->location_transfer_detail()->first();
                // })
                ->editColumn('subtotal', function ($row) {
                    return '<span class="display_currency row_subtotal" data-currency_symbol=true data-orig-value="' . $row->purchase_qty * $row->subtotal . '">' . $row->purchase_qty * $row->subtotal . '</span>';
                })
                ->editColumn('purchase_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol=true data-orig-value="' . $row->purchase_price . '">' . $row->purchase_price . '</span>';
                })
                ->editColumn('transaction_date', function ($row) {
                    return  Carbon::parse($row->transaction_date)->format('d-m-Y H:i');
                })
                ->editColumn('location_name', function ($row) {
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                ->editColumn('unit_purchase_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_purchase_price . '</span>';
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        // if (auth()->user()->can("product.view")) {
                        return  action('ProductController@view', [$row->product_id]);
                        // } else {
                        //     return '';
                        // }
                    }
                ])
                ->rawColumns(['purchase_price', 'ref_no', 'unit_purchase_price', 'subtotal', 'purchase_qty', 'quantity_adjusted', 'image'])
                ->make(true);
        }

        $business_locations = BusinessLocation::notMainForDropdown($business_id);
        $business_locations_all = BusinessLocation::forDropdown($business_id);
        $suppliers = Supplier::forDropdown($business_id);
        // $suppliers = Contact::suppliersDropdown($business_id);

        return view('report.product_purchase_report')
            ->with(compact('business_locations', 'suppliers', 'business_locations_all'));
    }
    public function old_getproductPurchaseReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = PurchaseLine::join(
                'transactions as t',
                'purchase_lines.transaction_id',
                '=',
                't.id'
            )
                ->join(
                    'variations as v',
                    'purchase_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('contacts as c', 't.contact_id', '=', 'c.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'purchase')
                ->select(
                    'p.name as product_name',
                    'p.type as product_type',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'c.name as supplier',
                    't.id as transaction_id',
                    't.ref_no',
                    't.transaction_date as transaction_date',
                    'purchase_lines.purchase_price_inc_tax as unit_purchase_price',
                    DB::raw('(purchase_lines.quantity - purchase_lines.quantity_returned) as purchase_qty'),
                    'purchase_lines.quantity_adjusted',
                    'u.short_name as unit',
                    DB::raw('((purchase_lines.quantity - purchase_lines.quantity_returned - purchase_lines.quantity_adjusted) * purchase_lines.purchase_price_inc_tax) as subtotal')
                )
                ->groupBy('purchase_lines.id');
            if (!empty($variation_id)) {
                $query->where('purchase_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $supplier_id = $request->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $query->where('t.contact_id', $supplier_id);
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('ref_no', function ($row) {
                    return '<a data-href="' . action('PurchaseController@show', [$row->transaction_id])
                        . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->ref_no . '</a>';
                })
                ->editColumn('purchase_qty', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency purchase_qty" data-currency_symbol=false data-orig-value="' . (float) $row->purchase_qty . '" data-unit="' . $row->unit . '" >' . (float) $row->purchase_qty . '</span> ' . $row->unit;
                })
                ->editColumn('quantity_adjusted', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency quantity_adjusted" data-currency_symbol=false data-orig-value="' . (float) $row->quantity_adjusted . '" data-unit="' . $row->unit . '" >' . (float) $row->quantity_adjusted . '</span> ' . $row->unit;
                })
                ->editColumn('subtotal', function ($row) {
                    return '<span class="display_currency row_subtotal" data-currency_symbol=true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('unit_purchase_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_purchase_price . '</span>';
                })
                ->rawColumns(['ref_no', 'unit_purchase_price', 'subtotal', 'purchase_qty', 'quantity_adjusted'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $suppliers = Contact::suppliersDropdown($business_id);

        return view('report.product_purchase_report')
            ->with(compact('business_locations', 'suppliers'));
    }

    /**
     * Shows product purchase report
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductSellReport(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 180); //3 minutes
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);

            $location_id = $request->get('location_id', null);

            $vld_str = '';
            if (!empty($location_id)) {
                $vld_str = "AND vld.location_id=$location_id";
            }

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
                ->leftjoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
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
                    DB::raw('((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
                )
                ->orderBy('p.name', 'ASC')
                // ->orderBy('t.invoice_no','DESC')
                ->groupBy('transaction_sell_lines.id');
            // dd($query->first());
            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }

            $purchase_start_date = $request->get('purchase_start_date');
            $purchase_end_date = $request->get('purchase_end_date');

            if (!empty($purchase_start_date) && !empty($purchase_end_date)) {
                $query->whereBetween(DB::raw('date(product_updated_at)'), [$purchase_start_date, $purchase_end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            $supplier_id = $request->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $query->where('p.supplier_id', $supplier_id);
            }
            $category_id = $request->get('category_id', null);
            if (!empty($category_id)) {
                $query->where('p.category_id', $category_id);
            }
            $sub_category_id = $request->get('sub_category_id', null);
            if (!empty($sub_category_id)) {
                $query->where('p.sub_category_id', $sub_category_id);
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('product_updated_at', function ($row) {
                    return Carbon::parse($row->product_updated_at)->format('d-M-Y H:i');
                })
                ->addColumn('size', function ($row) {
                    if (isset($row->product()->first()->sub_size()->first()['name']) && $row->product()->first()->sub_size()->first()['name']) {
                        return $row->product()->first()->sub_size()->first()['name'];
                    } else {
                        return 'T.U';
                    }
                })
                ->editColumn('invoice_no', function ($row) {
                    return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                        . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
                })
                ->editColumn('image', function ($row) {
                    return '<div style="display: flex;"><img src="' . $row->image_url . '" alt="Product image" class="product-thumbnail-small"></div>';
                })
                ->editColumn('refference', function ($row) {
                    if ($row->refference) {
                        return $row->refference;
                    } else {
                        return '<b class="text-center">-</b>';
                    }
                })
                ->editColumn('supplier_id', function ($row) {
                    if ($row->product()->first()->supplier()->first()) {
                        return $row->product()->first()->supplier()->first()['name'];
                    } else {
                        return '-';
                    }
                })
                // ->editColumn('product_updated_at', function($row){
                //     return Carbon::parse($row->product_updated_at)->format('d-M-Y H:i');
                // })
                ->editColumn('transaction_date', function ($row) {
                    return Carbon::parse($row->transaction_date)->format('d-M-Y H:i');
                })
                ->editColumn('unit_sale_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_sale_price . '</span>';
                })
                ->editColumn('sell_qty', function ($row) {
                    return '<span  class="sell_qty" data-currency_symbol=false data-orig-value="' . (int)$row->sell_qty . '" data-unit="' . $row->unit . '" >' . (int) $row->sell_qty . '</span> ' . $row->unit;
                })
                ->editColumn('subtotal', function ($row) {
                    return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                })
                ->editColumn('total_sold', function ($row) {
                    return '<span  class="total_sold" data-currency_symbol=false data-orig-value="' . (int)$row->total_sold . '" data-unit="' . $row->unit . '" >' . (int) $row->total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('unit_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_price . '</span>';
                })
                ->editColumn('original_amount', function ($row) {
                    if ($row->original_amount) {
                        return '<span class="original_amount display_currency" data-currency_symbol = true>' . $row->original_amount . '</span>';
                    } else {
                        return '-';
                    }
                })
                ->editColumn('discount_amount', '
                    @if($discount_type == "percentage")
                        {{@number_format($discount_amount)}} %
                    @elseif($discount_type == "fixed")
                        {{@number_format($discount_amount)}}
                    @endif
                    ')
                ->editColumn('tax', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' .
                        $row->item_tax .
                        '</span>' . '<br>' . '<span class="tax" data-orig-value="' . (float)$row->item_tax . '" data-unit="' . $row->tax . '"><small>(' . $row->tax . ')</small></span>';
                })
                ->editColumn('current_stock', function ($row) {
                    // if ($row->enable_stock) {
                    return '<span data-is_quantity="true" class="display_currency current_stock" data-currency_symbol=false data-orig-value="' . (int) $row->current_stock . '" data-unit="' . $row->unit . '" >' . (int) $row->current_stock . '</span> ' . $row->unit;
                    // } else {
                    //     return '';
                    // }
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("product.view")) {
                            return  action('ProductController@view', [$row->product_id]);
                        } else {
                            return '';
                        }
                    }
                ])
                ->rawColumns(['original_amount', 'refference', 'image', 'invoice_no', 'unit_sale_price', 'subtotal', 'sell_qty', 'discount_amount', 'unit_price', 'tax', 'current_stock', 'total_sold'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id);
        $suppliers = Supplier::forDropdown($business_id);
        $categories = Category::where('parent_id', 0)->pluck('name', 'id');
        $sub_categories = [];
        if (!empty($request->category_id)) {
            $sub_categories = [];
            $sub_categories[] = Category::where('parent_id', $request->category_id)
                ->pluck('name', 'id')
                ->toArray();
        }
        return view('report.product_sell_report')
            ->with(compact('business_locations', 'customers', 'suppliers', 'categories', 'sub_categories'));
    }

    // test sub function

    public function generateSoldSubquery($location_filter, $dateInterval, $alias)
    {

        return DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
        AND TSL.product_refference = p.refference AND transactions.transaction_date > CURDATE() - INTERVAL $dateInterval day) as $alias");
    }


    /**
     * Shows product sell report grouped by date
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductSellGroupedReport(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 280); //3 minutes
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);

        $location_filter = '';
        if (!empty($location_id)) {
            $location_filter = "AND transactions.location_id=$location_id";
            // $location_filter = "AND t.location_id=$location_id";
        }

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }
        // $start_date = $request->get('start_date');
        // $end_date = $request->get('end_date');
        if ($request->ajax()) {
            // $sevenDaySoldSubquery = $this->generateSoldSubquery($location_filter, 6, 'seven_day_sold');
            // $fifteenDaySoldSubquery = $this->generateSoldSubquery($location_filter, 14, 'fifteen_day_sold');
            // $allTimeSoldSubquery = $this->generateSoldSubquery($location_filter,null , 'all_time_sold');
            $sevenDaySoldSubquery = $this->generateSoldSubquery($location_filter, 6, 'seven_day_sold');
            $fifteenDaySoldSubquery = $this->generateSoldSubquery($location_filter, 14, 'fifteen_day_sold');


            $variation_id = $request->get('variation_id', null);
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
                // ->rightjoin('variation_location_details as vlds', 'v.id', '=', 'vlds.variation_id')
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                // ->join('suppliers as s', 'p.supplier_id', '=', 's.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                // $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
                $query->whereDate('t.transaction_date', '>=', $start_date)->whereDate('t.transaction_date', '<=', $end_date);
            }
            // Usage


            $query->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.image as image',
                'p.refference as refference',
                'p.sku as barcode',
                'p.supplier_id as supplier',
                'p.enable_stock',
                'p.type as product_type',
                'pv.name as product_variation',
                'v.name as variation_name',
                't.id as transaction_id',
                't.transaction_date as transaction_date',
                'transaction_sell_lines.unit_price_before_discount as unit_price',

                DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.product_refference=p.refference  $vld_str) as current_stock"),
                // DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) FROM transaction_sell_lines WHERE transaction_sell_lines.product_refference = p.refference) as all_time_sold"),
                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND TSL.product_refference = p.refference) as all_time_sold "),
                // DB::raw($allTimeSoldSubquery),
                DB::raw($sevenDaySoldSubquery),
                DB::raw($fifteenDaySoldSubquery),

                // DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                //     JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                //     WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                //     AND TSL.product_refference = p.refference AND transactions.transaction_date > CURDATE() - INTERVAL 6 day) as seven_day_sold"),

                // DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                //     JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                //     WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                //     AND TSL.product_refference = p.refference AND TSL.updated_at > CURDATE() - INTERVAL 14 day) as fifteen_day_sold"),

                // DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned)  FROM transaction_sell_lines WHERE transaction_sell_lines.product_refference = p.refference $location_filter AND transaction_sell_lines.updated_at > now() - INTERVAL 6 day) as seven_day_sold"),
                // DB::raw("(SELECT SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned)  FROM transaction_sell_lines WHERE transaction_sell_lines.product_refference = p.refference AND transaction_sell_lines.updated_at > now() - INTERVAL 15 day) as fifteen_day_sold"),
                // DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as current_stock"),
                DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold'),
                DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_refference = p.refference) as total_sold"),
                DB::raw('DATE_FORMAT(p.product_updated_at, "%Y-%m-%d %H:%i:%s") as product_updated_at'),
                // 'p.product_updated_at as product_updated_at',
                'u.short_name as unit',
                DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
            )


                // ->groupBy('v.id')
                ->orderBy('total_qty_sold', 'DESC')
                ->groupBy('transaction_sell_lines.product_refference');
            // ->groupBy('formated_date');

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }


            // $start_date = $request->get('start_date');
            // $end_date = $request->get('end_date');
            // if (!empty($start_date) && !empty($end_date)) {
            //     // $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            //     $query->whereDate('t.transaction_date', '>=', $start_date)->whereDate('t.transaction_date', '<=', $end_date);
            // }

            $purchase_start_date = $request->get('purchase_start_date');
            $purchase_end_date = $request->get('purchase_end_date');

            if (!empty($purchase_start_date) && !empty($purchase_end_date)) {
                $query->whereBetween(DB::raw('date(p.product_updated_at)'), [$purchase_start_date, $purchase_end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $supplier_id = $request->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $query->where('p.supplier_id', $supplier_id);
            }
            $category_id = $request->get('category_id', null);
            if (!empty($category_id)) {
                $query->where('p.category_id', $category_id);
            }
            $sub_category_id = $request->get('sub_category_id', null);
            if (!empty($sub_category_id)) {
                $query->where('p.sub_category_id', $sub_category_id);
            }
            return Datatables::of($query)
                ->editColumn('refference', function ($row) {
                    if ($row->refference) {
                        return $row->refference;
                    } else {
                        return '<span>-</span>';
                    }
                })
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('transaction_date', '{{@format_date($formated_date)}}')
                ->editColumn('total_qty_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (int) $row->total_qty_sold . '" data-unit="' . $row->unit . '" >' . (int) $row->total_qty_sold . '</span> ' . $row->unit;
                })
                ->editColumn('image', function ($row) {
                    return '<div style="display: flex;"><img src="' . $row->image_url . '" alt="Product image" class="product-thumbnail-small"></div>';
                })
                ->editColumn('product_updated_at', function ($row) {
                    return Carbon::parse($row->product_updated_at)->format('d-M-Y H:i');
                })
                ->editColumn('unit_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_price . '</span>';
                })
                ->editColumn('total_sold', function ($row) {
                    return '<span  class="total_sold" data-currency_symbol=false data-orig-value="' . (int)$row->total_sold . '" data-unit="' . $row->unit . '" >' . (int) $row->total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('all_time_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency all_time_sold" data-currency_symbol=false data-orig-value="' . (int)$row->all_time_sold  . '" data-unit="' . $row->unit . '" >' . (int) $row->all_time_sold . '</span> ' . $row->unit;
                })
                ->editColumn('seven_day_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency seven_day_sold" data-currency_symbol=false data-orig-value="' . (int)$row->seven_day_sold . '" data-unit="' . $row->unit . '" >' . (int) $row->seven_day_sold . '</span> ' . $row->unit;
                })
                ->editColumn('fifteen_day_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency fifteen_day_sold" data-currency_symbol=false data-orig-value="' . (int)$row->fifteen_day_sold . '" data-unit="' . $row->unit . '" >' . (int) $row->fifteen_day_sold . '</span> ' . $row->unit;
                })
                ->addColumn('all_time_purchased', function ($row) {
                    return '<span  class="total_sold" data-currency_symbol=false data-orig-value="' . ((int)$row->all_time_sold + (int)$row->current_stock) . '" data-unit="' . $row->unit . '" >' . ((int)$row->all_time_sold + (int)$row->current_stock) . '</span> ' . $row->unit;
                })
                ->editColumn('current_stock', function ($row) {
                    if ($row->enable_stock) {
                        return '<span data-is_quantity="true" class="display_currency current_stock" data-currency_symbol=false data-orig-value="' . (float) $row->current_stock . '" data-unit="' . $row->unit . '" >' . (float) $row->current_stock . '</span> ' . $row->unit;
                    } else {
                        return '-';
                    }
                })
                ->addColumn('detail', function ($row) {
                    $start_date = request()->get('start_date');
                    $end_date = request()->get('end_date');
                    // return '<a id="color-detail-modal" href="' . url("/product/color-detail/" . $row->product_name . '/' . $start_date . '/' . $end_date) . '" data-product-name="' . $row->product_name . '" class="btn btn-primary btn-sm">Color Report <i class="fa fa-eye"></i></a>';
                    return '<a  href="' . url("/product/color-detail/" . $row->product_name . '/' . $start_date . '/' . $end_date) . '" target="_blank" data-product-name="' . $row->product_name . '" class="btn btn-primary btn-sm">Color Report <i class="fa fa-eye"></i></a>';
                })
                ->addColumn('sale_percentage', function ($row) {
                    if ($row->refference && ($row->total_qty_sold > 0 || $row->current_stock > 0)) {
                        $sum = $row->total_qty_sold + $row->current_stock;
                        if ($sum) {
                            $percentage = ($row->total_qty_sold * 100) / $sum;
                            return (int)$percentage . ' %';
                        } else {

                            return '0 %';
                        }
                    } else {
                        return '-';
                    }
                })
                ->editColumn('subtotal', function ($row) {
                    return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("product.view")) {
                            return  action('ProductController@viewProductRefDetailWithSale', [$row->refference]);
                            // return  action('ProductController@view', [$row->product_id]);
                        } else {
                            return '';
                        }
                    }
                ])
                ->rawColumns(['image', 'total_sold', 'unit_price', 'current_stock', 'subtotal', 'total_qty_sold', 'detail', 'refference', 'all_time_purchased', 'all_time_sold', 'seven_day_sold', 'fifteen_day_sold'])
                ->make(true);
        }
    }

    //Changes Function
    /* public function getproductSellReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
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
                ->join('business_locations as bl', 'bl.id', '=', 't.location_id')
                // ->rightjoin('sizes as s', 's.id', '=', 'p.sub_size_id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->leftjoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                // ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                // ->join('contacts as c', 't.contact_id', '=', 'c.id')
                // ->join('products as p', 'pv.product_id', '=', 'p.id')
                // ->join('sizes as s', 's.id', '=', 'p.sub_size_id')
                // ->join('business_locations as bl', 'bl.id', '=', 't.location_id')
                // ->leftjoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
                // ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                // // ->where('t.business_id', $business_id)
                // ->where('t.type', 'sell')
                // ->where('t.status', 'final')
                ->select(
                    'p.name as product_name',
                    'p.image as image',
                    'p.type as product_type',
                    // 'p.refference as product_reffernce',
                    'p.sku as product_barcode',
                    'bl.name as location_name',
                    // 's.name as product_size',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'c.name as customer',
                    't.id as transaction_id',
                    't.invoice_no',
                    't.transaction_date as transaction_date',
                    'transaction_sell_lines.unit_price_before_discount as unit_price',
                    'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                    DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
                    'transaction_sell_lines.line_discount_type as discount_type',
                    'transaction_sell_lines.line_discount_amount as discount_amount',
                    'transaction_sell_lines.item_tax',
                    'tax_rates.name as tax',
                    'u.short_name as unit',
                    DB::raw('((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
                )
                ->groupBy('transaction_sell_lines.id');

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('image', function ($row) {
                    return '<div style="display: flex;"><img src="' . $row->image_url . '" alt="Product image" class="product-thumbnail-small"></div>';
                })
                ->editColumn('invoice_no', function ($row) {
                    return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                        . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
                })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('unit_sale_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_sale_price . '</span>';
                })
                ->editColumn('sell_qty', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float) $row->sell_qty . '" data-unit="' . $row->unit . '" >' . (float) $row->sell_qty . '</span> ' . $row->unit;
                })
                ->editColumn('subtotal', function ($row) {
                    return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                })
                ->editColumn('unit_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_price . '</span>';
                })
                ->editColumn('discount_amount', '
                    @if($discount_type == "percentage")
                        {{@number_format($discount_amount)}} %
                    @elseif($discount_type == "fixed")
                        {{@number_format($discount_amount)}}
                    @endif
                    ')
                ->editColumn('tax', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' .
                        $row->item_tax .
                        '</span>' . '<br>' . '<span class="tax" data-orig-value="' . (float) $row->item_tax . '" data-unit="' . $row->tax . '"><small>(' . $row->tax . ')</small></span>';
                })
                ->rawColumns(['image','invoice_no', 'unit_sale_price', 'subtotal', 'sell_qty', 'discount_amount', 'unit_price', 'tax'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id);

        return view('report.product_sell_report')
            ->with(compact('business_locations', 'customers'));
    } */

    /**
     * Shows product lot report
     *
     * @return \Illuminate\Http\Response
     */
    public function getLotReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Product::where('products.business_id', $business_id)
                ->leftjoin('units', 'products.unit_id', '=', 'units.id')
                ->join('variations as v', 'products.id', '=', 'v.product_id')
                ->join('purchase_lines as pl', 'v.id', '=', 'pl.variation_id')
                ->leftjoin(
                    'transaction_sell_lines_purchase_lines as tspl',
                    'pl.id',
                    '=',
                    'tspl.purchase_line_id'
                )
                ->join('transactions as t', 'pl.transaction_id', '=', 't.id');

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = 'WHERE ';

            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter = " LEFT JOIN transactions as t2 on pls.transaction_id=t2.id WHERE t2.location_id IN ($locations_imploded) AND ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');
                $query->where('t.location_id', $location_id);

                $location_filter = "LEFT JOIN transactions as t2 on pls.transaction_id=t2.id WHERE t2.location_id=$location_id AND ";
            }

            if (!empty($request->input('category_id'))) {
                $query->where('products.category_id', $request->input('category_id'));
            }

            if (!empty($request->input('sub_category_id'))) {
                $query->where('products.sub_category_id', $request->input('sub_category_id'));
            }

            if (!empty($request->input('brand_id'))) {
                $query->where('products.brand_id', $request->input('brand_id'));
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('products.unit_id', $request->input('unit_id'));
            }

            $products = $query->select(
                'products.name as product',
                'v.name as variation_name',
                'sub_sku',
                'pl.lot_number',
                'pl.exp_date as exp_date',
                DB::raw("( COALESCE((SELECT SUM(quantity - quantity_returned) from purchase_lines as pls $location_filter variation_id = v.id AND lot_number = pl.lot_number), 0) - 
                    SUM(COALESCE((tspl.quantity - tspl.qty_returned), 0))) as stock"),
                // DB::raw("(SELECT SUM(IF(transactions.type='sell', TSL.quantity, -1* TPL.quantity) ) FROM transactions
                //         LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id

                //         LEFT JOIN purchase_lines AS TPL ON transactions.id=TPL.transaction_id

                //         WHERE transactions.status='final' AND transactions.type IN ('sell', 'sell_return') $location_filter
                //         AND (TSL.product_id=products.id OR TPL.product_id=products.id)) as total_sold"),

                DB::raw("COALESCE(SUM(IF(tspl.sell_line_id IS NULL, 0, (tspl.quantity - tspl.qty_returned)) ), 0) as total_sold"),
                DB::raw("COALESCE(SUM(IF(tspl.stock_adjustment_line_id IS NULL, 0, tspl.quantity ) ), 0) as total_adjusted"),
                'products.type',
                'units.short_name as unit'
            )
                ->whereNotNull('pl.lot_number')
                ->groupBy('v.id')
                ->groupBy('pl.lot_number');

            return Datatables::of($products)
                ->editColumn('stock', function ($row) {
                    $stock = $row->stock ? $row->stock : 0;
                    return '<span data-is_quantity="true" class="display_currency total_stock" data-currency_symbol=false data-orig-value="' . (float) $stock . '" data-unit="' . $row->unit . '" >' . (float) $stock . '</span> ' . $row->unit;
                })
                ->editColumn('product', function ($row) {
                    if ($row->variation_name != 'DUMMY') {
                        return $row->product . ' (' . $row->variation_name . ')';
                    } else {
                        return $row->product;
                    }
                })
                ->editColumn('total_sold', function ($row) {
                    if ($row->total_sold) {
                        return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . (float) $row->total_sold . '" data-unit="' . $row->unit . '" >' . (float) $row->total_sold . '</span> ' . $row->unit;
                    } else {
                        return '0' . ' ' . $row->unit;
                    }
                })
                ->editColumn('total_adjusted', function ($row) {
                    if ($row->total_adjusted) {
                        return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false data-orig-value="' . (float) $row->total_adjusted . '" data-unit="' . $row->unit . '" >' . (float) $row->total_adjusted . '</span> ' . $row->unit;
                    } else {
                        return '0' . ' ' . $row->unit;
                    }
                })
                ->editColumn('exp_date', function ($row) {
                    if (!empty($row->exp_date)) {
                        $carbon_exp = \Carbon::createFromFormat('Y-m-d', $row->exp_date);
                        $carbon_now = \Carbon::now();
                        if ($carbon_now->diffInDays($carbon_exp, false) >= 0) {
                            return $this->productUtil->format_date($row->exp_date) . '<br><small>( <span class="time-to-now">' . $row->exp_date . '</span> )</small>';
                        } else {
                            return $this->productUtil->format_date($row->exp_date) . ' &nbsp; <span class="label label-danger no-print">' . __('report.expired') . '</span><span class="print_section">' . __('report.expired') . '</span><br><small>( <span class="time-from-now">' . $row->exp_date . '</span> )</small>';
                        }
                    } else {
                        return '--';
                    }
                })
                ->removeColumn('unit')
                ->removeColumn('id')
                ->removeColumn('variation_name')
                ->rawColumns(['exp_date', 'stock', 'total_sold', 'total_adjusted'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $brands = Brands::where('business_id', $business_id)
            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.lot_report')
            ->with(compact('categories', 'brands', 'units', 'business_locations'));
    }

    /**
     * Shows purchase payment report
     *
     * @return \Illuminate\Http\Response
     */
    public function purchasePaymentReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $supplier_id = $request->get('supplier_id', null);
            $contact_filter1 = !empty($supplier_id) ? "AND t.contact_id=$supplier_id" : '';
            $contact_filter2 = !empty($supplier_id) ? "AND transactions.contact_id=$supplier_id" : '';

            $location_id = $request->get('location_id', null);

            $parent_payment_query_part = empty($location_id) ? "AND transaction_payments.parent_id IS NULL" : "";

            $query = TransactionPayment::leftjoin('transactions as t', function ($join) use ($business_id) {
                $join->on('transaction_payments.transaction_id', '=', 't.id')
                    ->where('t.business_id', $business_id)
                    ->whereIn('t.type', ['purchase', 'opening_balance']);
            })
                ->where('transaction_payments.business_id', $business_id)
                ->where(function ($q) use ($business_id, $contact_filter1, $contact_filter2, $parent_payment_query_part) {
                    $q->whereRaw("(transaction_payments.transaction_id IS NOT NULL AND t.type IN ('purchase', 'opening_balance')  $parent_payment_query_part $contact_filter1)")
                        ->orWhereRaw("EXISTS(SELECT * FROM transaction_payments as tp JOIN transactions ON tp.transaction_id = transactions.id WHERE transactions.type IN ('purchase', 'opening_balance') AND transactions.business_id = $business_id AND tp.parent_id=transaction_payments.id $contact_filter2)");
                })

                ->select(
                    DB::raw("IF(transaction_payments.transaction_id IS NULL, 
                                (SELECT c.name FROM transactions as ts
                                JOIN contacts as c ON ts.contact_id=c.id 
                                WHERE ts.id=(
                                        SELECT tps.transaction_id FROM transaction_payments as tps
                                        WHERE tps.parent_id=transaction_payments.id LIMIT 1
                                    )
                                ),
                                (SELECT c.name FROM transactions as ts JOIN
                                    contacts as c ON ts.contact_id=c.id
                                    WHERE ts.id=t.id 
                                )
                            ) as supplier"),
                    'transaction_payments.amount',
                    'method',
                    'paid_on',
                    'transaction_payments.payment_ref_no',
                    'transaction_payments.document',
                    't.ref_no',
                    't.id as transaction_id',
                    'cheque_number',
                    'card_transaction_number',
                    'bank_account_number',
                    'transaction_no',
                    'transaction_payments.id as DT_RowId'
                )
                ->groupBy('transaction_payments.id');

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(paid_on)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            return Datatables::of($query)
                ->editColumn('ref_no', function ($row) {
                    if (!empty($row->ref_no)) {
                        return '<a data-href="' . action('PurchaseController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->ref_no . '</a>';
                    } else {
                        return '';
                    }
                })
                ->editColumn('paid_on', '{{@format_date($paid_on)}}')
                ->editColumn('method', function ($row) {
                    $method = __('lang_v1.' . $row->method);
                    if ($row->method == 'cheque') {
                        $method .= '<br>(' . __('lang_v1.cheque_no') . ': ' . $row->cheque_number . ')';
                    } elseif ($row->method == 'card') {
                        $method .= '<br>(' . __('lang_v1.card_transaction_no') . ': ' . $row->card_transaction_number . ')';
                    } elseif ($row->method == 'bank_transfer') {
                        $method .= '<br>(' . __('lang_v1.bank_account_no') . ': ' . $row->bank_account_number . ')';
                    } elseif ($row->method == 'custom_pay_1') {
                        $method = __('lang_v1.custom_payment_1') . '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    } elseif ($row->method == 'custom_pay_2') {
                        $method = __('lang_v1.custom_payment_2') . '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    } elseif ($row->method == 'custom_pay_3') {
                        $method = __('lang_v1.custom_payment_3') . '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    }
                    return $method;
                })
                ->editColumn('amount', function ($row) {
                    return '<span class="display_currency paid-amount" data-currency_symbol = true data-orig-value="' . $row->amount . '">' . $row->amount . '</span>';
                })
                ->addColumn('action', '<button type="button" class="btn btn-primary btn-xs view_payment" data-href="{{ action("TransactionPaymentController@viewPayment", [$DT_RowId]) }}">@lang("messages.view")
                    </button> @if(!empty($document))<a href="{{asset("/uploads/documents/" . $document)}}" class="btn btn-success btn-xs" download=""><i class="fa fa-download"></i> @lang("purchase.download_document")</a>@endif')
                ->rawColumns(['ref_no', 'amount', 'method', 'action'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $suppliers = Contact::suppliersDropdown($business_id, false);

        return view('report.purchase_payment_report')
            ->with(compact('business_locations', 'suppliers'));
    }

    /**
     * Shows sell payment report
     *
     * @return \Illuminate\Http\Response
     */
    public function sellPaymentReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $customer_id = $request->get('supplier_id', null);
            $contact_filter1 = !empty($customer_id) ? "AND t.contact_id=$customer_id" : '';
            $contact_filter2 = !empty($customer_id) ? "AND transactions.contact_id=$customer_id" : '';

            $location_id = $request->get('location_id', null);
            $parent_payment_query_part = empty($location_id) ? "AND transaction_payments.parent_id IS NULL" : "";

            $query = TransactionPayment::leftjoin('transactions as t', function ($join) use ($business_id) {
                $join->on('transaction_payments.transaction_id', '=', 't.id')
                    ->where('t.business_id', $business_id)
                    ->whereIn('t.type', ['sell', 'opening_balance']);
            })
                ->leftjoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->where('transaction_payments.business_id', $business_id)
                ->where(function ($q) use ($business_id, $contact_filter1, $contact_filter2, $parent_payment_query_part) {
                    $q->whereRaw("(transaction_payments.transaction_id IS NOT NULL AND t.type IN ('sell', 'opening_balance') $parent_payment_query_part $contact_filter1)")
                        ->orWhereRaw("EXISTS(SELECT * FROM transaction_payments as tp JOIN transactions ON tp.transaction_id = transactions.id WHERE transactions.type IN ('sell', 'opening_balance') AND transactions.business_id = $business_id AND tp.parent_id=transaction_payments.id $contact_filter2)");
                })
                ->select(
                    DB::raw("IF(transaction_payments.transaction_id IS NULL, 
                                (SELECT c.name FROM transactions as ts
                                JOIN contacts as c ON ts.contact_id=c.id 
                                WHERE ts.id=(
                                        SELECT tps.transaction_id FROM transaction_payments as tps
                                        WHERE tps.parent_id=transaction_payments.id LIMIT 1
                                    )
                                ),
                                (SELECT c.name FROM transactions as ts JOIN
                                    contacts as c ON ts.contact_id=c.id
                                    WHERE ts.id=t.id 
                                )
                            ) as customer"),
                    'transaction_payments.amount',
                    'method',
                    'paid_on',
                    'transaction_payments.payment_ref_no',
                    'transaction_payments.document',
                    't.invoice_no',
                    't.id as transaction_id',
                    'cheque_number',
                    'card_transaction_number',
                    'bank_account_number',
                    'transaction_payments.id as DT_RowId'
                )
                ->groupBy('transaction_payments.id');

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(paid_on)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }
            return Datatables::of($query)
                ->editColumn('invoice_no', function ($row) {
                    if (!empty($row->transaction_id)) {
                        return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
                    } else {
                        return '';
                    }
                })
                ->editColumn('paid_on', '{{@format_date($paid_on)}}')
                ->editColumn('method', function ($row) {
                    $method = __('lang_v1.' . $row->method);
                    if ($row->method == 'cheque') {
                        $method .= '<br>(' . __('lang_v1.cheque_no') . ': ' . $row->cheque_number . ')';
                    } elseif ($row->method == 'card') {
                        $method .= '<br>(' . __('lang_v1.card_transaction_no') . ': ' . $row->card_transaction_number . ')';
                    } elseif ($row->method == 'bank_transfer') {
                        $method .= '<br>(' . __('lang_v1.bank_account_no') . ': ' . $row->bank_account_number . ')';
                    } elseif ($row->method == 'custom_pay_1') {
                        $method = __('lang_v1.custom_payment_1') . '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    } elseif ($row->method == 'custom_pay_2') {
                        $method = __('lang_v1.custom_payment_2') . '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    } elseif ($row->method == 'custom_pay_3') {
                        $method = __('lang_v1.custom_payment_3') . '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    }
                    return $method;
                })
                ->editColumn('amount', function ($row) {
                    return '<span class="display_currency paid-amount" data-orig-value="' . $row->amount . '" data-currency_symbol = true>' . $row->amount . '</span>';
                })
                ->addColumn('action', '<button type="button" class="btn btn-primary btn-xs view_payment" data-href="{{ action("TransactionPaymentController@viewPayment", [$DT_RowId]) }}">@lang("messages.view")
                    </button> @if(!empty($document))<a href="{{asset("/uploads/documents/" . $document)}}" class="btn btn-success btn-xs" download=""><i class="fa fa-download"></i> @lang("purchase.download_document")</a>@endif')
                ->rawColumns(['invoice_no', 'amount', 'method', 'action'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id, false);

        return view('report.sell_payment_report')
            ->with(compact('business_locations', 'customers'));
    }


    /**
     * Shows tables report
     *
     * @return \Illuminate\Http\Response
     */
    public function getTableReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = ResTable::leftjoin('transactions AS T', 'T.res_table_id', '=', 'res_tables.id')
                ->where('T.business_id', $business_id)
                ->where('T.type', 'sell')
                ->where('T.status', 'final')
                ->groupBy('res_tables.id')
                ->select(DB::raw("SUM(final_total) as total_sell"), 'res_tables.name as table');

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('T.location_id', $location_id);
            }

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }

            return Datatables::of($query)
                ->editColumn('total_sell', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $row->total_sell . '</span>';
                })
                ->rawColumns(['total_sell'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.table_report')
            ->with(compact('business_locations'));
    }

    /**
     * Shows service staff report
     *
     * @return \Illuminate\Http\Response
     */
    public function getServiceStaffReport(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $waiters = $this->transactionUtil->serviceStaffDropdown($business_id);

        return view('report.service_staff_report')
            ->with(compact('business_locations', 'waiters'));
    }


    /**
     * Shows product stock details and allows to adjust mismatch
     *
     * @return \Illuminate\Http\Response
     */
    public function productStockDetails()
    {
        if (!auth()->user()->can('report.stock_details')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $stock_details = [];
        $location = null;
        $total_stock_calculated = 0;
        if (!empty(request()->input('location_id'))) {
            $variation_id = request()->get('variation_id', null);
            $location_id = request()->input('location_id');

            $location = BusinessLocation::where('business_id', $business_id)
                ->where('id', $location_id)
                ->first();

            $query = Variation::leftjoin('products as p', 'p.id', '=', 'variations.product_id')
                ->leftjoin('units', 'p.unit_id', '=', 'units.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->leftjoin('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->where('vld.location_id', $location_id);
            if (!is_null($variation_id)) {
                $query->where('variations.id', $variation_id);
            }

            $stock_details = $query->select(
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity, 0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity_returned, 0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_sell_return"),
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity,0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_sell_transfered"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity,0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='purchase_transfer' AND transactions.location_id=$location_id 
                        AND PL.variation_id=variations.id) as total_purchase_transfered"),
                DB::raw("(SELECT SUM(COALESCE(SAL.quantity, 0)) FROM transactions 
                        LEFT JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' AND transactions.location_id=$location_id 
                        AND SAL.variation_id=variations.id) as total_adjusted"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='purchase' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_purchased"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity_returned, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='purchase' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_purchase_return"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='opening_stock' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_opening_stock"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sub_sku',
                'p.name as product',
                'p.id as product_id',
                'p.type',
                'p.sku as sku',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                'variations.sell_price_inc_tax as unit_price',
                'pv.name as product_variation',
                'variations.name as variation_name',
                'variations.id as variation_id'
            )
                ->groupBy('variations.id')
                ->get();

            foreach ($stock_details as $index => $row) {
                $total_sold = $row->total_sold ?: 0;
                $total_sell_return = $row->total_sell_return ?: 0;
                $total_sell_transfered = $row->total_sell_transfered ?: 0;

                $total_purchase_transfered = $row->total_purchase_transfered ?: 0;
                $total_adjusted = $row->total_adjusted ?: 0;
                $total_purchased = $row->total_purchased ?: 0;
                $total_purchase_return = $row->total_purchase_return ?: 0;
                $total_opening_stock = $row->total_opening_stock ?: 0;

                $total_stock_calculated = $total_opening_stock + $total_purchased + $total_purchase_transfered + $total_sell_return
                    - ($total_sold + $total_sell_transfered + $total_adjusted + $total_purchase_return);

                $stock_details[$index]->total_stock_calculated = $total_stock_calculated;
            }
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        return view('report.product_stock_details')
            ->with(compact('stock_details', 'business_locations', 'location'));
    }

    /**
     * Adjusts stock availability mismatch if found
     *
     * @return \Illuminate\Http\Response
     */
    public function adjustProductStock()
    {
        if (!auth()->user()->can('report.stock_details')) {
            abort(403, 'Unauthorized action.');
        }

        if (
            !empty(request()->input('variation_id'))
            && !empty(request()->input('location_id'))
            && !empty(request()->input('stock'))
        ) {
            $business_id = request()->session()->get('user.business_id');

            $vld = VariationLocationDetails::leftjoin(
                'business_locations as bl',
                'bl.id',
                '=',
                'variation_location_details.location_id'
            )
                ->where('variation_location_details.location_id', request()->input('location_id'))
                ->where('variation_id', request()->input('variation_id'))
                ->where('bl.business_id', $business_id)
                ->select('variation_location_details.*')
                ->first();

            if (!empty($vld)) {
                $vld->qty_available = request()->input('stock');
                $vld->save();
            }
        }

        return redirect()->back()->with(['status' => [
            'success' => 1,
            'msg' => __('lang_v1.updated_succesfully')
        ]]);
    }

    /**
     * Retrieves line orders/sales
     *
     * @return obj
     */
    public function serviceStaffLineOrders()
    {
        $business_id = request()->session()->get('user.business_id');

        $query = TransactionSellLine::leftJoin('transactions as t', 't.id', '=', 'transaction_sell_lines.transaction_id')
            ->leftJoin('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->leftJoin('products as p', 'v.product_id', '=', 'p.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->leftJoin('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->leftJoin('users as ss', 'ss.id', '=', 'transaction_sell_lines.res_service_staff_id')
            ->leftjoin(
                'business_locations AS bl',
                't.location_id',
                '=',
                'bl.id'
            )
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNotNull('transaction_sell_lines.res_service_staff_id');


        if (!empty(request()->service_staff_id)) {
            $query->where('transaction_sell_lines.res_service_staff_id', request()->service_staff_id);
        }

        if (request()->has('location_id')) {
            $location_id = request()->get('location_id');
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }
        }

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start = request()->start_date;
            $end =  request()->end_date;
            $query->whereDate('t.transaction_date', '>=', $start)
                ->whereDate('t.transaction_date', '<=', $end);
        }

        $query->select(
            'p.name as product_name',
            'p.type as product_type',
            'v.name as variation_name',
            'pv.name as product_variation_name',
            'u.short_name as unit',
            't.id as transaction_id',
            'bl.name as business_location',
            't.transaction_date',
            't.invoice_no',
            'transaction_sell_lines.quantity',
            'transaction_sell_lines.unit_price_before_discount',
            'transaction_sell_lines.line_discount_type',
            'transaction_sell_lines.line_discount_amount',
            'transaction_sell_lines.item_tax',
            'transaction_sell_lines.unit_price_inc_tax',
            DB::raw('CONCAT(COALESCE(ss.first_name, ""), COALESCE(ss.last_name, "")) as service_staff')
        );

        $datatable = Datatables::of($query)
            ->editColumn('product_name', function ($row) {
                $name = $row->product_name;
                if ($row->product_type == 'variable') {
                    $name .= ' - ' . $row->product_variation_name . ' - ' . $row->variation_name;
                }
                return $name;
            })
            ->editColumn(
                'unit_price_inc_tax',
                '<span class="display_currency unit_price_inc_tax" data-currency_symbol="true" data-orig-value="{{$unit_price_inc_tax}}">{{$unit_price_inc_tax}}</span>'
            )
            ->editColumn(
                'item_tax',
                '<span class="display_currency item_tax" data-currency_symbol="true" data-orig-value="{{$item_tax}}">{{$item_tax}}</span>'
            )
            ->editColumn(
                'quantity',
                '<span class="display_currency quantity" data-unit="{{$unit}}" data-currency_symbol="false" data-orig-value="{{$quantity}}">{{$quantity}}</span> {{$unit}}'
            )
            ->editColumn(
                'unit_price_before_discount',
                '<span class="display_currency unit_price_before_discount" data-currency_symbol="true" data-orig-value="{{$unit_price_before_discount}}">{{$unit_price_before_discount}}</span>'
            )
            ->addColumn(
                'total',
                '<span class="display_currency total" data-currency_symbol="true" data-orig-value="{{$unit_price_inc_tax * $quantity}}">{{$unit_price_inc_tax * $quantity}}</span>'
            )
            ->editColumn(
                'line_discount_amount',
                function ($row) {
                    $discount = !empty($row->line_discount_amount) ? $row->line_discount_amount : 0;

                    if (!empty($discount) && $row->line_discount_type == 'percentage') {
                        $discount = $row->unit_price_before_discount * ($discount / 100);
                    }

                    return '<span class="display_currency total-discount" data-currency_symbol="true" data-orig-value="' . $discount . '">' . $discount . '</span>';
                }
            )
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')

            ->rawColumns(['line_discount_amount', 'unit_price_before_discount', 'item_tax', 'unit_price_inc_tax', 'item_tax', 'quantity', 'total'])
            ->make(true);

        return $datatable;
    }

    /**
     * Lists profit by product, category, brand, location, invoice and date
     *
     * @return string $by = null
     */
    public function getProfit($by = null)
    {
        $business_id = request()->session()->get('user.business_id');

        $query = TransactionSellLinesPurchaseLines::join('transaction_sell_lines 
                        as SL', 'SL.id', '=', 'transaction_sell_lines_purchase_lines.sell_line_id')
            ->join('transactions as sale', 'SL.transaction_id', '=', 'sale.id')
            ->join('purchase_lines as PL', 'PL.id', '=', 'transaction_sell_lines_purchase_lines.purchase_line_id')
            ->where('sale.business_id', $business_id);

        $query->select(DB::raw('SUM( 
                        (transaction_sell_lines_purchase_lines.quantity - transaction_sell_lines_purchase_lines.qty_returned) * (SL.unit_price_inc_tax - PL.purchase_price_inc_tax) ) as gross_profit'));

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start = request()->start_date;
            $end =  request()->end_date;
            $query->whereDate('sale.transaction_date', '>=', $start)
                ->whereDate('sale.transaction_date', '<=', $end);
        }

        if ($by == 'product') {
            $query->join('variations as V', 'SL.variation_id', '=', 'V.id')
                ->join('products as P', 'V.product_id', '=', 'P.id')
                ->leftJoin('product_variations as PV', 'PV.id', '=', 'V.product_variation_id')
                ->addSelect(DB::raw("IF(P.type='single', CONCAT(P.name, ' (', P.sku, ')'), CONCAT(P.name, ' - ', PV.name, ' - ', V.name, ' (', V.sub_sku, ')')) as product"))
                ->groupBy('V.id');
        }

        if ($by == 'category') {
            $query->join('variations as V', 'SL.variation_id', '=', 'V.id')
                ->join('products as P', 'V.product_id', '=', 'P.id')
                ->leftJoin('categories as C', 'C.id', '=', 'P.category_id')
                ->addSelect("C.name as category")
                ->groupBy('C.id');
        }

        if ($by == 'brand') {
            $query->join('variations as V', 'SL.variation_id', '=', 'V.id')
                ->join('products as P', 'V.product_id', '=', 'P.id')
                ->leftJoin('brands as B', 'B.id', '=', 'P.brand_id')
                ->addSelect("B.name as brand")
                ->groupBy('B.id');
        }

        if ($by == 'location') {
            $query->join('business_locations as L', 'sale.location_id', '=', 'L.id')
                ->addSelect("L.name as location")
                ->groupBy('L.id');
        }

        if ($by == 'invoice') {
            $query->addSelect('sale.invoice_no', 'sale.id as transaction_id')
                ->groupBy('sale.invoice_no');
        }

        if ($by == 'date') {
            $query->addSelect("sale.transaction_date")
                ->groupBy(DB::raw('DATE(sale.transaction_date)'));
        }

        if ($by == 'day') {
            $results = $query->addSelect(DB::raw("DAYNAME(sale.transaction_date) as day"))
                ->groupBy(DB::raw('DAYOFWEEK(sale.transaction_date)'))
                ->get();

            $profits = [];
            foreach ($results as $result) {
                $profits[strtolower($result->day)] = $result->gross_profit;
            }
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

            return view('report.partials.profit_by_day')->with(compact('profits', 'days'));
        }

        if ($by == 'customer') {
            $query->join('contacts as CU', 'sale.contact_id', '=', 'CU.id')
                ->addSelect("CU.name as customer")
                ->groupBy('sale.contact_id');
        }

        $datatable = Datatables::of($query)
            ->editColumn(
                'gross_profit',
                '<span class="display_currency gross-profit" data-currency_symbol="true" data-orig-value="{{$gross_profit}}">{{$gross_profit}}</span>'
            );

        if ($by == 'category') {
            $datatable->editColumn(
                'category',
                '{{$category ?? __("lang_v1.uncategorized")}}'
            );
        }
        if ($by == 'brand') {
            $datatable->editColumn(
                'brand',
                '{{$brand ?? __("report.others")}}'
            );
        }

        if ($by == 'date') {
            $datatable->editColumn('transaction_date', '{{@format_date($transaction_date)}}');
        }

        $row_columns = ['gross_profit'];
        if ($by == 'invoice') {
            $datatable->editColumn('invoice_no', function ($row) {
                return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                    . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
            });
            $row_columns[] = 'invoice_no';
        }
        return $datatable->rawColumns($row_columns)
            ->make(true);
    }
    /**
     * Daily Sales
     * 
     **/
    public function dailySales1(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            // dd("Hello");
            $query = Transaction::leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
                ->leftJoin(
                    'transactions AS SR',
                    'transactions.id',
                    '=',
                    'SR.return_parent_id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', '!=', 'hide');
            // ->leftJoin(
            //     'transaction_sell_lines as tsl',
            //     'transactions.id',
            //     '=',
            //     'tsl.transaction_id'
            // )
            // $transaction_ids = CashRegisterTransaction::where('transaction_id', 'transactions.id')
            // ->pluck('transaction_id')
            // ->toArray();
            // dd($transaction_ids);
            // $transaction_ids = CashRegisterTransaction::where('cash_register_id',$id)->pluck('transaction_id')->toArray();
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $permitted_locations = auth()->user()->permitted_locations();

            // dd($permitted_locations);
            // $location_filter = '';
            // if ($permitted_locations != 'all') {
            //     $working = $query->whereIn('transactions.location_id', $permitted_locations);

            //     $locations_imploded = implode(', ', $permitted_locations);
            //     dd($working);
            //     $location_filter .= "AND t.location_id IN ($locations_imploded) ";
            // }
            // dd($request->input('location_id'));
            if (!empty($request->get('location_id'))) {
                $query->where('transactions.location_id', $request->input('location_id'));
            }
            $query->select(
                'transactions.id',

                'bl.name as location_name',

                'bl.id as location_id',
                // DB::raw('COUNT(transactions.invoice_no) as items'),

                // DB::raw('SUM(tsl.quantity/2) as items'),

                // DB::raw('(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl GROUP BY DATE(tsl.created_at))'),
                // DB::raw('(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE DATE_FORMAT(transactions.created_at, "%Y-%m-%d") = DATE_FORMAT(tsl.created_at, "%Y-%m-%d")) as items'),
                // DB::raw('(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.created_at = transactions.created_at) as items'),

                DB::raw('COUNT(DISTINCT(transactions.invoice_no)) as invoices'),
                // orignal one
                // DB::raw('SUM(IF(tp.is_return = 1,-1*tp.amount,tp.amount)) as cash'),
                //     DB::raw("(SELECT SUM(
                //         IF(ct.pay_method = 'cash' AND ct.amount > 0, ct.amount, 0)
                //     ) 
                //     FROM cash_register_transactions as ct  
                //     WHERE DATE(ct.created_at) BETWEEN '$start_date' AND '$end_date'
                //     AND ct.transaction_type = 'sell'
                // ) as cash"),
                // DB::raw("(SELECT SUM(
                //     IF(ct.pay_method = 'cash' AND ct.amount > 0, ct.amount, 0)
                // ) 
                // FROM cash_register_transactions as ct  
                // JOIN transactions as t ON t.id = ct.transaction_id
                // WHERE DATE(ct.created_at) BETWEEN '$start_date' AND '$end_date'
                // AND ct.transaction_type = 'sell'
                // ) as cash"),
                DB::raw("(SELECT SUM(
                    IF(ct.pay_method = 'cash' AND ct.amount > 0, ct.amount, 0)
                ) 
                FROM cash_register_transactions as ct  
                JOIN transactions as t ON t.id = ct.transaction_id
                WHERE DATE(ct.created_at) BETWEEN '$start_date' AND '$end_date'
                AND ct.transaction_type = 'sell'
                ) as cash"),

                DB::raw('SUM(IF(tp.is_return = 1,-1*transactions.final_total,transactions.final_total)) as final_total'),
                // orignal one
                // DB::raw('SUM(IF(tp.method="card",tp.amount,0)) as card'),
                DB::raw("(SELECT SUM(
                        IF(ct.pay_method = 'card' AND ct.amount > 0, ct.amount, 0)
                    ) 
                    FROM cash_register_transactions as ct  
                    WHERE DATE(ct.created_at) BETWEEN '$start_date' AND '$end_date'
                    AND ct.transaction_type = 'sell'
                ) as card"),
                // orignal one 
                // DB::raw('SUM(IF(tp.is_convert="coupon",transactions.final_total,0)) as coupon'),
                DB::raw("(SELECT SUM(
                        IF(ct.pay_method = 'coupon', ct.amount, 0)
                    ) 
                    FROM cash_register_transactions as ct  
                    WHERE DATE(ct.created_at) BETWEEN '$start_date' AND '$end_date'
                    AND ct.transaction_type = 'sell'
                ) as coupon"),

                DB::raw('SUM(IF(tp.is_convert="gift_card",transactions.final_total,0)) as gift_card'),

                DB::raw("DATE_FORMAT(transactions.created_at, '%Y-%m-%d')as date"),

                // DB::raw('SUM(tsl.discounted_amount/2) as discount'),

                // DB::raw('(SELECT DATE(tsl.created_at) tsl_date , SUM(tsl.discounted_amount) discount FROM transaction_sell_lines as tsl WHERE tsl.discounted_amount GROUP BY tsl_date LIMIT 0,1)% 10'),
                // DB::raw('(SELECT SUM(tsl.discounted_amount) FROM transaction_sell_lines as tsl WHERE tsl.transaction_id = transactions.id AND tp.is_return=0) as discount'),

                // DB::raw('(SELECT SUM(tsl.discounted_amount) FROM transaction_sell_lines as tsl JOIN transactions as tr WHERE tr.created_at = tsl.created_at ) as discount'),
                // DB::raw('(SELECT SUM(tsl.discounted_amount) FROM transaction_sell_lines as tsl WHERE DATE_FORMAT(transactions.created_at, "%Y-%m-%d") = DATE_FORMAT(tsl.created_at, "%Y-%m-%d")) as discount'),

                // DB::raw('SUM(IF(tp.is_return = 1,transactions.discount_amount,transactions.discount_amount)) as discount'),

                DB::raw('COUNT(SR.id) as return_exists'),
                DB::raw('(SELECT SUM(TP2.amount) FROM transaction_payments AS TP2 WHERE
                        TP2.transaction_id=SR.id ) as return_paid'),
                DB::raw('COALESCE(SR.final_total, 0) as amount_return'),
                'SR.id as return_transaction_id'
            )->orderBy('transactions.created_at', 'DESC')
                ->groupBy(
                    DB::raw("DATE_FORMAT(transactions.created_at, '%Y-%m-%d')")
                );
            // dd($query->first());




            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transactions.created_at)'), [$start_date, $end_date]);
            }
            // dd($query->get());
            return Datatables::of($query)
                ->addColumn('total', function ($row) {
                    $total = $row->cash + $row->card + $row->coupon + $row->gift_card;
                    // $total = ($row->cash - $row->coupon) + $row->card + $row->coupon + $row->gift_card;

                    return '<span class="display_currency total_amount" data-currency_symbol="true"  data-orig-value="' . $total . '">' .
                        $total . '</span>';
                })
                // ->editColumn('discount', function ($row) {
                //     return
                //         '<span class="display_currency discounted_amount" data-currency_symbol="true" data-orig-value="' . $row->discount . '">' . $row->discount . '</span>';
                // })
                ->editColumn('location_name', function ($row) {
                    if (!empty(request()->get('location_id'))) {
                        return $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                ->editColumn('card', function ($row) {
                    return '<span class="display_currency card_amount" data-currency_symbol="true"  data-orig-value="' . $row->card . '">' .
                        $row->card . '</span>';
                })
                ->editColumn('cash', function ($row) {
                    $total = $row->cash;
                    // $total = $row->cash - $row->card;
                    // if ($total < 0) {
                    //     $total = $total * -1;
                    // }
                    return '<span class="display_currency cash_amount" data-currency_symbol="true"  data-orig-value="' . $total . '">' .
                        $total . '</span>';
                })
                ->editColumn('coupon', function ($row) {
                    $total = $row->coupon;

                    return '<span class="display_currency coupon_amount" data-currency_symbol="true"  data-orig-value="' . $total . '">' .
                        $total . '</span>';
                })
                ->editColumn('gift_card', function ($row) {
                    $total = $row->gift_card;

                    return '<span class="display_currency giftcard_amount" data-currency_symbol="true"  data-orig-value="' . $total . '">' .
                        $total . '</span>';
                })
                // ->editColumn('items', function ($row) {
                //     return '<span class=" items" data-currency_symbol="false"  data-orig-value="' . (int)$row->items . '">' .
                //         (int)$row->items . '</span>';
                // })
                ->editColumn('invoices', function ($row) {
                    return '<span class=" invoices" data-currency_symbol="false"  data-orig-value="' . (int)$row->invoices . '">' .
                        (int)$row->invoices . '</span>';
                })
                ->editColumn('date', function ($row) {
                    return  Carbon::parse($row->date)->format('d-M-Y');
                })
                ->rawColumns(['cash', 'card', 'coupon', 'total', 'gift_card', 'discount', 'invoices', 'items'])
                ->make(true);
        }

        $business = BusinessLocation::forDropdown($business_id, true);
        return view('report.daily_sales', compact('business'));
    }
    public function dailySales(Request $request)
    {
        if (!auth()->user()->can('register_report.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = $request->session()->get('user.business_id');
        //Return the details in ajax call
        if ($request->ajax()) {
            $registers = CashRegister::join(
                'cash_register_transactions as ct',
                'ct.cash_register_id',
                '=',
                'cash_registers.id'
            )
                ->join(
                    'business_locations as bl',
                    'bl.id',
                    '=',
                    'cash_registers.location_id'
                )

                ->join(
                    'transaction_sell_lines as tsl',
                    'tsl.transaction_id',
                    '=',
                    'ct.transaction_id'
                )
                ->join(
                    'transactions as t',
                    't.id',
                    '=',
                    'ct.transaction_id'
                )
                ->where('ct.transaction_type', 'sell');
            // ->where('t.business_id', $business_id);
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $registers->select(
                'cash_registers.id as register_id',
                'cash_registers.created_at as created_at',
                'cash_registers.location_id as location_id',
                // 'bl.name as location_name',
                'bl.name as location_name',
                'cash_registers.statusss as status',
                //Orignal one 
                // DB::raw("SUM(IF(tp.method = 'cash' AND ct.amount > 0 ,tp.amount, 0)) as cash"),

                DB::raw("
                        (SELECT 
                            SUM(
                                IF(ct.pay_method = 'cash' AND ct.amount > 0, ct.amount, 0)
                            ) 
                            FROM cash_register_transactions as ct 
                            WHERE ct.cash_register_id = cash_registers.id 
                            AND ct.transaction_type = 'sell'
                        ) as cash
                    "),
                //orignal one 
                // DB::raw("SUM(IF(tp.method = 'card' AND ct.amount > 0, t.final_total, 0)) as card"),
                // DB::raw("SUM(IF(ct.pay_method = 'card' AND ct.amount > 0, ct.amount, 0)) as card"),
                DB::raw("
                        (SELECT 
                            SUM(
                                IF(ct.pay_method = 'card' AND ct.amount > 0, ct.amount, 0)
                            ) 
                            FROM cash_register_transactions as ct 
                            WHERE ct.cash_register_id = cash_registers.id 
                            AND ct.transaction_type = 'sell'
                        ) as card
                    "),
                DB::raw("
                        (SELECT 
                            SUM(
                                IF(ct.pay_method = 'coupon', ct.amount, 0)
                            ) 
                            FROM cash_register_transactions as ct 
                            WHERE ct.cash_register_id = cash_registers.id 
                            AND ct.transaction_type = 'sell'
                        ) as coupon
                    "),
                //orignal one
                // DB::raw("SUM(IF(tp.is_convert = 'gift_card' AND ct.amount > 0, t.final_total, 0)) as gift_card"),
                // DB::raw("SUM(IF(tp.is_convert = 'coupon' AND ct.amount > 0, t.final_total, 0)) as coupon"),
                //orignal one
                // DB::raw('SUM(IF(tp.is_convert="coupon",t.final_total,0)) as coupon'),
                // DB::raw("SUM(IF(ct.pay_method = 'gift_card', amount, 0)) as gift_card"),
                // DB::raw("SUM(IF(ct.pay_method = 'coupon', amount, 0)) as coupon"),


                DB::raw("COUNT(DISTINCT(ct.transaction_id)) as invoices"),
                //orignal one
                DB::raw("SUM(IF(ct.amount > 0, tsl.discounted_amount, 0)) as discounted_amount"),
                //orignal one
                DB::raw("SUM(IF(ct.amount > 0, tsl.quantity, 0)) as items"),
                DB::raw("DATE_FORMAT(t.created_at, '%Y-%m-%d')as date"),

            )

                ->orderBy('created_at', 'DESC')
                ->groupBy('register_id');

            if (!empty($request->input('user_id'))) {
                $registers->where('cash_registers.user_id', $request->input('user_id'));
            }

            if (!empty($request->input('location_id'))) {
                $registers->where('cash_registers.location_id', $request->input('location_id'));
            }



            if (!empty($start_date) && !empty($end_date)) {
                $registers->whereBetween(DB::raw('date(cash_registers.created_at)'), [$start_date, $end_date]);
            }

            if (!empty($request->input('status'))) {
                $registers->where('cash_registers.status', $request->input('status'));
            }
            return Datatables::of($registers)

                ->addColumn('total', function ($row) {
                    $total = $row->cash + $row->card + $row->coupon + $row->gift_card;
                    return '<span class="display_currency total_amount" data-currency_symbol="true"  data-orig-value="' . $total . '">' .
                        $total . '</span>';
                })
                ->editColumn('cash', function ($row) {
                    $cash = $row->cash;
                    // $cash = $row->cash - $row->card - $row->coupon - $row->gift_card;
                    return '<span class="display_currency cash_amount" data-currency_symbol="true"  data-orig-value="' . $cash . '">' .
                        $cash  . '</span>';
                })

                ->editColumn('card', function ($row) {
                    return '<span class="display_currency card_amount" data-currency_symbol="true"  data-orig-value="' . $row->card . '">' .
                        $row->card  . '</span>';
                })
                ->editColumn('gift_card', function ($row) {
                    return '<span class="display_currency giftcard_amount" data-currency_symbol="true" data-orig-value="' . $row->gift_card . '">' .
                        $row->gift_card . '</span>';
                })
                ->editColumn('coupon', function ($row) {
                    return '<span class="display_currency coupon_amount" data-currency_symbol="true" data-orig-value="' . $row->coupon . '">' .
                        $row->coupon . '</span>';
                })

                ->editColumn('invoices', function ($row) {
                    return '<span class="display_currency invoices" data-currency_symbol="false" data-orig-value="' . $row->invoices . '">' . $row->invoices . '</span>';
                })
                ->editColumn('date', function ($row) {
                    return  Carbon::parse($row->date)->format('d-M-Y');
                })
                ->rawColumns(['action', 'cash', 'card', 'gift_card', 'coupon', 'discounted_amount', 'total', 'invoices', 'items'])
                ->make(true);
        }

        $users = User::forDropdown($business_id, false);

        $business = BusinessLocation::forDropdown($business_id, true);
        return view('report.daily_sales', compact('business'));
    }
    /**
     * Monthly Sales
     * 
     **/
    public function monthlySales(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            // dd("Hello");
            $query = Transaction::leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
                ->leftJoin(
                    'transactions AS SR',
                    'transactions.id',
                    '=',
                    'SR.return_parent_id'
                )
                // ->leftJoin(
                //     'transaction_sell_lines AS tsl',
                //     'transactions.id',
                //     '=',
                //     'tsl.transaction_id'
                // )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')

                ->select(
                    'transactions.id',
                    // DB::raw('COUNT(transactions.invoice_no) as items'),

                    // DB::raw('SUM(tsl.quantity/2) as items'),

                    // DB::raw('(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl GROUP BY DATE(tsl.created_at)) as items'),
                    // DB::raw('(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.created_at = transactions.created_at) as items'),

                    DB::raw('COUNT(DISTINCT(transactions.invoice_no)) as invoices'),

                    DB::raw('SUM(IF(tp.is_return = 1,-1*tp.amount,tp.amount)) as cash'),
                    DB::raw('SUM(IF(tp.is_return = 1,-1*transactions.final_total,transactions.final_total)) as final_total'),
                    DB::raw('SUM(IF(tp.method="card",tp.amount,0)) as card'),

                    DB::raw('SUM(IF(tp.is_convert="coupon",transactions.final_total,0)) as coupon'),

                    DB::raw('SUM(IF(tp.is_convert="gift_card",transactions.final_total,0)) as gift_card'),

                    DB::raw("DATE_FORMAT(transactions.created_at, '%Y-%m')as date"),

                    // DB::raw('SUM(transactions.discounted_amount/2) as discount'),
                    // DB::raw('(SELECT SUM(tsl.discounted_amount) FROM transaction_sell_lines as tsl WHERE tsl.transaction_id = transactions.id AND tp.is_return=0) as discount'),

                    // DB::raw('(SELECT SUM(tsl.discounted_amount) FROM transaction_sell_lines as tsl WHERE DATE_FORMAT(transactions.created_at, "%Y-%m-%d") = DATE_FORMAT(tsl.created_at, "%Y-%m-%d")) as discount'),

                    // DB::raw('SUM(IF(tp.is_return = 1,transactions.discount_amount,transactions.discount_amount)) as discount'),
                    'bl.name as location_name',
                    'bl.id as location_id',
                    DB::raw('COUNT(SR.id) as return_exists'),
                    DB::raw('(SELECT SUM(TP2.amount) FROM transaction_payments AS TP2 WHERE
                        TP2.transaction_id=SR.id ) as return_paid'),
                    DB::raw('COALESCE(SR.final_total, 0) as amount_return'),
                    'SR.id as return_transaction_id'
                )->orderBy('transactions.created_at', 'DESC')
                ->groupBy(
                    DB::raw("DATE_FORMAT(transactions.created_at, '%Y-%m')")
                );
            // dd($query->first());
            if (!empty($request->get('location_id'))) {
                $query->where('transactions.location_id', $request->input('location_id'));
            }
            // dd($query->first());
            // if (auth()->user()->getRoleNameAttribute() !=='Admin') {
            if (auth()->user()->username !== 'superadmin') {
                // dd(auth()->user()->getRoleNameAttribute() );
                $query->where('transactions.status', '!=', 'hide');
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transactions.created_at)'), [$start_date, $end_date]);
            }
            // dd($query->get());
            return Datatables::of($query)
                ->addColumn('total', function ($row) {
                    $total = $row->cash;
                    // $total = ($row->cash - $row->coupon) + $row->card + $row->coupon + $row->gift_card;

                    return '<span class="display_currency total_amount" data-currency_symbol="true"  data-orig-value="' . $total . '">' .
                        $total . '</span>';
                })
                // ->editColumn('discount', function ($row) {
                //     return
                //         '<span class="display_currency discounted_amount" data-currency_symbol="true" data-orig-value="' . $row->discount . '">' . $row->discount . '</span>';
                // })
                ->editColumn('location_name', function ($row) {
                    if (!empty(request()->get('location_id'))) {
                        return $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                ->editColumn('card', function ($row) {
                    return '<span class="display_currency card_amount" data-currency_symbol="true"  data-orig-value="' . $row->card . '">' .
                        $row->card . '</span>';
                })
                ->editColumn('cash', function ($row) {
                    $total = $row->cash - $row->card;
                    if ($total < 0) {
                        $total = $total * -1;
                    }

                    return '<span class="display_currency cash_amount" data-currency_symbol="true"  data-orig-value="' . $total . '">' .
                        $total . '</span>';
                })
                ->editColumn('coupon', function ($row) {
                    $total = $row->coupon;

                    return '<span class="display_currency coupon_amount" data-currency_symbol="true"  data-orig-value="' . $total . '">' .
                        $total . '</span>';
                })
                ->editColumn('gift_card', function ($row) {
                    $total = $row->gift_card;

                    return '<span class="display_currency giftcard_amount" data-currency_symbol="true"  data-orig-value="' . $total . '">' .
                        $total . '</span>';
                })
                // ->editColumn('items', function ($row) {
                //     return '<span class=" items" data-currency_symbol="false"  data-orig-value="' . (int)$row->items . '">' .
                //         (int)$row->items . '</span>';
                // })
                ->editColumn('invoices', function ($row) {
                    return '<span class=" invoices" data-currency_symbol="false"  data-orig-value="' . (int)$row->invoices . '">' .
                        (int)$row->invoices . '</span>';
                })
                ->editColumn('date', function ($row) {
                    return  Carbon::parse($row->date)->format('M-Y');
                })
                ->rawColumns(['cash', 'card', 'coupon', 'total', 'gift_card', 'discount', 'invoices', 'items'])
                ->make(true);
        }

        $business = BusinessLocation::forDropdown($business_id, true);
        return view('report.monthly_sales', compact('business'));
    }
    public function old_monthlySales(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $query = Transaction::join('transaction_payments as tp', 'tp.transaction_id', '=', 'transactions.id')
                ->where('transactions.type', 'sell')
                ->join('business_locations as bl', 'bl.id', '=', 'transactions.location_id')
                ->join('transaction_sell_lines as tsl', 'tsl.transaction_id', '=', 'transactions.id');


            if (!empty($request->get('location_id'))) {
                $query->where('transactions.location_id', $request->input('location_id'));
            }

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transactions.created_at)'), [$start_date, $end_date]);
            }

            $query->select(
                'transactions.location_id as location_id',
                'bl.name as location_name',
                'transactions.created_at as transaction_date',

                // DB::raw('DATE_FORMAT(transactions.created_at,"%d %m %y") as date'),
                // AND tp.is_convert!="coupon" AND tp.is_convert="gift_card"

                DB::raw("DATE_FORMAT(transactions.created_at, '%Y-%m')as date"),

                // DB::raw('SUM(IF(tp.method="cash" AND tp.is_return=0,transactions.final_total,0)) as cash'),
                DB::raw('SUM(IF(tp.method="cash" AND tp.is_return=0,transactions.final_total,0)) as cash'),

                DB::raw('SUM(IF(tp.method="card" AND tp.is_return=0 ,transactions.final_total,0)) as card'),

                DB::raw('SUM(IF(tp.is_convert="coupon",transactions.final_total,0)) as coupon'),

                DB::raw('SUM(IF(tp.is_convert="gift_card",transactions.final_total,0)) as gift_card'),

                DB::raw('SUM(tsl.discounted_amount/2) as discount'),

                // DB::raw('COUNT(IF(tp.is_return=0 ,tp.id,0)) as invoices'),
                DB::raw("COUNT(DISTINCT(tp.transaction_id)) as invoices"),

                DB::raw('SUM(tsl.quantity/2) as items'),

                // DB::raw("(SELECT COUNT(tr.invoice_no) FROM transactions as tr WHERE tr.id=t.transaction_id) as invoice"),
                // DB::raw("SUM(IF(DISTINCT(tp.transaction_id), tsl.quantity, 0)) as items"),
            )
                ->orderBy('transactions.created_at', 'DESC')
                ->groupBy(DB::raw("DATE_FORMAT(transactions.created_at, '%Y-%m')"));

            return Datatables::of($query)
                ->addColumn('total', function ($row) {
                    $total = ($row->cash - $row->coupon) + $row->card + $row->coupon + $row->gift_card;

                    return '<span class="display_currency total_amount" data-currency_symbol="true"  data-orig-value="' . $total . '">' .
                        $total . '</span>';
                })
                ->editColumn('discount', function ($row) {
                    return
                        '<span class="display_currency discounted_amount" data-currency_symbol="true" data-orig-value="' . $row->discount . '">' . $row->discount . '</span>';
                })
                ->editColumn('location_name', function ($row) {
                    if (!empty(request()->get('location_id'))) {
                        return $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                ->editColumn('card', function ($row) {
                    return '<span class="display_currency card_amount" data-currency_symbol="true"  data-orig-value="' . $row->card . '">' .
                        $row->card . '</span>';
                })
                ->editColumn('cash', function ($row) {
                    $total = $row->cash - $row->coupon - $row->gift_card;

                    return '<span class="display_currency cash_amount" data-currency_symbol="true"  data-orig-value="' . $total . '">' .
                        $total . '</span>';
                })
                ->editColumn('coupon', function ($row) {
                    $total = $row->coupon;

                    return '<span class="display_currency coupon_amount" data-currency_symbol="true"  data-orig-value="' . $total . '">' .
                        $total . '</span>';
                })
                ->editColumn('gift_card', function ($row) {
                    $total = $row->gift_card;

                    return '<span class="display_currency giftcard_amount" data-currency_symbol="true"  data-orig-value="' . $total . '">' .
                        $total . '</span>';
                })
                ->editColumn('items', function ($row) {
                    return '<span class=" items" data-currency_symbol="false"  data-orig-value="' . (int)$row->items . '">' .
                        (int)$row->items . '</span>';
                })
                ->editColumn('invoices', function ($row) {
                    return '<span class=" invoices" data-currency_symbol="false"  data-orig-value="' . (int)$row->invoices . '">' .
                        (int)$row->invoices . '</span>';
                })
                ->editColumn('date', function ($row) {
                    return  Carbon::parse($row->date)->format('M-Y');
                })
                ->rawColumns(['cash', 'card', 'coupon', 'total', 'gift_card', 'discount', 'invoices', 'items'])
                ->make(true);
        }

        $business = BusinessLocation::forDropdown($business_id, true);
        return view('report.monthly_sales', compact('business'));
    }
    public function knowQtySubquery($location_filter, $from_date, $to_date)
    {
        return DB::raw("(SELECT SUM(COALESCE(TSL.quantity - TSL.quantity_returned, 0)) 
        FROM transactions 
        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE transactions.status = 'final' 
        AND transactions.type = 'sell' 
        AND (TSL.product_refference = p.refference)
        $location_filter  
        AND (DATE(transactions.created_at) BETWEEN '$from_date' AND '$to_date')) as total_sold");
    }
    public function unknowQtySubquery($location_filter, $from_date, $to_date)
    {
        return DB::raw("(SELECT SUM(COALESCE(TSL.quantity - TSL.quantity_returned, 0)) 
        FROM transactions 
        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE transactions.status = 'final' 
        AND transactions.type = 'sell' 
        AND (TSL.product_refference IS NULL)
        $location_filter  
        AND (DATE(transactions.created_at) BETWEEN '$from_date' AND '$to_date')) as total_sold");
    }
    public function knowPriceSubquery($location_filter, $from_date, $to_date)
    {
        return  DB::raw("(SELECT SUM((TSL.quantity - TSL.quantity_returned) * TSL.unit_price_inc_tax)
        FROM transactions 
        JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE transactions.status = 'final' 
        AND transactions.type = 'sell' $location_filter
        AND TSL.product_refference = p.refference
        AND DATE(transactions.created_at) BETWEEN '$from_date' AND '$to_date') as total_sale_price");
    }
    public function unknowPriceSubquery($location_filter, $from_date, $to_date)
    {
        return  DB::raw("(SELECT SUM((TSL.quantity - TSL.quantity_returned) * TSL.unit_price_inc_tax)
        FROM transactions 
        JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE transactions.status = 'final' 
        AND transactions.type = 'sell' $location_filter
        AND  TSL.product_refference IS NULL
        AND DATE(transactions.created_at) BETWEEN '$from_date' AND '$to_date') as total_sale_price");
    }
    public function knowDiscountSubquery($location_filter, $from_date, $to_date)
    {
        return  DB::raw("(SELECT SUM((TSL.discounted_amount))
        FROM transactions 
        JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE transactions.status = 'final' 
        AND transactions.type = 'sell' $location_filter
        AND TSL.product_refference = p.refference
        AND DATE(transactions.created_at) BETWEEN '$from_date' AND '$to_date') as discount_amount");
    }
    public function unknowDiscountSubquery($location_filter, $from_date, $to_date)
    {
        return  DB::raw("(SELECT SUM((TSL.discounted_amount))
        FROM transactions 
        JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE transactions.status = 'final' 
        AND transactions.type = 'sell' $location_filter
        AND  TSL.product_refference IS NULL
        AND DATE(transactions.created_at) BETWEEN '$from_date' AND '$to_date') as discount_amount");
    }
    // old purchase report data get from purchase report
    public function getstockInOutGroupedReportPurchaseOLD(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 280); //3 minutes
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);

            $location_id = $request->get('location_id', null);

            $vld_str = '';
            if (!empty($location_id)) {
                $vld_str .= "AND vldd.location_id=$location_id ";
            }

            $start = $request->get('start_date');
            $end = $request->get('end_date');
            if (!empty($start) && !empty($end)) {
                $vld_str .= "AND Date(vldd.transfered_on)>='$start' AND Date(vldd.transfered_on)<='$end'";
                // dd($vld_str,$start,$end);
            }
            $products = $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                // ->join('sizes as s', 'p.sub_size_id', '=', 's.id')
                ->leftjoin('suppliers as c', 'p.supplier_id', '=', 'c.id')
                ->join('location_transfer_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('purchase_lines', 'p.id', '=', 'purchase_lines.product_id')
                ->join('business_locations as bl', 'vld.location_id', '=', 'bl.id')
                ->select(
                    'p.id as product_id',
                    'p.name as product_name',
                    'p.image as image',
                    'p.type as product_type',
                    'c.name as supplier',
                    // 's.name as size',
                    // 't.id as transaction_id',
                    'p.refference as ref_no',
                    'vld.transfered_on as transaction_date',
                    'vld.transfered_from as transfered_from_id',
                    'bl.name as location_name',
                    'purchase_lines.purchase_price_inc_tax as unit_purchase_price',
                    // 'vld.quantity as purchase_qty',
                    DB::raw("(SELECT bls.name FROM business_locations as bls WHERE vld.transfered_from=bls.id) as transfered_from"),
                    // DB::raw("(SUM(vld.quantity)) as purchase_qty"),
                    DB::raw("(SELECT SUM(vldd.quantity)  FROM location_transfer_details as vldd WHERE vldd.product_refference=p.refference $vld_str) as purchase_qty"),
                    // DB::raw('(SUM(vld.quantity) - purchase_lines.quantity_returned) as purchase_qty'),
                    'variations.default_purchase_price as purchase_price',
                    'purchase_lines.quantity_adjusted',
                    'variations.default_purchase_price as subtotal'
                    // DB::raw('( SUM(vld.quantity) * variations.default_purchase_price) as subtotal')
                )
                // ->distinct('p.refference') 
                ->orderBy('vld.transfered_on', 'DESC')
                ->distinct('ref_no')
                // ->groupBy('p.refference');
                ->groupBy('vld.product_refference');
            // ->groupBy('purchase_lines.product_id');


            if (!empty($variation_id)) {
                $query->where('purchase_lines.variation_id', $variation_id);
            }
            $start = $request->get('start_date');
            $end = $request->get('end_date');
            if (!empty($start) && !empty($end)) {
                $query->whereBetween(DB::raw('date(vld.transfered_on)'), [$start, $end]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('vld.location_id', $location_id);
            }
            $transfered_from = $request->get('transfered_from', null);
            if (!empty($transfered_from)) {
                $query->where('vld.transfered_from', $transfered_from);
            }

            $supplier_id = $request->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $query->where('p.supplier_id', $supplier_id);
            }

            $results = $products;
            $totalPurchaseValues = $results->pluck('purchase_qty');
            $totalPurchasePrice = $results->pluck('purchase_price');
            $totalPurchaseSum = $totalPurchaseValues->sum();
            $newArray = $totalPurchasePrice->map(function ($price, $index) use ($totalPurchaseValues) {
                return $price * $totalPurchaseValues[$index];
            });
            $sumPrice = $newArray->sum();
            // dd( $totalPurchasePrice, $totalPurchaseValues, $newArray,$sumPrice);
        }
        $ajaxResponse = [
            'totalPurchasePrice' => $sumPrice,
            'totalPurchaseSum' => $totalPurchaseSum,
        ];
        return response()->json($ajaxResponse);
    }

    // new purchase report data get from stock group report
    public function getstockInOutGroupedReportPurchase(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->join('categories', 'p.category_id', '=', 'categories.id')
                ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }

            if (!empty($request->input('supplier_id'))) {
                $query->where('p.supplier_id', $request->input('supplier_id'));
            }
            $from_date = request()->get('start_date', null);

            $to_date = request()->get('end_date', null);
            if (!empty($to_date)) {
                $query->whereDate('p.created_at', '>=', $from_date)->whereDate('p.created_at', '<=', $to_date);
                $query->where('vld.qty_available', '>', 0);
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }
            $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
            // $query->join('product.color_id','=','color.id');
            $products = $query->select(
                'vld.sell_price as unit_price',
                DB::raw('SUM(vld.qty_available) as current_stock')
            )
                ->groupBy('p.name')
                ->orderBy('vld.product_updated_at', 'DESC')
                ->get();

            $results = $products;
            $totalPurchaseValues = $results->pluck('current_stock');
            // dd($totalPurchaseValues);
            $totalPurchasePrice = $results->pluck('unit_price');
            $totalPurchaseSum = $totalPurchaseValues->sum();
            // dd($totalPurchasePrice);
            $newArray = $totalPurchasePrice->map(function ($price, $index) use ($totalPurchaseValues) {
                return $price * $totalPurchaseValues[$index];
            });
            $sumPrice = $newArray->sum();
            // dd( $totalPurchasePrice, $totalPurchaseValues, $newArray,$sumPrice);
        }
        $ajaxResponse = [
            'totalPurchasePrice' => $sumPrice,
            'totalPurchaseSum' => $totalPurchaseSum,
        ];
        return response()->json($ajaxResponse);
    }
    public function getstockInOutGroupedReportSell(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 280); //3 minutes
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);

        $location_filter = '';
        if (!empty($location_id)) {
            $location_filter = "AND transactions.location_id=$location_id";
        }

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
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
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereDate('t.transaction_date', '>=', $start_date)->whereDate('t.transaction_date', '<=', $end_date);
            }
            $products = $query->select(
                't.transaction_date as transaction_date',
                'transaction_sell_lines.unit_price_before_discount as unit_price',
                DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold'),
                DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
            )
                ->orderBy('total_qty_sold', 'DESC')
                ->groupBy('transaction_sell_lines.product_refference')
                ->get();
            $results = $products;
            $totalsubtotalValues = $results->pluck('subtotal');
            $total_qty_sold = $results->pluck('total_qty_sold');
            $totalsubtotalSum = $totalsubtotalValues->sum();
            $totalQtySold = $total_qty_sold->sum();
        }
        // dd($totalsubtotalSum);
        $ajaxResponse = [
            'totalQtySold' => $totalQtySold,
            'totalSoldSum' => $totalsubtotalSum,
        ];
        return response()->json($ajaxResponse);
    }
    public function getstockInOutGroupedReportTotalKnow(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 180); //3 minutes
        if (!auth()->user()->can('stock_in_out.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }
        //Return the details in ajax call
        if ($request->ajax()) {

            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->leftjoin('units', 'p.unit_id', '=', 'units.id')
                ->leftjoin('colors', 'p.color_id', '=', 'colors.id')
                ->leftjoin('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->leftjoin('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->leftjoin('categories', 'p.category_id', '=', 'categories.id')
                ->leftjoin('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->leftjoin('location_transfer_details as ltd2', 'variations.id', '=', 'ltd2.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);
            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';
            $location_filter2 = '';
            $location_filterqty = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
                $location_filter2 .= "AND ltd2.location_id  IN ($locations_imploded) ";
                $location_filterqty .= "AND pq.location_id  IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
                $location_filter2 .= "AND ltd2.location_id =$location_id";
                $location_filterqty .= "AND pq.location_id =$location_id";
            }
            $from_date = $request->get('start_date');

            $to_date = $request->get('end_date');
            if (!empty($from_date) && !empty($to_date)) {
                $query->whereDate('vld.updated_at', '>=', $from_date)->whereDate('vld.updated_at', '<=', $to_date);
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }
            $product_type = $request->get('product_type');
            if ($product_type == 1) {
                $query->where('p.refference', '!=', null);
                $qtySubquery = $this->knowQtySubquery($location_filter, $from_date, $to_date);
                $discountSubquery = $this->knowDiscountSubquery($location_filter, $from_date, $to_date);
            } else {
                $query->where('p.refference', null);
                $qtySubquery = $this->unknowQtySubquery($location_filter, $from_date, $to_date);
                $discountSubquery = $this->unknowDiscountSubquery($location_filter, $from_date, $to_date);
            }
            $products = $query->select(

                DB::raw($qtySubquery),
                DB::raw($discountSubquery),
            )
                ->groupBy('p.refference')
                ->orderBy('vld.updated_at', 'DESC')
                ->get();
            $results = $products;
            $totaldiscount = $results->pluck('discount_amount');
            $totalSold = $results->pluck('total_sold');
            $totalDiscountSum = $totaldiscount->sum();
            $totalSoldSum = $totalSold->sum();
        }
        $ajaxResponse = [
            'totalDiscountSum' => $totalDiscountSum,
            'totalSoldSum' => $totalSoldSum,
        ];
        return response()->json($ajaxResponse);
    }
    // old getstockInOutGroupedReportTotal for discount
    public function getstockInOutGroupedReportTotalOLD(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 180); //3 minutes
        if (!auth()->user()->can('stock_in_out.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }
        //Return the details in ajax call
        if ($request->ajax()) {

            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->leftjoin('units', 'p.unit_id', '=', 'units.id')
                ->leftjoin('colors', 'p.color_id', '=', 'colors.id')
                ->leftjoin('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->leftjoin('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->leftjoin('categories', 'p.category_id', '=', 'categories.id')
                ->leftjoin('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->leftjoin('location_transfer_details as ltd2', 'variations.id', '=', 'ltd2.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);
            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';
            $location_filter2 = '';
            $location_filterqty = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
                $location_filter2 .= "AND ltd2.location_id  IN ($locations_imploded) ";
                $location_filterqty .= "AND pq.location_id  IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
                $location_filter2 .= "AND ltd2.location_id =$location_id";
                $location_filterqty .= "AND pq.location_id =$location_id";
            }
            $from_date = $request->get('start_date');

            $to_date = $request->get('end_date');
            if (!empty($from_date) && !empty($to_date)) {
                $query->whereDate('vld.updated_at', '>=', $from_date)->whereDate('vld.updated_at', '<=', $to_date);
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }
            $product_type = $request->get('product_type');
            if ($product_type == 1) {
                $query->where('p.refference', '!=', null);
                $qtySubquery = $this->knowQtySubquery($location_filter, $from_date, $to_date);
                $priceSubquery = $this->knowPriceSubquery($location_filter, $from_date, $to_date);
                $discountSubquery = $this->knowDiscountSubquery($location_filter, $from_date, $to_date);
            } else {
                $query->where('p.refference', null);
                $qtySubquery = $this->unknowQtySubquery($location_filter, $from_date, $to_date);
                $priceSubquery = $this->unknowPriceSubquery($location_filter, $from_date, $to_date);
                $discountSubquery = $this->unknowDiscountSubquery($location_filter, $from_date, $to_date);
            }
            $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
            $products = $query->select(
                DB::raw("SUM(CASE WHEN (DATE(transfered_on) BETWEEN '$from_date' AND '$to_date') AND transfered_from = 1 $location_filter2 THEN ltd2.quantity ELSE 0 END) as main_transfered"),
                DB::raw("SUM(CASE WHEN (DATE(transfered_on) BETWEEN '$from_date' AND '$to_date') AND transfered_from != 1 $location_filter2 THEN ltd2.quantity ELSE 0 END) as subshop_transfered"),
                DB::raw("(SELECT SUM(quantity) 
                FROM product_quantities as pq 
                WHERE DATE(pq.created_at) BETWEEN '$from_date' AND '$to_date' $location_filterqty 
                AND pq.refference = p.refference) as total_qty"),
                DB::raw($qtySubquery),
                DB::raw($priceSubquery),
                DB::raw($discountSubquery),
                DB::raw("SUM(vld.qty_available) as stock"),
                // 'pq.quantity as total_qty',
                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.name as product',
                'p.image as image',
                // 'p.description as description',
                'p.type',
                'p.refference as refference',
                'colors.name as color_name',
                // 'suppliers.name as supplier_name',
                // 'categories.name as category_name',
                // 'sub_cat.name as sub_category_name',
                'sizes.name as size_name',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                // 'variations.sell_price_inc_tax as unit_price',
                'vld.sell_price as unit_price',
                'pv.name as product_variation',
                'vld.updated_at as product_date',
                'vld.location_print_qty as printing_qty',
                'variations.name as variation_name',
                'variations.default_purchase_price as purchase_price',
                'vld.updated_at',
                // 'vld.qty_available as stock',
                DB::raw("(SUM(CASE WHEN (DATE(transfered_on) BETWEEN '$from_date' AND '$to_date') $location_filter2 THEN ltd2.quantity ELSE 0 END) * variations.default_purchase_price ) as total_buying_amount"),
                DB::raw('SUM(vld.qty_available) as current_stock'),
                DB::raw('COUNT(DISTINCT(p.refference)) as total_refference'),
                // DB::raw('SUM(total_sold) as total_sold1')
            )
                // // ->having('total_sold', '>', 0)
                ->groupBy('p.refference')
                ->orderBy('vld.updated_at', 'DESC')
                ->get();
            $results = $products;
            // dd($results);
            $totalBuyingAmountValues = $results->pluck('total_buying_amount');
            $totaldiscount = $results->pluck('discount_amount');
            $totalSellPrice = $results->pluck('total_sale_price');
            $totalSold = $results->pluck('total_sold');
            $totalReffernce = $results->pluck('total_refference');
            $totalBuyingAmountSum = $totalBuyingAmountValues->sum();
            $totalDiscountSum = $totaldiscount->sum();
            $totalSoldSum = $totalSold->sum();
            $totalReffernceSum = $totalReffernce->sum();
            $totalSellPriceSum = $totalSellPrice->sum();
            // dd($totalBuyingAmountSum);

        }
        // Prepare the data for the AJAX response
        $ajaxResponse = [
            'totalBuyingAmountSum' => $totalBuyingAmountSum,
            'totalDiscountSum' => $totalDiscountSum,
            'totalSoldSum' => $totalSoldSum,
            'totalReffernce' => $totalReffernceSum,
            'totalSellPriceSum' => $totalSellPriceSum,
        ];

        // Return the response as JSON
        return response()->json($ajaxResponse);
    }
    public function getstockInOutGroupedReport(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 180); //3 minutes
        if (!auth()->user()->can('stock_in_out.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }
        //Return the details in ajax call
        if ($request->ajax()) {
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
                ->leftjoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final');
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->leftjoin('units', 'p.unit_id', '=', 'units.id')
                ->leftjoin('colors', 'p.color_id', '=', 'colors.id')
                ->leftjoin('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->leftjoin('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->leftjoin('categories', 'p.category_id', '=', 'categories.id')
                ->leftjoin('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->leftjoin('location_transfer_details as ltd2', 'variations.id', '=', 'ltd2.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);
            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';
            $location_filter2 = '';
            $location_filterqty = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
                $location_filter2 .= "AND ltd2.location_id  IN ($locations_imploded) ";
                $location_filterqty .= "AND pq.location_id  IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
                $location_filter2 .= "AND ltd2.location_id =$location_id";
                $location_filterqty .= "AND pq.location_id =$location_id";
            }

            // if (!empty($request->input('category_id'))) {
            //     $query->where('p.category_id', $request->input('category_id'));
            // }
            // if (!empty($request->input('sub_category_id'))) {
            //     $query->where('p.sub_category_id', $request->input('sub_category_id'));
            // }
            // if (!empty($request->input('brand_id'))) {
            //     $query->where('p.brand_id', $request->input('brand_id'));
            // }

            // if (!empty($request->input('supplier_id'))) {
            //     $query->where('p.supplier_id', $request->input('supplier_id'));
            // }


            $from_date = $request->get('start_date');

            $to_date = $request->get('end_date');
            // $start_date = $request->get('start_date');
            //     $end_date = $request->get('end_date');
            // if (!empty($to_date)) {
            if (!empty($from_date) && !empty($to_date)) {
                // dd($products->first());
                $query->whereDate('vld.updated_at', '>=', $from_date)->whereDate('vld.updated_at', '<=', $to_date);

                // $query->whereDate('p.created_at', '>=', $from_date)->whereDate('p.created_at', '<=', $to_date);

                // ->whereDate('vld.updated_at', '>=', $from_date)->whereDate('vld.updated_at', '<=', $to_date);
                // $query->whereDate('tsl.updated_at', '>=', $from_date)->whereDate('tsl.updated_at', '<=', $to_date);
                // $query->whereDate('t.transaction_date', '>=', $from_date)->whereDate('t.transaction_date', '<=', $to_date);
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }
            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN
            $product_type = $request->get('product_type');
            if ($product_type == 1) {
                $query->where('p.refference', '!=', null);
                $qtySubquery = $this->knowQtySubquery($location_filter, $from_date, $to_date);
                $priceSubquery = $this->knowPriceSubquery($location_filter, $from_date, $to_date);
                $discountSubquery = $this->knowDiscountSubquery($location_filter, $from_date, $to_date);
            } else {
                $query->where('p.refference', null);
                $qtySubquery = $this->unknowQtySubquery($location_filter, $from_date, $to_date);
                $priceSubquery = $this->unknowPriceSubquery($location_filter, $from_date, $to_date);
                $discountSubquery = $this->unknowDiscountSubquery($location_filter, $from_date, $to_date);
            }
            $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
            // $query->join('product.color_id','=','color.id');
            $products = $query->select(


                // DB::raw("(SELECT SUM(quantity) FROM product_quantities 
                // JOIN product_quantities AS pq ON variations.id=pq.variation_id
                // WHERE (DATE(pq.updated_at) BETWEEN '$from_date' AND '$to_date') $location_filterqty) as total_qty"),           
                // DB::raw("SUM(CASE WHEN (DATE(pq.updated_at) BETWEEN '$from_date' AND '$to_date') $location_filterqty  THEN pq.quantity ELSE 0 END) as total_qty"),    


                // DB::raw("SUM(CASE WHEN (DATE(transfered_on) BETWEEN '$from_date' AND '$to_date') $location_filter2  THEN ltd2.quantity ELSE 0 END) as total_transfered"),
                DB::raw("SUM(CASE WHEN (DATE(transfered_on) BETWEEN '$from_date' AND '$to_date') AND transfered_from = 1 $location_filter2 THEN ltd2.quantity ELSE 0 END) as main_transfered"),
                DB::raw("SUM(CASE WHEN (DATE(transfered_on) BETWEEN '$from_date' AND '$to_date') AND transfered_from != 1 $location_filter2 THEN ltd2.quantity ELSE 0 END) as subshop_transfered"),
                DB::raw("(SELECT SUM(quantity) 
                FROM product_quantities as pq 
                WHERE DATE(pq.created_at) BETWEEN '$from_date' AND '$to_date' $location_filterqty 
                AND pq.refference = p.refference) as total_qty"),
                // DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal'),
                // DB::raw("(SELECT SUM((TSL.quantity - TSL.quantity_returned) * TSL.unit_price_inc_tax))
                //         FROM transactions 
                //         JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                //         WHERE transactions.status='final' AND transactions.type='sell' $location_filter
                //         AND TSL.variation_id=variations.id AND (DATE(transactions.created_at) BETWEEN '$from_date' AND '$to_date')) as total_sale_price"),
                // TSL.variation_id = variations.id
                // DB::raw("(SELECT SUM((TSL.quantity - TSL.quantity_returned) * TSL.unit_price_inc_tax)
                //         FROM transactions 
                //         JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
                //         WHERE transactions.status = 'final' 
                //         AND transactions.type = 'sell' $location_filter
                //         AND ((TSL.product_refference = p.refference) OR TSL.product_refference IS NULL)
                //         AND DATE(transactions.created_at) BETWEEN '$from_date' AND '$to_date') as total_sale_price"),

                // DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned)  TSL.product_refference=p.refference
                // //         $location_filter  AND (DATE(transactions.created_at) BETWEEN '$from_date' AND '$to_date')) as total_sold"),
                // DB::raw('SUM(tsl.quantity - tsl.quantity_returned) as total_sold'),
                // orignal one
                // DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                //         JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                //         WHERE transactions.status = 'final' 
                //         AND transactions.type = 'sell' 
                //         AND (TSL.product_refference = p.refference)
                //         $location_filter  AND (DATE(transactions.created_at) BETWEEN '$from_date' AND '$to_date')) as total_sold"),
                // DB::raw("(SELECT SUM(COALESCE(TSL.quantity - TSL.quantity_returned, 0)) 
                //         FROM transactions 
                //         LEFT JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
                //         WHERE transactions.status = 'final' 
                //         AND transactions.type = 'sell' 
                //         AND (TSL.product_refference IS NULL OR TSL.product_refference = p.refference)
                //         $location_filter  
                //         AND (DATE(transactions.created_at) BETWEEN '$from_date' AND '$to_date')) as total_sold"),
                DB::raw($qtySubquery),
                DB::raw($priceSubquery),
                DB::raw($discountSubquery),
                // DB::raw($fifteenDaySoldSubquery),

                // DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                //         JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                //         WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                //         AND (TSL.variation_id=variations.id)) as total_transfered"),
                // DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                //         JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                //         WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                //         AND (SAL.variation_id=variations.id)) as total_adjusted"),
                // DB::raw("SUM(CASE WHEN (DATE(transfered_on) BETWEEN '$from_date' AND '$to_date') $location_filter2  THEN ltd2.quantity ELSE 0 END) as total_buying_amount"),
                // DB::raw("SUM(CASE WHEN (DATE(transfered_on) BETWEEN '$from_date' AND '$to_date') $location_filter2 THEN ltd2.quantity ELSE 0 END)  as total_buying_amount"),
                DB::raw("SUM(vld.qty_available) as stock"),
                // 'pq.quantity as total_qty',
                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.name as product',
                'p.image as image',
                // 'p.description as description',
                'p.type',
                'p.refference as refference',
                'colors.name as color_name',
                // 'suppliers.name as supplier_name',
                // 'categories.name as category_name',
                // 'sub_cat.name as sub_category_name',
                'sizes.name as size_name',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                // 'variations.sell_price_inc_tax as unit_price',
                'vld.sell_price as unit_price',
                'pv.name as product_variation',
                'vld.updated_at as product_date',
                'vld.location_print_qty as printing_qty',
                'variations.name as variation_name',
                'variations.default_purchase_price as purchase_price',
                'vld.updated_at',
                // 'vld.qty_available as stock',
                DB::raw("(SUM(CASE WHEN (DATE(transfered_on) BETWEEN '$from_date' AND '$to_date') $location_filter2 THEN ltd2.quantity ELSE 0 END) * variations.default_purchase_price ) as total_buying_amount"),
                DB::raw('SUM(vld.qty_available) as current_stock'),
                DB::raw('COUNT(DISTINCT(p.refference)) as total_refference'),
                // DB::raw('SUM(total_sold) as total_sold1')
            )
                // // ->having('total_sold', '>', 0)
                ->groupBy('p.refference')
                ->orderBy('vld.updated_at', 'DESC')
                ->get();
            // $results = $products;
            // // dd($results);
            // $totalBuyingAmountValues = $results->pluck('total_buying_amount');
            // $totaldiscount = $results->pluck('discount_amount');
            // $totalSellPrice = $results->pluck('total_sale_price');
            // $totalSold = $results->pluck('total_sold');
            // $totalReffernce = $results->pluck('total_refference');
            // $totalSellPriceSum = $totalSellPrice->sum();
            // $totalBuyingAmountSum = $totalBuyingAmountValues->sum();
            // $totalDiscountSum = $totaldiscount->sum();
            // $totalSoldSum = $totalSold->sum();
            // dd($totalBuyingAmountSum);

            return DataTables::of($products)
                ->addIndexColumn()
                ->editColumn('image', function ($row) {
                    $product = Product::find($row->product_id);
                    $url = url("/products/view/") . '/';
                    if (!empty($product->image) && !is_null($product->image)) {
                        return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="' . $url . $row->product()->first()->id . '"></div>';
                        // return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    } else {
                        return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="data-href="' . $url . $row->product()->first()->id . '"></div>';
                    }
                })
                ->editColumn('refference', function ($row) {
                    if ($row->refference) {
                        return $row->refference;
                    } else {
                        return '<span>-</span>';
                    }
                })
                ->addColumn('detail', function ($row) {
                    $location_id = 0;
                    if (!empty(request()->input('location_id'))) {
                        $location_id = request()->input('location_id');
                    }

                    $start_date = request()->get('start_date', 'null');
                    $end_date = request()->get('end_date', 'null');
                    if (isset($start_date) && isset($end_date)) {
                        return '<a  href="' . url("/product/color-detail/" . $row->product . '/' . $start_date . '/' . $end_date) . '" target="_blank" class="btn btn-primary btn-sm">Color Report <i class="fa fa-eye"></i></a>';
                    } else {
                        return '<a  href="#"  class="btn btn-primary btn-sm">Color Report <i class="fa fa-eye"></i></a>';
                    }
                    // return '<a id="color-detail-modal" href="' . url("/product/color-detail/" . $row->product . '/' . $start_date . '/' . $end_date) . '" data-product-name="' . $row->product . '" class="btn btn-primary btn-sm">Color Report <i class="fa fa-eye"></i></a>';
                })
                // ->addColumn('sale_percent', function ($row) {
                //     $quantity_sold =  (float) $row->total_sold;
                //     $quantity_available =  (float) $row->stock  + $quantity_sold;
                //     if ($quantity_available < 1) {
                //         $quantity_available = 1;
                //     }
                //     $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                //     return $percent . '%';
                // })
                // ->editColumn('current_stock', function ($row) {
                //     if ($row->enable_stock) {
                //         $current_stock = $row->current_stock ? $row->current_stock : 0;
                //         return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float) $current_stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float) $current_stock . '</span>' . ' ' . $row->unit;
                //     } else {
                //         return 'N/A';
                //     }
                // })
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float) $stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float) $stock . '</span>' . ' ' . $row->unit;
                    } else {
                        return 'N/A';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float) $row->total_sold;
                    }
                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('total_sale_price', function ($row) {
                    // $html = '';
                    $total_sale_price = 0;
                    if ($row->total_sale_price) {
                        $total_sale_price =  $row->total_sale_price;
                    }
                    return '<span class="display_currency total_sale_price" data-currency_symbol = true data-orig-value="' . $row->total_sale_price . '">' . $row->total_sale_price . '</span>';
                })
                ->editColumn('discount_amount', function ($row) {
                    $discount_amount = 0;
                    if ($row->discount_amount) {
                        $discount_amount =  $row->discount_amount;
                    }
                    return '<span class="display_currency discount_amount" data-currency_symbol = true data-orig-value="' . $discount_amount . '">' . $discount_amount . '</span>';
                })

                ->editColumn('total_refference', function ($row) {
                    $total_refference = 0;
                    if ($row->total_refference) {
                        $total_refference =  $row->total_refference;
                    }

                    return  $total_refference;
                })
                ->editColumn('front_total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float) $row->total_sold;
                    }
                    return $total_sold . ' ' . $row->unit;
                })

                ->editColumn('total_transfered', function ($row) {
                    $total_transfered = 0;
                    $location_id = 0;
                    if (!empty(request()->input('location_id'))) {
                        $location_id = request()->input('location_id');
                        $total_transfered =  (float) $row->main_transfered + (float) $row->subshop_transfered;
                    } else {
                        $total_transfered =  (float) $row->main_transfered / 2 + (float) $row->subshop_transfered / 2;
                    }
                    $this->totalTransferedValue = $total_transfered;
                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                })
                ->editColumn('main_transfered', function ($row) {
                    $main_transfered = 0;
                    $location_id = 0;
                    if (!empty(request()->input('location_id'))) {
                        $location_id = request()->input('location_id');
                        // dd($location_id);
                        $main_transfered =   $row->main_transfered;
                    } else {
                        if ($row->main_transfered) {
                            $main_transfered =  intval($row->main_transfered / 2);
                        }
                    }
                    return '<span  class=" main_transfered" data-orig-value="' . $main_transfered . '" data-unit="' . $row->unit . '" >' . $main_transfered . '</span> ' . $row->unit;
                })
                ->editColumn('subshop_transfered', function ($row) {
                    $subshop_transfered = 0;
                    // if ($row->subshop_transfered) {
                    //     $subshop_transfered =  (float) $row->subshop_transfered/2;
                    // }
                    $location_id = 0;
                    if (!empty(request()->input('location_id'))) {
                        $location_id = request()->input('location_id');
                        $subshop_transfered =  (float) $row->subshop_transfered;
                    } else {
                        if ($row->subshop_transfered) {
                            $subshop_transfered =  (float) $row->subshop_transfered / 2;
                        }
                    }

                    return '<span data-is_quantity="true" class="display_currency subshop_transfered" data-currency_symbol=false data-orig-value="' . $subshop_transfered . '" data-unit="' . $row->unit . '" >' . $subshop_transfered . '</span> ' . $row->unit;
                })

                ->editColumn('total_qty', function ($row) {
                    // $total_qty =  $row->total_qty;
                    $total_qty = 0;
                    // dd($row->total_qty);
                    if ($row->total_qty) {
                        $total_qty =  $row->total_qty;
                    }
                    // // return $total_qty;
                    // return $total_qty;
                    return '<span data-is_quantity="true" class="display_currency total_qty" data-currency_symbol=false data-orig-value="' . $total_qty . '"  >' . $total_qty . '</span> ';
                })
                ->editColumn('stock_in', function ($row) {
                    $stock_in = 0;
                    // dd($total_transfered);
                    $stock_in =  $this->totalTransferedValue + $row->total_qty;
                    return '<span data-is_quantity="true" class="display_currency stock_in" data-currency_symbol=false data-orig-value="' . $stock_in . '"  >' . $stock_in . '</span> ';
                })


                ->editColumn('purchase_price', function ($row) {
                    $html = '';
                    $html .= '<span class="display_currency" data-currency_symbol=true >'
                        . $row->purchase_price . '</span>';
                    return $html;
                })
                ->editColumn('total_buying_amount', function ($row) {
                    $total_buying_amount = 0;
                    // if ($row->total_buying_amount) {
                    //     $total_buying_amount =  (float) $row->total_buying_amount;
                    // }
                    if (!empty(request()->input('location_id'))) {
                        $location_id = request()->input('location_id');
                        // dd($location_id);
                        $total_buying_amount =  (float) $row->total_buying_amount;
                    } else {
                        if ($row->total_buying_amount) {
                            $total_buying_amount =  (float) $row->total_buying_amount / 2;
                        }
                    }
                    return '<span class="display_currency row_subtotal" data-currency_symbol=true data-orig-value="' . $total_buying_amount . '">' . $total_buying_amount . '</span>';
                })
                ->editColumn('transfered_added', function ($row) {
                    $transfered_added = 0;
                    if ($row->transfered_added) {
                        $transfered_added =  (float) $row->transfered_added;
                    }
                    return  $transfered_added;
                })
                // ->editColumn('total_transfered', function ($row) {
                //     $total_transfered = 0;
                //     if ($row->total_transfered) {
                //         $total_transfered =  (float) $row->total_transfered;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                // })
                ->editColumn('location_name', function ($row) {
                    // return '<span max="' . $row->location_id . '" id="location_' . $row->product_id . '">' . $row->location_name . '</span> ';
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                // ->editColumn('total_adjusted', function ($row) {
                //     $total_adjusted = 0;
                //     if ($row->total_adjusted) {
                //         $total_adjusted =  (float) $row->total_adjusted;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                // })

                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                            . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) . '">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                // ->setRowAttr([
                //     'data-href' => function ($row) {
                //         if (auth()->user()->can("product.view")) {
                //             return  action('ProductController@viewProductDetailWithSale', [$row->product()->first()->id]);
                //             // return  action('ProductController@view', [$row->product()->first()->id]);
                //         } else {
                //             return '';
                //         }
                //     }
                // ])
                ->removeColumn('id')
                ->rawColumns(['mass_delete', 'printing_qty', 'unit_price', 'total_transfered', 'main_transfered', 'subshop_transfered', 'total_buying_amount', 'discount_amount', 'refference', 'total_refference', 'location_name', 'total_sold', 'front_total_sold', 'purchase_price', 'total_adjusted', 'stock', 'actions', 'image', 'detail', 'total_qty', 'total_sale_price', 'stock_in'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $suppliers = Supplier::orderBy('name', 'ASC')->pluck('name', 'id');

        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);


        return view('report.stock_in_out')
            ->with(compact('categories', 'suppliers', 'units', 'business_locations', 'totalBuyingAmountSum'));
    }
    // stock in report old
    public function getstockInOutReportOLD(Request $request)
    {

        if (!auth()->user()->can('stock_in_out.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->join('categories', 'p.category_id', '=', 'categories.id')
                ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->join('location_transfer_details as ltd2', 'variations.id', '=', 'ltd2.variation_id')
                // ->leftjoin('product_quantities as pq','variations.product_variation_id', '=', 'pq.variation_id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';
            $location_filter2 = '';
            $location_filterqty = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
                $location_filter2 .= "AND ltd2.location_id  IN ($locations_imploded)";
                $location_filterqty .= "AND pq.location_id  IN ($locations_imploded)";
            }
            // dd($location_filter);

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
                $location_filter2 .= "AND ltd2.location_id=$location_id";
                $location_filterqty .= "AND pq.location_id=$location_id";
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }

            if (!empty($request->input('supplier_id'))) {
                $query->where('p.supplier_id', $request->input('supplier_id'));
            }

            $from_date = request()->get('start_date', null);

            $to_date = request()->get('end_date', null);
            // dd($to_date);
            // if($to_date == 'no'){
            //     $to_date = Carbon::now();
            // }
            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('vld.updated_at', '>=', $from_date)->whereDate('vld.updated_at', '<=', $to_date);
                // $query->where('vld.qty_available', '>', 0);
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }

            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN


            $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
            // $query->join('product.color_id','=','color.id');
            // DB::raw("SUM(CASE WHEN (DATE(pq.updated_at) BETWEEN '$from_date' AND '$to_date') $location_filterqty THEN pq.quantity ELSE 0 END) as total_qty");     
            // DB::table('product_quantities AS pq')
            //     ->selectRaw("CASE WHEN (DATE(pq.updated_at) BETWEEN ? AND ?) $location_filterqty THEN pq.quantity ELSE 0 END as total_qty", [$from_date, $to_date])
            //     ->get();
            // DB::raw("(SELECT pq.quantity FROM variation_location_details 
            //              JOIN product_quantities AS pq ON variation_location_details.variation_id=variations.id
            //              WHERE variation_location_details.product_id=pq.product_id
            //              AND (DATE(pq.created_at) BETWEEN '$from_date' AND '$to_date') $location_filterqty) as total_qty");
            // dd($location_filter2);

            // $query->whereBetween('pq.created_at', [$from_date, $to_date])->where('pq.location_id', $permitted_locations);

            info($location_filterqty);

            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),
                // DB::raw("SUM(CASE WHEN vld.location_id  = $location_id AND (DATE(vld.created_at) BETWEEN '$from_date' AND '$to_date') THEN vld.qty_available ELSE 0 END) as stock"),
                // DB::raw("SUM(CASE WHEN ltd2.location_id = $location_id AND (DATE(transfered_on) BETWEEN '$from_date' AND '$to_date') THEN ltd2.quantity ELSE 0 END) as total_transfered"),
                DB::raw("SUM(CASE WHEN (DATE(transfered_on) BETWEEN '$from_date' AND '$to_date') $location_filter2 THEN ltd2.quantity ELSE 0 END) as total_transfered"),

                DB::raw("(SELECT SUM(quantity) 
                    FROM product_quantities as pq 
                    WHERE DATE(pq.created_at) BETWEEN '$from_date' AND '$to_date' $location_filterqty 
                    AND pq.variation_id = variations.id) as total_qty"),


                // DB::raw("SUM(CASE WHEN (DATE(pq.created_at) BETWEEN '$from_date' AND '$to_date') $location_filterqty THEN pq.quantity ELSE 0 END) as total_qty"),
                // DB::raw("SUM(CASE WHEN (pq.location_id = 1) THEN pq.location_id ELSE 0 END) as total_qty"),

                // DB::raw("(SELECT pq.quantity FROM variation_location_details 
                //         JOIN product_quantities AS pq ON variation_location_details.variation_id=variations.id
                //         WHERE variation_location_details.product_id=pq.product_id
                //         AND (DATE(pq.updated_at) BETWEEN '$from_date' AND '$to_date') $location_filterqty) as total_qty"),  


                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                            JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                            WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                            AND TSL.variation_id=variations.id) as total_sold"),
                // DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                //         JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                //         WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                //         AND (TSL.variation_id=variations.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                            JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                            WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                            AND (SAL.variation_id=variations.id)) as total_adjusted"),

                // DB::raw("SUM(CASE WHEN vld.location_id = $location_id AND (DATE(product_updated_at) BETWEEN '$from_date' AND '$to_date') THEN vld.qty_available ELSE 0 END) as stock"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.show_pos as show_pos',
                'p.name as product',
                'p.image as image',
                'p.description as description',
                'p.type',
                'p.refference',
                'p.updated_at as product_updated',
                'colors.name as color_name',
                'suppliers.name as supplier_name',
                'categories.name as category_name',
                'sub_cat.name as sub_category_name',
                'sizes.name as size_name',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                // 'variations.sell_price_inc_tax as unit_price',
                'vld.sell_price as unit_price',
                'pv.name as product_variation',
                'vld.updated_at as product_date',
                'vld.location_print_qty as printing_qty',
                'variations.name as variation_name',
                'vld.updated_at',
                // 'vld.qty_available as current_stock'
                // DB::raw('SUM(vld.qty_available) as current_stock'),
            )->groupBy('variations.id')
                // ->having('total_transfered', '>', 0)
                ->havingRaw('total_transfered > 0 OR total_qty > 0')
                ->orderBy('vld.updated_at', 'DESC');


            // ->orderBy('vld.product_updated_at', 'DESC');
            // dd($products->first());
            // dd($products->first()->product()->first()->image_url);

            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('mass_delete', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"> <input type="number" class="row-print-qty form-control disabled" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="stock_qty_' . $row->product_id . '">';
                    // return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"><input type="number" class="row-qty form-control" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="qty_' . $row->product_id . '">';
                })
                ->editColumn('show_pos', function ($row) {
                    $web_items = WebsiteProducts::where('product_id', $row->product_id)->first();
                    $data = '';
                    if ($row->show_pos) {
                        $data .= '<span class="btn btn-xs btn-info">TOP</span>';
                    } else {
                        $data .= '<span class="btn btn-xs btn-danger">Normal</span>';
                    }
                    if ($web_items) {
                        $data .= '<span class="btn btn-xs btn-success">On Website</span>';
                    }
                    return $data;
                })
                ->editColumn('printing_qty', function ($row) {
                    if ($row->printing_qty < 1) {
                        $qty = $row->current_stock;
                    } else {
                        $qty = $row->printing_qty;
                    }
                    return  'Print: <input type="number" class="row-print-qty form-control" value="' . number_format($qty) . '" max="' . number_format($qty) . '" style="width:70px;" id="printing_qty_' . $row->product_id . '">';
                })
                // ->addColumn('color_id', function ($row) {
                //     // return  $row->first()->product()->first()->color()->first()->name;
                //     $product = Product::find($row->product_id);
                //     return  $product->color()->first()->id;
                // })
                // ->addColumn('supplier_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->supplier()->first()->name;
                // })
                // ->addColumn('category_id', function ($row) {
                //     $product = Product::find($row->product_id);

                //     return  $product->category()->first()->name;
                // })
                // ->addColumn('sub_category_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->sub_category()->first()->name;
                // })
                // ->addColumn('sub_size_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->sub_size()->first()->name;
                // })
                ->editColumn('image', function ($row) {
                    $product = Product::find($row->product_id);
                    $url = url("/products/view/") . '/';
                    if (!empty($product->image) && !is_null($product->image)) {
                        return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="' . $url . $row->product()->first()->id . '"></div>';
                        // return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    } else {
                        return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="data-href="{{url("/products/view/".$row->product()->first()->id)}}"></div>';
                        // return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    }
                })
                ->addColumn('sale_percent', function ($row) {
                    $quantity_sold =  (float) $row->total_sold;
                    $quantity_available =  (float) $row->stock  + $quantity_sold;
                    if ($quantity_available < 1) {
                        $quantity_available = 1;
                    }
                    $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                    return $percent . '%';
                })
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float) $stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float) $stock . '</span>' . ' ' . $row->unit;
                    } else {
                        return 'N/A';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float) $row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })

                // ->editColumn('total_transfered', function ($row) {
                //     $total_transfered = 0;
                //     if ($row->total_transfered) {
                //         $total_transfered =  (float) $row->total_transfered;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '"  >' . $total_transfered . '</span> ';
                // })
                ->editColumn('total_transfered', function ($row) {
                    $total_transfered = 0;
                    // if ($row->location_id != null) {
                    //    dd($row->location_name);
                    // }
                    $location_id = 0;
                    if (!empty(request()->input('location_id'))) {
                        $location_id = request()->input('location_id');
                        $total_transfered =  (float) $row->total_transfered;
                    } else {
                        $total_transfered =  (float) $row->total_transfered / 2;
                    }
                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                })
                ->editColumn('total_qty', function ($row) {
                    // $total_qty =  $row->total_qty;
                    $total_qty = 0;
                    // dd($row->total_qty);
                    if ($row->total_qty) {
                        $total_qty =  $row->total_qty;
                    }
                    // // return $total_qty;
                    // return $total_qty;
                    return '<span data-is_quantity="true" class="display_currency total_qty" data-currency_symbol=false data-orig-value="' . $total_qty . '"  >' . $total_qty . '</span> ';
                })
                // ->editColumn('total_transfered', function ($row) {
                //     $total_transfered = 0;
                //     if ($row->total_transfered) {
                //         $total_transfered =  (float) $row->total_transfered;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                // })
                ->editColumn('location_name', function ($row) {
                    // return '<span max="' . $row->location_id . '" id="location_' . $row->product_id . '">' . $row->location_name . '</span> ';
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                ->editColumn('total_adjusted', function ($row) {
                    $total_adjusted = 0;
                    if ($row->total_adjusted) {
                        $total_adjusted =  (float) $row->total_adjusted;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                })
                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                            . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) . '">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                // ->addColumn(
                //     'actions',
                //     function ($row) use ($selling_price_group_count) {
                //         $html =
                //             '<div class="btn-group">
                //             <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false"> <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                //             </button>
                //             <ul class="dropdown-menu dropdown-menu-right" role="menu">
                //                 <li><a href="' . action('LabelsController@show') . '?product_id=' . $row->product()->first()->id . '" data-toggle="tooltip" title="Print Barcode/Label"><i class="fa fa-barcode"></i> ' . __('barcode.labels') . '</a></li>';

                //         if (auth()->user()->can('product.view')) {
                //             $html .=
                //                 '<li><a href="' . action('ProductController@view', [$row->product()->first()->id]) . '" class="view-product"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                //         }

                //         if (auth()->user()->can('product.update')) {
                //             $html .=
                //                 '<li><a href="' . action('ProductController@edit', [$row->product()->first()->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                //         }

                //         if (auth()->user()->can('product.delete')) {
                //             $html .=
                //                 '<li><a href="' . action('ProductController@destroy', [$row->product()->first()->id]) . '" class="delete-product"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                //         }

                //         if ($row->is_inactive == 1) {
                //             $html .=
                //                 '<li><a href="' . action('ProductController@activate', [$row->product()->first()->id]) . '" class="activate-product"><i class="fa fa-circle-o"></i> ' . __("lang_v1.reactivate") . '</a></li>';
                //         }

                //         $html .= '<li class="divider"></li>';

                //         if (auth()->user()->can('product.create')) {
                //             if ($row->enable_stock == 1) {
                //                 $html .=
                //                     '<li><a href="#" data-href="' . action('OpeningStockController@add', ['product_id' => $row->product()->first()->id]) . '" class="add-opening-stock"><i class="fa fa-database"></i> ' . __("lang_v1.add_edit_opening_stock") . '</a></li>';
                //             }

                //             if ($selling_price_group_count > 0) {
                //                 $html .=
                //                     '<li><a href="' . action('ProductController@addSellingPrices', [$row->product()->first()->id]) . '"><i class="fa fa-money"></i> ' . __("lang_v1.add_selling_price_group_prices") . '</a></li>';
                //             }

                //             $html .=
                //                 '<li><a href="' . action('ProductController@create', ["d" => $row->product()->first()->id]) . '"><i class="fa fa-copy"></i> ' . __("lang_v1.duplicate_product") . '</a></li>';
                //         }

                //         $html .= '</ul></div>';

                //         return $html;
                //     }
                // )

                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                // ->setRowAttr([
                //     'data-href' => function ($row) {
                //         if (auth()->user()->can("product.view")) {
                //             return  action('ProductController@view', [$row->product()->first()->id]);
                //         } else {
                //             return '';
                //         }
                //     }
                // ])
                // ->removeColumn('id')
                ->rawColumns(['mass_delete', 'printing_qty', 'unit_price', 'total_transfered', 'show_pos', 'total_qty', 'location_name', 'total_sold', 'total_adjusted', 'stock', 'actions', 'image'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $suppliers = Supplier::orderBy('name', 'ASC')->pluck('name', 'id');

        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.stock_in_out')
            ->with(compact('categories', 'suppliers', 'units', 'business_locations'));
    }
    public function getstockInOutGroupedReportTotal(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 180); //3 minutes
        if (!auth()->user()->can('stock_in_out.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $registers = CashRegister::join(
                'cash_register_transactions as ct',
                'ct.cash_register_id',
                '=',
                'cash_registers.id'
            )
                ->join(
                    'business_locations as bl',
                    'bl.id',
                    '=',
                    'cash_registers.location_id'
                )

                ->join(
                    'transaction_sell_lines as tsl',
                    'tsl.transaction_id',
                    '=',
                    'ct.transaction_id'
                )
                ->join(
                    'transactions as t',
                    't.id',
                    '=',
                    'ct.transaction_id'
                )
                ->where('ct.transaction_type', 'sell');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $products = $registers->select(
                'cash_registers.id as register_id',
                'cash_registers.created_at as created_at',
                'cash_registers.location_id as location_id',
                'bl.name as location_name',
                'cash_registers.statusss as status',
                DB::raw("
                    (SELECT 
                        SUM(
                            IF(ct.pay_method = 'cash' AND ct.amount > 0, ct.amount, 0)
                        ) 
                        FROM cash_register_transactions as ct 
                        WHERE ct.cash_register_id = cash_registers.id 
                        AND ct.transaction_type = 'sell'
                    ) as cash
                "),
                DB::raw("
                    (SELECT 
                        SUM(
                            IF(ct.pay_method = 'card' AND ct.amount > 0, ct.amount, 0)
                        ) 
                        FROM cash_register_transactions as ct 
                        WHERE ct.cash_register_id = cash_registers.id 
                        AND ct.transaction_type = 'sell'
                    ) as card
                "),
                DB::raw("
                    (SELECT 
                        SUM(
                            IF(ct.pay_method = 'coupon', ct.amount, 0)
                        ) 
                        FROM cash_register_transactions as ct 
                        WHERE ct.cash_register_id = cash_registers.id 
                        AND ct.transaction_type = 'sell'
                    ) as coupon
                "),
                DB::raw("COUNT(DISTINCT(ct.transaction_id)) as invoices"),
                //orignal one
                DB::raw("SUM(IF(ct.amount > 0, tsl.discounted_amount, 0)) as discounted_amount"),
                //orignal one
                DB::raw("SUM(IF(ct.amount > 0, tsl.quantity, 0)) as items"),
            )

                ->orderBy('created_at', 'DESC')
                ->groupBy('register_id');
            // ->get();

            if (!empty($request->input('user_id'))) {
                $registers->where('cash_registers.user_id', $request->input('user_id'));
            }

            if (!empty($request->input('location_id'))) {
                $registers->where('cash_registers.location_id', $request->input('location_id'));
            }



            if (!empty($start_date) && !empty($end_date)) {
                $registers->whereBetween(DB::raw('date(cash_registers.created_at)'), [$start_date, $end_date]);
            }

            if (!empty($request->input('status'))) {
                $registers->where('cash_registers.status', $request->input('status'));
            }

            $results = $products;
            $totaldiscount = $results->pluck('discounted_amount');
            // dd($totaldiscount);
            $totalDiscountSum = $totaldiscount->sum();
        }
        $ajaxResponse = [
            'totalDiscountSum' => $totalDiscountSum,
        ];

        // Return the response as JSON
        return response()->json($ajaxResponse);
    }
    // stockIN report new from stock details
    public function getStockInOutReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->join('categories', 'p.category_id', '=', 'categories.id')
                ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }

            if (!empty($request->input('supplier_id'))) {
                $query->where('p.supplier_id', $request->input('supplier_id'));
            }

            $from_date = request()->get('start_date', null);

            $to_date = request()->get('end_date', null);

            if (!empty($to_date)) {
                // dd($products->first());
                $query->whereDate('p.created_at', '>=', $from_date)->whereDate('p.created_at', '<=', $to_date);
                // $query->where('vld.qty_available', '>', 0);
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }

            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }

            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN


            $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
            // $query->join('product.color_id','=','color.id');
            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                        AND (TSL.variation_id=variations.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                        AND (SAL.variation_id=variations.id)) as total_adjusted"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.show_pos as show_pos',
                'p.name as product',
                'p.image as image',
                'p.description as description',
                'p.type',
                'p.refference',
                'p.updated_at as product_updated',
                'colors.name as color_name',
                'suppliers.name as supplier_name',
                'categories.name as category_name',
                'sub_cat.name as sub_category_name',
                'sizes.name as size_name',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                // 'variations.sell_price_inc_tax as unit_price',
                'vld.sell_price as unit_price',
                'pv.name as product_variation',
                'vld.product_updated_at as product_date',
                'vld.location_print_qty as printing_qty',
                'variations.name as variation_name',
                'vld.updated_at',
                // 'vld.qty_available as current_stock'

                DB::raw('SUM(vld.qty_available) as current_stock')
            )->groupBy('variations.id')
                // ->having('current_stock', '>', 0)
                ->having('current_stock', '!=', 0)
                ->orderBy('vld.product_updated_at', 'DESC');

            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('mass_delete', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"> <input type="number" class="row-print-qty form-control disabled" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="stock_qty_' . $row->product_id . '">';
                    // return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"><input type="number" class="row-qty form-control" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="qty_' . $row->product_id . '">';
                })
                ->editColumn('show_pos', function ($row) {
                    $web_items = WebsiteProducts::where('product_id', $row->product_id)->first();
                    $data = '';
                    if ($row->show_pos) {
                        $data .= '<span class="btn btn-xs btn-info">TOP</span>';
                    } else {
                        $data .= '<span class="btn btn-xs btn-danger">Normal</span>';
                    }
                    if ($web_items) {
                        $data .= '<span class="btn btn-xs btn-success">On Website</span>';
                    }
                    return $data;
                })
                ->editColumn('printing_qty', function ($row) {
                    if ($row->printing_qty < 1) {
                        $qty = $row->current_stock;
                    } else {
                        $qty = $row->printing_qty;
                    }
                    return  'Print: <input type="number" class="row-print-qty form-control" value="' . number_format($qty) . '" max="' . number_format($qty) . '" style="width:70px;" id="printing_qty_' . $row->product_id . '">';
                })
                ->editColumn('price_input', function ($row) {
                    $price = $row->unit_price;
                    return  '<input type="hidden" step="any" class="row-print-qty form-control" value="' . $price . '" style="width:70px;" id="vld_old_sell_price_' . $row->product_id . '">
                             <input type="number" step="any" class="row-print-qty form-control" value="' . $price . '" style="width:70px;" id="vld_sell_price_' . $row->product_id . '">';
                })

                ->editColumn('image', function ($row) {
                    $product = Product::find($row->product_id);
                    $url = url("/products/view/") . '/';
                    if (!empty($product->image) && !is_null($product->image)) {
                        return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="' . $url . $row->product()->first()->id . '"></div>';
                        // return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    } else {
                        return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="data-href="{{url("/products/view/".$row->product()->first()->id)}}"></div>';
                        // return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    }
                })
                ->addColumn('sale_percent', function ($row) {
                    $quantity_sold =  (float) $row->total_sold;
                    $quantity_available =  (float) $row->stock  + $quantity_sold;
                    if ($quantity_available < 1) {
                        $quantity_available = 1;
                    }
                    $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                    return $percent . '%';
                })
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float) $stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float) $stock . '</span>' . ' ' . $row->unit;
                    } else {
                        return 'N/A';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float) $row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('total_transfered', function ($row) {
                    $total_transfered = 0;
                    if ($row->total_transfered) {
                        $total_transfered =  (float) $row->total_transfered;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                })
                ->editColumn('location_name', function ($row) {
                    // return '<span max="' . $row->location_id . '" id="location_' . $row->product_id . '">' . $row->location_name . '</span> ';
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                ->editColumn('total_adjusted', function ($row) {
                    $total_adjusted = 0;
                    if ($row->total_adjusted) {
                        $total_adjusted =  (float) $row->total_adjusted;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                })
                ->editColumn('description', function ($row) {
                    $description = '-';
                    if ($row->description) {
                        $description = $row->description;
                    }
                    return $description;
                })
                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                            . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) . '">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                ->addColumn(
                    'actions',
                    function ($row) use ($selling_price_group_count) {
                        $html =
                            '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false"> <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li><a href="' . action('LabelsController@show') . '?product_id=' . $row->product()->first()->id . '" data-toggle="tooltip" title="Print Barcode/Label"><i class="fa fa-barcode"></i> ' . __('barcode.labels') . '</a></li>';

                        if (auth()->user()->can('product.view')) {
                            $html .=
                                '<li><a href="' . action('ProductController@view', [$row->product()->first()->id]) . '" class="view-product"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                        }

                        if (auth()->user()->can('product.update')) {
                            $html .=
                                '<li><a href="' . action('ProductController@edit', [$row->product()->first()->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        }

                        if (auth()->user()->can('product.delete')) {
                            $html .=
                                '<li><a href="' . action('ProductController@destroy', [$row->product()->first()->id]) . '" class="delete-product"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        }

                        if ($row->is_inactive == 1) {
                            $html .=
                                '<li><a href="' . action('ProductController@activate', [$row->product()->first()->id]) . '" class="activate-product"><i class="fa fa-circle-o"></i> ' . __("lang_v1.reactivate") . '</a></li>';
                        }

                        $html .= '<li class="divider"></li>';

                        if (auth()->user()->can('product.create')) {
                            if ($row->enable_stock == 1) {
                                $html .=
                                    '<li><a href="#" data-href="' . action('OpeningStockController@add', ['product_id' => $row->product()->first()->id]) . '" class="add-opening-stock"><i class="fa fa-database"></i> ' . __("lang_v1.add_edit_opening_stock") . '</a></li>';
                            }

                            if ($selling_price_group_count > 0) {
                                $html .=
                                    '<li><a href="' . action('ProductController@addSellingPrices', [$row->product()->first()->id]) . '"><i class="fa fa-money"></i> ' . __("lang_v1.add_selling_price_group_prices") . '</a></li>';
                            }

                            $html .=
                                '<li><a href="' . action('ProductController@create', ["d" => $row->product()->first()->id]) . '"><i class="fa fa-copy"></i> ' . __("lang_v1.duplicate_product") . '</a></li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->removeColumn('enable_stock')
                ->removeColumn('unit')

                ->rawColumns(['mass_delete', 'printing_qty', 'unit_price', 'total_transfered', 'show_pos', 'location_name', 'total_sold', 'total_adjusted', 'stock', 'actions', 'image', 'price_input'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $suppliers = Supplier::orderBy('name', 'ASC')->pluck('name', 'id');

        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.stock_in_out')
            ->with(compact('categories', 'suppliers', 'units', 'business_locations'));
    }

    // stock out report old 
    public function getstockOutReportOLD(Request $request)
    {
        if (!auth()->user()->can('stock_in_out.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $selling_price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();
        $allowed_selling_price_group = false;
        foreach ($selling_price_groups as $selling_price_group) {
            if (auth()->user()->can('selling_price_group.' . $selling_price_group->id)) {
                $allowed_selling_price_group = true;
                break;
            }
        }

        //Return the details in ajax call
        if ($request->ajax()) {

            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('colors', 'p.color_id', '=', 'colors.id')
                ->join('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->join('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->join('categories', 'p.category_id', '=', 'categories.id')
                ->join('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                ->join('location_transfer_details as ltd2', 'variations.id', '=', 'ltd2.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')

                ->where('p.business_id', $business_id)
                ->whereIn('p.type', ['single', 'variable']);
            // dd($query);
            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';
            $location_filter2 = '';

            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
                $location_filter2 .= "AND ltd2.transfered_from  IN ($locations_imploded) ";
            }
            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
                $location_filter2 .= "AND ltd2.transfered_from=$location_id ";
            }
            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }
            if (!empty($request->input('supplier_id'))) {
                $query->where('p.supplier_id', $request->input('supplier_id'));
            }
            $from_date = request()->get('start_date', null);
            $to_date = request()->get('end_date', null);
            if (!empty($to_date)) {
                // dd($products->first());
                // $query->whereDate('p.updated_at', '>=', $from_date)->whereDate('p.updated_at', '<=', $to_date);
                $query->whereDate('vld.updated_at', '>=', $from_date)->whereDate('vld.updated_at', '<=', $to_date);
                // $query->where('vld.qty_available', '>', 0);
            }
            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }
            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }
            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }
            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN
            $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
            // $query->join('product.color_id','=','color.id');
            // dd($from_date,$to_date);
            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),
                // DB::raw("SUM(CASE WHEN ltd2.transfered_from = $location_id AND (DATE(transfered_on) BETWEEN '$from_date' AND '$to_date') THEN ltd2.quantity ELSE 0 END) as total_transfered"),
                DB::raw("SUM(CASE WHEN (DATE(transfered_on) BETWEEN '$from_date' AND '$to_date') $location_filter2 THEN ltd2.quantity ELSE 0 END) as total_transfered"),
                // DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                //         JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                //         WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                //         AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                            JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                            WHERE transactions.status='final' AND transactions.type='sell' $location_filter
                            AND TSL.variation_id=variations.id AND (DATE(transactions.created_at) BETWEEN '$from_date' AND '$to_date')) as total_sold"),
                // DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                //         JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                //         WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                //         AND (TSL.variation_id=variations.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                            JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                            WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                            AND (SAL.variation_id=variations.id)) as total_adjusted"),
                DB::raw("SUM(vld.qty_available) as stock"),

                'variations.sub_sku as sku',
                'p.id as product_id',
                'bl.name as location_name',
                'vld.location_id as location_id',
                'p.created_at',
                'p.show_pos as show_pos',
                'p.name as product',
                'p.image as image',
                'p.description as description',
                'p.type',
                'p.refference',
                'p.updated_at as product_updated',
                'colors.name as color_name',
                'suppliers.name as supplier_name',
                'categories.name as category_name',
                'sub_cat.name as sub_category_name',
                'sizes.name as size_name',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                // 'variations.sell_price_inc_tax as unit_price',
                'vld.sell_price as unit_price',
                'pv.name as product_variation',
                'vld.updated_at as product_date',
                'vld.location_print_qty as printing_qty',
                'variations.name as variation_name',
                'vld.updated_at',
                // 'ltd.quantity as stock_out',
                // 'vld.qty_available as current_stock'
                // DB::raw('SUM(vld.qty_available) as current_stock'),
                // DB::raw('SUM(ltd.quantity) as total_transfered')

            )->groupBy('variations.id')
                // ->having('total_sold', '>', 0)
                ->havingRaw('total_transfered > 0 OR total_sold > 0')

                ->orderBy('vld.updated_at', 'DESC');

            // ->orderBy('vld.product_updated_at', 'DESC');
            // dd($products->first());
            // dd($products->first()->product()->first()->image_url);


            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('mass_delete', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"> <input type="number" class="row-print-qty form-control disabled" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="stock_qty_' . $row->product_id . '">';
                    // return  '<input type="checkbox" class="row-select" value="' . $row->product_id . '"><input type="number" class="row-qty form-control" value="' . number_format($row->current_stock) . '" max="' . number_format($row->current_stock) . '" style="width:70px;" id="qty_' . $row->product_id . '">';
                })
                ->editColumn('show_pos', function ($row) {
                    // dd($row);   
                    $web_items = WebsiteProducts::where('product_id', $row->product_id)->first();
                    $data = '';
                    if ($row->show_pos) {
                        $data .= '<span class="btn btn-xs btn-info">TOP</span>';
                    } else {
                        $data .= '<span class="btn btn-xs btn-danger">Normal</span>';
                    }
                    if ($web_items) {
                        $data .= '<span class="btn btn-xs btn-success">On Website</span>';
                    }
                    return $data;
                })
                ->editColumn('printing_qty', function ($row) {
                    if ($row->printing_qty < 1) {
                        $qty = $row->current_stock;
                    } else {
                        $qty = $row->printing_qty;
                    }
                    return  'Print: <input type="number" class="row-print-qty form-control" value="' . number_format($qty) . '" max="' . number_format($qty) . '" style="width:70px;" id="printing_qty_' . $row->product_id . '">';
                })
                // ->addColumn('color_id', function ($row) {
                //     // return  $row->first()->product()->first()->color()->first()->name;
                //     $product = Product::find($row->product_id);
                //     return  $product->color()->first()->id;
                // })
                // ->addColumn('supplier_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->supplier()->first()->name;
                // })
                // ->addColumn('category_id', function ($row) {
                //     $product = Product::find($row->product_id);

                //     return  $product->category()->first()->name;
                // })
                // ->addColumn('sub_category_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->sub_category()->first()->name;
                // })
                // ->addColumn('sub_size_id', function ($row) {
                //     $product = Product::find($row->product_id);
                //     return  $product->sub_size()->first()->name;
                // })
                ->editColumn('image', function ($row) {
                    $product = Product::find($row->product_id);
                    $url = url("/products/view/") . '/';
                    if (!empty($product->image) && !is_null($product->image)) {
                        return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="' . $url . $row->product()->first()->id . '"></div>';
                        // return '<div style="display: flex;"><img src="' . asset('/uploads/img/' . $product->image) . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    } else {
                        return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="data-href="{{url("/products/view/".$row->product()->first()->id)}}"></div>';
                        // return '<div style="display: flex;"><img src="' . $product->image_url . '" alt="Product image" class="product-thumbnail-small" data-href="{{action(ProductController@view, [$row->product()->first()->id])}}"></div>';
                    }
                })
                ->addColumn('sale_percent', function ($row) {
                    $quantity_sold =  (float) $row->total_sold;
                    $quantity_available =  (float) $row->stock  + $quantity_sold;
                    if ($quantity_available < 1) {
                        $quantity_available = 1;
                    }
                    $percent = number_format((($quantity_sold / $quantity_available) * 100), 0);
                    return $percent . '%';
                })
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float) $stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float) $stock . '</span>' . ' ' . $row->unit;
                    } else {
                        return 'N/A';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float) $row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('total_transfered', function ($row) {
                    $total_transfered = 0;
                    // if ($row->total_transfered) {
                    //     $total_transfered =  (float) $row->total_transfered;
                    // }
                    $location_id = 0;
                    if (!empty(request()->input('location_id'))) {
                        $location_id = request()->input('location_id');
                        $total_transfered =  (float) $row->total_transfered;
                    } else {
                        $total_transfered =  (float) $row->total_transfered / 2;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '"  >' . $total_transfered . '</span> ';
                })
                // ->editColumn('total_transfered', function ($row) {
                //     $total_transfered = 0;
                //     if ($row->total_transfered) {
                //         $total_transfered =  (float) $row->total_transfered;
                //     }

                //     return '<span data-is_quantity="true" class="display_currency total_transfered" data-currency_symbol=false data-orig-value="' . $total_transfered . '" data-unit="' . $row->unit . '" >' . $total_transfered . '</span> ' . $row->unit;
                // })
                ->editColumn('location_name', function ($row) {
                    // return '<span max="' . $row->location_id . '" id="location_' . $row->product_id . '">' . $row->location_name . '</span> ';
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                ->editColumn('total_adjusted', function ($row) {
                    $total_adjusted = 0;
                    if ($row->total_adjusted) {
                        $total_adjusted =  (float) $row->total_adjusted;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false  data-orig-value="' . $total_adjusted . '" data-unit="' . $row->unit . '" >' . $total_adjusted . '</span> ' . $row->unit;
                })
                // ->editColumn('description', function ($row) {
                //     $description = '-';
                //     if ($row->description) {
                //         $description = $row->description;
                //     }
                //     return $description;
                // })
                ->editColumn('unit_price', function ($row) use ($allowed_selling_price_group) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                            . $row->unit_price . '</span>';
                    }

                    if ($allowed_selling_price_group) {
                        $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) . '">' . __('lang_v1.view_group_prices') . '</button>';
                    }

                    return $html;
                })
                // ->addColumn(
                //     'actions',
                //     function ($row) use ($selling_price_group_count) {
                //         $html =
                //             '<div class="btn-group">
                //             <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false"> <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                //             </button>
                //             <ul class="dropdown-menu dropdown-menu-right" role="menu">
                //                 <li><a href="' . action('LabelsController@show') . '?product_id=' . $row->product()->first()->id . '" data-toggle="tooltip" title="Print Barcode/Label"><i class="fa fa-barcode"></i> ' . __('barcode.labels') . '</a></li>';

                //         if (auth()->user()->can('product.view')) {
                //             $html .=
                //                 '<li><a href="' . action('ProductController@view', [$row->product()->first()->id]) . '" class="view-product"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                //         }

                //         if (auth()->user()->can('product.update')) {
                //             $html .=
                //                 '<li><a href="' . action('ProductController@edit', [$row->product()->first()->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                //         }

                //         if (auth()->user()->can('product.delete')) {
                //             $html .=
                //                 '<li><a href="' . action('ProductController@destroy', [$row->product()->first()->id]) . '" class="delete-product"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                //         }

                //         if ($row->is_inactive == 1) {
                //             $html .=
                //                 '<li><a href="' . action('ProductController@activate', [$row->product()->first()->id]) . '" class="activate-product"><i class="fa fa-circle-o"></i> ' . __("lang_v1.reactivate") . '</a></li>';
                //         }

                //         $html .= '<li class="divider"></li>';

                //         if (auth()->user()->can('product.create')) {
                //             if ($row->enable_stock == 1) {
                //                 $html .=
                //                     '<li><a href="#" data-href="' . action('OpeningStockController@add', ['product_id' => $row->product()->first()->id]) . '" class="add-opening-stock"><i class="fa fa-database"></i> ' . __("lang_v1.add_edit_opening_stock") . '</a></li>';
                //             }

                //             if ($selling_price_group_count > 0) {
                //                 $html .=
                //                     '<li><a href="' . action('ProductController@addSellingPrices', [$row->product()->first()->id]) . '"><i class="fa fa-money"></i> ' . __("lang_v1.add_selling_price_group_prices") . '</a></li>';
                //             }

                //             $html .=
                //                 '<li><a href="' . action('ProductController@create', ["d" => $row->product()->first()->id]) . '"><i class="fa fa-copy"></i> ' . __("lang_v1.duplicate_product") . '</a></li>';
                //         }

                //         $html .= '</ul></div>';

                //         return $html;
                //     }
                // )
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                // ->setRowAttr([
                //     'data-href' => function ($row) {
                //         if (auth()->user()->can("product.view")) {
                //             return  action('ProductController@view', [$row->product()->first()->id]);
                //         } else {
                //             return '';
                //         }
                //     }
                // ])
                // ->removeColumn('id')
                ->rawColumns(['mass_delete', 'printing_qty', 'unit_price', 'total_transfered', 'show_pos', 'location_name', 'total_sold', 'total_adjusted', 'stock', 'actions', 'image'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $suppliers = Supplier::orderBy('name', 'ASC')->pluck('name', 'id');

        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.stock_in_out')
            ->with(compact('categories', 'suppliers', 'units', 'business_locations'));
    }

    // new function for fetstockOutReport
    public function getstockOutReport(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 280); //3 minutes
        if (!auth()->user()->can('stock_in_out.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);

        $location_filter = '';
        if (!empty($location_id)) {
            $location_filter = "AND transactions.location_id=$location_id";
        }

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }
        if ($request->ajax()) {

            $variation_id = $request->get('variation_id', null);
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
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('colors', 'p.color_id', '=', 'colors.id')
                ->leftjoin('sizes', 'p.sub_size_id', '=', 'sizes.id')
                ->leftjoin('suppliers', 'p.supplier_id', '=', 'suppliers.id')
                ->leftjoin('categories', 'p.category_id', '=', 'categories.id')
                ->leftjoin('categories as sub_cat', 'p.sub_category_id', '=', 'sub_cat.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereDate('t.transaction_date', '>=', $start_date)->whereDate('t.transaction_date', '<=', $end_date);
            }
            $query->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.image as image',
                'p.refference as refference',
                'p.sku as barcode',
                'p.supplier_id as supplier',
                'p.enable_stock',
                'p.type as product_type',
                'pv.name as product_variation',
                'v.name as variation_name',
                't.id as transaction_id',
                'colors.name as color_name',
                'suppliers.name as supplier_name',
                'categories.name as category_name',
                'sub_cat.name as sub_category_name',
                'sizes.name as size_name',
                't.transaction_date as transaction_date',
                'transaction_sell_lines.unit_price_before_discount as unit_price',

                DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),

                DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold'),
                // DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_refference = p.refference) as total_sold"),
                DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_id = p.id) as total_sold"),
                DB::raw('DATE_FORMAT(p.product_updated_at, "%Y-%m-%d %H:%i:%s") as product_updated_at'),
                'u.short_name as unit',
                DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
            )
                ->orderBy('total_qty_sold', 'DESC')
                // ->groupBy('transaction_sell_lines.product_refference');
                ->groupBy('transaction_sell_lines.product_id');

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $purchase_start_date = $request->get('purchase_start_date');
            $purchase_end_date = $request->get('purchase_end_date');

            if (!empty($purchase_start_date) && !empty($purchase_end_date)) {
                $query->whereBetween(DB::raw('date(p.product_updated_at)'), [$purchase_start_date, $purchase_end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $supplier_id = $request->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $query->where('p.supplier_id', $supplier_id);
            }
            $category_id = $request->get('category_id', null);
            if (!empty($category_id)) {
                $query->where('p.category_id', $category_id);
            }
            $sub_category_id = $request->get('sub_category_id', null);
            if (!empty($sub_category_id)) {
                $query->where('p.sub_category_id', $sub_category_id);
            }
            return Datatables::of($query)
                ->editColumn('refference', function ($row) {
                    if ($row->refference) {
                        return $row->refference;
                    } else {
                        return '<span>-</span>';
                    }
                })->editColumn('color_name', function ($row) {
                    if ($row->color_name) {
                        return $row->color_name;
                    } else {
                        return '<span>-</span>';
                    }
                })->editColumn('category_name', function ($row) {
                    if ($row->category_name) {
                        return $row->category_name;
                    } else {
                        return '<span>-</span>';
                    }
                })->editColumn('sub_category_name', function ($row) {
                    if ($row->sub_category_name) {
                        return $row->sub_category_name;
                    } else {
                        return '<span>-</span>';
                    }
                })->editColumn('size_name', function ($row) {
                    if ($row->size_name) {
                        return $row->size_name;
                    } else {
                        return '<span>-</span>';
                    }
                })
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('transaction_date', '{{@format_date($formated_date)}}')
                ->editColumn('total_qty_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (int) $row->total_qty_sold . '" data-unit="' . $row->unit . '" >' . (int) $row->total_qty_sold . '</span> ' . $row->unit;
                })
                ->editColumn('image', function ($row) {
                    return '<div style="display: flex;"><img src="' . $row->image_url . '" alt="Product image" class="product-thumbnail-small"></div>';
                })
                ->editColumn('product_updated_at', function ($row) {
                    return Carbon::parse($row->product_updated_at)->format('d-M-Y H:i');
                })
                ->editColumn('unit_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_price . '</span>';
                })
                ->editColumn('total_sold', function ($row) {
                    return '<span  class="total_sold" data-currency_symbol=false data-orig-value="' . (int)$row->total_sold . '" data-unit="' . $row->unit . '" >' . (int) $row->total_sold . '</span> ' . $row->unit;
                })
                ->addColumn('sale_percentage', function ($row) {
                    if ($row->refference && ($row->total_qty_sold > 0 || $row->current_stock > 0)) {
                        $sum = $row->total_qty_sold + $row->current_stock;
                        if ($sum) {
                            $percentage = ($row->total_qty_sold * 100) / $sum;
                            return (int)$percentage . ' %';
                        } else {

                            return '0 %';
                        }
                    } else {
                        return '-';
                    }
                })
                ->editColumn('subtotal', function ($row) {
                    return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("product.view")) {
                            return  action('ProductController@viewProductRefDetailWithSale', [$row->refference]);
                        } else {
                            return '';
                        }
                    }
                ])
                ->rawColumns(['image', 'total_sold','size_name','sub_category_name','category_name','color_name', 'unit_price', 'current_stock', 'subtotal', 'total_qty_sold', 'detail', 'refference', 'all_time_purchased', 'all_time_sold', 'seven_day_sold', 'fifteen_day_sold'])
                ->make(true);
        }
    }

    public function getstockInOutGroupedReport1(Request $request)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 180); //3 minutes
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id');

        $location_filter = '';
        if (!empty($location_id)) {
            $location_filter = "AND transactions.location_id=$location_id";
        }

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }
        // $start_date = $request->get('start_date');
        // $end_date = $request->get('end_date');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
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
                // ->rightjoin('variation_location_details as vlds', 'v.id', '=', 'vlds.variation_id')
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                // ->join('suppliers as s', 'p.supplier_id', '=', 's.id')
                ->leftjoin('variation_location_details as vld', 'v.id', '=', 'vld.variation_id')
                ->leftjoin('location_transfer_details as ltd2', 'v.id', '=', 'ltd2.variation_id')
                ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                // $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
                $query->whereDate('t.transaction_date', '>=', $start_date)->whereDate('t.transaction_date', '<=', $end_date);
            }


            $query->select(
                'p.id as product_id',
                'p.name as product',
                'p.image as image',
                'bl.name as location_name',
                'p.refference as refference',
                'p.sku as barcode',
                'p.supplier_id as supplier',
                'p.enable_stock',
                'p.type as product_type',
                'pv.name as product_variation',
                'v.name as variation_name',
                't.id as transaction_id',
                't.transaction_date as transaction_date',
                'transaction_sell_lines.unit_price_before_discount as unit_price',
                DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.product_refference=p.refference  $vld_str) as current_stock"),
                DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_sold'),
                DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold'),
                // DB::raw("(SELECT SUM(tsl.quantity) FROM transaction_sell_lines as tsl WHERE tsl.product_refference = p.refference) as total_sold"),
                DB::raw('DATE_FORMAT(p.product_updated_at, "%Y-%m-%d %H:%i:%s") as product_updated_at'),
                // 'p.product_updated_at as product_updated_at',
                'u.short_name as unit',
                DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
            )
                // ->groupBy('v.id')
                ->orderBy('total_qty_sold', 'DESC')
                ->groupBy('transaction_sell_lines.product_refference');
            // ->groupBy('formated_date');

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }


            // $supplier_id = $request->get('supplier_id', null);
            // if (!empty($supplier_id)) {
            //     $query->where('p.supplier_id', $supplier_id);
            // }
            // $category_id = $request->get('category_id', null);
            // if (!empty($category_id)) {
            //     $query->where('p.category_id', $category_id);
            // }
            // $sub_category_id = $request->get('sub_category_id', null);
            // if (!empty($sub_category_id)) {
            //     $query->where('p.sub_category_id', $sub_category_id);
            // }
            return Datatables::of($query)
                ->addIndexColumn()
                ->editColumn('refference', function ($row) {
                    if ($row->refference) {
                        return $row->refference;
                    } else {
                        return '<span>-</span>';
                    }
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                // ->editColumn('transaction_date', '{{@format_date($formated_date)}}')
                ->editColumn('total_qty_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float) $row->total_qty_sold . '" data-unit="' . $row->unit . '" >' . (float) $row->total_qty_sold . '</span> ' . $row->unit;
                })
                ->editColumn('image', function ($row) {
                    return '<div style="display: flex;"><img src="' . $row->image_url . '" alt="Product image" class="product-thumbnail-small"></div>';
                })
                ->editColumn('product_updated_at', function ($row) {
                    return Carbon::parse($row->product_updated_at)->format('d-M-Y H:i');
                })
                ->editColumn('unit_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_price . '</span>';
                })
                ->editColumn('total_sold', function ($row) {
                    return '<span  class="total_sold" data-currency_symbol=false data-orig-value="' . (int)$row->total_sold . '" data-unit="' . $row->unit . '" >' . (int) $row->total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('location_name', function ($row) {
                    // return '<span max="' . $row->location_id . '" id="location_' . $row->product_id . '">' . $row->location_name . '</span> ';
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })

                ->editColumn('current_stock', function ($row) {
                    if ($row->enable_stock) {
                        return '<span data-is_quantity="true" class="display_currency current_stock" data-currency_symbol=false data-orig-value="' . (float) $row->current_stock . '" data-unit="' . $row->unit . '" >' . (float) $row->current_stock . '</span> ' . $row->unit;
                    } else {
                        return '-';
                    }
                })
                ->addColumn('detail', function ($row) {
                    $start_date = request()->get('start_date');
                    $end_date = request()->get('end_date');
                    return '<a id="color-detail-modal" href="' . url("/product/color-detail/" . $row->product_name . '/' . $start_date . '/' . $end_date) . '" data-product-name="' . $row->product_name . '" class="btn btn-primary btn-sm">Color Report <i class="fa fa-eye"></i></a>';
                })
                ->addColumn('sale_percentage', function ($row) {
                    if ($row->refference && ($row->total_qty_sold > 0 || $row->current_stock > 0)) {
                        $sum = $row->total_qty_sold + $row->current_stock;
                        if ($sum) {
                            $percentage = ($row->total_qty_sold * 100) / $sum;
                            return (int)$percentage . ' %';
                        } else {

                            return '0 %';
                        }
                    } else {
                        return '-';
                    }
                })
                ->editColumn('subtotal', function ($row) {
                    return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("product.view")) {
                            return  action('ProductController@viewProductRefDetailWithSale', [$row->refference]);
                            // return  action('ProductController@view', [$row->product_id]);
                        } else {
                            return '';
                        }
                    }
                ])
                ->rawColumns(['image', 'total_sold', 'unit_price', 'current_stock', 'subtotal', 'total_qty_sold', 'detail', 'location_name', 'refference', 'all_time_purchased',])
                ->make(true);
        }
        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
        $suppliers = Supplier::orderBy('name', 'ASC')->pluck('name', 'id');

        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.stock_in_out')
            ->with(compact('categories', 'suppliers', 'units', 'business_locations'));
    }


    public function massVldSellPrice(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        // dd($request->input());
        try {
            if (!empty($request->input('selected_products_bulkprice'))) {
                $business_id = $request->session()->get('user.business_id');
                $user_location_id = $request->session()->get('user.business_location_id');
                if ($request->input('current_location_id')) {
                    if ($request->input('current_location_id') == 0) {
                        $output = [
                            'success' => 0,
                            'msg' => "All Location is Invalid Select Valid Location"
                        ];
                        return redirect()->back()->with('status', $output);
                    }
                    $user_location_id = $request->input('current_location_id');
                }
                $user_id = $request->session()->get('user.id');

                $selected_products = explode(',', $request->input('selected_products_bulkprice'));
                $business_location_id = $request->input('bussiness_bulkPrice');
                $location_id = $business_location_id;
                // dd($user_location_id,$location_id,$request->input());
                // dd($selected_products);
                foreach ($selected_products as $key => $objProduct) {
                    # code...
                    $arr = explode("@", $objProduct);
                    $productId = $arr[0];
                    $productQty = $arr[1];
                    $productOrignalQty = $arr[2];
                    // dd($arr,$location_id);
                    $all_products = VariationLocationDetails::where("location_id", $location_id)->where("product_id", $productId)->get();
                    foreach ($all_products as $all_product) {
                        // dd($arr,$location_id);
                        $all_product->update([
                            'sell_price' => (float)$productQty,
                            'old_sell_price' => (float)$productOrignalQty,
                            'updated_at' => Carbon::now(),
                            'product_updated_at' => Carbon::now(),
                        ]);
                        // dd($all_product);
                    }
                }
                $output = [
                    'success' => 1,
                    'msg' => "PRICE CHANGE SUCCESSFULY !"
                ];
            }

            $output = [
                'success' => 1,
                'msg' => "PRICE CHANGE SUCCESSFULY !"
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
    public function getCouponReport(Request $request)
    {
        if (!auth()->user()->can('coupon_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');

        if (request()->ajax()) {
            $products = Coupon::leftjoin('transactions', 'coupons.transaction_id', '=', 'transactions.id')
                // $products = Coupon::leftJoin('transactions', DB::raw("TRIM(LEADING ',' FROM coupons.transaction_id)"), '=', 'transactions.id')
                ->leftjoin('business_locations as bl', 'bl.id', '=', 'coupons.location_id')
                // leftJoin('gift_cards', 'coupons.gift_card_id', '=', 'gift_cards.id')
                ->where('coupons.business_id', $business_id)
                ->where('coupons.created_by', $user_id);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';

            if ($permitted_locations != 'all') {
                $products->whereIn('transactions.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }
            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $products->where('coupons.location_id', $location_id);
            };
            $from_date = request()->get('start_date', null);
            $to_date = request()->get('end_date', null);
            if (!empty($to_date)) {
                $products->whereDate('coupons.created_at', '>=', $from_date)->whereDate('coupons.created_at', '<=', $to_date);
            }

            $products->select(
                'coupons.id',
                'coupons.name',
                // 'gift_cards.barcode as GiftCardBarcode',
                'bl.name as location_name',
                'coupons.value',
                'coupons.orig_value',
                'coupons.barcode',
                'coupons.start_date',
                'coupons.isActive',
                'coupons.details',
                'coupons.isUsed',
                // 'transactions.location_id',
                'coupons.location_id',
                'transactions.created_at as usedDate',
            )
                ->groupBy('coupons.barcode');

            $type = request()->get('type', null);
            if (!empty($type)) {
                $products->where('coupons.isActive', $type);
            } else {
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
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">' . __("messages.actions") . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
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
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return '<div style="display: flex;">' . $row->name . '</div>';
                })
                ->editColumn('value', function ($row) {
                    // return '<div style="display: flex;">' . $row->value . ' / ' . $row->orig_value . '</div>';
                    return '<div style="display: flex;">' . $row->value . '</div>';
                })
                ->editColumn('used_value', function ($row) {
                    return ($row->orig_value - $row->value);
                })
                ->editColumn('barcode', function ($row) {
                    return '<div style="display: flex;">' . $row->barcode . '</div>';
                })
                ->editColumn('location_name', function ($row) {
                    // return '<span max="' . $row->location_id . '" id="location_' . $row->product_id . '">' . $row->location_name . '</span> ';
                    $location_id = request()->get('location_id', null);
                    if ($location_id) {
                        return  $row->location_name;
                    } else {
                        return 'All Locations';
                    }
                })
                ->editColumn('details', function ($row) {
                    return $row->details;
                })
                ->editColumn('start_date', function ($row) {
                    return date("Y-m-d h:i A", strtotime($row->start_date));
                })
                ->editColumn('usedDate', function ($row) {
                    if (is_null($row->usedDate)) {
                        return 'Not used till';
                    } else {
                        return date("Y-m-d h:i A", strtotime($row->usedDate));
                    }
                })
                ->editColumn('CouponExpiryDate', function ($row) {
                    return date("Y-m-d h:i A", strtotime($row->start_date . "+3 months"));
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
                ->rawColumns(['action', 'name', 'details', 'value', 'mass_delete', 'barcode', 'start_date', 'location_name', 'usedDate'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        return view('report.coupon_report')->with(compact('business_locations'));
    }
}
