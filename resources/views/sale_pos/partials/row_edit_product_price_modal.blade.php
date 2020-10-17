<div class="modal-dialog" role="document">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel">{{$product->product_name}} - {{$product->sub_sku}}</h4>
		</div>
		<div class="modal-body">
			<div class="row">
				<div class="form-group col-xs-6 @if(!auth()->user()->can('edit_product_price_from_sale_screen')) hide @endif">
					<label>@lang('sale.unit_price')</label>
						<input type="text" id="unit_price_{{$row_count}}" name="products[{{$row_count}}][unit_price]" class="form-control pos_unit_price input_number mousetrap" value="{{!empty($product->unit_price_before_discount) ? $product->unit_price_before_discount : $product->default_sell_price}}">
				</div>
				@php
					$discount_type = !empty($product->line_discount_type) ? $product->line_discount_type : 'fixed';
					$discount_amount = !empty($product->line_discount_amount) ? $product->line_discount_amount : 0;
					
					if(!empty($discount)) {
						$discount_type = $discount->discount_type;
						$discount_amount = $discount->discount_amount;
					}
				@endphp

				@if(!empty($discount))
					{!! Form::hidden("products[$row_count][discount_id]", $discount->id); !!}
				@endif
				<div class="form-group col-xs-6 hide col-sm-6 @if(!auth()->user()->can('edit_product_discount_from_sale_screen')) hide @endif">
					<label>@lang('sale.discount_type')</label>
						{!! Form::select("products[$row_count][line_discount_type]", ['percentage' => __('lang_v1.percentage')], $discount_type , ['class' => 'form-control row_discount_type',"id" => "row_discount_type_$row_count"]); !!}
						<input type="hidden" class="rowTc" value="{{$row_count}}" >
					@if(!empty($discount))
						<p class="help-block">{!! __('lang_v1.applied_discount_text', ['discount_name' => $discount->name, 'starts_at' => $discount->formated_starts_at, 'ends_at' => $discount->formated_ends_at]) !!}</p>
					@endif
				</div>
				<div class="form-group col-xs-6 col-sm-6 @if(!auth()->user()->can('edit_product_discount_from_sale_screen')) hide @endif">
					<label>@lang('sale.discount_amount')</label>
						{!! Form::text("products[$row_count][line_discount_amount]", $discount_amount, ["id"=>"row_discount_amount_$row_count",'class' => 'form-control input_number row_discount_amount']); !!}
				</div>
				<div class="form-group col-xs-6 {{$hide_tax}}">
					<label>@lang('sale.tax')</label>

					{!! Form::hidden("products[$row_count][item_tax]", @num_format($item_tax), ['class' => 'item_tax']); !!}
		
					{!! Form::select("products[$row_count][tax_id]", $tax_dropdown['tax_rates'], $tax_id, ['placeholder' => 'Select', 'class' => 'form-control tax_id'], $tax_dropdown['attributes']); !!}
				</div>
				<div class="col-md-12">
		      		<button type="button" class="btn btn-lg btn-danger" onclick="giveDiscount(0,<?=$row_count?>);"><i class="fa fa-check-circle-o" ></i> NO DISCOUNT : 0 </button>
		      		<button type="button" class="btn btn-lg btn-info" onclick="giveDiscount(10,<?=$row_count?>);"><i class="fa fa-check-circle-o" ></i> DISCOUNT : 10 %</button>
		      		<button type="button" class="btn btn-lg btn-warning" onclick="giveDiscount(20,<?=$row_count?>);"><i class="fa fa-check-circle-o" ></i> DISCOUNT : 20 %</button>
		      		<button type="button" class="btn btn-lg btn-primary" onclick="giveDiscount(30,<?=$row_count?>);"><i class="fa fa-check-circle-o" ></i> DISCOUNT : 30 %</button>
		      		<button type="button" class="btn btn-lg btn-success" onclick="giveDiscount(40,<?=$row_count?>);"><i class="fa fa-check-circle-o" ></i> DISCOUNT : 40 %</button>
		      		<button type="button" class="btn btn-lg btn-success" onclick="giveDiscount(50,<?=$row_count?>);"><i class="fa fa-check-circle-o" ></i> DISCOUNT : 50 %</button>
		      	</div>
			</div>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
		</div>
	</div>
</div>