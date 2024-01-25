<?php

namespace App\Http\Controllers;

use App\CashRegister;
use App\CashRegisterTransaction;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use Illuminate\Http\Request;

use App\Utils\CashRegisterUtil;

use DB;

class CashRegisterController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $cashRegisterUtil;

    /**
     * Constructor
     *
     * @param CashRegisterUtil $cashRegisterUtil
     * @return void
     */
    public function __construct(CashRegisterUtil $cashRegisterUtil)
    {
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->payment_types = ['cash' => 'Cash', 'card' => 'Card', 'cheque' => 'Cheque', 'bank_transfer' => 'Bank Transfer', 'other' => 'Other'];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('cash_register.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //Check if there is a open register, if yes then redirect to POS screen.
        // dd($this->cashRegisterUtil->countOpenedRegister());
        if ($this->cashRegisterUtil->countOpenedRegister() != 0) {
            return redirect()->action('SellPosController@create');
        }

        return view('cash_register.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $initial_amount = 0;
            if (!empty($request->input('amount'))) {
                $initial_amount = $this->cashRegisterUtil->num_uf($request->input('amount'));
            }
            $user_id = $request->session()->get('user.id');
            $business_id = $request->session()->get('user.business_id');
            $location_id = request()->session()->get('user.business_location_id');

            $register = CashRegister::create([
                'business_id' => $business_id,
                'user_id' => $user_id,
                'location_id' => $location_id,
                'statusss' => 'open'
            ]);
            $register->cash_register_transactions()->create([
                'amount' => $initial_amount,
                'pay_method' => 'cash',
                'type' => 'credit',
                'transaction_type' => 'initial'
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
        }

        return redirect()->action('SellPosController@create');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CashRegister  $cashRegister
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $register_details =  $this->cashRegisterUtil->getRegisterDetails($id);
        // dd($register_details);
        $user_id = auth()->user()->id;
        $location_id = request()->session()->get('user.business_location_id');
        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();
        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time,$id);
        // dd($details);
        // $request = new Request();
        $transaction_ids = CashRegisterTransaction::where('cash_register_id',$id)->pluck('transaction_id')->toArray();

        $business_id = request()->session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');
        $location_id = request()->session()->get('user.business_location_id');
        $transaction_status = 'final';
        // $transaction_status = request()->get('status');

        // dd($transaction_status);

        $register = $this->cashRegisterUtil->getCurrentCashRegister($user_id);

        // dd($register->cash_register_transactions()->first());
        $query = Transaction::whereIn('transactions.id', $transaction_ids)
            ->where('transactions.type', 'sell')
            ->where('is_direct_sale', 0);
        // dd(!empty($register->id),$id);
        if ($transaction_status == 'final') {
            if (!empty($id)) {
                $query->leftjoin('cash_register_transactions as crt', 'transactions.id', '=', 'crt.transaction_id')
                    ->where('crt.cash_register_id', $id);
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

        $before_transaction = $query;
        $transactions = $query->orderBy('transactions.created_at', 'desc')
            ->groupBy('transactions.id')
            ->select('transactions.*')
            ->with(['contact'])
            ->get();
        $location_id = request()->session()->get('user.business_location_id');

        $prices = CashRegister::join(
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
            ->where('cash_registers.id', $id);

        $transaction_ids = $prices->distinct('ct.transaction_id')->pluck('ct.transaction_id');
        // dd($transaction_ids);
        // $register_prices = TransactionSellLine::whereIn('transaction_id',$transaction_ids)->where('line_discount_amount','>',0)->get()->unique('created_at');

        // $discount = $register_prices->sum('line_discount_amount');

        
        $discount = TransactionSellLine::whereIn('transaction_id', $transaction_ids)->where('discounted_amount', '>', 0)->get()->sum('discounted_amount');

        $forced_prices = TransactionSellLine::whereIn('transaction_id', $transaction_ids)->where('original_amount', '>', 0)->where('discounted_amount', 0.00)->count('id');
            
        $payment_methods = TransactionPayment::whereIn('transaction_id', $transaction_ids)->get();

        $giftcard_method = TransactionPayment::whereIn('transaction_id', $transaction_ids)->where('is_convert', 'gift_card')->pluck('transaction_id');

        $gift_card = Transaction::whereIn('id', $giftcard_method)->sum('final_total');
        // $gift_card = $payment_methods->where('method','gift_card')->sum('amount');

        $coupon_method = TransactionPayment::whereIn('transaction_id', $transaction_ids)->where('is_convert', 'coupon')->pluck('transaction_id');
        // $coupon = Transaction::whereIn('id', $coupon_method)->sum('final_total');
        // $coupon = CashRegisterTransaction::where('cash_register_id', $register['id'])->where('pay_method', 'coupon')->get()->sum('amount');
        $coupon = CashRegisterTransaction::whereIn('transaction_id', $transaction_ids)
        ->where('cash_register_id', $id)->where('pay_method', 'coupon')
        ->groupBy('transaction_id')
        ->get()
        ->sum('amount');

        // $coupon = $payment_methods->where('method','coupon')->sum('amount');
        // dd($coupon);

        $card_id = TransactionPayment::whereIn('transaction_id', $transaction_ids)->where('method', 'card')->pluck('transaction_id');
        // $card = $query->sum('final_total');
        // $card = Transaction::whereIn('id',$card_id)->get()->sum('final_total');
        // $card = CashRegisterTransaction::where('cash_register_id', $id)->where('pay_method', 'card')->get()->sum('amount');

        // $cash = CashRegisterTransaction::where('cash_register_id', $id)->where('pay_method', 'cash')->get()->sum('amount');
        $card = CashRegisterTransaction::where('cash_register_id', $id)->where('transaction_type', 'sell')->where('pay_method', 'card')->where('amount', '>', 0)->get()->sum('amount');
        $cash = CashRegisterTransaction::where('cash_register_id', $id)->where('transaction_type', 'sell')->where('pay_method', 'cash')->where('amount', '>', 0)->get()->sum('amount');
                // dd($cash);
        // $cash_in_hand = CashRegisterTransaction::where('transaction_type','initial')->where('amount','>',0)->orderBy('id','DESC')->first()->amount;
        $cash_in_hand = CashRegisterTransaction::where('cash_register_id',$id)->first()->amount;
        // $cash_in_hand = $register->cash_register_transactions()->first()->amount;

        // dd($cash_in_hand);

        $show_detail = false;
        // dd(compact('register_details', 'details', 'transactions', 'discount', 'gift_card', 'coupon', 'card', 'cash', 'cash_in_hand', 'forced_prices', 'show_detail'));
        return view('cash_register.register_details')
            ->with(compact('register_details', 'details', 'transactions', 'discount', 'gift_card', 'coupon', 'card', 'cash', 'cash_in_hand', 'forced_prices', 'show_detail'));
    }
    public function old_show($id)
    {
        $register_details =  $this->cashRegisterUtil->getRegisterDetails($id);
        $user_id = $register_details->user_id;
        $location_id = request()->session()->get('user.business_location_id');
        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();
        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time);
        return view('cash_register.register_details')
            ->with(compact('register_details', 'details'));
    }

    /**
     * Shows register details modal.
     *
     * @param  void
     * @return \Illuminate\Http\Response
     */
    public function getRegisterDetails()
    {
        $register_details =  $this->cashRegisterUtil->getRegisterDetails();

        $user_id = auth()->user()->id;
        $location_id = request()->session()->get('user.business_location_id');
        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();
        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time);

        // $request = new Request();

        $business_id = request()->session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');
        $location_id = request()->session()->get('user.business_location_id');
        $transaction_status = 'final';
        // $transaction_status = request()->get('status');

        // dd($transaction_status);

        $register = $this->cashRegisterUtil->getCurrentCashRegister($user_id);

        // dd($register->cash_register_transactions()->first());
        $query = Transaction::where('business_id', $business_id)
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

        $before_transaction = $query;
        $transactions = $query->orderBy('transactions.created_at', 'desc')
            ->groupBy('transactions.id')
            ->select('transactions.*')
            ->with(['contact'])
            ->get();
        // ->limit(10)
        // dd($transactions);
        // dd($register_details,$details,$transactions);
        $location_id = request()->session()->get('user.business_location_id');
        $prices = CashRegister::join(
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
            )->where('cash_registers.location_id', $location_id)
            ->where('cash_registers.statusss', 'open');

        // $transaction_ids = $prices->distinct('ct.transaction_id')->pluck('ct.transaction_id');
        $transaction_ids = $prices->distinct('ct.transaction_id')->pluck('ct.transaction_id');
        // $register_prices = TransactionSellLine::whereIn('transaction_id',$transaction_ids)->where('line_discount_amount','>',0)->get()->unique('created_at');
                // dd($transaction_ids);
        // $discount = $register_prices->sum('line_discount_amount');

        $register_prices = TransactionSellLine::whereIn('transaction_id', $transaction_ids)->where('discounted_amount', '>', 0)->get();

        $discount = $register_prices->sum('discounted_amount');

        $forced_prices = TransactionSellLine::whereIn('transaction_id', $transaction_ids)->where('original_amount', '!=', 'unit_price_before_discount')->where('discounted_amount', 0.00)->count('id');

        $payment_methods = TransactionPayment::whereIn('transaction_id', $transaction_ids)->get();

        $giftcard_method = TransactionPayment::whereIn('transaction_id', $transaction_ids)->where('is_convert', 'gift_card')->pluck('transaction_id');

        $gift_card = Transaction::whereIn('id', $giftcard_method)->sum('final_total');
        // $gift_card = $payment_methods->where('method','gift_card')->sum('amount');

        // $coupon_method = TransactionPayment::whereIn('transaction_id', $transaction_ids)->where('is_convert', 'coupon')->pluck('transaction_id');
        // orignal one
        // $coupon = Transaction::whereIn('id', $coupon_method)->sum('final_total');
        $coupon = CashRegisterTransaction::where('cash_register_id', $register['id'])->where('pay_method', 'coupon')->groupBy('transaction_id')->get()->sum('amount');
        // $coupon = $payment_methods->where('method','coupon')->sum('amount');
        // $coupon = 1;
        // dd($coupon);

        $card_id = $payment_methods->where('method', 'card')->unique('created_at')->pluck('transaction_id');
        // $card = $query->sum('final_total');
        // $card = Transaction::whereIn('id',$card_id)->get()->sum('final_total');
        $card = CashRegisterTransaction::where('cash_register_id', $register['id'])->where('transaction_type', 'sell')->where('pay_method', 'card')->where('amount', '>', 0)->get()->sum('amount');
        $cash = CashRegisterTransaction::where('cash_register_id', $register['id'])->where('transaction_type', 'sell')->where('pay_method', 'cash')->where('amount', '>', 0)->get()->sum('amount');
        // dd($cash);
        // $cash_in_hand = CashRegisterTransaction::where('transaction_type','initial')->where('amount','>',0)->orderBy('id','DESC')->first()->amount;
        $cash_in_hand = $register->cash_register_transactions()->first()->amount;

        // dd($cash_in_hand);

        // dd($register_details);
        return view('cash_register.register_details')
            ->with(compact('register_details', 'details', 'transactions', 'discount', 'gift_card', 'coupon', 'card', 'cash', 'cash_in_hand', 'forced_prices'));
    }

    /**
     * Shows close register form.
     *
     * @param  void
     * @return \Illuminate\Http\Response
     */
    public function getCloseRegister()
    {
        $register_details =  $this->cashRegisterUtil->getRegisterDetails();

        $user_id = auth()->user()->id;
        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();
        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time);

        $location_id = request()->session()->get('user.business_location_id');
        $prices = CashRegister::join(
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
            )->where('cash_registers.location_id', $location_id)
            ->where('cash_registers.status', 'open');
        $transaction_ids = $prices->distinct('ct.transaction_id')->pluck('ct.transaction_id');
        $register_prices = TransactionSellLine::whereIn('transaction_id', $transaction_ids)->where('line_discount_amount', '>', 0)->get()->unique('created_at');

        $forced_prices = TransactionSellLine::whereIn('transaction_id', $transaction_ids)->where('original_amount', '>', 0)->where('discounted_amount', 0.00)->get()->unique('created_at')->count('id');

        $discount = $register_prices->sum('line_discount_amount');

        $payment_methods = TransactionPayment::whereIn('transaction_id', $transaction_ids)->get();

        $gift_card = $payment_methods->where('method', 'gift_card')->sum('amount');
        $coupon = $payment_methods->where('method', 'coupon')->sum('amount');

        $card = $payment_methods->where('method', 'card')->sum('amount');

        $cash_in_hand = CashRegisterTransaction::where('transaction_type', 'initial')->where('amount', '>', 0)->orderBy('id', 'DESC')->first()->amount;
        // dd($details);
        return view('cash_register.close_register_modal')
            ->with(compact('register_details', 'details', 'discount', 'gift_card', 'coupon', 'card', 'cash_in_hand', 'forced_prices'));
    }

    /**
     * Closes currently opened register.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postCloseRegister(Request $request)
    {
        try {
            //Disable in demo
            if (config('app.env') == 'demo') {
                $output = [
                    'success' => 0,
                    'msg' => 'Feature disabled in demo!!'
                ];
                return redirect()->action('HomeController@index')->with('status', $output);
            }

            $input = $request->only([
                'closing_amount', 'total_card_slips', 'total_cheques',
                'closing_note'
            ]);
            $input['closing_amount'] = $this->cashRegisterUtil->num_uf($input['closing_amount']);
            $user_id = $request->session()->get('user.id');
            $location_id = request()->session()->get('user.business_location_id');
            $input['closed_at'] = \Carbon::now()->format('Y-m-d H:i:s');
            $input['status'] = 'close';

            CashRegister::where('location_id', $location_id)
                ->where('status', 'open')
                ->update($input);
            $output = [
                'success' => 1,
                'msg' => __('cash_register.close_success')
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __("messages.something_went_wrong")];
        }

        return redirect()->action('HomeController@index')->with('status', $output);
    }

    /**
     * Close Register Automatically On the basis of time 
     * 
     **/
    public function autoCloseRegister()
    {
        $registers = CashRegister::where('status', 'open')->where('location_id', '>', 0)->get();
        foreach ($registers as $key => $value) {
            $total_sale = $this->cashRegisterUtil->getRegisterDetails($value->id)->total_sale;

            $value->closing_amount = $total_sale;
            $location_id = $value->location_id;
            $value->closed_at = \Carbon::now()->format('Y-m-d H:i:s');
            $value->statusss = 'close';

            $value->save();
        }
    }
}
