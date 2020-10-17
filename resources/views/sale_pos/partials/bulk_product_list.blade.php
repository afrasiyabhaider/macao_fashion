@forelse($products as $product)
	<div class="col-md-4 col-xs-4 product_list no-print">
		<div class="product_box bg-gray" data-toggle="tooltip" data-placement="bottom" data-variation_id="{{$product->variation_id}}" title="{{$product->name}} @if($product->type == 'variable')- {{$product->variation}} @endif {{ '(' . $product->sub_sku . ')'}}">
			<div class="image-container">
				<img src="{{$product->image_url}}" alt="" class="img-fluid img-thumbnail" style="width:80%;height: 70px;">
			</div>
			<span class="text text-uppercase">
				<small>
					<strong class="text-dark">Name : {{$product->name}} </strong> <br>
					@if($product->type == 'variable')
					- {{$product->variation}}
					@endif
				</small>
               </span>
               @php
				$product_detail = \App\Product::find($product->product_id);
				// dd($product_detail->sub_size()->first());
                    // if ($product_detail->size()->first() != null) {
                    //      $size = $product_detail->size()->first(); 
                    // } else{
                         $size = $product_detail->sub_size()->first(); 
				// }	
                         $color = $product_detail->color()->first(); 
			@endphp
			<span class="text-info">Size: {{$size['name']}} </span>
			<br>
			<span class="text-info">Color: {{$color['name']}} </span>
			<br>
			<span class="text-success">
				Barcode: [{{$product->sku}}]
			</span>
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