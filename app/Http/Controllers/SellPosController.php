<?php
/* LICENSE: This source file belongs to The Web Fosters. The customer
 * is provided a licence to use it.
 * Permission is hereby granted, to any person obtaining the licence of this
 * software and associated documentation files (the "Software"), to use the
 * Software for personal or business purpose ONLY. The Software cannot be
 * copied, published, distribute, sublicense, and/or sell copies of the
 * Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. THE AUTHOR CAN FIX
 * ISSUES ON INTIMATION. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH
 * THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author     The Web Fosters <thewebfosters@gmail.com>
 * @owner      The Web Fosters <thewebfosters@gmail.com>
 * @copyright  2018 The Web Fosters
 * @license    As attached in zip file.
 */

namespace App\Http\Controllers;

use App\GiftCard;


use App\Account;
use App\Coupon;
use App\AccountTransaction;
use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\CustomerGroup;
use App\Media;
use App\Product;
use App\ProductNameCategory;
use App\SellingPriceGroup;
use App\Supplier;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\Unit;
use App\User;

use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ContactUtil;

use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\WebsiteProducts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use Yajra\DataTables\Facades\DataTables;

class SellPosController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $contactUtil;
    protected $productUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $cashRegisterUtil;
    protected $moduleUtil;
    protected $notificationUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(
        ContactUtil $contactUtil,
        ProductUtil $productUtil,
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        CashRegisterUtil $cashRegisterUtil,
        ModuleUtil $moduleUtil,
        NotificationUtil $notificationUtil
    ) {
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->notificationUtil = $notificationUtil;

        $this->dummyPaymentLine = [
            'method' => 'cash',
            'amount' => 0,
            'note' => '',
            'card_transaction_number' => '',
            'card_number' => '',
            'card_type' => '',
            'card_holder_name' => '',
            'card_month' => '',
            'card_year' => '',
            'card_security' => '',
            'cheque_number' => '',
            'bank_account_number' => '',
            'gift_card' => '',
            'coupon' => '',
            'force_price' => '',
            'unknown' => '',
            'bonus_points' => '',
            'is_return' => 0,
            'transaction_no' => ''
        ];
    }



    function verifyGiftCard($getGiftCard)
    {
        $output = [];
        try {
            $business_id = request()->session()->get('user.business_id');
            $output['success'] = true;
            $attributes = ['name' => $getGiftCard, 'barcode' => $getGiftCard];
            $objGiftCards = GiftCard::where('gift_cards.business_id', $business_id)
                ->where(function ($query) use ($attributes) {
                    foreach ($attributes as $key => $value) {
                        //you can use orWhere the first time, dosn't need to be ->where
                        $query->orWhere($key, $value);
                    }
                })
                ->select(
                    'gift_cards.id',
                    'gift_cards.name',
                    'gift_cards.value',
                    'gift_cards.barcode',
                    'gift_cards.start_date',
                    'gift_cards.expiry_date',
                    'gift_cards.details',
                    'gift_cards.isActive',
                    'gift_cards.isUsed'
                )->first();

            if (!empty($objGiftCards)) {
                $objGiftCards['current_date'] = date('Y-m-d h:i:s');
                $output['msg'] = $objGiftCards;
            } else {
                $output['success'] = false;
                $output['msg'] = "Sorry No Data Found Regarding This Gift Card " . $getGiftCard;
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output['success'] = false;
            $output['msg'] = __('gift.error') . " \n " . $e->getMessage();
        }

        return $output;;
    }

    function getCustDiscount($getCustId)
    {
        $output = [];
        try {
            $business_id = request()->session()->get('user.business_id');
            $output['success'] = true;

            $objContact = Contact::where('id', $getCustId)->where('business_id', $business_id)->first();

            if (!empty($objContact)) {
                $output['msg'] = $objContact;
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output['success'] = false;
            $output['msg'] = __('gift.error') . " \n " . $e->getMessage();
        }

        return $output;;
    }

    function verifyCoupon($getGiftCard)
    {
        $output = [];
        try {
            $business_id = request()->session()->get('user.business_id');
            $output['success'] = true;
            $attributes = ['barcode' => $getGiftCard];
            $objGiftCards = Coupon::where('coupons.business_id', $business_id)
                ->where(function ($query) use ($attributes) {
                    foreach ($attributes as $key => $value) {
                        //you can use orWhere the first time, dosn't need to be ->where
                        $query->orWhere($key, $value);
                    }
                })
                ->select(
                    'coupons.id',
                    'coupons.name',
                    'coupons.value',
                    'coupons.barcode',
                    'coupons.start_date',
                    'coupons.isActive',
                    'coupons.isUsed'
                )->first();

            if (!empty($objGiftCards)) {
                $objGiftCards['current_date'] = date('Y-m-d h:i:s');
                $expiryDate = date("Y-m-d h:i:s", strtotime($objGiftCards['current_date'] . "+3 months"));
                if ($objGiftCards['start_date'] <= $expiryDate) {
                    $output['msg'] = $objGiftCards;
                } else {
                    $output['success'] = false;
                    $output['msg'] = "Sorry This Coupon " . $getGiftCard . " Is Expired ";
                }
            } else {
                $output['success'] = false;
                $output['msg'] = "Sorry No Data Found Regarding This Gift Card " . $getGiftCard;
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output['success'] = false;
            $output['msg'] = __('gift.error') . " \n " . $e->getMessage();
        }

        return $output;;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');


        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        return view('sale_pos.index')->with(compact('business_locations', 'customers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // dd(1);
        if (!auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        $objBusiness = Business::where("id", $business_id)->first();
        //Update USER SESSION
        $user_id = request()->session()->get('user.id');
        $user = \App\User::find($user_id);

        request()->session()->put('user', $user->toArray());

        $business_location_id = request()->session()->get('user.business_location_id');
        //Update USER SESSION

        //Check if subscribed or not, then check for users quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('HomeController@index'));
        } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellPosController@index'));
        }

        //Check if there is a open register, if no then redirect to Create Register screen.
        if ($this->cashRegisterUtil->countOpenedRegister() == 0) {
            return redirect()->action('CashRegisterController@create');
        }

        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

        // dd($walk_in_customer);

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);
        $payment_types = $this->productUtil->payment_types();

        $payment_lines[] = $this->dummyPaymentLine;

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $default_location = null;
        if (count($business_locations) == 1) {
            foreach ($business_locations as $id => $name) {
                $default_location = $id;
            }
        }

        //Shortcuts
        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id, false);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id, false);
        }

        //If brands, category are enabled then send else false.
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id) : false;
        $brands = Supplier::orderBy('name', 'ASC')->pluck('name', 'id');
        // $brands = (request()->session()->get('business.enable_brand') == 1) ? Brands::where('business_id', $business_id)
        //     ->pluck('name', 'id')
        //     ->prepend(__('lang_v1.all_brands'), 'all') : false;

        $change_return = $this->dummyPaymentLine;

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        //Selling Price Group Dropdown
        $price_groups = SellingPriceGroup::forDropdown($business_id);
        // dd($categories);
        return view('sale_pos.create')
            ->with(compact(
                'business_details',
                'taxes',
                'payment_types',
                'walk_in_customer',
                'payment_lines',
                'business_locations',
                'bl_attributes',
                'default_location',
                'shortcuts',
                'commission_agent',
                'categories',
                'brands',
                'pos_settings',
                'change_return',
                'types',
                'customer_groups',
                'accounts',
                'objBusiness',
                'price_groups',
            ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }
        $is_direct_sale = false;
        if (!empty($request->input('is_direct_sale'))) {
            $is_direct_sale = true;
        }

        //Check if there is a open register, if no then redirect to Create Register screen.
        if (!$is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0) {
            return redirect()->action('CashRegisterController@create');
        }

        try {
            $input = $request->except('_token');

            //Check Customer credit limit
            $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input);

            if ($is_credit_limit_exeeded !== false) {
                $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount])
                ];
                if (!$is_direct_sale) {
                    return $output;
                } else {
                    return redirect()
                        ->action('SellController@index')
                        ->with('status', $output);
                }
            }

            $input['is_quotation'] = 0;
            //status is send as quotation from Add sales screen.
            if ($input['status'] == 'quotation') {
                $input['status'] = 'draft';
                $input['is_quotation'] = 1;
            }

            if (!empty($input['products'])) {

                $business_id = $request->session()->get('user.business_id');

                //Check if subscribed or not, then check for users quota
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse();
                } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
                    return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellPosController@index'));
                }

                $user_id = $request->session()->get('user.id');
                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');

                $discount = [
                    'discount_type' => $input['discount_type'],
                    'discount_amount' => $input['discount_amount']
                ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

                $checkGift = $this->GiftCardCheck($input['products']);
                $checkCoupon = $this->CouponCheck($input['products'],$request);

                DB::beginTransaction();

                if (empty($request->input('transaction_date'))) {
                    $input['transaction_date'] =  \Carbon::now();
                } else {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }
                if ($is_direct_sale) {
                    $input['is_direct_sale'] = 1;
                }
                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                if ($commsn_agnt_setting == 'logged_in_user') {
                    $input['commission_agent'] = $user_id;
                }
                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }
                //Customer group details
                $contact_id = $request->get('contact_id', null);
                $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;

                //set selling price group id
                if ($request->has('price_group')) {
                    $input['selling_price_group_id'] = $request->input('price_group');
                }

                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend']  ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }

                //Generate reference number
                if (!empty($input['is_recurring'])) {
                    //Update reference count
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount('subscription');
                    $input['subscription_no'] = $this->transactionUtil->generateReferenceNumber('subscription', $ref_count);
                }

                $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);
                
                $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id']);

                if (!$is_direct_sale) {
                    //Add change return
                    $change_return = $this->dummyPaymentLine;
                    $change_return['amount'] = $this->productUtil->num_uf($input['change_return']);
                    // $change_return['amount'] = $input['change_return'];
                    $change_return['is_return'] = 1;
                    $input['payment'][] = $change_return;
                }

                if (!$transaction->is_suspend && !empty($input['payment'])) {

                    // dd($input['payment'], $input['payment'][0]['method'],$transaction);
                    // dd($input['payment'][0]['method']);
                    $new_coupon = $this->transactionUtil->createOrUpdatePaymentLines2($transaction, $input['payment'], $request);
                }
                // ADD BONUS POINTS 
                // if ($transaction->contact_id != '1') {
                //     $objContact = Contact::where('business_id', $transaction->business_id)->where("id", $transaction->contact_id)->first();
                //     $per = $objContact->discount / 100;

                //     $leftAmount = $transaction->final_total * $per;
                //     $currentDate = date('Y-m-d');
                //     $BpExpiryDate = date('Y-m-d', strtotime($objContact->bp_expiry));

                //     if ($currentDate <= $BpExpiryDate) {
                //         $newPoints = $objContact->bonus_points + $leftAmount;
                //     } else {
                //         $newPoints =  $leftAmount;
                //         $dataUpdate = ['bonus_points' => $newPoints, 'bp_expiry' => date("Y-m-d", strtotime($currentDate . "+6 months"))];
                //     }
                //     $dataUpdate = ['bonus_points' => $newPoints, 'bp_expiry' => date("Y-m-d", strtotime($currentDate . "+6 months"))];
                //     $dataWhere = ['business_id' => $transaction->business_id, 'id' => $objContact->id];

                //     if (strcmp($input['cust_discount'], $objContact->discount) != 0) {
                //         $dataUpdate['discount'] = $input['cust_discount'];
                //     }

                //     Contact::where($dataWhere)->update($dataUpdate);
                // }
                $update_transaction = false;
                if ($this->transactionUtil->isModuleEnabled('tables')) {
                    $transaction->res_table_id = request()->get('res_table_id');
                    $update_transaction = true;
                }
                if ($this->transactionUtil->isModuleEnabled('service_staff')) {
                    $transaction->res_waiter_id = request()->get('res_waiter_id');
                    $update_transaction = true;
                }
                if ($update_transaction) {
                    $transaction->save();
                }


                //Check for final and do some processing.
                if ($input['status'] == 'final') {
                    //update product stock
                    foreach ($input['products'] as $product) {
                        if ($product['enable_stock']) {
                            $decrease_qty = $this->productUtil->num_uf($product['quantity']);
                            if (!empty($product['base_unit_multiplier'])) {
                                $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                            }

                            $this->productUtil->decreaseProductQuantity(
                                $product['product_id'],
                                $product['variation_id'],
                                $input['location_id'],
                                $decrease_qty
                            );
                        }
                    }

                    //Add payments to Cash Register
                    if (!$is_direct_sale && !$transaction->is_suspend && !empty($input['payment'])) {
                        $leftAmount_total = 0;
                        $request_bonus_point = (int)$request->cust_bonus_point;

                        if ($request_bonus_point != 0) {
                            $objContact = Contact::where('business_id', $transaction->business_id)->where("id", $transaction->contact_id)->first();
                            $discount = config('app.discount_amount');
                            // amount deduct on points
                            $amount_bonus_point = $request_bonus_point;
                            $leftAmount_total = $amount_bonus_point;
                        }
                        $this->cashRegisterUtil->addSellPayments($transaction, $input['payment'],$leftAmount_total,$request);
                    }

                    //Update payment status
                    $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                    //Allocate the quantity from purchase and add mapping of purchase & sell lines in
                    //transaction_sell_lines_purchase_lines table
                    $business_details = $this->businessUtil->getDetails($business_id);
                    $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                    $business = [
                        'id' => $business_id,
                        'accounting_method' => $request->session()->get('business.accounting_method'),
                        'location_id' => $input['location_id'],
                        'pos_settings' => $pos_settings
                    ];
                    $this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');

                    //Auto send notification
                    $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
                }

                //Set Module fields
                if (!empty($input['has_module_data'])) {
                    $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                }


                Media::uploadMedia($business_id, $transaction, $request, 'documents');

                DB::commit();

                $msg = '';
                $receipt = '';
                if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                    $msg = trans("sale.draft_added");
                } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                    $msg = trans("lang_v1.quotation_added");
                    if (!$is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    } else {
                        $receipt = '';
                    }
                } elseif ($input['status'] == 'final') {
                    // dd("Controller ".(object)$new_coupon);
                    if (empty($input['sub_type'])) {
                        $msg = trans("sale.pos_sale_added");
                        if (!$is_direct_sale && !$transaction->is_suspend) {
                            $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id, null, false, $new_coupon);
                        } else {
                            $receipt = '';
                        }
                    } else {
                        $msg = trans("sale.pos_sale_added");
                        $receipt = '';
                    }
                }

                $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt];
            } else {
                $output = [
                    'success' => 0,
                    'msg' => trans("messages.something_went_wrong")
                ];
            }
            $data = collect($request->input('products'));
            $product_ids = $data->pluck('product_id')->toArray();
            $this->removeProductsFromWebsite($product_ids);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            dd($e->getMessage() . ' in File: ' . $e->getFile() . ' on Line: ' . $e->getLine());
            $msg = trans("messages.something_went_wrong Here . " . $e->getMessage());

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = [
                'success' => 0,
                'msg' => $msg
            ];
        }

        if (!$is_direct_sale) {
            // dd($this->cashRegisterUtil->countOpenedRegister());
            return $output;
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return redirect()
                        ->action('SellController@getQuotations')
                        ->with('status', $output);
                } else {
                    return redirect()
                        ->action('SellController@getDrafts')
                        ->with('status', $output);
                }
            } else {
                if (!empty($input['sub_type']) && $input['sub_type'] == 'repair') {
                    $redirect_url = $input['print_label'] == 1 ? action('\Modules\Repair\Http\Controllers\RepairController@printLabel', [$transaction->id]) : action('\Modules\Repair\Http\Controllers\RepairController@index');
                    return redirect($redirect_url)
                        ->with('status', $output);
                }
                return redirect()
                    ->action('SellController@index')
                    ->with('status', $output);
            }
        }
    }
    /**
     * Remove products from website 
     * 
     **/
    public function removeProductsFromWebsite($product_ids)
    {
        // dd($product_ids);
        // $connection = DB::connection('mysql');
        for ($i = 0; $i < count($product_ids); $i++) {
            $product = Product::find($product_ids[$i]);
            $all_product_ids = Product::where('name', $product->name)->pluck('id')->toArray();
            // $products = WebsiteProducts::join('products as p', 'p.refference', '=', 'website_products.refference')
            $products = Product::leftJoin('variation_location_details as vld', 'vld.product_id', '=', 'products.id')
                ->leftJoin('variations as v', 'products.id', '=', 'v.product_id')
                ->leftJoin('sizes as s', 'products.sub_size_id', '=', 's.id')
                ->leftJoin('colors as c', 'c.id', '=', 'products.color_id')
                ->whereIn('products.id', $all_product_ids)

                // ->leftJoin('categories as cat', 'cat.id', '=', 'products.category_id')
                // ->leftJoin('categories as sub_cat', 'sub_cat.id', '=', 'products.sub_category_id')
                ->groupBy('id')
                ->get([
                    'products.id',
                    'products.name as name',
                    'products.sku as sku',
                    'products.image',
                    // 'cat.name as category_name',
                    // 'sub_cat.name as sub_category_name',
                    // 'c.name as color',
                    'c.color_code as color_code',
                    's.name as size',
                    'v.sell_price_inc_tax as price',
                    DB::raw('SUM(vld.qty_available) as qty'),
                    // 'website_products.quantity as quantity',
                    // DB::raw('(SELECT qty_available from variation_location_details) as qty'),
                    // 'vld.qty_available',
                ])
                ->groupBy('name');
            // ->toArray();
            // dd($product_ids, $all_product_ids, $products);
            // dd($products, $all_product_ids);
            DB::beginTransaction();
            $qurrey_count = 0;
            $all_product = 0;
            $product = 0;
            // dd($products);
            foreach ($products as $key => $value) {
                // for ($i=0; $i < count($products); $i++) {
                $qurrey_count++;
                $current_product = $value;
                $cat_id = NULL;
                $subcat_id = NULL;
                $child_id = NULL;
                $web = DB::connection('website');
                // if ($web->table('categories')->where('name', $current_product[0]->category_name)->first()) {
                //     $cat_id = $web->table('categories')->where('name', $current_product[0]->category_name)->first()->id;
                // }
                // if ($web->table('subcategories')->where('name', $current_product[0]->category_name)->first()) {
                //     $sub_category = $web->table('subcategories')->where('name', $current_product[0]->category_name)->first();
                //     $subcat_id = $sub_category->id;
                //     $cat_id = $sub_category->category_id;;
                // }
                // if ($web->table('childcategories')->where('name', $current_product[0]->sub_category_name)->first()) {
                //     $child = $web->table('childcategories')->where('name', $current_product[0]->sub_category_name)->first();
                //     $child_id = $child->id;
                //     $subcat_id = $child->subcategory_id;
                //     $cat_id = $web->table('subcategories')->find($subcat_id)->category_id;
                // }
                $size = [];
                $color = [];
                $quantity = [];
                $price = [];
                for ($j = 0; $j < count($current_product); $j++) {
                    // dd($current_product, $current_product[$j]);
                    $size[$j] =  $current_product[$j]->size;
                    if (($j > 0) && (isset($color[($j - 1)]) && ($color[($j - 1)] != $current_product[$j]->color))) {
                        $color[$j] = $current_product[$j]->color;
                    } elseif ($j == 0) {
                        $color[0] = $current_product[$j]->color;
                    }
                    // $quantity[$j] = $current_product[$j]->quantity;
                    if ($current_product[$j]->qty) {
                        $quantity[$j] = (int) $current_product[$j]->qty;
                    } else {
                        $quantity[$j] = 0;
                    }
                    $price[$j] = (float)$current_product[0]->price;
                    $all_product++;
                }
                // Create Product here
                // if (!Product::where('name', $current_product[0]->name)->first()) {
                //     $data = new Product;
                $wproduct = $web->table('products')->where('name', $value[0]->name)->first();
                if ($wproduct) {
                    // $web_product = $web->table('products')->find($wproduct->id);
                    $input = [];
                    // $input['name'] = $current_product[0]->name;
                    // $input['slug'] = strtolower($current_product[0]->name);
                    // $input['sku'] = $current_product[0]->sku;
                    // $input['photo'] = $current_product[0]->image;
                    // $input['thumbnail'] = $current_product[0]->image;
                    $input['size'] = implode(",", $size);
                    $input['size_price'] = implode(",", $price);
                    $input['size_qty'] = implode(",", $quantity);
                    $input['stock'] = $current_product[0]->qty;
                    // $input['quantity'] = $current_product[0]->qty;
                    // $input['color'] = implode(",", $color);
                    // $input['price'] = (float)$current_product[0]->price;
                    // $input['category_id'] = $cat_id;
                    // $input['subcategory_id'] = $subcat_id;
                    // $input['childcategory_id'] = $child_id;
                    $web->table('products')->where('id', $wproduct->id)->update($input); //save product
                    // $web_product->fill($input)->save(); //save product
                    $product++;
                }
            }
            // dd("Hello");
            // }
            DB::commit();

            /* $qurrey_count = 0;
            $all_product = 0;
            foreach ($products as $key => $value) {
                $qurrey_count++;
                $current_product = $value;
                $size = [];
                // $color = [];
                $quantity = [];
                // $price = [];
                for ($j = 0; $j < count($current_product); $j++) {
                    $size[$j] =  $current_product[$j]->size;
                    // $quantity[$j] = $current_product[$j]->quantity;
                    if ($current_product[$j]->qty) {
                        $quantity[$j] = (int) $current_product[$j]->qty;
                    } else {
                        $quantity[$j] = 0;
                    }
                    $name = $current_product[$j]->name;
                }
                // dd($current_product[0]);
                // Create Product here
                $web = DB::connection('website');
                $wproduct = $web->table('products')->where('name', $value[0]->name)->first();
                $web_product = $web->table('products')->find($wproduct->id);
                // dd($web_product);
                $web->table('products')->where('id', $wproduct->id)->update([
                    'size' => implode(",", $size),
                    'size_qty' => implode(",", $quantity),
                ]);
            } */
        }
    }

    function returnAndAdjust($transaction_id)
    {
        $business_id = request()->session()->get('user.business_id');
        $sell = Transaction::where('business_id', $business_id)
            ->with(['sell_lines', 'location', 'return_parent', 'contact', 'tax', 'sell_lines.sub_unit', 'sell_lines.product', 'sell_lines.product.unit'])
            ->find($transaction_id);
        // echo "<pre>";print_r($sell->sell_lines); die();
        $id = $transaction_id;
        if (!auth()->user()->can('sell.update')) {
            abort(403, 'Unauthorized action.');
        }
        //Check if the transaction can be edited or not.
        $edit_days = request()->session()->get('business.transaction_edit_days');
        if (!$this->transactionUtil->canBeEdited($id, $edit_days)) {
            return back()
                ->with('status', [
                    'success' => 0,
                    'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days])
                ]);
        }

        //Check if there is a open register, if no then redirect to Create Register screen.
        if ($this->cashRegisterUtil->countOpenedRegister() == 0) {
            return redirect()->action('CashRegisterController@create');
        }

        // //Check if return exist then not allowed
        // if ($this->transactionUtil->isReturnExist($id)) {
        //     // return back()->with('status', ['success' => 0, 'msg' => __('lang_v1.return_exist')]);
        // }

        $business_id = request()->session()->get('user.business_id');
        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);
        $payment_types = $this->productUtil->payment_types();

        $transaction = Transaction::where('business_id', $business_id)
            ->findorfail($id);

        $location_id = $transaction->location_id;
        $location_printer_type = BusinessLocation::find($location_id)->receipt_printer_type;

        $sell_details = TransactionSellLine::join(
            'products AS p',
            'transaction_sell_lines.product_id',
            '=',
            'p.id'
        )
            ->join(
                'variations AS variations',
                'transaction_sell_lines.variation_id',
                '=',
                'variations.id'
            )
            ->join(
                'product_variations AS pv',
                'variations.product_variation_id',
                '=',
                'pv.id'
            )
            ->leftjoin('variation_location_details AS vld', function ($join) use ($location_id) {
                $join->on('variations.id', '=', 'vld.variation_id')
                    ->where('vld.location_id', '=', $location_id);
            })
            ->leftjoin('units', 'units.id', '=', 'p.unit_id')
            ->where('transaction_sell_lines.transaction_id', $id)
            ->select(
                DB::raw("IF(pv.is_dummy = 0, CONCAT(p.name, ' (', pv.name, ':',variations.name, ')'), p.name) AS product_name"),
                'p.id as product_id',
                'p.enable_stock',
                'p.name as product_actual_name',
                'pv.name as product_variation_name',
                'pv.is_dummy as is_dummy',
                'variations.name as variation_name',
                'variations.sub_sku',
                'p.barcode_type',
                'p.enable_sr_no',
                'variations.id as variation_id',
                'units.short_name as unit',
                'units.allow_decimal as unit_allow_decimal',
                'transaction_sell_lines.tax_id as tax_id',
                'transaction_sell_lines.item_tax as item_tax',
                'transaction_sell_lines.unit_price as default_sell_price',
                'transaction_sell_lines.unit_price_before_discount as unit_price_before_discount',
                'transaction_sell_lines.unit_price_inc_tax as sell_price_inc_tax',
                'transaction_sell_lines.id as transaction_sell_lines_id',
                'transaction_sell_lines.quantity as quantity_ordered',
                'transaction_sell_lines.quantity_returned',
                'transaction_sell_lines.sell_line_note as sell_line_note',
                'transaction_sell_lines.parent_sell_line_id',
                'transaction_sell_lines.lot_no_line_id',
                'transaction_sell_lines.line_discount_type',
                'transaction_sell_lines.line_discount_amount',
                'transaction_sell_lines.res_service_staff_id',
                'units.id as unit_id',
                'transaction_sell_lines.sub_unit_id',
                DB::raw('vld.qty_available + transaction_sell_lines.quantity AS qty_available')
            )->orderBy('p.updated_at', 'DESC')
            ->get();
        $newSellDetails = array();
        if (!empty($sell_details)) {
            foreach ($sell_details as $key => $value) {
                //If modifier sell line then unset
                if (!empty($sell_details[$key]->parent_sell_line_id)) {
                    unset($sell_details[$key]);
                } else {
                    if ($transaction->status != 'final') {
                        $actual_qty_avlbl = $value->qty_available - $value->quantity_ordered;
                        $sell_details[$key]->qty_available = $actual_qty_avlbl;
                        $value->qty_available = $actual_qty_avlbl;
                    }

                    $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($value->qty_available, false, null, true);

                    //Add available lot numbers for dropdown to sell lines
                    $lot_numbers = [];
                    if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                        $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($value->variation_id, $business_id, $location_id);
                        foreach ($lot_number_obj as $lot_number) {
                            //If lot number is selected added ordered quantity to lot quantity available
                            if ($value->lot_no_line_id == $lot_number->purchase_line_id) {
                                $lot_number->qty_available += $value->quantity_ordered;
                            }

                            $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                            $lot_numbers[] = $lot_number;
                        }
                    }
                    $sell_details[$key]->lot_numbers = $lot_numbers;

                    if (!empty($value->sub_unit_id)) {
                        $value = $this->productUtil->changeSellLineUnit($business_id, $value);
                        $sell_details[$key] = $value;
                    }

                    $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($value->qty_available, false, null, true);

                    if ($this->transactionUtil->isModuleEnabled('modifiers')) {
                        //Add modifier details to sel line details
                        $sell_line_modifiers = TransactionSellLine::where('parent_sell_line_id', $sell_details[$key]->transaction_sell_lines_id)->get();
                        $modifiers_ids = [];
                        if (count($sell_line_modifiers) > 0) {
                            $sell_details[$key]->modifiers = $sell_line_modifiers;
                            foreach ($sell_line_modifiers as $sell_line_modifier) {
                                $modifiers_ids[] = $sell_line_modifier->variation_id;
                            }
                        }
                        $sell_details[$key]->modifiers_ids = $modifiers_ids;

                        //add product modifier sets for edit
                        $this_product = Product::find($sell_details[$key]->product_id);
                        if (count($this_product->modifier_sets) > 0) {
                            $sell_details[$key]->product_ms = $this_product->modifier_sets;
                        }
                    }
                }
                if ((int) $value->quantity_returned != 0) {
                    $value->default_sell_price = $value->default_sell_price * -1;
                    $value->unit_price_before_discount = $value->unit_price_before_discount * -1;
                    $value->quantity_ordered = $value->quantity_returned;
                    $value->sell_line_note = "return";
                    $newSellDetails[] = $value;
                }
            }
        }
        $sell_details = $newSellDetails;
        $payment_lines = $this->transactionUtil->getPaymentDetails($id);
        //If no payment lines found then add dummy payment line.
        if (empty($payment_lines)) {
            $payment_lines[] = $this->dummyPaymentLine;
        }

        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);


        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id, false);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id, false);
        }

        //If brands, category are enabled then send else false.
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id) : false;
        $brands = (request()->session()->get('business.enable_brand') == 1) ? Brands::where('business_id', $business_id)
            ->pluck('name', 'id')
            ->prepend(__('lang_v1.all_brands'), 'all') : false;

        $change_return = $this->dummyPaymentLine;

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        //Selling Price Group Dropdown
        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $waiters = null;
        if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
            $waiters_enabled = true;
            $waiters = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $default_location = null;
        if (count($business_locations) == 1) {
            foreach ($business_locations as $id => $name) {
                $default_location = $id;
            }
        }

        return view('sale_pos.return_add')
            ->with(compact('business_details', 'taxes', 'payment_types', 'walk_in_customer', 'sell_details', 'transaction', 'payment_lines', 'location_printer_type', 'shortcuts', 'commission_agent', 'categories', 'pos_settings', 'change_return', 'types', 'customer_groups', 'brands', 'accounts', 'price_groups', 'waiters', 'default_location'));
    }

    //Store Return Query 
      public function returnCreate(Request $request)
    {
        if (!auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }
        // dd($request->all());

        $is_direct_sale = false;
        if (!empty($request->input('is_direct_sale'))) {
            $is_direct_sale = true;
        }

        try {
            $input = $request->except('_token');

            //Check Customer credit limit
            $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input);

            if ($is_credit_limit_exeeded !== false) {
                $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount])
                ];
                if (!$is_direct_sale) {
                    return $output;
                } else {
                    return redirect()
                        ->action('SellController@index')
                        ->with('status', $output);
                }
            }

            $input['is_quotation'] = 0;
            //status is send as quotation from Add sales screen.
            if ($input['status'] == 'quotation') {
                $input['status'] = 'draft';
                $input['is_quotation'] = 1;
            }
            // dd($input['products']);
            if (!empty($input['products'])) {
                $business_id = $request->session()->get('user.business_id');

                //Check if subscribed or not, then check for users quota
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse();
                } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
                    return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellPosController@index'));
                }

                $user_id = $request->session()->get('user.id');
                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');

                $discount = [
                    'discount_type' => $input['discount_type'],
                    'discount_amount' => $input['discount_amount']
                ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

                // dd($invoice_total['final_total']);
                // dd(1);
                // dd($invoice_total);
                // $checkGift = $this->GiftCardCheck($input['products']);
                // $checkCoupon = $this->CouponCheck($input['products']);


                DB::beginTransaction();

                if (empty($request->input('transaction_date'))) {
                    $input['transaction_date'] =  \Carbon::now();
                } else {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }
                if ($is_direct_sale) {
                    $input['is_direct_sale'] = 1;
                }

                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                if ($commsn_agnt_setting == 'logged_in_user') {
                    $input['commission_agent'] = $user_id;
                }

                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }

                //Customer group details
                $contact_id = $request->get('contact_id', null);
                $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;

                //set selling price group id
                if ($request->has('price_group')) {
                    $input['selling_price_group_id'] = $request->input('price_group');
                }

                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend']  ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }

                //Generate reference number
                if (!empty($input['is_recurring'])) {
                    //Update reference count
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount('subscription');
                    $input['subscription_no'] = $this->transactionUtil->generateReferenceNumber('subscription', $ref_count);
                }
                    $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);
                
                // dd($transaction);
                // echo $transaction['final_total'];
                // print_r( $input['products']);die();
                $tempProducts = array();
                $calTotal = 0;
                
                foreach ($input['products'] as $key => $tempProduct) {
                    // dd(substr($tempProduct['product_id'], 1));
                    if ($tempProduct['unit_price_inc_tax'] < 0) {
                        $tempProduct['product_id'] = substr($tempProduct['product_id'], 1);
                        $tempProduct['variation_id'] = substr($tempProduct['variation_id'], 1);
                        $tempProduct['unit_price'] = $tempProduct['unit_price_inc_tax'];
                        $tempProduct['transaction_sell_lines_id'] = NULL;
                        $tempProduct['item_tax'] = 0;
                        $tempProduct['tax_id'] = NULL;
                    }
                    $calTotal += (float) $tempProduct['unit_price_inc_tax'];
                    $tempProducts[] = $tempProduct;
                    // dd($input['products']);
                }
                // if ($transaction['final_total'] < 0) {
                //     // return $input;
                //     $output = [
                //         'success' => 0,
                //         'msg' => 'Your Total Amount is ' . $transaction['final_total'] . ' Cannot Be in Negative .Please Add More Product '
                //     ];
                //     return $output;
                // }
                $input['products'] = $tempProducts;
                
                $tempPayment = array();
                foreach ($input['payment'] as $key => $payment) {
                    if (!empty($payment['payment_id'])) {
                        $payment['payment_id'] = NULL;
                        $payment['amount'] = $this->productUtil->num_uf($payment['amount']);
                    }
                    $tempPayment[] = $payment;
                }
                $input['payment'] = $tempPayment;
               
                // print_r($input['products']);die(); 
                // dd($transaction, $input['products'], $input['location_id']);
                 $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id']);
                // dd($is_direct_sale);
                
                if (!$is_direct_sale) {
                    
                    //Add change return
                    // $change_return = $this->dummyPaymentLine;
                    
                    // $change_return['amount'] = $input['change_return'];
                    // $change_return['is_return'] = 1;
                    // $input['payment'][] = $change_return;
                    // dd($change_return['amount']);
                }
                // dd($input['payment']);
                if (!$transaction->is_suspend && !empty($input['payment'])) {
                    // dd('transaction');
                    $new_coupon = $this->transactionUtil->createOrUpdatePaymentLines3($transaction, $input['payment'], $request);
                }
                // DB::commit();
                // dd(3);
                
                // ADD BONUS POINTS 
                if ($transaction->contact_id != '1') {
                    $objContact = Contact::where('business_id', $transaction->business_id)->where("id", $transaction->contact_id)->first();
                    $per = $objContact->discount / 100;
                    $leftAmount = $transaction->final_total * $per;

                    $currentDate = date('Y-m-d');
                    $BpExpiryDate = date('Y-m-d', strtotime($objContact->bp_expiry));
                    if ($currentDate <= $BpExpiryDate) {
                        $newPoints = $objContact->bonus_points + $leftAmount;
                    } else {
                        $newPoints =  $leftAmount;
                        $dataUpdate = ['bonus_points' => $newPoints, 'bp_expiry' => date("Y-m-d", strtotime($currentDate . "+6 months"))];
                    }
                    $dataUpdate = ['bonus_points' => $newPoints, 'bp_expiry' => date("Y-m-d", strtotime($currentDate . "+6 months"))];
                    $dataWhere = ['business_id' => $transaction->business_id, 'id' => $objContact->id];

                    if (isset($input['cust_discount'] )&&strcmp($input['cust_discount'], $objContact->discount) != 0) {
                        $dataUpdate['discount'] = $input['cust_discount'];
                    }

                    Contact::where($dataWhere)->update($dataUpdate);
                }
                

                $update_transaction = false;
                if ($this->transactionUtil->isModuleEnabled('tables')) {
                    $transaction->res_table_id = request()->get('res_table_id');
                    $update_transaction = true;
                }
                
                if ($this->transactionUtil->isModuleEnabled('service_staff')) {
                    $transaction->res_waiter_id = request()->get('res_waiter_id');
                    $update_transaction = true;
                }
                
                if ($update_transaction) {
                    $transaction->save();
                }
                

                //Check for final and do some processing.
                if ($input['status'] == 'final') {
                    //update product stock
                    foreach ($input['products'] as $product) {
                        if ($product['enable_stock']) {
                            $decrease_qty = $this->productUtil->num_uf($product['quantity']);
                            if (!empty($product['base_unit_multiplier'])) {
                                $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                            }
                            // dd($product);
                            if ($product['sell_line_note'] == 'return') {
                                $sell_line = TransactionSellLine::find($product['transaction_sell_lines_id']);
                                // return $sell_line;

                                $multiplier = 1;
                                if (!empty($sell_line->sub_unit)) {
                                    $multiplier = $sell_line->sub_unit->base_unit_multiplier;
                                }

                                $quantity = $this->transactionUtil->num_uf($product['transaction_sell_lines_id']) * $multiplier;
                                $quantity = $product['quantity'];

                                $quantity_before = '0,00';
                                // $quantity_before = $this->transactionUtil->num_f($sell_line->quantity_returned);

                                $quantity_formated = $quantity;

                                $sell_line->quantity_returned = $quantity;
                                $sell_line->save();
                               
                                $sell = Transaction::where('business_id', $business_id)
                                    ->with(['sell_lines', 'sell_lines.sub_unit'])
                                    ->findOrFail($sell_line->transaction_id);
                                    
                                // dd($sell_line, $quantity_formated, $quantity_before);

                                //Check if any sell return exists for the sale
                                $sell_return = Transaction::where('business_id', $business_id)
                                    ->where('type', 'sell_return')
                                    ->where('return_parent_id', $sell->id)
                                    ->first();

                                $sell_return->update(['payment_status' => 'paid']);
                                
                                //update quantity sold in corresponding purchase lines
                                $this->transactionUtil->updateQuantitySoldFromSellLine($sell_line, $quantity_formated, $quantity_before);
                                // dd($sell_line->product_id);
                                
                                // Update quantity in variation location details
                                $this->productUtil->updateProductQuantity($sell_return->location_id, $sell_line->product_id, $sell_line->variation_id, $quantity_formated, $quantity_before);
                                // dd($sell_line);
                                
                            }
                            // dd($product);
                            if ($product['unit_price_inc_tax'] > 0) {
                                // dd($decrease_qty);
                            // if ($product['unit_price_inc_tax'] < 0) {
                                $this->productUtil->decreaseProductQuantity(
                                    substr($product['product_id'], 1),
                                    $product['variation_id'],
                                    $input['location_id'],
                                    $decrease_qty
                                );
                                
                                // dd($product);
                                // dd($input['location_id']);
                            }
                        }
                    }
                        // dd(($input['payment']));
                    //Add payments to Cash Register
                    if (!$is_direct_sale && !$transaction->is_suspend && !empty($input['payment'])) {
                        // dd(1);
                        
                        // dd($input['payment']);
                        if($transaction['final_total'] >= 0){
                        $this->cashRegisterUtil->addSellPayments($transaction, $input['payment'],0,$request);
                        }
                    }
                    
                    //Update payment status
                    $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
                    
                    //Allocate the quantity from purchase and add mapping of
                    //purchase & sell lines in
                    //transaction_sell_lines_purchase_lines table
                    $business_details = $this->businessUtil->getDetails($business_id);
                    
                    $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
                    
                    $business = [
                        'id' => $business_id,
                        'accounting_method' => $request->session()->get('business.accounting_method'),
                        'location_id' => $input['location_id'],
                        'pos_settings' => $pos_settings
                    ];
                    
                    $this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');
                    
                    //Auto send notification
                    $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
                    
                }

                //Set Module fields
                if (!empty($input['has_module_data'])) {
                    $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                }
                // dd($input);
                Media::uploadMedia($business_id, $transaction, $request, 'documents');
                if($transaction['final_total'] < 0){
                    
                    // $transaction['final_total'] = $invoice_total['final_total']; 
                // dd($invoice_total['final_total']);
                // dd(1);
                }
                DB::commit();
                // dd(1);
                
                $msg = '';
                $receipt = '';
                if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                    $msg = trans("sale.draft_added");
                } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                    $msg = trans("lang_v1.quotation_added");
                    if (!$is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    } else {
                        $receipt = '';
                    }
                } elseif ($input['status'] == 'final') {
                    if (empty($input['sub_type'])) {
                        $msg = trans("sale.pos_sale_added");
                        if (!$is_direct_sale && !$transaction->is_suspend) {
                            $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id, null, false, $new_coupon);

                            // $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                        } else {
                            $receipt = '';
                        }
                    } else {
                        $msg = "Return Sale Adjusted and " . trans("sale.pos_sale_added");
                        $receipt = '';
                    }
                }

                // $RedirectURL = '<script>
                //             window.location = "'.url('/pos/create').'";
                //             </script>';
                $RedirectURL = ' ';

                $output = ['success' => 1, 'msg' => $msg . " " . $RedirectURL, 'receipt' => $receipt];
            } else {
                $output = [
                    'success' => 0,
                    'msg' => trans("messages.something_went_wrong")
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = trans("Something Went Wrong <br><br> File:" . $e->getFile() . "Line: " . $e->getLine() . " Message:" . $e->getMessage());

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = [
                'success' => 0,
                'msg' => $msg
            ];
        }

        if (!$is_direct_sale) {
            return $output;
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return redirect()
                        ->action('SellController@getQuotations')
                        ->with('status', $output);
                } else {
                    return redirect()
                        ->action('SellController@getDrafts')
                        ->with('status', $output);
                }
            } else {
                if (!empty($input['sub_type']) && $input['sub_type'] == 'repair') {
                    $redirect_url = $input['print_label'] == 1 ? action('\Modules\Repair\Http\Controllers\RepairController@printLabel', [$transaction->id]) : action('\Modules\Repair\Http\Controllers\RepairController@index');
                    return redirect($redirect_url)
                        ->with('status', $output);
                }
                return redirect()
                    ->action('SellController@index')
                    ->with('status', $output);
            }
        }
    }

    public function returnCreateOLD(Request $request)
    {
        if (!auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }
        // dd($request->all());

        $is_direct_sale = false;
        if (!empty($request->input('is_direct_sale'))) {
            $is_direct_sale = true;
        }

        try {
            $input = $request->except('_token');

            //Check Customer credit limit
            $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input);

            if ($is_credit_limit_exeeded !== false) {
                $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount])
                ];
                if (!$is_direct_sale) {
                    return $output;
                } else {
                    return redirect()
                        ->action('SellController@index')
                        ->with('status', $output);
                }
            }

            $input['is_quotation'] = 0;
            //status is send as quotation from Add sales screen.
            if ($input['status'] == 'quotation') {
                $input['status'] = 'draft';
                $input['is_quotation'] = 1;
            }
            if (!empty($input['products'])) {
                $business_id = $request->session()->get('user.business_id');

                //Check if subscribed or not, then check for users quota
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse();
                } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
                    return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellPosController@index'));
                }

                $user_id = $request->session()->get('user.id');
                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');

                $discount = [
                    'discount_type' => $input['discount_type'],
                    'discount_amount' => $input['discount_amount']
                ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);



                // $checkGift = $this->GiftCardCheck($input['products']);
                // $checkCoupon = $this->CouponCheck($input['products']);


                DB::beginTransaction();

                if (empty($request->input('transaction_date'))) {
                    $input['transaction_date'] =  \Carbon::now();
                } else {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }
                if ($is_direct_sale) {
                    $input['is_direct_sale'] = 1;
                }

                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                if ($commsn_agnt_setting == 'logged_in_user') {
                    $input['commission_agent'] = $user_id;
                }

                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }

                //Customer group details
                $contact_id = $request->get('contact_id', null);
                $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;

                //set selling price group id
                if ($request->has('price_group')) {
                    $input['selling_price_group_id'] = $request->input('price_group');
                }

                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend']  ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }

                //Generate reference number
                if (!empty($input['is_recurring'])) {
                    //Update reference count
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount('subscription');
                    $input['subscription_no'] = $this->transactionUtil->generateReferenceNumber('subscription', $ref_count);
                }

                $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);
                
                // dd($transaction);
                // echo $transaction['final_total'];
                // print_r( $input['products']);die();
                $tempProducts = array();
                $calTotal = 0;
                
                foreach ($input['products'] as $key => $tempProduct) {
                    // dd(substr($tempProduct['product_id'], 1));
                    if ($tempProduct['unit_price_inc_tax'] < 0) {
                        $tempProduct['product_id'] = substr($tempProduct['product_id'], 1);
                        $tempProduct['variation_id'] = substr($tempProduct['variation_id'], 1);
                        $tempProduct['unit_price'] = $tempProduct['unit_price_inc_tax'];
                        $tempProduct['transaction_sell_lines_id'] = NULL;
                        $tempProduct['item_tax'] = 0;
                        $tempProduct['tax_id'] = NULL;
                    }
                    $calTotal += (float) $tempProduct['unit_price_inc_tax'];
                    $tempProducts[] = $tempProduct;
                    // dd($input['products']);
                }
                // if ($transaction['final_total'] < 0) {
                //     // return $input;
                //     $output = [
                //         'success' => 0,
                //         'msg' => 'Your Total Amount is ' . $transaction['final_total'] . ' Cannot Be in Negative .Please Add More Product '
                //     ];
                //     return $output;
                // }
                $input['products'] = $tempProducts;
                
                $tempPayment = array();
                foreach ($input['payment'] as $key => $payment) {
                    if (!empty($payment['payment_id'])) {
                        $payment['payment_id'] = NULL;
                        $payment['amount'] = $this->productUtil->num_uf($payment['amount']);
                    }
                    $tempPayment[] = $payment;
                }
                $input['payment'] = $tempPayment;
               
                // print_r($input['products']);die(); 
                // dd($transaction, $input['products'], $input['location_id']);
                $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id']);
                // dd($is_direct_sale);
                
                if (!$is_direct_sale) {
                    
                    //Add change return
                    // $change_return = $this->dummyPaymentLine;
                    
                    // $change_return['amount'] = $input['change_return'];
                    // $change_return['is_return'] = 1;
                    // $input['payment'][] = $change_return;
                    // dd($change_return['amount']);
                }
                // dd($input['payment']);
                if (!$transaction->is_suspend && !empty($input['payment'])) {
                    // dd('transaction');
                    $this->transactionUtil->createOrUpdatePaymentLines3($transaction, $input['payment'], $request);
                }
                // DB::commit();
                // dd(3);
                
                // ADD BONUS POINTS 
                if ($transaction->contact_id != '1') {
                    $objContact = Contact::where('business_id', $transaction->business_id)->where("id", $transaction->contact_id)->first();
                    $per = $objContact->discount / 100;
                    $leftAmount = $transaction->final_total * $per;

                    $currentDate = date('Y-m-d');
                    $BpExpiryDate = date('Y-m-d', strtotime($objContact->bp_expiry));
                    if ($currentDate <= $BpExpiryDate) {
                        $newPoints = $objContact->bonus_points + $leftAmount;
                    } else {
                        $newPoints =  $leftAmount;
                        $dataUpdate = ['bonus_points' => $newPoints, 'bp_expiry' => date("Y-m-d", strtotime($currentDate . "+6 months"))];
                    }
                    $dataUpdate = ['bonus_points' => $newPoints, 'bp_expiry' => date("Y-m-d", strtotime($currentDate . "+6 months"))];
                    $dataWhere = ['business_id' => $transaction->business_id, 'id' => $objContact->id];

                    if (isset($input['cust_discount'] )&&strcmp($input['cust_discount'], $objContact->discount) != 0) {
                        $dataUpdate['discount'] = $input['cust_discount'];
                    }

                    Contact::where($dataWhere)->update($dataUpdate);
                }
                

                $update_transaction = false;
                if ($this->transactionUtil->isModuleEnabled('tables')) {
                    $transaction->res_table_id = request()->get('res_table_id');
                    $update_transaction = true;
                }
                
                if ($this->transactionUtil->isModuleEnabled('service_staff')) {
                    $transaction->res_waiter_id = request()->get('res_waiter_id');
                    $update_transaction = true;
                }
                
                if ($update_transaction) {
                    $transaction->save();
                }
                

                //Check for final and do some processing.
                if ($input['status'] == 'final') {
                    //update product stock
                    foreach ($input['products'] as $product) {
                        if ($product['enable_stock']) {
                            $decrease_qty = $this->productUtil->num_uf($product['quantity']);
                            if (!empty($product['base_unit_multiplier'])) {
                                $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                            }
                            // dd($product);
                            if ($product['sell_line_note'] == 'return') {
                                $sell_line = TransactionSellLine::find($product['transaction_sell_lines_id']);
                                // return $sell_line;

                                $multiplier = 1;
                                if (!empty($sell_line->sub_unit)) {
                                    $multiplier = $sell_line->sub_unit->base_unit_multiplier;
                                }

                                $quantity = $this->transactionUtil->num_uf($product['transaction_sell_lines_id']) * $multiplier;
                                $quantity = $product['quantity'];

                                $quantity_before = '0,00';
                                // $quantity_before = $this->transactionUtil->num_f($sell_line->quantity_returned);

                                $quantity_formated = $quantity;

                                $sell_line->quantity_returned = $quantity;
                                $sell_line->save();
                               
                                $sell = Transaction::where('business_id', $business_id)
                                    ->with(['sell_lines', 'sell_lines.sub_unit'])
                                    ->findOrFail($sell_line->transaction_id);
                                    
                                // dd($sell_line, $quantity_formated, $quantity_before);

                                //Check if any sell return exists for the sale
                                $sell_return = Transaction::where('business_id', $business_id)
                                    ->where('type', 'sell_return')
                                    ->where('return_parent_id', $sell->id)
                                    ->first();

                                $sell_return->update(['payment_status' => 'paid']);
                                
                                //update quantity sold in corresponding purchase lines
                                $this->transactionUtil->updateQuantitySoldFromSellLine($sell_line, $quantity_formated, $quantity_before);
                                // dd($sell_line->product_id);
                                
                                // Update quantity in variation location details
                                $this->productUtil->updateProductQuantity($sell_return->location_id, $sell_line->product_id, $sell_line->variation_id, $quantity_formated, $quantity_before);
                                // dd($sell_line);
                                
                            }
                            // dd($product);
                            if ($product['unit_price_inc_tax'] > 0) {
                                // dd($decrease_qty);
                            // if ($product['unit_price_inc_tax'] < 0) {
                                $this->productUtil->decreaseProductQuantity(
                                    substr($product['product_id'], 1),
                                    $product['variation_id'],
                                    $input['location_id'],
                                    $decrease_qty
                                );
                                
                                // dd($product);
                                // dd($input['location_id']);
                            }
                        }
                    }
                        // dd(($input['payment']));
                    //Add payments to Cash Register
                    if (!$is_direct_sale && !$transaction->is_suspend && !empty($input['payment'])) {
                        // dd(1);
                        
                        // dd($input['payment']);
                        $this->cashRegisterUtil->addSellPayments($transaction, $input['payment'],0,$request);
                        
                    }
                    
                    //Update payment status
                    $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
                    
                    //Allocate the quantity from purchase and add mapping of
                    //purchase & sell lines in
                    //transaction_sell_lines_purchase_lines table
                    $business_details = $this->businessUtil->getDetails($business_id);
                    
                    $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
                    
                    $business = [
                        'id' => $business_id,
                        'accounting_method' => $request->session()->get('business.accounting_method'),
                        'location_id' => $input['location_id'],
                        'pos_settings' => $pos_settings
                    ];
                    
                    $this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');
                    
                    //Auto send notification
                    $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
                    
                }

                //Set Module fields
                if (!empty($input['has_module_data'])) {
                    $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                }
                // dd($input);
                Media::uploadMedia($business_id, $transaction, $request, 'documents');
                
                DB::commit();
                // dd(1);
                
                $msg = '';
                $receipt = '';
                if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                    $msg = trans("sale.draft_added");
                } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                    $msg = trans("lang_v1.quotation_added");
                    if (!$is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    } else {
                        $receipt = '';
                    }
                } elseif ($input['status'] == 'final') {
                    if (empty($input['sub_type'])) {
                        $msg = trans("sale.pos_sale_added");
                        if (!$is_direct_sale && !$transaction->is_suspend) {
                            $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                        } else {
                            $receipt = '';
                        }
                    } else {
                        $msg = "Return Sale Adjusted and " . trans("sale.pos_sale_added");
                        $receipt = '';
                    }
                }

                // $RedirectURL = '<script>
                //             window.location = "'.url('/pos/create').'";
                //             </script>';
                $RedirectURL = ' ';

                $output = ['success' => 1, 'msg' => $msg . " " . $RedirectURL, 'receipt' => $receipt];
            } else {
                $output = [
                    'success' => 0,
                    'msg' => trans("messages.something_went_wrong")
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = trans("Something Went Wrong <br><br> File:" . $e->getFile() . "Line: " . $e->getLine() . " Message:" . $e->getMessage());

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = [
                'success' => 0,
                'msg' => $msg
            ];
        }

        if (!$is_direct_sale) {
            return $output;
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return redirect()
                        ->action('SellController@getQuotations')
                        ->with('status', $output);
                } else {
                    return redirect()
                        ->action('SellController@getDrafts')
                        ->with('status', $output);
                }
            } else {
                if (!empty($input['sub_type']) && $input['sub_type'] == 'repair') {
                    $redirect_url = $input['print_label'] == 1 ? action('\Modules\Repair\Http\Controllers\RepairController@printLabel', [$transaction->id]) : action('\Modules\Repair\Http\Controllers\RepairController@index');
                    return redirect($redirect_url)
                        ->with('status', $output);
                }
                return redirect()
                    ->action('SellController@index')
                    ->with('status', $output);
            }
        }
    }


    public function GiftCardCheck($products)
    {
        foreach ($products as $product) {
            $objProduct = Product::where("id", $product['product_id'])->first();
            // print_r($objProduct);die();
            if (strcmp($objProduct->p_type, "gift_card") == 0) {
                GiftCard::where('id', $objProduct->gift_card)->update(['isActive' => 'active']);
                // GiftCard::where('id', substr($objProduct->sku,4))->update(['isActive' => 'active']); 

                Product::where('id', $product['product_id'])->update(['is_inactive' => '1']);
                return true;
            }
        }
    }
    public function CouponCheck($products, Request $request)
    {
        foreach ($products as $product) {
            $objProduct = Product::where("id", $product['product_id'])->first();
            // print_r($objProduct);die();
            if (strcmp($objProduct->p_type, "coupon") == 0) {
                Coupon::where('id', $objProduct->coupon)->update([
                        'isActive' => 'active',
                        'location_id' => $request->location_id,
                    ]
                );

                Product::where('id', $product['product_id'])->update(['is_inactive' => '1']);
                return true;
            }
        }
    }

    /**
     * Returns the content for the receipt
     *
     * @param  int  $business_id
     * @param  int  $location_id
     * @param  int  $transaction_id
     * @param string $printer_type = null
     *
     * @return array
     */
    private function receiptContent(
        $business_id,
        $location_id,
        $transaction_id,
        $printer_type = null,
        $is_package_slip = false,
        $new_coupon = null
    ) {
        $output = [
            'is_enabled' => false,
            'print_type' => 'browser',
            'html_content' => null,
            'printer_config' => [],
            'data' => []
        ];

        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);

        //Check if printing of invoice is enabled or not.
        if ($location_details->print_receipt_on_invoice == 1) {
            //If enabled, get print type.
            $output['is_enabled'] = true;

            $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $location_id, $location_details->invoice_layout_id);

            //Check if printer setting is provided.
            $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;
            // dd($new_coupon->first());
            $coupon_id = null;
            if (isset($new_coupon->id)) {
                $coupon_id = $new_coupon->id;
            }
            $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type, $coupon_id);


            $currency_details = [
                'symbol' => $business_details->currency_symbol,
                'thousand_separator' => $business_details->thousand_separator,
                'decimal_separator' => $business_details->decimal_separator,
            ];
            $receipt_details->currency = $currency_details;

            if ($is_package_slip) {
                $output['html_content'] = view('sale_pos.receipts.packing_slip', compact('receipt_details'))->render();
                return $output;
            }
            //If print type browser - return the content, printer - return printer config data, and invoice format config
            if ($receipt_printer_type == 'printer') {
                $output['print_type'] = 'printer';
                $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
                $output['data'] = $receipt_details;
            } else {
                // dd($receipt_details);
                $layout = !empty($receipt_details->design) ? 'sale_pos.receipts.' . $receipt_details->design : 'sale_pos.receipts.classic';

                $output['html_content'] = view($layout, compact('receipt_details'))->render();
            }
        }

        // dd($receipt_details);

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('sell.update')) {
            abort(403, 'Unauthorized action.');
        }
        //Check if the transaction can be edited or not.
        $edit_days = request()->session()->get('business.transaction_edit_days');
        if (!$this->transactionUtil->canBeEdited($id, $edit_days)) {
            return back()
                ->with('status', [
                    'success' => 0,
                    'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days])
                ]);
        }

        //Check if there is a open register, if no then redirect to Create Register screen.
        if ($this->cashRegisterUtil->countOpenedRegister() == 0) {
            return redirect()->action('CashRegisterController@create');
        }

        //Check if return exist then not allowed
        if ($this->transactionUtil->isReturnExist($id)) {
            return back()->with('status', [
                'success' => 0,
                'msg' => __('lang_v1.return_exist')
            ]);
        }

        $business_id = request()->session()->get('user.business_id');
        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);
        $payment_types = $this->productUtil->payment_types();

        $transaction = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->findorfail($id);

        $location_id = $transaction->location_id;
        $location_printer_type = BusinessLocation::find($location_id)->receipt_printer_type;

        $sell_details = TransactionSellLine::join(
            'products AS p',
            'transaction_sell_lines.product_id',
            '=',
            'p.id'
        )
            ->join(
                'variations AS variations',
                'transaction_sell_lines.variation_id',
                '=',
                'variations.id'
            )
            ->join(
                'product_variations AS pv',
                'variations.product_variation_id',
                '=',
                'pv.id'
            )
            ->leftjoin('variation_location_details AS vld', function ($join) use ($location_id) {
                $join->on('variations.id', '=', 'vld.variation_id')
                    ->where('vld.location_id', '=', $location_id);
            })
            ->leftjoin('units', 'units.id', '=', 'p.unit_id')
            ->where('transaction_sell_lines.transaction_id', $id)
            ->select(
                DB::raw("IF(pv.is_dummy = 0, CONCAT(p.name, ' (', pv.name, ':',variations.name, ')'), p.name) AS product_name"),
                'p.id as product_id',
                'p.enable_stock',
                'p.name as product_actual_name',
                'pv.name as product_variation_name',
                'pv.is_dummy as is_dummy',
                'variations.name as variation_name',
                'variations.sub_sku',
                'p.barcode_type',
                'p.enable_sr_no',
                'variations.id as variation_id',
                'units.short_name as unit',
                'units.allow_decimal as unit_allow_decimal',
                'transaction_sell_lines.tax_id as tax_id',
                'transaction_sell_lines.item_tax as item_tax',
                'transaction_sell_lines.unit_price as default_sell_price',
                'transaction_sell_lines.unit_price_before_discount as unit_price_before_discount',
                'transaction_sell_lines.unit_price_inc_tax as sell_price_inc_tax',
                'transaction_sell_lines.id as transaction_sell_lines_id',
                'transaction_sell_lines.quantity as quantity_ordered',
                'transaction_sell_lines.sell_line_note as sell_line_note',
                'transaction_sell_lines.parent_sell_line_id',
                'transaction_sell_lines.lot_no_line_id',
                'transaction_sell_lines.line_discount_type',
                'transaction_sell_lines.line_discount_amount',
                'transaction_sell_lines.res_service_staff_id',
                'units.id as unit_id',
                'transaction_sell_lines.sub_unit_id',
                DB::raw('vld.qty_available + transaction_sell_lines.quantity AS qty_available')
            )
            ->get();
        if (!empty($sell_details)) {
            foreach ($sell_details as $key => $value) {
                //If modifier sell line then unset
                if (!empty($sell_details[$key]->parent_sell_line_id)) {
                    unset($sell_details[$key]);
                } else {
                    if ($transaction->status != 'final') {
                        $actual_qty_avlbl = $value->qty_available - $value->quantity_ordered;
                        $sell_details[$key]->qty_available = $actual_qty_avlbl;
                        $value->qty_available = $actual_qty_avlbl;
                    }

                    $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($value->qty_available, false, null, true);

                    //Add available lot numbers for dropdown to sell lines
                    $lot_numbers = [];
                    if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                        $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($value->variation_id, $business_id, $location_id);
                        foreach ($lot_number_obj as $lot_number) {
                            //If lot number is selected added ordered quantity to lot quantity available
                            if ($value->lot_no_line_id == $lot_number->purchase_line_id) {
                                $lot_number->qty_available += $value->quantity_ordered;
                            }

                            $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                            $lot_numbers[] = $lot_number;
                        }
                    }
                    $sell_details[$key]->lot_numbers = $lot_numbers;

                    if (!empty($value->sub_unit_id)) {
                        $value = $this->productUtil->changeSellLineUnit($business_id, $value);
                        $sell_details[$key] = $value;
                    }

                    $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($value->qty_available, false, null, true);

                    if ($this->transactionUtil->isModuleEnabled('modifiers')) {
                        //Add modifier details to sel line details
                        $sell_line_modifiers = TransactionSellLine::where('parent_sell_line_id', $sell_details[$key]->transaction_sell_lines_id)->get();
                        $modifiers_ids = [];
                        if (count($sell_line_modifiers) > 0) {
                            $sell_details[$key]->modifiers = $sell_line_modifiers;
                            foreach ($sell_line_modifiers as $sell_line_modifier) {
                                $modifiers_ids[] = $sell_line_modifier->variation_id;
                            }
                        }
                        $sell_details[$key]->modifiers_ids = $modifiers_ids;

                        //add product modifier sets for edit
                        $this_product = Product::find($sell_details[$key]->product_id);
                        if (count($this_product->modifier_sets) > 0) {
                            $sell_details[$key]->product_ms = $this_product->modifier_sets;
                        }
                    }
                }
            }
        }

        $payment_lines = $this->transactionUtil->getPaymentDetails($id);
        //If no payment lines found then add dummy payment line.
        if (empty($payment_lines)) {
            $payment_lines[] = $this->dummyPaymentLine;
        }

        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id, false);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id, false);
        }

        //If brands, category are enabled then send else false.
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id) : false;
        $brands = (request()->session()->get('business.enable_brand') == 1) ? Brands::where('business_id', $business_id)
            ->pluck('name', 'id')
            ->prepend(__('lang_v1.all_brands'), 'all') : false;

        $change_return = $this->dummyPaymentLine;

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        //Selling Price Group Dropdown
        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $waiters = null;
        if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
            $waiters_enabled = true;
            $waiters = $this->productUtil->serviceStaffDropdown($business_id);
        }

        return view('sale_pos.edit')
            ->with(compact('business_details', 'taxes', 'payment_types', 'walk_in_customer', 'sell_details', 'transaction', 'payment_lines', 'location_printer_type', 'shortcuts', 'commission_agent', 'categories', 'pos_settings', 'change_return', 'types', 'customer_groups', 'brands', 'accounts', 'price_groups', 'waiters'));
    }

    /**
     * Update the specified resource in storage.
     * TODO: Add edit log.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('sell.update') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->except('_token');

            //status is send as quotation from edit sales screen.
            $input['is_quotation'] = 0;
            if ($input['status'] == 'quotation') {
                $input['status'] = 'draft';
                $input['is_quotation'] = 1;
            }

            $is_direct_sale = false;
            if (!empty($input['products'])) {
                //Get transaction value before updating.
                $transaction_before = Transaction::find($id);
                $status_before =  $transaction_before->status;

                if ($transaction_before->is_direct_sale == 1) {
                    $is_direct_sale = true;
                }

                //Check Customer credit limit
                $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input, $id);

                if ($is_credit_limit_exeeded !== false) {
                    $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                    $output = [
                        'success' => 0,
                        'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount])
                    ];
                    if (!$is_direct_sale) {
                        return $output;
                    } else {
                        return redirect()
                            ->action('SellController@index')
                            ->with('status', $output);
                    }
                }

                //Check if there is a open register, if no then redirect to Create Register screen.
                if (!$is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0) {
                    return redirect()->action('CashRegisterController@create');
                }

                $business_id = $request->session()->get('user.business_id');
                $user_id = $request->session()->get('user.id');
                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');

                $discount = [
                    'discount_type' => $input['discount_type'],
                    'discount_amount' => $input['discount_amount']
                ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

                if (!empty($request->input('transaction_date'))) {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }

                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                if ($commsn_agnt_setting == 'logged_in_user') {
                    $input['commission_agent'] = $user_id;
                }

                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }

                //Customer group details
                $contact_id = $request->get('contact_id', null);
                $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;

                //set selling price group id
                if ($request->has('price_group')) {
                    $input['selling_price_group_id'] = $request->input('price_group');
                }

                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend']  ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }

                //Begin transaction
                DB::beginTransaction();

                $transaction = $this->transactionUtil->updateSellTransaction($id, $business_id, $input, $invoice_total, $user_id);

                //Update Sell lines
                $deleted_lines = $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id'], true, $status_before);

                //Update update lines
                if (!$is_direct_sale && !$transaction->is_suspend) {
                    //Add change return
                    $change_return = $this->dummyPaymentLine;
                    $change_return['amount'] = $input['change_return'];
                    $change_return['is_return'] = 1;
                    if (!empty($input['change_return_id'])) {
                        $change_return['id'] = $input['change_return_id'];
                    }
                    $input['payment'][] = $change_return;

                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment']);

                    //Update cash register
                    $this->cashRegisterUtil->updateSellPayments($status_before, $transaction, $input['payment']);
                    
                }

                //Update payment status
                $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                //Update product stock
                $this->productUtil->adjustProductStockForInvoice($status_before, $transaction, $input);

                //Allocate the quantity from purchase and add mapping of
                //purchase & sell lines in
                //transaction_sell_lines_purchase_lines table
                $business = [
                    'id' => $business_id,
                    'accounting_method' => $request->session()->get('business.accounting_method'),
                    'location_id' => $input['location_id']
                ];
                $this->transactionUtil->adjustMappingPurchaseSell($status_before, $transaction, $business, $deleted_lines);

                if ($this->transactionUtil->isModuleEnabled('tables')) {
                    $transaction->res_table_id = request()->get('res_table_id');
                    $transaction->save();
                }
                if ($this->transactionUtil->isModuleEnabled('service_staff')) {
                    $transaction->res_waiter_id = request()->get('res_waiter_id');
                    $transaction->save();
                }
                $log_properties = [];
                if (isset($input['repair_completed_on'])) {
                    $completed_on = !empty($input['repair_completed_on']) ? $this->transactionUtil->uf_date($input['repair_completed_on'], true) : null;
                    if ($transaction->repair_completed_on != $completed_on) {
                        $log_properties['completed_on_from'] = $transaction->repair_completed_on;
                        $log_properties['completed_on_to'] = $completed_on;
                    }
                }

                //Set Module fields
                if (!empty($input['has_module_data'])) {
                    $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                }

                if (!empty($input['update_note'])) {
                    $log_properties['update_note'] = $input['update_note'];
                }

                Media::uploadMedia($business_id, $transaction, $request, 'documents');

                activity()
                    ->performedOn($transaction)
                    ->withProperties($log_properties)
                    ->log('edited');

                DB::commit();

                $msg = '';
                $receipt = '';

                if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                    $msg = trans("sale.draft_added");
                } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                    $msg = trans("lang_v1.quotation_updated");
                    if (!$is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    } else {
                        $receipt = '';
                    }
                } elseif ($input['status'] == 'final') {
                    $msg = trans("sale.pos_sale_updated");
                    if (!$is_direct_sale && !$transaction->is_suspend) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    } else {
                        $receipt = '';
                    }
                }

                $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt];
            } else {
                $output = [
                    'success' => 0,
                    'msg' => trans("messages.something_went_wrong. " . $e->getMessage())
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = trans("messages.something_went_wrong" . $e->getMessage());

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = [
                'success' => 0,
                'msg' => $msg
            ];
        }

        if (!$is_direct_sale) {
            return $output;
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return redirect()
                        ->action('SellController@getQuotations')
                        ->with('status', $output);
                } else {
                    return redirect()
                        ->action('SellController@getDrafts')
                        ->with('status', $output);
                }
            } else {
                if (!empty($transaction->sub_type) && $transaction->sub_type == 'repair') {
                    return redirect()
                        ->action('\Modules\Repair\Http\Controllers\RepairController@index')
                        ->with('status', $output);
                }

                return redirect()
                    ->action('SellController@index')
                    ->with('status', $output);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete_transaction($id)
    {
        // dd($id);
        if (!auth()->user()->can('sell.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                //Check if return exist then not allowed
                if ($this->transactionUtil->isReturnExist($id)) {
                    $output = [
                        'success' => false,
                        'msg' => __('lang_v1.return_exist')
                    ];
                    return $output;
                }

                $business_id = request()->session()->get('user.business_id');
                $transaction = Transaction::where('id', $id)
                    ->where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->with(['sell_lines'])
                    ->first();

                //Begin transaction
                DB::beginTransaction();

                if (!empty($transaction)) {
                    //If status is draft direct delete transaction
                    if ($transaction->status == 'draft') {
                        $transaction->delete();
                    } else {
                        $deleted_sell_lines = $transaction->sell_lines;
                        $deleted_sell_lines_ids = $deleted_sell_lines->pluck('id')->toArray();
                        $this->transactionUtil->deleteSellLines(
                            $deleted_sell_lines_ids,
                            $transaction->location_id
                        );

                        $transaction->status = 'draft';
                        $business = [
                            'id' => $business_id,
                            'accounting_method' => request()->session()->get('business.accounting_method'),
                            'location_id' => $transaction->location_id
                        ];

                        $this->transactionUtil->adjustMappingPurchaseSell('final', $transaction, $business, $deleted_sell_lines_ids);

                        //Delete Cash register transactions
                        $transaction->cash_register_payments()->delete();

                        $transaction->delete();
                    }
                }

                //Delete account transactions
                AccountTransaction::where('transaction_id', $transaction->id)->delete();

                DB::commit();
                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.sale_delete_success')
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output['success'] = false;
                $output['msg'] = trans("messages.something_went_wrong");
            }

            return $output;
        }
    }

    public function bulkHide(Request $request)
    {
        $transactionIds = $request->get("slips");

        try {
            $business_id = request()->session()->get('user.business_id');
            foreach ($transactionIds as $id) {
                //Check if return exist then not allowed
                if ($this->transactionUtil->isReturnExist($id)) {
                    $output = [
                        'success' => false,
                        'msg' => __('lang_v1.return_exist')
                    ];
                    return $output;
                }

                $transaction = Transaction::where('id', $id)
                    ->where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->with(['sell_lines'])
                    ->first();

                //Begin transaction
                DB::beginTransaction();

                if (!empty($transaction)) {
                    //If status is draft direct delete transaction
                    $transaction->status = 'hide';
                    $transaction->save();
                }

                DB::commit();
            }

            $output = [
                'success' => true,
                'msg' => count($transactionIds) . " Hide Successfully"
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output['success'] = false;
            $output['msg'] = trans("messages.something_went_wrong");
        }

        return $output;
    }

    public function bulkUnHide(Request $request)
    {
        $transactionIds = $request->get("slips");

        try {
            $business_id = request()->session()->get('user.business_id');
            foreach ($transactionIds as $id) {
                //Check if return exist then not allowed
                if ($this->transactionUtil->isReturnExist($id)) {
                    $output = [
                        'success' => false,
                        'msg' => __('lang_v1.return_exist')
                    ];
                    return $output;
                }

                $transaction = Transaction::where('id', $id)
                    ->where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->with(['sell_lines'])
                    ->first();

                //Begin transaction
                DB::beginTransaction();

                if (!empty($transaction)) {
                    //If status is draft direct delete transaction
                    $transaction->status = 'final';
                    $transaction->save();
                }

                DB::commit();
            }

            $output = [
                'success' => true,
                'msg' => count($transactionIds) . " UnHide Successfully"
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output['success'] = false;
            $output['msg'] = trans("messages.something_went_wrong");
        }

        return $output;
    }

    public function hide($id)
    {
        if (!auth()->user()->can('sell.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                //Check if return exist then not allowed
                if ($this->transactionUtil->isReturnExist($id)) {
                    $output = [
                        'success' => false,
                        'msg' => __('lang_v1.return_exist')
                    ];
                    return $output;
                }

                $business_id = request()->session()->get('user.business_id');
                $transaction = Transaction::where('id', $id)
                    ->where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->with(['sell_lines'])
                    ->first();

                //Begin transaction
                DB::beginTransaction();

                if (!empty($transaction)) {
                    //If status is draft direct delete transaction
                    $transaction->status = 'hide';
                    $transaction->save();
                }


                DB::commit();
                $output = [
                    'success' => true,
                    'msg' => "Hide Successfully"
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output['success'] = false;
                $output['msg'] = trans("messages.something_went_wrong");
            }

            return $output;
        }
    }

    public function unhide($id)
    {
        if (!auth()->user()->can('sell.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                //Check if return exist then not allowed
                if ($this->transactionUtil->isReturnExist($id)) {
                    $output = [
                        'success' => false,
                        'msg' => __('lang_v1.return_exist')
                    ];
                    return $output;
                }

                $business_id = request()->session()->get('user.business_id');
                $transaction = Transaction::where('id', $id)
                    ->where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->with(['sell_lines'])
                    ->first();

                //Begin transaction
                DB::beginTransaction();

                if (!empty($transaction)) {
                    //If status is draft direct delete transaction
                    $transaction->status = 'final';
                    $transaction->save();
                }


                DB::commit();
                $output = [
                    'success' => true,
                    'msg' => "UnHide Successfully And Show In Sale"
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output['success'] = false;
                $output['msg'] = trans("messages.something_went_wrong");
            }

            return $output;
        }
    }

    /**
     * Returns the HTML row for a product in POS
     *
     * @param  int  $variation_id
     * @param  int  $location_id
     * @return \Illuminate\Http\Response
     */
    public function getProductRow($variation_id, $location_id)
    {
        $output = [];
        try {
            $row_count = request()->get('product_row');
            $row_count = $row_count + 1;
            $is_direct_sell = false;
            if (request()->get('is_direct_sell') == 'true') {
                $is_direct_sell = true;
            }

            $business_id = request()->session()->get('user.business_id');

            // $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, 3);
            $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id);

            $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available, false, null, true);

            $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id);

            //Get customer group and change the price accordingly
            $customer_id = request()->get('customer_id', null);
            $cg = $this->contactUtil->getCustomerGroup($business_id, $customer_id);
            $percent = (empty($cg) || empty($cg->amount)) ? 0 : $cg->amount;
            $product->default_sell_price = $product->sell_price + ($percent * $product->sell_price / 100);
            $product->sell_price_inc_tax = $product->sell_price + ($percent * $product->sell_price / 100);
            $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
            $enabled_modules = $this->transactionUtil->allModulesEnabled();

            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[] = $lot_number;
                }
            }
            $product->lot_numbers = $lot_numbers;

            $price_group = request()->input('price_group');
            if (!empty($price_group)) {
                $variation_group_prices = $this->productUtil->getVariationGroupPrice($variation_id, $price_group, $product->tax_id);

                if (!empty($variation_group_prices['price_inc_tax'])) {
                    $product->sell_price_inc_tax = $variation_group_prices['price_inc_tax'];
                    $product->default_sell_price = $variation_group_prices['price_exc_tax'];
                }
            }

            $business_details = $this->businessUtil->getDetails($business_id);
            $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

            $output['success'] = true;

            $waiters = null;
            if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
                $waiters_enabled = true;
                $waiters = $this->productUtil->serviceStaffDropdown($business_id);
            }

            if (request()->get('type') == 'sell-return') {
                $output['html_content'] =  view('sell_return.partials.product_row')
                    ->with(compact('product', 'row_count', 'tax_dropdown', 'enabled_modules', 'sub_units'))
                    ->render();
            } else {
                $is_cg = !empty($cg->id) ? true : false;
                $is_pg = !empty($price_group) ? true : false;
                $discount = $this->productUtil->getProductDiscount($product, $business_id, $location_id, $is_cg, $is_pg);

                $output['html_content'] =  view('sale_pos.product_row')
                    ->with(compact('product', 'row_count', 'tax_dropdown', 'enabled_modules', 'pos_settings', 'sub_units', 'discount', 'waiters'))
                    ->render();
            }

            $output['enable_sr_no'] = $product->enable_sr_no;

            if ($this->transactionUtil->isModuleEnabled('modifiers')  && !$is_direct_sell) {
                $this_product = Product::where('business_id', $business_id)
                    ->find($product->product_id);
                if (count($this_product->modifier_sets) > 0) {
                    $product_ms = $this_product->modifier_sets;
                    $output['html_modifier'] =  view('restaurant.product_modifier_set.modifier_for_product')
                        ->with(compact('product_ms', 'row_count'))->render();
                }
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output['success'] = false;
            $output['msg'] = __('Product Does not exist in Selected Location');
        }

        return $output;
    }
    public function getProductRowOld($variation_id, $location_id)
    {
        $output = [];
        try {
            $row_count = request()->get('product_row');
            $row_count = $row_count + 1;
            $is_direct_sell = false;
            if (request()->get('is_direct_sell') == 'true') {
                $is_direct_sell = true;
            }

            $business_id = request()->session()->get('user.business_id');
            // dd($location_id);

            $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, 3);

            // dd($product);
            // $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id);

            // dd($product);
            // /Here

            // return json_encode($product);

            $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available, false, null, true);

            $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id);

            //Get customer group and change the price accordingly
            $customer_id = request()->get('customer_id', null);
            $cg = $this->contactUtil->getCustomerGroup($business_id, $customer_id);
            $percent = (empty($cg) || empty($cg->amount)) ? 0 : $cg->amount;
            // dd($product);
            $product->default_sell_price = $product->sell_price_inc_tax + ($percent * $product->sell_price_inc_tax / 100);

            // unit_price_before_discount
            // dd($product->default_sell_price);
            // $product->default_sell_price = $product->default_sell_price + ($percent * $product->default_sell_price / 100);

            $product->sell_price_inc_tax = $product->sell_price_inc_tax + ($percent * $product->sell_price_inc_tax / 100);

            $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);

            $enabled_modules = $this->transactionUtil->allModulesEnabled();

            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[] = $lot_number;
                }
            }
            $product->lot_numbers = $lot_numbers;

            $price_group = request()->input('price_group');
            if (!empty($price_group)) {
                $variation_group_prices = $this->productUtil->getVariationGroupPrice($variation_id, $price_group, $product->tax_id);

                if (!empty($variation_group_prices['price_inc_tax'])) {
                    $product->sell_price_inc_tax = $variation_group_prices['price_inc_tax'];
                    $product->default_sell_price = $variation_group_prices['price_exc_tax'];
                }
            }

            $business_details = $this->businessUtil->getDetails($business_id);
            $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

            $output['success'] = true;

            $waiters = null;
            if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
                $waiters_enabled = true;
                $waiters = $this->productUtil->serviceStaffDropdown($business_id);
            }

            if (request()->get('type') == 'sell-return') {
                $output['html_content'] =  view('sell_return.partials.product_row')
                    ->with(compact('product', 'row_count', 'tax_dropdown', 'enabled_modules', 'sub_units'))
                    ->render();
            } else {
                $is_cg = !empty($cg->id) ? true : false;
                $is_pg = !empty($price_group) ? true : false;
                $discount = $this->productUtil->getProductDiscount($product, $business_id, $location_id, $is_cg, $is_pg);

                $output['html_content'] =  view('sale_pos.product_row')
                    ->with(compact('product', 'row_count', 'tax_dropdown', 'enabled_modules', 'pos_settings', 'sub_units', 'discount', 'waiters'))
                    ->render();
            }

            $output['enable_sr_no'] = $product->enable_sr_no;

            if ($this->transactionUtil->isModuleEnabled('modifiers')  && !$is_direct_sell) {
                $this_product = Product::where('business_id', $business_id)
                    ->find($product->product_id);
                if (count($this_product->modifier_sets) > 0) {
                    $product_ms = $this_product->modifier_sets;
                    $output['html_modifier'] =  view('restaurant.product_modifier_set.modifier_for_product')
                        ->with(compact('product_ms', 'row_count'))->render();
                }
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output['success'] = false;
            $output['msg'] = __('Product Does not exist in Selected Location');
        }

        return $output;
    }

    /**
     * Returns the HTML row for a payment in POS
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getPaymentRow(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $row_index = $request->input('row_index');
        $removable = true;
        $payment_types = $this->productUtil->payment_types();

        $payment_line = $this->dummyPaymentLine;

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }

        return view('sale_pos.partials.payment_row')
            ->with(compact('payment_types', 'row_index', 'removable', 'payment_line', 'accounts'));
    }

    /**
     * Returns recent transactions
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getRecentTransactions(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->session()->get('user.business_location_id');
        $user_id = $request->session()->get('user.id');
        $transaction_status = $request->get('status');
        $register = $this->cashRegisterUtil->getCurrentCashRegister($user_id);
        $query = Transaction::where('business_id', $business_id)
            // ->where('transactions.created_by', $user_id)
            ->where('transactions.location_id', $location_id)
            ->where('transactions.type', 'sell')
            ->where('is_direct_sale', 0);

        if ($transaction_status == 'final') {
            if (!empty($register->id)) {
                $query->leftjoin('cash_register_transactions as crt', 'transactions.id', '=', 'crt.transaction_id')
                    ->where('crt.cash_register_id', $register->id);
            }
        }

        if ($transaction_status == 'quotation') {
            $query->where('transactions.status', 'draft')
                ->where('is_quotation', 1);
        } elseif ($transaction_status == 'draft') {
            $query->where('transactions.status', 'draft')
                ->where('is_quotation', 0);
        } else {
            $query->where('transactions.status', $transaction_status);
        }

        $transactions = $query->orderBy('transactions.created_at', 'desc')
            ->groupBy('transactions.id')
            ->select('transactions.*')
            ->with(['contact'])
            // ->limit(10)
            ->get();

        return view('sale_pos.partials.recent_transactions')
            ->with(compact('transactions'));
    }

    /**
     * Prints invoice for sell
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function printInvoice(Request $request, $transaction_id)
    {
        if (request()->ajax()) {
            try {
                $output = [
                    'success' => 0,
                    'msg' => trans("messages.something_went_wrong")
                ];

                $business_id = $request->session()->get('user.business_id');

                $transaction = Transaction::where('business_id', $business_id)
                    ->where('id', $transaction_id)
                    ->with(['location'])
                    ->first();

                if (empty($transaction)) {
                    return $output;
                }

                $printer_type = 'browser';
                if (!empty(request()->input('check_location')) && request()->input('check_location') == true) {
                    $printer_type = $transaction->location->receipt_printer_type;
                }

                $is_package_slip = !empty($request->input('package_slip')) ? true : false;

                $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction_id, $printer_type, $is_package_slip);

                if (!empty($receipt)) {
                    $output = ['success' => 1, 'receipt' => $receipt];
                }
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => 0,
                    'msg' => trans("messages.something_went_wrong") . $e->getMessage()
                ];
            }

            return $output;
        }
    }

    /**
     * Gives suggetion for product based on category
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getProductSuggestion(Request $request)
    {
        if ($request->ajax()) {
            $category_id = $request->get('category_id');
            $supplier_id = $request->get('brand_id');
            $location_id = $request->get('location_id');
            // dd($location_id);
            if ($request->get('search')) {
                $search_txt = $request->get('search');
                $products = Product::where('products.name', 'LIKE', "%{$search_txt}%")
                    ->orWhere('products.sku', 'LIKE', "%{$search_txt}%")
                    ->join('sizes', 'products.sub_size_id', '=', 'sizes.id')
                    ->orWhere('sizes.name', 'LIKE', "%{$search_txt}%")
                    ->join('variations', 'products.id', '=', 'variations.product_id')
                    ->orWhere('variations.sell_price_inc_tax', 'LIKE', "%{$search_txt}%");
            } else {
                $products = Product::leftjoin(
                    'variations',
                    'products.id',
                    '=',
                    'variations.product_id'
                );
            }
            $term = $request->get('term');
            $check_qty = false;
            $business_id = $request->session()->get('user.business_id');


            $products->leftjoin('transaction_sell_lines as tsl', 'tsl.product_id', '=', 'products.id')
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
                )
                ->active()->where('products.type', '!=', 'modifier');

            if (isset($location_id)) {
                $products->where('VLD.location_id', "=", $location_id);
            }
            //Include search
            if (!empty($term)) {
                $products->where(function ($query) use ($term) {
                    $query->where('products.name', 'like', '%' . $term . '%');
                    $query->orWhere('sku', 'like', '%' . $term . '%');
                    $query->orWhere('sub_sku', 'like', '%' . $term . '%');
                });
            }

            //Include check for quantity
            // if ($check_qty) {
            //     $products->where('VLD.qty_available', '>', 0);
            // }

            if ($category_id != 'all') {
                $products->where(function ($query) use ($category_id) {
                    $query->where('products.category_id', $category_id);
                    $query->orWhere('products.sub_category_id', $category_id);
                });
            }
            if ($supplier_id != 'all') {
                $products->where('products.supplier_id', $supplier_id);
            }
            if ($category_id === 'all' && $supplier_id === 'all') {
                // dd(1);
                $products->where('show_pos', '!=', 0);
                // $products->orderBy('show_pos', 'DESC');
            }
            if ($category_id === 'all' || $supplier_id != 'all') {
                $products->orderBy('products.updated_at', 'DESC');
            } else {
                $products->orderBy('created_at', 'DESC');
            }
            $products = $products->select(
                'products.id as product_id',
                'products.name',
                'products.show_pos as show_pos',
                'products.type',
                'products.enable_stock',
                'products.sub_size_id',
                'variations.id as variation_id',
                'variations.name as variation',
                'VLD.qty_available',
                'VLD.product_updated_at',
                'tsl.unit_price_before_discount as unit_price_before_discount',
                'variations.sub_sku',
                'variations.default_sell_price',
                // 'variations.sell_price_inc_tax as sell_price',
                'VLD.sell_price as sell_price',
                'products.sku',
                'products.image',
                'products.color_id',
                'products.sub_size_id',
                'products.updated_at as created_at',
                DB::raw("(SELECT purchase_price_inc_tax FROM purchase_lines WHERE 
                        variation_id=variations.id ORDER BY id DESC LIMIT 1) as last_purchased_price")
            )
                ->distinct()
                ->where("p_type", "product")
                ->where("products.sub_size_id", '!=', 'null')

                ->groupBy('products.id')
                // ->groupBy('variations.id')
                ->orderBy('products.name', 'asc')
                ->latest()
                // ->limit(50)
                // ->get();
                ->paginate(50);


            return view('sale_pos.partials.product_list')->with(compact('products'));
        }
    }
    /**
     * Gives product for bulkAdd.blade.php
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getProductRefferenceSuggestion(Request $request)
    {
        // dd($request->ajax());
        if ($request->ajax()) {
            $category_id = $request->get('category_id');
            $supplier_id = $request->get('supplier_id');
            $location_id = $request->get('location_id');
            $location_id = $request->get('location_id');
            $search = $request->get('search');
            $check_qty = false;
            $business_id = $request->session()->get('user.business_id');
            if (!empty($request->get('search'))) {
                // dd($request->get('search'));
                $search_txt = $request->get('search');
                $products = Product::where('products.name', 'LIKE', "%{$search_txt}%")
                    ->orWhere('products.description', 'LIKE', "%{$search_txt}%")
                    ->orWhere('products.sku', 'LIKE', "%{$search_txt}%")
                    ->orWhere('products.refference', 'LIKE', "%{$search_txt}%")
                    ->join('sizes', 'products.sub_size_id', '=', 'sizes.id')
                    ->orWhere('sizes.name', 'LIKE', "%{$search_txt}%")
                    ->join('variations', 'products.id', '=', 'variations.product_id')
                    ->orWhere('variations.sell_price_inc_tax', 'LIKE', "%{$search_txt}%");
            } else {
                $products = Product::join(
                    'variations',
                    'products.id',
                    '=',
                    'variations.product_id'
                );
            }
            $products->leftjoin(
                'variation_location_details AS VLD',
                function ($join) use ($location_id, $search) {
                    $join->on('variations.id', '=', 'VLD.variation_id');

                    //Include Location
                    if ($location_id != 'all' && empty($search)) {
                        // if (!empty($location_id) && empty($search)) {
                        $join->where(function ($query) use ($location_id) {
                            $query->where('VLD.location_id', '=', $location_id);
                            //Check null to show products even if no quantity is available in a location.
                            //TODO: Maybe add a settings to show product not available at a location or not.
                            $query->orWhereNull('VLD.location_id');
                        });
                    }
                }
            )
                ->active()->where('products.type', '!=', 'modifier');
            if ($location_id != 'all' && empty($search)) {
                // if (!empty($location_id) && empty($search)) {
                $products->where('VLD.location_id', "=", $location_id);
            } else {
                if (empty($search)) {
                    $products->where('VLD.location_id', "!=", 2);
                }
            }
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

            if ($category_id != 'all') {
                $products->where(function ($query) use ($category_id) {
                    $query->where('products.category_id', $category_id);
                    $query->orWhere('products.sub_category_id', $category_id);
                });
            }
            if ($supplier_id != 'all' && empty($search)) {
                $products->where('products.supplier_id', $supplier_id);
            }

            $products = $products->select(
                'products.id as product_id',
                'products.name',
                'products.type',
                'products.sku',
                'products.color_id',
                'products.enable_stock',
                'variations.id as variation_id',
                'variations.name as variation',
                'VLD.qty_available',
                'VLD.sell_price as sell_price',
                'variations.default_sell_price as selling_price',
                'variations.sub_sku',
                'products.image',
                'products.size_id',
                'products.refference'
            )
                ->where("p_type", "product")
                // ->orderBy('products.name', 'asc')
                ->groupBy('variations.id')
                ->orderBy('VLD.product_updated_at', 'DESC')
                // ->orderBy('products.updated_at', 'DESC')
                ->paginate(1000);


            // if ($location_id != 'all') {
            // dd($products->unique('refference'));
            $records = $products->unique('refference');
            // dd(count($records));
            // } else {
                // $records = $products;
            // }
            // dd($products);
            return view('sale_pos.partials.bulk_product_list')
                ->with(compact('products', 'records'));
        }
    }

    /**
     *  Get Product Details for bulkAdd.blade.php
     * 
     */
    public function getBulkProductDetails($variation_id)
    {
        $business_id = request()->session()->get('user.business_id');

        $variation_product = $this->productUtil->getDetailsFromVariationForOtherThanPOS($variation_id, $business_id);

        $data[] = 'null';
        // dd($variation_id);
        if ($variation_product != null) {
            $product = Product::find($variation_product->product_id);
            $product_name = ProductNameCategory::where('name', $product->name)->first();
            $product_prices = Variation::find($variation_id);
            $supplier = Supplier::find($product->supplier_id);
            $sub_category = Category::find($product->sub_category_id);
            $size = $product->size()->first();
            $sub_size = $product->sub_size()->first();
            $purchase_lines = $product->purchase_lines()->first();
            $color = $product->color()->first();
            $VariationLocationDetail = $product->variation_location_details()->where('location_id')->first();
            // dd($color);
            if ($sub_category) {
                $category = Category::find($sub_category->parent_id);
            } else {
                $sub_category = 'null';
                $category = 'null';
            }
            $ut = new ProductUtil();
            //  $ut = new \App\Utils\ProductUtil();
            $unit_price = $ut->num_f($product_prices->dpp_inc_tax);
            $single_dpp = $ut->num_f($product_prices->dpp_inc_tax);
            $sale_price = $ut->num_f($product_prices->sell_price_inc_tax);
            $product_qty = Product::join('variation_location_details as vld', 'vld.product_id', 'products.id')
                ->join('colors as c', 'c.id', 'products.color_id')
                ->where('products.refference', $product->refference)
                ->select([
                    DB::raw('SUM(vld.qty_available) as qty'),
                    'c.name as color_name',
                    'products.name as product_name',
                    'products.id'
                ])

                ->groupBy('color_name')
                ->get();

            $data = [
                'product' => $product,
                'product_name' => $product_name,
                'variation' => $variation_product,
                'variation_location_details' => $VariationLocationDetail,
                'product_price' => $product_prices,
                'supplier' => $supplier,
                'sub_category' => $sub_category,
                'category' => $category,
                'purchase_lines' => $purchase_lines,
                'size' => $size,
                'sub_size' => $sub_size,
                'color' => $color,
                'unit_price' => $unit_price,
                'single_dpp' => $single_dpp,
                'sale_price' => $sale_price,
                'product_qty' => $product_qty,
            ];
        }
        // dd($data);
        // $category = Category::find($product->category_id);

        return response()->json($data);
    }
    /**
     *  Get Product Details for products/edit.blade.php with location
     * 
     */
    public function getBulkProductLocationDetails($variation_id, $location_id)
    {
        $business_id = request()->session()->get('user.business_id');

        $variation_product = $this->productUtil->getDetailsFromVariationForOtherThanPOS($variation_id, $business_id);

        $data[] = 'null';
        // dd($variation_id);
        if ($variation_product != null) {
            $product = Product::find($variation_product->product_id);
            $product_name = ProductNameCategory::where('name', $product->name)->first();
            $product_prices = Variation::find($variation_id);
            $supplier = Supplier::find($product->supplier_id);
            $sub_category = Category::find($product->sub_category_id);
            $size = $product->size()->first();
            $sub_size = $product->sub_size()->first();
            $purchase_lines = $product->purchase_lines()->first();
            $color = $product->color()->first();
            $VariationLocationDetail = $product->variation_location_details()->where('location_id', $location_id)->first();
            // dd($color);
            if ($sub_category) {
                $category = Category::find($sub_category->parent_id);
            } else {
                $sub_category = 'null';
                $category = 'null';
            }
            $ut = new ProductUtil();
            //  $ut = new \App\Utils\ProductUtil();
            $unit_price = $ut->num_f($product_prices->dpp_inc_tax);
            $single_dpp = $ut->num_f($product_prices->dpp_inc_tax);
            $sale_price = $ut->num_f($product_prices->sell_price_inc_tax);

            $product_qty = Product::join('variation_location_details as vld', 'vld.product_id', 'products.id')
                ->join('colors as c', 'c.id', 'products.color_id')
                ->where('products.refference', $product->refference)
                ->select([
                    DB::raw('SUM(vld.qty_available) as qty'),
                    'c.name as color_name',
                    'products.name as product_name',
                    'products.id'
                ])
                ->groupBy('color_name')
                ->get();

            $data = [
                'product' => $product,
                'product_name' => $product_name,
                'variation' => $variation_product,
                'variation_location_details' => $VariationLocationDetail,
                'product_price' => $product_prices,
                'supplier' => $supplier,
                'sub_category' => $sub_category,
                'category' => $category,
                'purchase_lines' => $purchase_lines,
                'size' => $size,
                'sub_size' => $sub_size,
                'color' => $color,
                'unit_price' => $unit_price,
                'single_dpp' => $single_dpp,
                'sale_price' => $sale_price,
                'product_qty' => $product_qty,
            ];
        }
        request()->session()->put('location_id',$location_id);
        // dd($data);
        // $category = Category::find($product->category_id);

        return response()->json($data);
    }

    /**
     * Shows invoice url.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showInvoiceUrl($id)
    {
        if (!auth()->user()->can('sell.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $transaction = Transaction::where('business_id', $business_id)
                ->findorfail($id);
            $url = $this->transactionUtil->getInvoiceUrl($id, $business_id);

            return view('sale_pos.partials.invoice_url_modal')
                ->with(compact('transaction', 'url'));
        }
    }

    /**
     * Shows invoice to guest user.
     *
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function showInvoice($token)
    {
        $transaction = Transaction::where('invoice_token', $token)->with(['business'])->first();

        if (!empty($transaction)) {
            $receipt = $this->receiptContent($transaction->business_id, $transaction->location_id, $transaction->id, 'browser');

            $title = $transaction->business->name . ' | ' . $transaction->invoice_no;
            return view('sale_pos.partials.show_invoice')
                ->with(compact('receipt', 'title'));
        } else {
            die(__("messages.something_went_wrong"));
        }
    }

    /**
     * Display a listing of the recurring invoices.
     *
     * @return \Illuminate\Http\Response
     */
    public function listSubscriptions()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->where('transactions.is_recurring', 1)
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.is_direct_sale',
                    'transactions.invoice_no',
                    'contacts.name',
                    'transactions.subscription_no',
                    'bl.name as business_location',
                    'transactions.recur_parent_id',
                    'transactions.recur_stopped_on',
                    'transactions.is_recurring',
                    'transactions.recur_interval',
                    'transactions.recur_interval_type',
                    'transactions.recur_repetitions'
                )->with(['subscription_invoices']);



            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }
            $datatable = Datatables::of($sells)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '';

                        if ($row->is_recurring == 1 && auth()->user()->can("sell.update")) {
                            $link_text = !empty($row->recur_stopped_on) ? __('lang_v1.start_subscription') : __('lang_v1.stop_subscription');
                            $link_class = !empty($row->recur_stopped_on) ? 'btn-success' : 'btn-danger';

                            $html .= '<a href="' . action('SellPosController@toggleRecurringInvoices', [$row->id]) . '" class="toggle_recurring_invoice btn btn-xs ' . $link_class . '"><i class="fa fa-power-off"></i> ' . $link_text . '</a>';

                            if ($row->is_direct_sale == 0) {
                                $html .= '<a target="_blank" class="btn btn-xs btn-primary" href="' . action('SellPosController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a>';
                            } else {
                                $html .= '<a target="_blank" class="btn btn-xs btn-primary" href="' . action('SellController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a>';
                            }
                        }

                        return $html;
                    }
                )
                ->removeColumn('id')
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('recur_interval', function ($row) {
                    $type = $row->recur_interval == 1 ? str_singular(__('lang_v1.' . $row->recur_interval_type)) : __('lang_v1.' . $row->recur_interval_type);
                    return $row->recur_interval . $type;
                })
                ->addColumn('subscription_invoices', function ($row) {
                    $invoices = [];
                    if (!empty($row->subscription_invoices)) {
                        $invoices = $row->subscription_invoices->pluck('invoice_no')->toArray();
                    }

                    $html = '';
                    $count = 0;
                    if (!empty($invoices)) {
                        $imploded_invoices = '<span class="label bg-info">' . implode('</span>, <span class="label bg-info">', $invoices) . '</span>';
                        $count = count($invoices);
                        $html .= '<small>' . $imploded_invoices . '</small>';
                    }
                    if ($count > 0) {
                        $html .= '<br><small class="text-muted">' .
                            __('sale.total') . ': ' . $count . '</small>';
                    }

                    return $html;
                })
                ->addColumn('last_generated', function ($row) {
                    if (!empty($row->subscription_invoices)) {
                        $last_generated_date = $row->subscription_invoices->max('created_at');
                    }
                    return !empty($last_generated_date) ? $last_generated_date->diffForHumans() : '';
                })
                ->addColumn('upcoming_invoice', function ($row) {
                    if (empty($row->recur_stopped_on)) {
                        $last_generated = !empty($row->subscription_invoices) ? \Carbon::parse($row->subscription_invoices->max('transaction_date')) : \Carbon::parse($row->transaction_date);
                        if ($row->recur_interval_type == 'days') {
                            $upcoming_invoice = $last_generated->addDays($row->recur_interval);
                        } elseif ($row->recur_interval_type == 'months') {
                            $upcoming_invoice = $last_generated->addMonths($row->recur_interval);
                        } elseif ($row->recur_interval_type == 'years') {
                            $upcoming_invoice = $last_generated->addYears($row->recur_interval);
                        }
                    }
                    return !empty($upcoming_invoice) ? $this->transactionUtil->format_date($upcoming_invoice) : '';
                })
                ->rawColumns(['action', 'subscription_invoices'])
                ->make(true);

            return $datatable;
        }
        return view('sale_pos.subscriptions');
    }

    /**
     * Starts or stops a recurring invoice.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleRecurringInvoices($id)
    {
        if (!auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $transaction = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('is_recurring', 1)
                ->findorfail($id);

            if (empty($transaction->recur_stopped_on)) {
                $transaction->recur_stopped_on = \Carbon::now();
            } else {
                $transaction->recur_stopped_on = null;
            }
            $transaction->save();

            $output = [
                'success' => 1,
                'msg' => trans("lang_v1.updated_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => trans("messages.something_went_wrong")
            ];
        }

        return $output;
    }
    public function getTransationHistory(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->session()->get('user.business_location_id');
        $user_id = $request->session()->get('user.id');
        $transaction_status = $request->get('status');
        $customer_id = $request->customer_id;
        $register = $this->cashRegisterUtil->getCurrentCashRegister($user_id);
        // return $location_id;
        // dd($transaction_status);

        $query = Transaction::where('business_id', $business_id)
            ->where('transactions.created_by', $user_id)
            // ->where('transactions.location_id', $location_id)
            ->where('transactions.type', 'sell')
            ->where('is_direct_sale', 0)
            ->with('payment_lines');


        if ($transaction_status == 'final') {
            if (!empty($register->id)) {
                $query->leftjoin('cash_register_transactions as crt', 'transactions.id', '=', 'crt.transaction_id')
                    ->where('crt.cash_register_id', $register->id);
            }
        }

        // if ($transaction_status == 'quotation') {
        //     $query->where('transactions.status', 'draft')
        //         ->where('is_quotation', 1);
        // } elseif ($transaction_status == 'draft') {
        //     $query->where('transactions.status', 'draft')
        //         ->where('is_quotation', 0);
        // } else {
        //     $query->where('transactions.status', $transaction_status);
        // }
        

        $transactions = $query->orderBy('transactions.created_at', 'desc')
            ->groupBy('transactions.id')
            ->when($customer_id != '1',function($q) use($customer_id){
                $q->where('contact_id', $customer_id);
             })
            ->select('transactions.*')
           
            ->with(['contact'])
            // ->limit(10)
            ->get();
            // if ($customer_id != '1') {
            //     $transactions->where('contact_id ', $customer_id);
            // }
        // $product_name = Transaction::sell_lines()->pluck('product_id');
        // foreach ($product_name as $product) {
        //     // Do something with each user name, like printing it.
        //      dd($product) ;
        // }
        // dd($transactions->first()->sell_lines()->pluck('product_id'));
        // dd($transactions->sum('final_total'));
        // dd($transactions->first(),$transactions->first()->payment_lines()->first());
        // dd($transactions->first()->cash_register_payments()->first()->cash_register()->first()->user()->first());
        // dd($transactions->first()->cash_register_payments()->first()->cash_register()->first());
        // dd($transactions->first()->cash_register_payments()->first()->pay_method);
        // dd($transactions);

        return view('sale_pos.receipts.transactions_history')->with(compact('transactions'));
    }
}
