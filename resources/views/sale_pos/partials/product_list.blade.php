@forelse($products as $product)
{{-- @dd($product->color()->first()) --}}
{{-- @dd($product->pluck('name'))
 --}}
{{-- @dd($products[20]->name, $product->sub_size()->first()) --}}
{{-- {{ dd($product) }} --}}
<div class="col-md-3 col-xs-4 product_list no-print">
	<div class="product_box bg-gray" data-toggle="tooltip" data-placement="bottom"
		data-variation_id="{{$product->variation_id}}"
		title=" @if($product->name){{$product->name}} @if($product->type == 'variable')- {{$product->variation}} @endif @endif {{ '(' . $product->sub_sku . ')'}}">
		<div class="image-container">
			<img src="{{$product->image_url}}" alt="" class="img-fluid img-thumbnail"
				style="width:100%;height: 75px">
		</div>
		<div class="text text-uppercase">
			<small>
				{{-- @php
				$size = App\Size::find($product->sub_size_id);
				$size_name =" ";
				if ($size) {
				$size_name = $size->name;
				}
				@endphp --}}
				{{-- {{$size_name}} - --}}
				{{$product->sub_size()->first()->name}} -
				<strong class="text-info">{{$product->name}} </strong>
				@if($product->type == 'variable')
				- {{$product->variation}}
				@endif -
				
				{{-- {{$product->selling_price}}  --}}
				{{@num_format($product->sell_price) }}
				
				<i class="fa fa-euro"></i>
			</small>
		</div>
		<small class="text-success">
			({{$product->sku}})
		</small>
	</div>
</div>
@empty
<input type="hidden" id="no_products_found">
<div class="col-md-12">
	<h4 class="text-center">
		@lang('lang_v1.no_products_to_display')
	</h4>
</div>
@endforelse