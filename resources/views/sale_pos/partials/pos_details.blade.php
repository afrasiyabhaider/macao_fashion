<style>
    .cust-pad {
        padding: 10px;
        margin-left: 5px;
    }
</style>
<div>
    {{-- <div class="panel-body bg-gray ">
		<div class="col-sm-8"></div>
		<div class="col-sm-4">
			<label>Amount Given:</label>
			<input type="number" min="1" name="change" id="change_amount" class="form-control" placeholder="Amount Given" onkeyup="clacuateChange(this);">
			<br>
			<h4 class="text-success">
				<label>Change: </label>
				<span id="change_text"></span>
			</h4>
		</div>
	</div> --}}
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-body bg-gray disabled" style="margin-bottom: 0px !important">
                <table class="table table-condensed" style="margin-bottom: 0px !important">
                    <tbody>
                        <tr>
                            <td>
                                <div class="col-sm-1 col-xs-3 d-inline-table">
                                    <b>@lang('sale.item'):</b>
                                    <br />
                                    <span class="total_quantity">0</span>
                                </div>

                                <div class="col-sm-4 col-xs-4 d-inline-table ">
                                    <b>Amount Given:</b>
                                    <br />
                                    <input type="number" min="1" name="change" id="change_amount"
                                        class="form-control" placeholder="Amount Given" onkeyup="clacuateChange(this);">
                                </div>
                                <div class="col-1">
                                </div>
                                <div class="col-sm-3 col-xs-3 d-inline-table text-center">
                                    <b>Change: </b>
                                    <br>
                                    <h4 class="text-success lead text-bold">
                                        <span id="change_text" style="font-size:40px"></span>
                                    </h4>
                                </div>

                                <div class="col-sm-2 col-xs-3 d-inline-table hide">
                                    <b>@lang('sale.total'):</b>
                                    <br />
                                    <span class="price_total">0</span>
                                </div>

                                <div class="col-sm-2 col-xs-6 d-inline-table hide">

                                    <span class="@if ($pos_settings['disable_discount'] != 0) hide @endif">

                                        <b>@lang('sale.discount')(-): @show_tooltip(__('tooltip.sale_discount'))</b>
                                        <br />
                                        <i class="fa fa-pencil-square-o cursor-pointer" id="pos-edit-discount"
                                            title="@lang('sale.edit_discount')" aria-hidden="true" data-toggle="modal"
                                            data-target="#posEditDiscountModal"></i>
                                        <span id="total_discount">0</span>
                                        <input type="hidden" name="discount_type" id="discount_type"
                                            value="@if (empty($edit)) {{ 'percentage' }}@else{{ $transaction->discount_type }} @endif"
                                            data-default="percentage">

                                        <input type="hidden" name="discount_amount" id="discount_amount"
                                            value="@if (empty($edit)) {{ @num_format($business_details->default_sales_discount) }} @else {{ @num_format($transaction->discount_amount) }} @endif"
                                            data-default="{{ $business_details->default_sales_discount }}">

                                    </span>
                                </div>

                                <div class="col-sm-2 col-xs-6 d-inline-table hide ">

                                    <span class="@if ($pos_settings['disable_order_tax'] != 0) hide @endif">

                                        <b>@lang('sale.order_tax')(+): @show_tooltip(__('tooltip.sale_tax'))</b>
                                        <br />
                                        <i class="fa fa-pencil-square-o cursor-pointer" title="@lang('sale.edit_order_tax')"
                                            aria-hidden="true" data-toggle="modal" data-target="#posEditOrderTaxModal"
                                            id="pos-edit-tax"></i>
                                        <span id="order_tax">
                                            @if (empty($edit))
                                                0
                                            @else
                                                {{ $transaction->tax_amount }}
                                            @endif
                                        </span>

                                        <input type="hidden" name="tax_rate_id" id="tax_rate_id"
                                            value="@if (empty($edit)) {{ $business_details->default_sales_tax }} @else {{ $transaction->tax_id }} @endif"
                                            data-default="{{ $business_details->default_sales_tax }}">

                                        <input type="hidden" name="tax_calculation_amount" id="tax_calculation_amount"
                                            value="@if (empty($edit)) {{ @num_format($business_details->tax_calculation_amount) }} @else {{ @num_format(optional($transaction->tax)->amount) }} @endif"
                                            data-default="{{ $business_details->tax_calculation_amount }}">

                                    </span>
                                </div>

                                <!-- shipping -->
                                <div class="col-sm-2 col-xs-6 d-inline-table hide">

                                    <span class="@if ($pos_settings['disable_discount'] != 0) hide @endif">

                                        <b>@lang('sale.shipping')(+): @show_tooltip(__('tooltip.shipping'))</b>
                                        <br />
                                        <i class="fa fa-pencil-square-o cursor-pointer" title="@lang('sale.shipping')"
                                            aria-hidden="true" data-toggle="modal" data-target="#posShippingModal"></i>
                                        <span id="shipping_charges_amount">0</span>
                                        <input type="hidden" name="shipping_details" id="shipping_details"
                                            value="@if (empty($edit)) {{ '' }}@else{{ $transaction->shipping_details }} @endif"
                                            data-default="">

                                        <input type="hidden" name="shipping_charges" id="shipping_charges"
                                            value="@if (empty($edit)) {{ @num_format(0.0) }} @else{{ @num_format($transaction->shipping_charges) }} @endif"
                                            data-default="0.00">

                                    </span>
                                </div>


                                <div class="col-sm-3 col-xs-12 d-inline-table">
                                    <b>@lang('sale.total_payable'):</b>
                                    <br />
                                    <input type="float" name="final_total" id="final_total_input"
                                        style="display: none">
                                    <span id="total_payable" class="text-success lead text-bold"
                                        style="font-size:70px">0</span>
                                    {{-- @if (empty($edit))
									<button type="button" class="btn btn-danger btn-flat btn-xs pull-right" id="pos-cancel">@lang('sale.cancel')</button>
								@else
									<button type="button" class="btn btn-danger btn-flat hide btn-xs pull-right" id="pos-delete">@lang('messages.delete')</button>
								@endif --}}
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <div class="col-sm-5 col-xs-5 col-2px-padding">

                                    <button type="button" class="btn btn-warning btn-sm pull-left cust-pad"
                                        onClick="openDiscount();">
                                        All Discount
                                    </button>

                                    <button type="button" class=" hide btn btn-info btn-sm pull-left cust-pad"
                                        onClick="openUnkown();">
                                        Unknown
                                    </button>


                                    @if (empty($pos_settings['disable_suspend']))
                                        {{-- pos-express-finalize --}}
                                        <button type="button" class="btn bg-red btn-block btn-flat no-print cust-pad"
                                            data-pay_method="suspend" title="@lang('lang_v1.tooltip_suspend')">
                                            <div class="text-center">
                                                <i class="fa fa-pause" aria-hidden="true"></i>
                                                <b>@lang('lang_v1.suspend')</b>
                                            </div>
                                        </button>
                                    @endif
                                    {{-- </div>
							<div class="col-sm-2 col-xs-12 col-2px-padding"> --}}
                                    {{-- pos-express-btn --}}
                                    <button type="button"
                                        class="btn bg-navy   btn-sm no-print pull-left cust-pad @if ($pos_settings['disable_pay_checkout'] != 0) hide @endif"
                                        id="pos-finalize" title="@lang('lang_v1.tooltip_checkout_multi_pay')">
                                        <div class="text-center">
                                            <i class="fa fa-check" aria-hidden="true"></i>
                                            <b>@lang('lang_v1.checkout_multi_pay')</b>
                                        </div>
                                    </button>
                                    @if (empty($edit))
                                        <button type="button" class="btn btn-danger btn-sm pull-left cust-pad"
                                            id="pos-cancel">@lang('sale.cancel')</button>
                                    @else
                                        <button type="button" class="btn btn-danger btn-sm pull-left cust-pad"
                                            id="pos-delete">@lang('messages.delete')</button>
                                    @endif
                                </div>
                                {{-- <div class="col-sm-3 col-xs-12 col-2px-padding">
								<button type="button" class="btn btn-info btn-block btn-flat btn-lg bg-maroon btn-lg pull-left pos-express-btn pos-express-finalize" title="@lang('gift.title')"
									onclick="openGiftCard()">
									<div class="text-center">
										<i class="fa fa-check" aria-hidden="true"></i>
										<b style="font-size: 16px">@lang('gift.title')</b>
									</div>
								</button>
							</div> --}}
                                <div class="col-sm-3 col-xs-12 col-2px-padding">
                                    {{-- <button type="button" class="btn btn-info btn-block btn-flat btn-lg bg-blue-gradient  btn-lg pull-left pos-express-btn" title="Bonus Points"
									onclick="openBonusPoint()">
									<div class="text-center">
										<i class="fa fa-dollar" aria-hidden="true"></i> 
										<b style="font-size: 16px">Bonus Points</b>
									</div>
								</button> --}}
								 <button type="button"
                                     class="btn btn-info btn-block btn-flat btn-lg bg-blue-gradient  btn-lg pull-left pos-express-btn" title="Bonus Points"
                                    {{-- class="btn btn-info btn-block btn-flat btn-lg no-print @if ($pos_settings['disable_express_checkout'] != 0) hide @endif pos-express-btn pos-express-finalize"									 --}}
                                    onclick="couponPayment()">
									<i class="fa fa-dollar" aria-hidden="true"></i> 
                                    Coupon
								</button>
                                </div>
                                <div class="col-sm-2 col-xs-12 col-2px-padding">
                                    <button type="button"
                                        class="btn btn-info btn-block btn-flat btn-lg no-print @if ($pos_settings['disable_express_checkout'] != 0) hide @endif pos-express-btn pos-express-finalize"
                                        id="pos-save-card-external">
                                        {{-- data-pay_method="card"  --}}
                                        <i class="fa fa-credit-card"></i>
                                        Card
                                    </button>

                                </div>
                                <div class="col-sm-2 col-xs-12 col-2px-padding">
                                    <button type="button"  
                                        class="btn btn-success btn-block btn-flat btn-lg no-print @if ($pos_settings['disable_express_checkout'] != 0) hide @endif pos-express-btn pos-express-finalize"
                                        data-pay_method="cash" title="@lang('tooltip.express_checkout')">
                                        <div class="text-center">
                                            <i class="fa fa-dollar" aria-hidden="true"></i>
                                            <b>@lang('lang_v1.express_checkout_cash')</b>
                                        </div>
                                    </button>

                                </div>

                                <div class="div-overlay pos-processing"></div>
                            </td>
                        </tr>

                    </tbody>
                </table>

                <!-- Button to perform various actions -->
                <div class="row">
                </div>
            </div>
        </div>
    </div>
</div>

@if (isset($transaction))
    @include('sale_pos.partials.edit_discount_modal', [
        'sales_discount' => $transaction->discount_amount,
        'discount_type' => $transaction->discount_type,
    ])
@else
    @include('sale_pos.partials.edit_discount_modal', [
        'sales_discount' => $business_details->default_sales_discount,
        'discount_type' => 'percentage',
    ])
@endif

@if (isset($transaction))
    @include('sale_pos.partials.edit_order_tax_modal', ['selected_tax' => $transaction->tax_id])
@else
    @include('sale_pos.partials.edit_order_tax_modal', [
        'selected_tax' => $business_details->default_sales_tax,
    ])
@endif

@if (isset($transaction))
    @include('sale_pos.partials.edit_shipping_modal', [
        'shipping_charges' => $transaction->shipping_charges,
        'shipping_details' => $transaction->shipping_details,
    ])
@else
    @include('sale_pos.partials.edit_shipping_modal', [
        'shipping_charges' => '0.00',
        'shipping_details' => '',
    ])
@endif
