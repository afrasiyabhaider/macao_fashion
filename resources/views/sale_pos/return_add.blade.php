@extends('layouts.app')

@section('title', 'POS')

@section('content')

<!-- Content Header (Page header) -->
<!-- <section class="content-header">
    <h1>Add Purchase</h1> -->
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
<!-- </section> -->

<!-- Main content -->
<section class="content no-print">
	<div class="row">
		<div class="@if(!empty($pos_settings['hide_product_suggestion']) && !empty($pos_settings['hide_recent_trans'])) col-md-10 col-md-offset-1 @else col-md-7 @endif col-sm-12">
			<div class="box box-success">
				<div class="box-header with-border" >
					<h3 class="box-title">
						Editing 
						@if($transaction->status == 'draft' && $transaction->is_quotation == 1) 
							@lang('lang_v1.quotation')
						@elseif($transaction->status == 'draft') 
							Draft 
						@elseif($transaction->status == 'final') 
							Invoice 
						@endif 
						<span class="text-success">#{{$transaction->invoice_no}}</span> <i class="fa fa-keyboard-o hover-q text-muted" aria-hidden="true" data-container="body" data-toggle="popover" data-placement="bottom" data-content="@include('sale_pos.partials.keyboard_shortcuts_details')" data-html="true" data-trigger="hover" data-original-title="" title=""></i>
					</h3>
					<div class="pull-right box-tools ">
							{{-- onclick="openReturnWindow();" --}}
							<a title="Return Sale" data-toggle="tooltip" data-placement="bottom" class="btn btn-danger btn-md pull-right" href="{{url('sell-return/add')}}" target="__blank">
								<strong><i class="fa fa-undo"></i></strong>
								Return
							   {{-- <strong><i class="fa fa-asl-interpreting"></i></strong> --}}
							   {{-- RETURN --}}
						   </a>
							<button type="button" onclick="openPopupWindow('/products/transfer');" title="Transfer Products" data-toggle="tooltip" data-placement="bottom" class="btn btn-warning pull-right" style="margin-right: 5px">
								<strong><i class="fa fa-random fa-lg"></i></strong>
								Transfer
							</button>
							<button type="button" title="Gift Card" data-toggle="tooltip" data-placement="bottom" class="btn btn-success pos_add_quick_product pull-right" data-href="{{action('GiftCardController@quickAdd')}}" data-container=".quick_add_product_modal" style="margin-right: 5px">
								<i class="fa fa-archive fa-lg"></i>
								Gift Card
							</button>
							<button type="button" title="Add Cupons" data-toggle="tooltip" data-placement="bottom" class=" btn btn-success pos_add_quick_product pull-right" data-href="{{action('CouponController@quickAdd')}}" data-container=".quick_add_product_modal" style="margin-right: 5px">
								<i class="fa fa-calendar-check-o fa-lg"></i>
								Cupon
							</button>
						<button type="button" title="Add Cupons" data-toggle="tooltip" data-placement="bottom" class=" btn btn-success pos_add_quick_product pull-right" datahref="{{action('SellPosController@create')}}" style="margin-right: 5px">

							<strong>
								<i class="fa fa-laptop"></i> 
								POS
							</strong>
						</button>
					</div>
				</div>
				<input type="hidden" id="item_addition_method" value="{{$business_details->item_addition_method}}">
				{!! Form::open(['url' => action('SellPosController@returnCreate', [$transaction->id]), 'method' => 'post', 'id' => 'edit_pos_sell_form' ]) !!}

				{{ method_field('POST') }}

				{!! Form::hidden('location_id', $transaction->location_id, ['id' => 'location_id', 'data-receipt_printer_type' => !empty($location_printer_type) ? $location_printer_type : 'browser']); !!}

				<!-- /.box-header -->
				<div class="box-body">
					<div class="row">
						@if(config('constants.enable_sell_in_diff_currency') == true)
							<div class="col-md-4 col-sm-6">
								<div class="form-group">
									<div class="input-group">
										<span class="input-group-addon">
											<i class="fa fa-exchange"></i>
										</span>
										{!! Form::text('exchange_rate', @num_format($transaction->exchange_rate), ['class' => 'form-control input-sm input_number', 'placeholder' => __('lang_v1.currency_exchange_rate'), 'id' => 'exchange_rate']); !!}
									</div>
								</div>
							</div>
						@endif
						@if(!empty($price_groups))
							@if(count($price_groups) > 1)
								<div class="col-md-4 col-sm-6">
									<div class="form-group">
										<div class="input-group">
											<span class="input-group-addon">
												<i class="fa fa-money"></i>
											</span>
											{!! Form::hidden('hidden_price_group', $transaction->selling_price_group_id, ['id' => 'hidden_price_group']) !!}
											{!! Form::select('price_group', $price_groups, $transaction->selling_price_group_id, ['class' => 'form-control select2', 'id' => 'price_group', 'style' => 'width: 100%;']); !!}
											<span class="input-group-addon">
											@show_tooltip(__('lang_v1.price_group_help_text'))
										</span> 
										</div>
									</div>
								</div>
							@else
								{!! Form::hidden('price_group', $transaction->selling_price_group_id, ['id' => 'price_group']) !!}
							@endif
						@endif

						@if(in_array('subscription', $enabled_modules))
							<div class="col-md-4 pull-right col-sm-6">
								<div class="checkbox">
									<label>
						              {!! Form::checkbox('is_recurring', 1, $transaction->is_recurring, ['class' => 'input-icheck', 'id' => 'is_recurring']); !!} @lang('lang_v1.subscribe')?
						            </label><button type="button" data-toggle="modal" data-target="#recurringInvoiceModal" class="btn btn-link"><i class="fa fa-external-link"></i></button>@show_tooltip(__('lang_v1.recurring_invoice_help'))
								</div>
							</div>
						@endif
					</div>
					<div class="row">
						<div class="@if(!empty($commission_agent)) col-sm-4 @else col-sm-6 @endif">
							<div class="form-group">
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fa fa-user"></i>
									</span>
									<input type="hidden" id="default_customer_id" 
									value="{{ $transaction->contact->id }}" >
									<input type="hidden" id="default_customer_name" 
									value="{{ $transaction->contact->name }}" >
									{!! Form::select('contact_id', 
										[], null, ['class' => 'form-control mousetrap', 'id' => 'customer_id', 'placeholder' => 'Enter Customer name / phone', 'required', 'style' => 'width: 100%;']); !!}
									<span class="input-group-btn">
										<button type="button" class="btn btn-default bg-white btn-flat add_new_customer" data-name=""><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
									</span>
								</div>
							</div>
						</div>

						<input type="hidden" name="pay_term_number" id="pay_term_number" value="{{$transaction->pay_term_number}}">
						<input type="hidden" name="pay_term_type" id="pay_term_type" value="{{$transaction->pay_term_type}}">

						@if(!empty($commission_agent))
						<div class="col-sm-4">
							<div class="form-group">
							{!! Form::select('commission_agent', 
										$commission_agent, $transaction->commission_agent, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.commission_agent')]); !!}
							</div>
						</div>
						@endif
						<div class="@if(!empty($commission_agent)) col-sm-4 @else col-sm-6 @endif">
							<div class="form-group">
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fa fa-barcode"></i>
									</span>
									{!! Form::text('search_product', null, ['class' => 'form-control mousetrap', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'autofocus']); !!}
									<span class="input-group-btn">
										<button type="button" class="btn btn-default bg-white btn-flat pos_add_quick_product" data-href="{{action('ProductController@quickAdd')}}" data-container=".quick_add_product_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
									</span>
								</div>
							</div>
						</div>

						<!-- Call restaurant module if defined -->
				        @if(in_array('tables' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
				        	<span id="restaurant_module_span" 
				        		data-transaction_id="{{$transaction->id}}">
				          		<div class="col-md-3"></div>
				        	</span>
				        @endif
				     </div>
					<div class="row col-sm-12 pos_product_div">

						<input type="hidden" name="sell_price_tax" id="sell_price_tax" value="{{$business_details->sell_price_tax}}">

						<!-- Keeps count of product rows -->
						<input type="hidden" id="product_row_count" 
							value="{{count($sell_details)}}">
						@php
							$hide_tax = '';
							if( session()->get('business.enable_inline_tax') == 0){
								$hide_tax = 'hide';
							}
						@endphp
						<table class="table table-condensed table-bordered table-striped table-responsive" id="pos_table">
							<thead>
								<tr>
									<th class="text-center @if(!empty($pos_settings['inline_service_staff'])) col-md-3 @else col-md-4 @endif">	
										@lang('sale.product')
									</th>
									<th class="text-center col-md-3">
										Discount
									</th>
									<th class="text-center col-md-3">
										@lang('sale.qty')
									</th>
									@if(!empty($pos_settings['inline_service_staff']))
										<th class="text-center col-md-2">
											@lang('restaurant.service_staff')
										</th>
									@endif
									<th class="text-center col-md-2 {{$hide_tax}}">
										@lang('sale.price_inc_tax')
									</th>
									<th class="text-center col-md-3">
										Original Price
									</th>
									<th class="text-center col-md-3">
										Up
									</th>
									<th class="text-center col-md-3">
										@lang('sale.subtotal')
									</th>
									<th class="text-center"><i class="fa fa-close" aria-hidden="true"></i></th>
								</tr>
							</thead>
							<tbody>
								@foreach($sell_details as $sell_line)
									@include('sale_pos.product_row', ['product' => $sell_line, 'row_count' => $loop->index, 'tax_dropdown' => $taxes, 'sub_units' => !empty($sell_line->unit_details) ? $sell_line->unit_details : []  ])
								@endforeach
							</tbody>
						</table>
					</div>
					@include('sale_pos.partials.pos_details', ['edit' => true])

					@include('sale_pos.partials.payment_modal')

					@if(empty($pos_settings['disable_suspend']))
						@include('sale_pos.partials.suspend_note_modal')
					@endif

					@if(empty($pos_settings['disable_recurring_invoice']))
						@include('sale_pos.partials.recurring_invoice_modal')
					@endif
				</div>
				<!-- /.box-body -->
				{!! Form::close() !!}
			</div>
			<!-- /.box -->
		</div>

		<div class="col-md-5 col-sm-12">
			@include('sale_pos.partials.right_div')
		</div>
	</div>
</section>

<!-- This will be printed -->
<section class="invoice print_section" id="receipt_section">
</section>
<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	@include('contact.create', ['quick_add' => true])
</div>
<!-- /.content -->
<div class="modal fade register_details_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade close_register_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

<div class="modal fade in" tabindex="-1" role="dialog" id="unknownDiscountModal" >
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button"  id="closeThis" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
				<h4 class="modal-title">Unknown Discount</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-6">
				        <div class="form-group">
				            <label for="discount_type_modal">Discount Amount:*</label>
				            <div class="input-group">
				                <span class="input-group-addon">
				                    <i class="fa fa-info"></i>
				                </span>
				                <input type="text" class="form-control" placeholder="0.00" id="unknownDiscountAmount">
				            </div>
				        </div>
				    </div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" onclick="updateUnknown();">Update</button>
			    <button type="button" class="btn btn-default" data-dismiss="modal">Ok</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div>
@stop
@section('javascript')
<script type="text/javascript">

	function ResetFields(index)
	{
		$("#amount_"+index).removeAttr("max");
		$("#amount_"+index).removeAttr("min");
		$("#amount_"+index).removeAttr("readonly");
		$("#amount_"+index).val(0);
	}
 
	function applyGiftCard(input,rowIndex)
    {
    	ResetFields(rowIndex);
    	isOk = true;
    	$('.gift_cardc').each(function() {
		    var currentElement = $(this);
		    if(currentElement.attr("id") != input.id)
		    {
		    	var value = currentElement.val(); 
		    	if(input.value == value)
		    	{
		    		alert("you Already use This Please use another One");
		    		isOk = false;
		    		return(false);
		    	} 
		    }
		});
		if(!isOk)
		{
			return(false);
		}
    	name = input.value;
    	PATCH = "PATCH";
    	$.ajax({
           type:'GET',
           url:'/sells/pos/verifyGiftCard/'+name, 
           success:function(data){
              if(data.success)
              { 
              	var obj = data.msg;
              	if(obj['isActive'] == "active")
              	{ 
              		if(obj['current_date'] <= obj['expiry_date'] )
              		{
              			$("#amount_"+rowIndex).attr("readonly",true);
              			$("#amount_"+rowIndex).val(obj['value']).change();
              			// $("#amount_"+rowIndex).attr("readonly",true);
			            $("#note_"+rowIndex).val('Availed Gift Card : ' + obj['name'] + ' Value: '+obj['value']).change();
              		}else
              		{
              			alert("Sorry This Gift Card is Expired or Consumed \n Expiry Date : "+obj['expiry_date']);
              			$("#amount_"+rowIndex).val(0).change();
		              	$("#note_"+rowIndex).val('');
				    	ResetFields(rowIndex);
              		} 
              	}else
              	{
              		alert("Sorry This Gift Card Status is "+obj['isActive'])
              		$("#amount_"+rowIndex).val(0).change();
	              	$("#note_"+rowIndex).val('');
			    	ResetFields(rowIndex);
              	}
              }else
              {
              	alert(" "+data.msg);
              	$("#amount_"+rowIndex).val(0).change();
              	$("#note_"+rowIndex).val('');
		    	ResetFields(rowIndex);
              }
           }
        });
        
    }
    function applyCoupon(input,rowIndex)
    {
    	ResetFields(rowIndex);
    	isOk = true;
    	$('.couponc').each(function() {
		    var currentElement = $(this);
		    if(currentElement.attr("id") != input.id)
		    {
		    	var value = currentElement.val(); 
		    	if(input.value == value)
		    	{
		    		alert("you Already use This Please use another One");
		    		isOk = false;
		    		return(false);
		    	} 
		    }
		});
		if(!isOk)
		{
			return(false);
		}
    	name = input.value;
    	PATCH = "PATCH";
    	$.ajax({
           type:'GET',
           url:'/sells/pos/verifyCoupon/'+name, 
           success:function(data){
              if(data.success)
              { 
              	var obj = data.msg;
              	if(obj['isActive'] == "active")
              	{ 
              		$("#amount_"+rowIndex).val(obj['value']).change();
		            $("#note_"+rowIndex).val('Availed Coupon : ' + obj['name'] + ' Value: '+obj['value']).change(); 
              	}else
              	{
              		alert("Sorry This Gift Card Status is "+obj['isActive'])
              		$("#amount_"+rowIndex).val(0).change();
	              	$("#note_"+rowIndex).val('');
			    	ResetFields(rowIndex);
              	}
              }else
              {
              	alert(" "+data.msg);
              	$("#amount_"+rowIndex).val(0).change();
              	$("#note_"+rowIndex).val('');
		    	ResetFields(rowIndex);
              }
           }
        });
    }

     function checkThiss(obj)
    {
       if(obj.value != '1')
       {
        $.ajax({
           type:'GET',
           url:'/sells/pos/getCustDiscount/'+obj.value, 
           success:function(data){
              if(data.success)
              { 
              	var objData = data.msg;
              	 //Update values
              	// $('input#discount_amount_modal').val(objData['discount']);
              	$('input#cust_points').val(objData['bonus_points']);
              	$('input#cust_discount').val(objData['discount']);
              	$('input#cust_expiry').val(objData['bp_expiry']);

		        // $('input#discount_type').val($('select#discount_type_modal').val());
		        // __write_number($('input#discount_amount'), __read_number($('input#discount_amount_modal')));
		        // pos_total_row();
              }else
              {
              	alert(" "+data.msg); 
              }
           }
        });
       }else
       {
	       	$('input#cust_points').val(0);
          	$('input#cust_discount').val(0);
          	$('input#cust_expiry').val(0);
       }
    }
    function updateDiscount(Value)
    {
    	// $('input#discount_type_modal').val('percentage');
    	// $('input#discount_amount_modal').val(Value);
        // $('#posEditDiscountModalUpdate').click();
    }
    function openGiftCard(){
    	$('#modal_payment').modal('show');
    	//method_0
    	$('#method_0').val("gift_card").change();
    	$('#gift_card_0').focus();

    }
	function openBonusPoint(){
    	$('#modal_payment').modal('show');
    	//method_0
    	$('#method_0').val("bonus_points").change();
    	$('#bonus_points_0').focus();

    }
    function changePayment(obj,rowIndex)
    {
    	$("#amount_"+rowIndex).val(obj.value).change();
    	$("#amount_"+rowIndex).removeAttr("max");
    }
    function applyUnknown(obj,rowIndex)
    {
	  	$('input#discount_amount_modal').val(obj.value);
	  	$("#discount_type_modal").val("fixed");

	  	$("#posEditDiscountModalUpdate").click();
 
    }
    function applyBonusPoint(rowIndex)
    {
    	var ed  = $('input#cust_expiry').val(); 
    	var today = new Date();
		var dd = String(today.getDate()).padStart(2, '0');
		var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
		var yyyy = today.getFullYear();

		var td = yyyy + '-' + mm + '-' + dd;
    	if(ed == '0' || ed == '' )
    	{
    		alert("your Bonus Points is Expired ");return(false);
    	} 
    	if(td >= ed )
    	{
    		alert("This user Bonus Points is Expired ");return(false);
    	}
    	var points  = $('input#cust_points').val(); 
    	$("#bonus_points_"+rowIndex).val(points);
    	$("#amount_"+rowIndex).val(points).change();

    	$("#bonus_points_"+rowIndex).attr("max",points)
    	$("#amount_"+rowIndex).attr("max",points)

    }
    function giveDiscount(discount,rowIndex,isButton=true)
    {
    	

    	var currentDiscount = parseInt($("#row_discount_amount_"+rowIndex).val());
    	var currentTotal = parseInt($("#pos_line_total_"+rowIndex).val());
    	if(discount == 0)
    	{

    	}

    	currentDiscount += parseInt(discount);
    	if(discount == 0)
    	{
    		currentDiscount = 0;
    	}
    	if(currentDiscount > 100)
    	{
    		alert("You cannot Give More Discount");
    		return(false);
    	}
    	$("#row_discount_type_"+rowIndex).val('percentage');
    	$("#val_un_discount_"+rowIndex).html(currentDiscount);
    	$("#row_discount_amount_"+rowIndex).val(currentDiscount);
    	$("#row_discount_amount_"+rowIndex).trigger("change");
    }
    function unKnownDiscountTotal(rowIndex)
    { 
    	var currentDiscount = parseInt($("#un_discount_"+rowIndex).val());
    	var currentTotal = parseInt($("#pos_line_total_"+rowIndex).val());
    	fTotal = currentTotal - currentDiscount;
    	$("#pos_line_total_"+rowIndex).val(fTotal);
    	$("#pos_line_total_text_"+rowIndex).text(__currency_trans_from_en(fTotal, true));
    }
    var isUnknownUse = false;

    function openDiscount(){
    	if(!isUnknownUse)
    	{
    		$("#discount_type_modal").val('percentage');
    	}else
    	{
    		$("#discount_type_modal").val('fixed');
    	}
		// $("#discount_type_modal option").filter(function() {
		//     return this.value == "percentage"; 
		// }).attr('selected', true);
    	$('#posEditDiscountModal').modal('show'); 
    } 
    function openUnkown(){
    	$('#unknownDiscountModal').modal('show'); 
    } 
    function updateUnknown()
    {
    	isUnknownUse = true;
    	var discount = $("#unknownDiscountAmount").val(); 
		$("#discount_type_modal option").filter(function() {
		    return this.text == "Unknown"; 
		}).attr('selected', true);
    	$('input#discount_amount_modal').val(discount);
        $('#posEditDiscountModalUpdate').click();
        $('#closeThis').click();
    }

    function openReturnWindow() {
    	link = "<?=url('pos');?>";
	   window.open(link, "_blank", "toolbar=yes,scrollbars=yes,resizable=yes,top=500,left=200,width=1000,height=800");
	}
     
	$( "#quick_add_product_modal" ).on('shown.bs.modal', function (e) {
	    $("#CustomPrice").focus();
	    $("#value").focus();
	});
</script>
	<script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
	@include('sale_pos.partials.keyboard_shortcuts')

	<!-- Call restaurant module if defined -->
    @if(in_array('tables' ,$enabled_modules) || in_array('modifiers' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
    	<script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
@endsection

@section('css')
	<style type="text/css">
		/*CSS to print receipts*/
		.print_section{
		    display: none;
		}
		@media print{
		    .print_section{
		        display: block !important;
		    }
		}
		@page {
		    size: 3.1in auto;/* width height */
		    height: auto !important;
		    margin-top: 0mm;
		    margin-bottom: 0mm;
		}
	</style>
@endsection
