<!-- business information here -->
<style type="text/css">
	.pgBr {
		page-break-before: always;
	}

	.barcode-img {
		width: 20% !important;
		height: 100px !important;
		margin-left: 30px !important;
	}

	.margin-top {
		margin-top: 20px !important;
		margin-right: 20px !important;
		margin-left: 40px !important;
	}
	.coupon-ml{
		margin-left: 40px !important;
	}
</style>
<div class="row">
	<!-- Logo -->
	@if(!empty($receipt_details->logo))
	<img src="{{$receipt_details->logo}}" class="img img-responsive center-block">
	@endif
	<div class="row">
		<div class="col-xs-12">
			{{-- Barcode --}}
			<p>
				<img class="center-block margin-top"
					src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 3,40,array(55, 55, 55), false)}}">
			</p>
			@php
			$barcodeArr = str_split($receipt_details->invoice_no, 1);
			@endphp
			<center class='barcodetc' style='word-spacing: 5px;font-size: 20px;font-weight: bold;'>
				@foreach($barcodeArr As $b)
				<span>{{$b}}</span>
				@endforeach
			</center>
		</div>
	</div>
	<!-- Header text -->
	@if(!empty($receipt_details->header_text))
	<div class="col-xs-12">
		{!! $receipt_details->header_text !!}
	</div>
	@endif

	<!-- business information here -->
	<div class="col-xs-12 text-center">
		<!--<h2 class="text-center">-->
		<!--	MACAOBE-->
		<!--</h2>-->
		<h3 class="text-center">
			<!-- Shop & Location Name  -->
			@if(!empty($receipt_details->display_name))
			{{$receipt_details->display_name}}
			@endif
		</h3>
		<p>
			<b>
				@if(!empty($receipt_details->address))
				{!! $receipt_details->address !!}
				@endif
				@if(!empty($receipt_details->contact))
				<br /> {{ $receipt_details->contact }}
				@endif </b>


			@if(!empty($receipt_details->location_custom_fields))
			<br>{{ $receipt_details->location_custom_fields }}
			@endif
		</p>
		<p>
			@if(!empty($receipt_details->sub_heading_line1))
			{{ $receipt_details->sub_heading_line1 }}
			@endif
			@if(!empty($receipt_details->sub_heading_line2))
			<br>{{ $receipt_details->sub_heading_line2 }}
			@endif
			@if(!empty($receipt_details->sub_heading_line3))
			<br>{{ $receipt_details->sub_heading_line3 }}
			@endif
			@if(!empty($receipt_details->sub_heading_line4))
			<br>{{ $receipt_details->sub_heading_line4 }}
			@endif
			@if(!empty($receipt_details->sub_heading_line5))
			<br>{{ $receipt_details->sub_heading_line5 }}
			@endif
		</p>
		<p>
			@if(!empty($receipt_details->tax_info1))
			<b>{{ $receipt_details->tax_label1 }}</b> {{ $receipt_details->tax_info1 }}
			@endif

			@if(!empty($receipt_details->tax_info2))
			<b>{{ $receipt_details->tax_label2 }}</b> {{ $receipt_details->tax_info2 }}
			@endif
		</p>


		<!-- Invoice  number, Date  -->
		<p style="width: 100% !important" class="word-wrap">
			<span class="pull-left text-left word-wrap">

				<!-- Table information-->
				@if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
				<br />
				<span class="pull-left text-left">
					@if(!empty($receipt_details->table_label))
					<b>{!! $receipt_details->table_label !!}</b>
					@endif
					{{$receipt_details->table}}

					<!-- Waiter info -->
				</span>
				@endif


			</span>

			<span class="pull-center text-center col-xs-12 col-md-12">
				<b>{{$receipt_details->date_label}}</b>
				{{date("d/m/Y  H:i:s",strtotime($receipt_details->invoice_date))}} 
				
				<br>
				<span>
					<b>Operator : </b> {{$receipt_details->user}}
				</span>

				@if(!empty($receipt_details->serial_no_label) || !empty($receipt_details->repair_serial_no))
				<br>
				@if(!empty($receipt_details->serial_no_label))
				<b>{!! $receipt_details->serial_no_label !!}</b>
				@endif
				{{$receipt_details->repair_serial_no}}<br>
				@endif
				@if(!empty($receipt_details->repair_status_label) || !empty($receipt_details->repair_status))
				@if(!empty($receipt_details->repair_status_label))
				<b>{!! $receipt_details->repair_status_label !!}</b>
				@endif
				{{$receipt_details->repair_status}}<br>
				@endif

				@if(!empty($receipt_details->repair_warranty_label) || !empty($receipt_details->repair_warranty))
				@if(!empty($receipt_details->repair_warranty_label))
				<b>{!! $receipt_details->repair_warranty_label !!}</b>
				@endif
				{{$receipt_details->repair_warranty}}
				<br>
				@endif
				<!-- Waiter info -->
				@if(!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
				<br />
				@if(!empty($receipt_details->service_staff_label))
				<b>{!! $receipt_details->service_staff_label !!}</b>
				@endif
				{{$receipt_details->service_staff}}
				@endif
			</span>

			{{-- <span class="pull-center text-center col-xs-12 col-md-12">
					<span class="pull-left text-center col-xs-6 col-md-6">
        				<!-- customer info -->
        				@if(!empty($receipt_details->customer_name))
        					<b>{{ $receipt_details->customer_label }}</b> {{ $receipt_details->customer_name }} <br>
			@endif

			</span>
			<span class="pull-right text-center col-xs-6 col-md-6">
				<!-- customer info -->
				<b>Operator : </b> {{$receipt_details->user}}

			</span>

			</span> --}}

		</p>
	</div>

	@if(!empty($receipt_details->defects_label) || !empty($receipt_details->repair_defects))
	<div class="col-xs-12">
		<br>
		@if(!empty($receipt_details->defects_label))
		<b>{!! $receipt_details->defects_label !!}</b>
		@endif
		{{$receipt_details->repair_defects}}
	</div>
	@endif
	<!-- /.col -->
</div>


<div class="row">
	<div class="col-xs-12">
		<br />
		<table class="table table-responsive table-bordered table-condensed" style="padding: 0">
			<thead>
				<tr>
					<th>Sr</th>
					<th>{{$receipt_details->table_product_label}}</th>
					<th>Qty</th>
					<th>PDO</th>
					<th>Disc%</th>
					<th>Price</th>
					{{-- <th>Disc</th> --}}
					<!-- <th>{{$receipt_details->table_subtotal_label}}</th> -->
				</tr>
			</thead>
			<tbody>
				{{-- @dd($receipt_details->lines) --}}
				@php $i=0; @endphp
				@forelse($receipt_details->lines as $line)
				@php $i++; @endphp
				<tr>
					<td style="word-break: break-all;"> {{$i}}</td>
					<td style="word-break: break-all;">
						@if(!empty($line['image']))
						<img src="{{$line['image']}}" alt="Image" width="50"
							style="float: left; margin-right: 8px;">
						@endif
						{{$line['name']}} {{$line['variation']}}
						@if(!empty($line['sub_sku']))
						@endif @if(!empty($line['brand'])), {{$line['brand']}} @endif
						@if(!empty($line['cat_code'])), {{$line['cat_code']}}@endif
						@if(!empty($line['product_custom_fields'])), {{$line['product_custom_fields']}} @endif
						@if(!empty($line['sell_line_note']))({{$line['sell_line_note']}}) @endif
						@if(!empty($line['lot_number']))<br> {{$line['lot_number_label']}}:
						{{$line['lot_number']}} @endif
						@if(!empty($line['product_expiry'])), {{$line['product_expiry_label']}}:
						{{$line['product_expiry']}} @endif
					</td>
					<td>{{$line['quantity']}} </td>
					<td>{{$line['unit_price_before_discount']}}</td>
					{{-- <td>{{$line['unit_price_inc_tax']}}</td> --}}
					@php
					$arr = explode(" ",$line['line_discount']);
					$discPrice = $arr[0];
					$discPercentagePrice = $arr[1];
					@endphp
					<td>{{$discPercentagePrice}}</td>
					<td>
						{{
								$line['line_total']
							}}
					</td>
					{{-- <td>{{($line['unit_price_before_discount']*$line['quantity'])-$discPrice}}</td> --}}
					{{-- <td>{{$discPrice}}</td> --}}
					<!-- <td>{{$line['line_total']}}</td> -->
				</tr>
				@if(!empty($line['modifiers']))
				@foreach($line['modifiers'] as $modifier)
				<tr>
					<td style="word-break: break-all;"> {{$i}}</td>
					<td>
						{{$modifier['name']}} {{$modifier['variation']}}
						@if(!empty($modifier['sub_sku'])), {{$modifier['sub_sku']}} @endif
						@if(!empty($modifier['cat_code'])), {{$modifier['cat_code']}}@endif
						@if(!empty($modifier['sell_line_note']))({{$modifier['sell_line_note']}}) @endif
					</td>
					<td>{{$modifier['quantity']}} {{$modifier['units']}} </td>
					<td>{{$discPrice}}</td>
					<td>{{$discPercentagePrice}}</td>
					<!-- <td>{{$modifier['unit_price_inc_tax']}}</td> -->
					<!-- <td>{{$modifier['line_total']}}</td> -->
				</tr>
				@endforeach
				@endif
				@empty
				<tr>
					<td colspan="4">&nbsp;</td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
<div class="row">
	<div class="col-xs-6">

		<table class="table table-condensed  ">
			<thead>
				<th style="width: 50%">M.P</th>
				{{-- <th>Amount</th> --}}
				<!--<th>Date</th>-->
				@if(!empty($receipt_details->payments))
				@foreach($receipt_details->payments as $payment)
				@if($payment['method_name'] == 'gift_card' || $payment['method'] == 'coupon')
				{{-- <tr> --}}
				<td>
					{{$payment['method']}} 
					<br />
					<img class="center-block margin-top"
						src="data:image/png;base64,{{DNS1D::getBarcodePNG($payment['barcode'], 'C128', 2,40,array(55, 55, 55), true)}}">
				</td>
				{{-- <td>{{$payment['amount']}}</td> --}}
				<!--<td>{{$payment['date']}}</td>-->
				{{-- </tr> --}}
				@else
				{{-- <tr> --}}
					<td>
						{{$payment['method']}}
						<br>
					</td>
				{{-- <td>{{$payment['amount']}}</td> --}}
				{{-- <!--<td>{{$payment['date']}}</td>--> --}}
				{{-- </tr> --}}
				@endif

				@endforeach
				@endif
			</thead>


			<!-- Total Paid-->
			@if(!empty($receipt_details->total_paid))
			<tr>
				<th style="width: 40%">
					{!! $receipt_details->total_paid_label !!}
				</th>
				<td>
					{{$receipt_details->total_paid}}
				</td>
			</tr>
			@endif

			<!-- Total Due-->
			@if(!empty($receipt_details->total_due))
			<tr>
				<th>
					{!! $receipt_details->total_due_label !!}
				</th>
				<td>
					{{$receipt_details->total_due}}
				</td>
			</tr>
			@endif

			@if(!empty($receipt_details->all_due))
			<tr>
				<th>
					{!! $receipt_details->all_bal_label !!}
				</th>
				<td>
					{{$receipt_details->all_due}}
				</td>
			</tr>
			@endif
		</table>

		{{$receipt_details->additional_notes}}
	</div>

	<div class="col-xs-6">
		<div class="table-responsive">
			<table class="table table-condensed">
				<tbody>
					<tr>
						<th style="width:45%">
							{!! $receipt_details->subtotal_label !!}
						</th>
						<td>
							{{$receipt_details->subtotal}}
						</td>
					</tr>

					<!-- Shipping Charges -->
					@if(!empty($receipt_details->shipping_charges))
					<tr>
						<th style="width:40%">
							{!! $receipt_details->shipping_charges_label !!}
						</th>
						<td>
							{{$receipt_details->shipping_charges}}
						</td>
					</tr>
					@endif

					<!-- Discount -->

					@if( !empty($receipt_details->discounted_amount) )
					<tr>
						<th>
							{!! $receipt_details->discount_label !!}
						</th>

						<td>
							(-) {{$receipt_details->discounted_amount}}
						</td>
					</tr>
					{{-- @if ($receipt_details->discount_mode == "fixed") --}}
						<tr>
							<th>
								Mode
							</th>

							<td class="text-uppercase">
								{{ ($receipt_details->discount_mode = 'fixed' )? 'Unknown' : $receipt_details->discount_mode  }}
							</td>
						</tr>   
					{{-- @endif --}}
					@endif

					<!-- Tax -->
					@if( !empty($receipt_details->tax) )
					<tr>
						<th>
							{!! $receipt_details->tax_label !!}
						</th>
						<td>
							(+) {{$receipt_details->tax}}
						</td>
					</tr>
					@endif

					<!-- Total -->
					<tr>
						<th style="width: 40%">
							{!! $receipt_details->total_label !!}
						</th>
						<td>
							{{$receipt_details->total}}
						</td>
					</tr>



				</tbody>
			</table>
		</div>
	</div>
</div>

@if($receipt_details->show_barcode)
<div class="row">
	<div class="col-xs-12">
		{{-- Barcode --}}
		<img class="center-block margin-top"
			src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 'C128', 2,40,array(55, 55, 55), true)}}">
	</div>
</div>
@endif

@if(!empty($receipt_details->footer_text))
<div class="row" style="padding-left: 5px;padding-bottom:20px;padding-right:5px;">
	<div class="col-xs-12">
		{!! $receipt_details->footer_text !!}
	</div>
</div>
@endif
<!-- FOR Gift Cards and Coupons BarCodes -->

@if(!empty($receipt_details->payments))
@foreach($receipt_details->payments as $payment)

@if(($payment['method_name'] == 'coupon' && !empty($payment['coupon'])) || ($payment['method_name'] == 'coupon' &&
!empty($payment['coupon'])))
<div class="row pgBr">
	<div class="col-xs-12 text-center pgBr">
		<p>
			<h1> DETAILS</h1>
		</p>
		<img class="barcode-img center-block margin-top"
			src="data:image/png;base64,{{DNS1D::getBarcodePNG($payment['coupon']['barcode'], 'C128', 2,40,array(55, 55, 55), true)}}">
		<br />
		<h1>
			Value : {{$payment['coupon']['amount']}} €
		</h1>
		<p>Details : {!!$payment['coupon']['details']!!}</p>
		<p>You Can use this <b>Coupon</b> For Next Purchase within 3 Months or You can Extend the Expiry Date <br />
			Happy Shopping </p>
	</div>
</div>
@endif
@if(!empty($payment['p_type']))
@if(!empty($payment['p_type']['gift_card']))
@foreach($payment['p_type']['gift_card'] as $obj)
<div class="row pgBr">
	<div class="col-xs-12 text-center pgBr">
		<p>
			<h1> Gift Card Details</h1>
		</p>
		<p>
			<img class="center-block margin-top"
				src="data:image/png;base64,{{DNS1D::getBarcodePNG($obj['barcode'], 'C128', 2,40,array(55, 55, 55), false)}}">
		</p>
		@php
		$barcodeArr = str_split($obj['barcode'], 1);
		@endphp
		<center class='barcodetc' style='word-spacing: 5px;font-size: 20px;font-weight: bold;'>
			@foreach($barcodeArr As $b)
			<span>{{$b}}</span>
			@endforeach
		</center>
		<br />
		<h1>
			Value : {{$obj['value']}} €
		</h1>
		<p>
			Details : {{$obj['details']}}
		</p>
		<p>
			You Can use this <b>Gift Card</b> For Next Purchase within 3 Months or You can Extend the Expiry Date
			<br /> Happy Shopping
		</p>
	</div>
</div>
@endforeach
@endif
@if(!empty($payment['p_type']['coupon']))
@foreach($payment['p_type']['coupon'] as $obj)
<div class="row pgBr">
	<div class="col-xs-12 text-center pgBr">
		<p>
			<h1> Coupon Details</h1>
		</p>
		<p>
			<img class="center-block margin-top coupon-ml"
				src="data:image/png;base64,{{DNS1D::getBarcodePNG($obj['barcode'], 'C128', 2,40,array(55, 55, 55), false)}}">
		</p>
		@php
		$barcodeArr = str_split($obj['barcode'], 1);
		@endphp
		<center class='barcodetc' style='word-spacing: 5px;font-size: 20px;font-weight: bold;'>
			@foreach($barcodeArr As $b)
			<span>{{$b}}</span>
			@endforeach
		</center>
		<br />
		<h1>
			Value : {{$obj['value']}} €
		</h1>
		<p>
			Details : {{$obj['details']}}
		</p>
		<p>
			You Can use this <b>Coupon</b> For Next Purchase within 3 Months or You can Extend the Expiry Date <br />
			Happy Shopping
		</p>
	</div>
</div>
@endforeach
@endif
@endif

@endforeach
@endif