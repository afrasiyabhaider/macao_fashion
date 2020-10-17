<div class="payment_details_div @if( $payment_line['method'] !== 'gift_card' ) {{ 'hide' }} @endif" data-type="gift_card" >
	<div class="col-md-6">
		<div class="form-group">
			{!! Form::label("gift_card_$row_index", __('lang_v1.gift_card')) !!}
			{!! Form::text("payment[$row_index][gift_card]", $payment_line['gift_card'], ['class' => 'form-control gift_cardc', 'placeholder' => __('lang_v1.gift_card'), 'id' => "gift_card_$row_index","onChange" => "applyGiftCard(this,$row_index)"]); !!}
		</div>
	</div>
	 
	<div class="clearfix"></div>
</div>
<div class="payment_details_div @if( $payment_line['method'] !== 'coupon' ) {{ 'hide' }} @endif" data-type="coupon" >
	<div class="col-md-6">
		<div class="form-group">
			{!! Form::label("coupon_$row_index", __('lang_v1.coupon')) !!}
			{!! Form::text("payment[$row_index][coupon]", $payment_line['coupon'], ['class' => 'form-control couponc', 'placeholder' => 'Enter'.__('lang_v1.coupon'), 'id' => "coupon_$row_index","onChange" => "applyCoupon(this,$row_index)"]); !!}
		</div>
	</div>
	 
	<div class="clearfix"></div>
</div>
<!-- <div class="payment_details_div @if( $payment_line['method'] !== 'force_price' ) {{ 'hide' }} @endif" data-type="force_price" >
	<div class="col-md-6">
		<div class="form-group">
			{!! Form::label("force_price_$row_index", __('lang_v1.force_price')) !!}
			{!! Form::number("payment[$row_index][force_price]", $payment_line['force_price'], ['class' => 'form-control force_pricec', 'placeholder' => __('lang_v1.force_price'),"onchange"=> "changePayment(this,$row_index)", 'id' => "force_price_$row_index"]); !!}
		</div>
	</div>
	 
	<div class="clearfix"></div>
</div> -->
 
<div class="payment_details_div @if( $payment_line['method'] !== 'bonus_points' ) {{ 'hide' }} @endif" data-type="bonus_points" >
	<div class="col-md-6">
		<div class="form-group">
			{!! Form::label("bonus_points_$row_index", 'Bonus Points') !!}
			{!! Form::number("payment[$row_index][bonus_points]", $payment_line['bonus_points'], ['class' => 'form-control bonus_points', 'placeholder' => 'Bonus Points',"onchange"=> "changePayment(this,$row_index)", 'id' => "bonus_points_$row_index"]); !!}
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			<button type="button" class="btn btn-md btn-warning" onclick="applyBonusPoint(<?=$row_index?>);">Get Member Bonus Points</button>
		</div>
	</div>

	 
	<div class="clearfix"></div>
</div>
<div class="payment_details_div @if( $payment_line['method'] !== 'card' ) {{ 'hide' }} @endif" data-type="card" >
	<div class="col-md-4">
		<div class="form-group">
			{!! Form::label("card_number_$row_index", __('lang_v1.card_no')) !!}
			{!! Form::text("payment[$row_index][card_number]", $payment_line['card_number'], ['class' => 'form-control', 'placeholder' => __('lang_v1.card_no'), 'id' => "card_number_$row_index"]); !!}
		</div>
	</div>
	<div class="col-md-4">
		<div class="form-group">
			{!! Form::label("card_holder_name_$row_index", __('lang_v1.card_holder_name')) !!}
			{!! Form::text("payment[$row_index][card_holder_name]", $payment_line['card_holder_name'], ['class' => 'form-control', 'placeholder' => __('lang_v1.card_holder_name'), 'id' => "card_holder_name_$row_index"]); !!}
		</div>
	</div>
	<div class="col-md-4">
		<div class="form-group">
			{!! Form::label("card_transaction_number_$row_index",__('lang_v1.card_transaction_no')) !!}
			{!! Form::text("payment[$row_index][card_transaction_number]", $payment_line['card_transaction_number'], ['class' => 'form-control', 'placeholder' => __('lang_v1.card_transaction_no'), 'id' => "card_transaction_number_$row_index"]); !!}
		</div>
	</div>
	<div class="clearfix"></div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("card_type_$row_index", __('lang_v1.card_type')) !!}
			{!! Form::select("payment[$row_index][card_type]", ['credit' => 'Credit Card', 'debit' => 'Debit Card','visa' => 'Visa', 'master' => 'MasterCard'], $payment_line['card_type'],['class' => 'form-control', 'id' => "card_type_$row_index" ]); !!}
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("card_month_$row_index", __('lang_v1.month')) !!}
			{!! Form::text("payment[$row_index][card_month]", $payment_line['card_month'], ['class' => 'form-control', 'placeholder' => __('lang_v1.month'),
			'id' => "card_month_$row_index" ]); !!}
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("card_year_$row_index", __('lang_v1.year')) !!}
			{!! Form::text("payment[$row_index][card_year]", $payment_line['card_year'], ['class' => 'form-control', 'placeholder' => __('lang_v1.year'), 'id' => "card_year_$row_index" ]); !!}
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("card_security_$row_index",__('lang_v1.security_code')) !!}
			{!! Form::text("payment[$row_index][card_security]", $payment_line['card_security'], ['class' => 'form-control', 'placeholder' => __('lang_v1.security_code'), 'id' => "card_security_$row_index"]); !!}
		</div>
	</div>
	<div class="clearfix"></div>
</div>
<div class="payment_details_div @if( $payment_line['method'] !== 'cheque' ) {{ 'hide' }} @endif" data-type="cheque" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("cheque_number_$row_index",__('lang_v1.cheque_no')) !!}
			{!! Form::text("payment[$row_index][cheque_number]", $payment_line['cheque_number'], ['class' => 'form-control', 'placeholder' => __('lang_v1.cheque_no'), 'id' => "cheque_number_$row_index"]); !!}
		</div>
	</div>
</div>
<div class="payment_details_div @if( $payment_line['method'] !== 'bank_transfer' ) {{ 'hide' }} @endif" data-type="bank_transfer" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("bank_account_number_$row_index",__('lang_v1.bank_account_number')) !!}
			{!! Form::text( "payment[$row_index][bank_account_number]", $payment_line['bank_account_number'], ['class' => 'form-control', 'placeholder' => __('lang_v1.bank_account_number'), 'id' => "bank_account_number_$row_index"]); !!}
		</div>
	</div>
</div>
<div class="payment_details_div @if( $payment_line['method'] !== 'custom_pay_1' ) {{ 'hide' }} @endif" data-type="custom_pay_1" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("transaction_no_1_$row_index", __('lang_v1.transaction_no')) !!}
			{!! Form::text("payment[$row_index][transaction_no_1]", $payment_line['transaction_no'], ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no'), 'id' => "transaction_no_1_$row_index"]); !!}
		</div>
	</div>
</div>
<div class="payment_details_div @if( $payment_line['method'] !== 'custom_pay_2' ) {{ 'hide' }} @endif" data-type="custom_pay_2" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("transaction_no_2_$row_index", __('lang_v1.transaction_no')) !!}
			{!! Form::text("payment[$row_index][transaction_no_2]", $payment_line['transaction_no'], ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no'), 'id' => "transaction_no_2_$row_index"]); !!}
		</div>
	</div>
</div>
<div class="payment_details_div @if( $payment_line['method'] !== 'custom_pay_3' ) {{ 'hide' }} @endif" data-type="custom_pay_3" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("transaction_no_3_$row_index", __('lang_v1.transaction_no')) !!}
			{!! Form::text("payment[$row_index][transaction_no_3]", $payment_line['transaction_no'], ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no'), 'id' => "transaction_no_3_$row_index"]); !!}
		</div>
	</div>
</div>