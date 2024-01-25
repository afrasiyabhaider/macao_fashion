<?php

namespace App\Utils;

use App\CashRegister;
use App\CashRegisterTransaction;
use App\Coupon;
use App\Transaction;

use DB;
use Illuminate\Support\Facades\Log;

class CashRegisterUtil extends Util
{
    /**
     * Returns number of opened Cash Registers for the
     * current logged in user
     *
     * @return int
     */
    public function countOpenedRegister()
    {
        $user_id = auth()->user()->id;
        $location_id = request()->session()->get('user.business_location_id');
        $count =  CashRegister::where('location_id', $location_id)
            ->where('statusss', 'open')
            ->count();
        // dd($count);
        return $count;
    }

    /**
     * Adds sell payments to currently opened cash register
     *
     * @param object/int $transaction
     * @param array $payments
     *
     * @return boolean
     */
    public function addSellPayments($transaction, $payments, $leftAmount_total = 0, $request)
    {
        $user_id = auth()->user()->id;
        $location_id = request()->session()->get('user.business_location_id');
        $register =  CashRegister::where('location_id', $location_id)
            ->where('statusss', 'open')
            ->first();
        $payments_formatted = [];
        // $payments = null;
        // dd($payments);
        foreach ($payments as $payment) {
            // dd($payment);
            // dd($this->num_uf($payment['amount']) - $this->num_uf($leftAmount_total));
            $coupons = Coupon::where('barcode', $payment['coupon'])->where('coupon_type', 'product_return')->first();
            // dd($coupons);
            if ($coupons) {
                $c_value = 1;
            } else {
                $c_value = null;
            }

            $direct_cash = $request->direct_cash;
            if ($direct_cash != '0') {
                $amount = $payment['amount'] != 0.00 ? $payment['amount'] - $leftAmount_total : $payment['amount'];
            } else {
                $amount = $payment['amount'] != 0.00 ? $payment['amount'] : $payment['amount'];
            }
            // $amount = $payment['amount'] != 0.00 ? $payment['amount'] - $leftAmount_total : $payment['amount'];
            //  $amount = $this->num_uf($payment['amount']) != 0.00 ? $this->num_uf($payment['amount']) - $this->num_uf($leftAmount_total) : $this->num_uf($payment['amount']);
            //    dd($amount);
            $payments_formatted[] = new CashRegisterTransaction([
                // orignal one
                // 'amount' => (isset($payment['is_return']) && $payment['is_return'] == 1) ? (-1*$this->num_uf($amount)) :(float)$amount, 
                // 'amount' => $amount,
                'amount' => (isset($payment['is_return']) && $payment['is_return'] == 1) ? (-1 * (float)$amount) : (float)$amount,
                // 'amount' => (isset($payment['is_return']) && $payment['is_return'] == 1) ? (-1*$amount) :$amount, 
                // 'amount' => (isset($payment['is_return']) && $payment['is_return'] == 1) ? (-1*$this->num_uf($payment['amount'])) :(float)$payment['amount'],
                'pay_method' => (isset($payment['method']) && $payment['method']) ? $payment['method'] : '',
                'type' => 'credit',
                'transaction_type' => 'sell',
                'transaction_id' => $transaction->id,
                'return_coupon'  => $c_value,
            ]);
        }
        // dd(-$amount);

        // dd($payments_formatted);
        if (!empty($payments_formatted)) {
            $register->cash_register_transactions()->saveMany($payments_formatted);
            // Log::info([$payments_formatted]);
            foreach ($payments_formatted as $payment) {
                $getCashRegister = CashRegisterTransaction::where('transaction_id', $payment->transaction_id)->get();
    
                foreach ($getCashRegister as $transaction) {
                    // Check if pay_method is not "coupon" and amount is negative
                    if ($transaction->pay_method != 'coupon' && $transaction->amount < 0) {
    
                        // Find the record with pay_method "coupon" for the same transaction_id
                        $couponRecord = CashRegisterTransaction::where('transaction_id', $transaction->transaction_id)
                            ->where('pay_method', 'coupon')
                            ->where('return_coupon', null)
                            ->first();
                        if ($couponRecord && !$couponRecord->updated) {
                            $price = $couponRecord->amount - abs($transaction->amount);
            
                            $couponRecord->update([
                                'amount' => $price,
                                'updated' => true,
                            ]);
                        }
                        $couponRecord1 = CashRegisterTransaction::where('transaction_id', $transaction->transaction_id)
                            ->where('pay_method', 'coupon')
                            ->where('return_coupon', 1)
                            ->first();
                        if ($couponRecord1 && !$couponRecord1->updated) {
                            $price = $couponRecord1->amount - abs($transaction->amount);
                            $couponRecord1->update([
                                'amount' => -1 * $price,
                                'updated' => true,
                            ]);
                            // Log::info([$price]);
                        }
                    }
                    if ($transaction->pay_method != 'coupon' && $transaction->amount > 0) {
                        $couponRecord1 = CashRegisterTransaction::where('transaction_id', $transaction->transaction_id)
                            ->where('pay_method', 'coupon')
                            ->where('return_coupon', 1)
                            ->first();
                            // dd($couponRecord1->amount);
                        if ($couponRecord1 && !$couponRecord1->updated) {
                            $couponRecord1->update([
                                'amount' => -1 * $couponRecord1->amount,
                                'updated' => true,
                            ]);
                        }
                    }
                }
            }
        }
        
        return true;
    }

    /**
     * Adds sell payments to currently opened cash register
     *
     * @param object/int $transaction
     * @param array $payments
     *
     * @return boolean
     */
    public function updateSellPayments($status_before, $transaction, $payments)
    {
        $user_id = auth()->user()->id;
        $location_id = request()->session()->get('user.business_location_id');
        $register =  CashRegister::where('location_id', $location_id)
            ->where('statusss', 'open')
            ->first();
        // dd($register);
        //If draft -> final then add all
        //If final -> draft then refund all
        //If final -> final then update payments
        if ($status_before == 'draft' && $transaction->status == 'final') {
            $this->addSellPayments($transaction, $payments);
        } elseif ($status_before == 'final' && $transaction->status == 'draft') {
            $this->refundSell($transaction);
        } elseif ($status_before == 'final' && $transaction->status == 'final') {
            $prev_payments = CashRegisterTransaction::where('transaction_id', $transaction->id)
                ->select(
                    DB::raw("SUM(IF(pay_method='cash', IF(type='credit', amount, -1 * amount), 0)) as total_cash"),
                    DB::raw("SUM(IF(pay_method='card', IF(type='credit', amount, -1 * amount), 0)) as total_card"),
                    DB::raw("SUM(IF(pay_method='cheque', IF(type='credit', amount, -1 * amount), 0)) as total_cheque"),
                    DB::raw("SUM(IF(pay_method='bank_transfer', IF(type='credit', amount, -1 * amount), 0)) as total_bank_transfer"),
                    DB::raw("SUM(IF(pay_method='other', IF(type='credit', amount, -1 * amount), 0)) as total_other"),
                    DB::raw("SUM(IF(pay_method='custom_pay_1', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_1"),
                    DB::raw("SUM(IF(pay_method='custom_pay_2', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_2"),
                    DB::raw("SUM(IF(pay_method='custom_pay_3', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_3")
                )->first();
            if (!empty($prev_payments)) {
                $payment_diffs = [
                    'cash' => $prev_payments->total_cash,
                    'card' => $prev_payments->total_card,
                    'cheque' => $prev_payments->total_cheque,
                    'bank_transfer' => $prev_payments->total_bank_transfer,
                    'other' => $prev_payments->total_other,
                    'custom_pay_1' => $prev_payments->total_custom_pay_1,
                    'custom_pay_2' => $prev_payments->total_custom_pay_2,
                    'custom_pay_3' => $prev_payments->total_custom_pay_3,
                ];

                foreach ($payments as $payment) {
                    if (isset($payment['is_return']) && $payment['is_return'] == 1) {
                        $payment_diffs[$payment['method']] += $this->num_uf($payment['amount']);
                    } else {
                        $payment_diffs[$payment['method']] -= $this->num_uf($payment['amount']);
                    }
                }
                $payments_formatted = [];
                foreach ($payment_diffs as $key => $value) {
                    if ($value > 0) {
                        $payments_formatted[] = new CashRegisterTransaction([
                            'amount' => $value,
                            'pay_method' => $key,
                            'type' => 'debit',
                            'transaction_type' => 'refund',
                            'transaction_id' => $transaction->id
                        ]);
                    } elseif ($value < 0) {
                        $payments_formatted[] = new CashRegisterTransaction([
                            'amount' => -1 * $value,
                            'pay_method' => $key,
                            'type' => 'credit',
                            'transaction_type' => 'sell',
                            'transaction_id' => $transaction->id
                        ]);
                    }
                }
                if (!empty($payments_formatted)) {
                    $register->cash_register_transactions()->saveMany($payments_formatted);
                }
            }
        }

        return true;
    }

    /**
     * Refunds all payments of a sell
     *
     * @param object/int $transaction
     *
     * @return boolean
     */
    public function refundSell($transaction)
    {
        $user_id = auth()->user()->id;
        $location_id = request()->session()->get('user.business_location_id');
        $register =  CashRegister::where('location_id', $location_id)
            ->where('statusss', 'open')
            ->first();

        $total_payment = CashRegisterTransaction::where('transaction_id', $transaction->id)
            ->select(
                DB::raw("SUM(IF(pay_method='cash', IF(type='credit', amount, -1 * amount), 0)) as total_cash"),
                DB::raw("SUM(IF(pay_method='card', IF(type='credit', amount, -1 * amount), 0)) as total_card"),
                DB::raw("SUM(IF(pay_method='cheque', IF(type='credit', amount, -1 * amount), 0)) as total_cheque"),
                DB::raw("SUM(IF(pay_method='bank_transfer', IF(type='credit', amount, -1 * amount), 0)) as total_bank_transfer"),
                DB::raw("SUM(IF(pay_method='other', IF(type='credit', amount, -1 * amount), 0)) as total_other"),
                DB::raw("SUM(IF(pay_method='custom_pay_1', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_1"),
                DB::raw("SUM(IF(pay_method='custom_pay_2', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_2"),
                DB::raw("SUM(IF(pay_method='custom_pay_3', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_3")
            )->first();
        $refunds = [
            'cash' => $total_payment->total_cash,
            'card' => $total_payment->total_card,
            'cheque' => $total_payment->total_cheque,
            'bank_transfer' => $total_payment->total_bank_transfer,
            'other' => $total_payment->total_other,
            'custom_pay_1' => $total_payment->total_custom_pay_1,
            'custom_pay_2' => $total_payment->total_custom_pay_2,
            'custom_pay_3' => $total_payment->total_custom_pay_3,
        ];
        $refund_formatted = [];
        foreach ($refunds as $key => $val) {
            if ($val > 0) {
                $refund_formatted[] = new CashRegisterTransaction([
                    'amount' => $val,
                    'pay_method' => $key,
                    'type' => 'debit',
                    'transaction_type' => 'refund',
                    'transaction_id' => $transaction->id
                ]);
            }
        }

        if (!empty($refund_formatted)) {
            $register->cash_register_transactions()->saveMany($refund_formatted);
        }
        return true;
    }

    /**
     * Retrieves details of given rigister id else currently opened register
     *
     * @param $register_id default null
     *
     * @return object
     */
    public function getRegisterDetails($register_id = null)
    {
        $query = CashRegister::join(
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
            );
        if (empty($register_id)) {
            // $user_id = auth()->user()->id;
            $location_id = request()->session()->get('user.business_location_id');
            $query->where('cash_registers.location_id', $location_id)
                ->where('cash_registers.statusss', 'open');
        } else {
            $query->where('cash_registers.id', $register_id);
        }
        // dd($query->toSql());              
        $register_details = $query->select(
            'cash_registers.created_at as open_time',
            'cash_registers.user_id',
            'bl.id as location_id',
            'bl.location_id as locationCode',
            'bl.name as locationName',
            'cash_registers.closing_note',
            DB::raw("SUM(IF(transaction_type='initial', amount, 0)) as cash_in_hand"),
            DB::raw("SUM(IF(transaction_type='sell', amount, IF(transaction_type='refund', -1 * amount, 0))) as total_sale"),
            DB::raw("SUM(IF(pay_method='cash', IF(transaction_type='sell', amount, 0), 0)) as total_cash"),
            DB::raw("SUM(IF(pay_method='cheque', IF(transaction_type='sell', amount, 0), 0)) as total_cheque"),
            DB::raw("SUM(IF(pay_method='card', IF(transaction_type='sell', amount, 0), 0)) as total_card"),
            DB::raw("SUM(IF(pay_method='bank_transfer', IF(transaction_type='sell', amount, 0), 0)) as total_bank_transfer"),
            DB::raw("SUM(IF(pay_method='other', IF(transaction_type='sell', amount, 0), 0)) as total_other"),
            DB::raw("SUM(IF(pay_method='custom_pay_1', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_1"),
            DB::raw("SUM(IF(pay_method='custom_pay_2', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_2"),
            DB::raw("SUM(IF(pay_method='custom_pay_3', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_3"),
            DB::raw("SUM(IF(transaction_type='refund', amount, 0)) as total_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='cash', amount, 0), 0)) as total_cash_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='cheque', amount, 0), 0)) as total_cheque_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='card', amount, 0), 0)) as total_card_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='bank_transfer', amount, 0), 0)) as total_bank_transfer_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='other', amount, 0), 0)) as total_other_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_1', amount, 0), 0)) as total_custom_pay_1_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_2', amount, 0), 0)) as total_custom_pay_2_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_3', amount, 0), 0)) as total_custom_pay_3_refund"),
            DB::raw("SUM(IF(pay_method='cheque', 1, 0)) as total_cheques"),
            DB::raw("SUM(IF(pay_method='card', 1, 0)) as total_card_slips"),
            // DB::raw("SELECT unit_price FROM transaction_sell_lines WHERE line_discount_amount > 0 as discount_given"),
            // Start from here
            // DB::raw("SUM(t.line_discount_amount) as discount_given"),
            // DB::raw("count(t.line_discount_amount) as discounted_receipts"),
            // DB::raw("SUM(t.line_discount_amount) as discount_given WHERE t.line_discount_amount>'0'"),
            // DB::raw("SUM(IF(t.line_discount_amount > 0.00 , t.line_discount_amount, 0)) as discount_given"),
            // DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as user_name"),
            // DB::raw("SUM(IF(pay_method='gift_card', IF(transaction_type='sell', amount, 0), 0)) as total_gift"),
            DB::raw("SUM(IF(line_discount_amount > 0.00 && unit_price > 0,unit_price, 0)) as discount_given"),
            DB::raw("SUM(IF(unit_price = 0.00,unit_price_before_discount, 0)) as less_unit_price"),
            // 'u.email'
        )->first();
        // dd($register_details);
        return $register_details;
    }
    public function getRegisterDetails_old_17_08_23($register_id = null)
    {
        $query = CashRegister::join(
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
            );
        if (empty($register_id)) {
            // $user_id = auth()->user()->id;
            $location_id = request()->session()->get('user.business_location_id');
            $query->where('cash_registers.location_id', $location_id)
                ->where('cash_registers.statusss', 'open');
        } else {
            $query->where('cash_registers.id', $register_id);
        }
        // dd($query->toSql());              
        $register_details = $query->select(
            'cash_registers.created_at as open_time',
            'cash_registers.user_id',
            'bl.id as location_id',
            'bl.location_id as locationCode',
            'bl.name as locationName',
            'cash_registers.closing_note',
            DB::raw("SUM(IF(transaction_type='initial', amount, 0)) as cash_in_hand"),
            DB::raw("SUM(IF(transaction_type='sell', amount, IF(transaction_type='refund', -1 * amount, 0))) as total_sale"),
            DB::raw("SUM(IF(pay_method='cash', IF(transaction_type='sell', amount, 0), 0)) as total_cash"),
            DB::raw("SUM(IF(pay_method='cheque', IF(transaction_type='sell', amount, 0), 0)) as total_cheque"),
            DB::raw("SUM(IF(pay_method='card', IF(transaction_type='sell', amount, 0), 0)) as total_card"),
            DB::raw("SUM(IF(pay_method='bank_transfer', IF(transaction_type='sell', amount, 0), 0)) as total_bank_transfer"),
            DB::raw("SUM(IF(pay_method='other', IF(transaction_type='sell', amount, 0), 0)) as total_other"),
            DB::raw("SUM(IF(pay_method='custom_pay_1', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_1"),
            DB::raw("SUM(IF(pay_method='custom_pay_2', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_2"),
            DB::raw("SUM(IF(pay_method='custom_pay_3', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_3"),
            DB::raw("SUM(IF(transaction_type='refund', amount, 0)) as total_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='cash', amount, 0), 0)) as total_cash_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='cheque', amount, 0), 0)) as total_cheque_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='card', amount, 0), 0)) as total_card_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='bank_transfer', amount, 0), 0)) as total_bank_transfer_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='other', amount, 0), 0)) as total_other_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_1', amount, 0), 0)) as total_custom_pay_1_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_2', amount, 0), 0)) as total_custom_pay_2_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_3', amount, 0), 0)) as total_custom_pay_3_refund"),
            DB::raw("SUM(IF(pay_method='cheque', 1, 0)) as total_cheques"),
            DB::raw("SUM(IF(pay_method='card', 1, 0)) as total_card_slips"),
            // DB::raw("SELECT unit_price FROM transaction_sell_lines WHERE line_discount_amount > 0 as discount_given"),
            // Start from here
            // DB::raw("SUM(t.line_discount_amount) as discount_given"),
            // DB::raw("count(t.line_discount_amount) as discounted_receipts"),
            // DB::raw("SUM(t.line_discount_amount) as discount_given WHERE t.line_discount_amount>'0'"),
            // DB::raw("SUM(IF(t.line_discount_amount > 0.00 , t.line_discount_amount, 0)) as discount_given"),
            // DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as user_name"),
            // DB::raw("SUM(IF(pay_method='gift_card', IF(transaction_type='sell', amount, 0), 0)) as total_gift"),
            DB::raw("SUM(IF(line_discount_amount > 0.00 && unit_price > 0,unit_price, 0)) as discount_given"),
            DB::raw("SUM(IF(unit_price = 0.00,unit_price_before_discount, 0)) as less_unit_price"),
            // 'u.email'
        )->first();
        dd($register_details);
        return $register_details;
    }

    /**
     * Get the transaction details for a particular register
     *
     * @param $user_id int
     * @param $open_time datetime
     * @param $close_time datetime
     *
     * @return array
     */
    public function getRegisterTransactionDetails($user_id, $open_time, $close_time, $id = null)
    {
        $location_id = request()->session()->get('user.business_location_id');
        if ($id) {
            $register_transaction_ids = CashRegisterTransaction::where('cash_register_id', $id)->pluck('transaction_id')->toArray();
            $product_details = Transaction::whereIn('transactions.id', $register_transaction_ids)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->join('transaction_sell_lines AS TSL', 'transactions.id', '=', 'TSL.transaction_id')
                ->join('products AS P', 'TSL.product_id', '=', 'P.id')
                ->leftjoin('brands AS B', 'P.brand_id', '=', 'B.id')
                ->groupBy('B.id')
                ->select(
                    'B.name as brand_name',
                    DB::raw('SUM(TSL.quantity) as total_quantity'),
                    DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity) as total_amount'),
                    DB::raw('SUM(transactions.final_total) as final_total')
                )
                ->orderByRaw('CASE WHEN brand_name IS NULL THEN 2 ELSE 1 END, brand_name')
                ->get();

            $transaction_details = Transaction::whereIn('transactions.id', $register_transaction_ids)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->select(
                    DB::raw('SUM(tax_amount) as total_tax'),
                    DB::raw('SUM(IF(discount_type = "percentage", total_before_tax*discount_amount/100, discount_amount)) as total_discount')
                )
                ->first();

            return [
                'product_details' => $product_details,
                'transaction_details' => $transaction_details
            ];
        }
        $product_details = Transaction::where('transactions.location_id', $location_id)
            ->whereBetween('transaction_date', [$open_time, $close_time])
            ->whereBetween('transaction_date', [$open_time, $close_time])
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->join('transaction_sell_lines AS TSL', 'transactions.id', '=', 'TSL.transaction_id')
            ->join('products AS P', 'TSL.product_id', '=', 'P.id')
            ->leftjoin('brands AS B', 'P.brand_id', '=', 'B.id')
            ->groupBy('B.id')
            ->select(
                'B.name as brand_name',
                DB::raw('SUM(TSL.quantity) as total_quantity'),
                DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity) as total_amount'),
                DB::raw('SUM(transactions.final_total) as final_total')
            )
            ->orderByRaw('CASE WHEN brand_name IS NULL THEN 2 ELSE 1 END, brand_name')
            ->get();

        $transaction_details = Transaction::where('transactions.location_id', $location_id)
            ->whereBetween('transaction_date', [$open_time, $close_time])
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->select(
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(IF(discount_type = "percentage", total_before_tax*discount_amount/100, discount_amount)) as total_discount')
            )
            ->first();

        return [
            'product_details' => $product_details,
            'transaction_details' => $transaction_details
        ];
    }

    /**
     * Retrieves the currently opened cash register for the user
     *
     * @param $int user_id
     *
     * @return obj
     */
    public function getCurrentCashRegister($user_id, $id = null)
    {
        $location_id = request()->session()->get('user.business_location_id');
        $register =  CashRegister::where('location_id', $location_id)
            ->where('statusss', 'open')
            ->first();

        return $register;
    }
    /**
     * Retrieves the currently opened cash register for the user
     *
     * @param $int user_id
     *
     * @return obj
     */
    public function getCashRegister($id)
    {
        $register =  CashRegister::find($id);

        return $register;
    }
}
