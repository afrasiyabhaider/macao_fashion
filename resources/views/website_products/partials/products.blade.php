@forelse($products as $product)
	<div class="col-md-4 col-xs-4 product_list no-print">
		<div class="product_box bg-gray" data-toggle="tooltip" data-placement="bottom" data-variation_id="{{$product->id}}" title="{{$product->name}} {{ '(' . $product->sub_sku . ')'}}" >
			<a href="{{url('website/product/'.$product->id.'/images')}}">
			<div class="image-container">
				<img src="{{App\Product::find($product->id)->image_url}}" alt="" class="img-fluid img-thumbnail" style="width:80%;height: 70px;">
			</div>
			<span class="text text-uppercase">
				<small>
					<strong class="text-dark">{{$product->name}} </strong> 
					{{-- <br>
					@if($product->type == 'variable')
					- {{$product->variation}}
					@endif --}}
				</small>
               </span>
               @php
				$product_detail = \App\Product::find($product->id);
				// dd($product_detail->sub_size()->first());
                    // if ($product_detail->size()->first() != null) {
                    //      $size = $product_detail->size()->first(); 
                    // } else{
                         $size = $product_detail->sub_size()->first(); 
				// }	
                         $color = $product_detail->color()->first(); 
			@endphp
			{{-- <span class="text-info">Size: {{$size['name']}} </span>
			<br> --}}
			{{-- <span class="text-info">Color: {{$color['name']}} </span>
			<br>
			<span class="text-success">
				Barcode: [{{$product->sku}}]
			</span> --}}
			Images: 
			{{
				$product->images()->count()
			}}
		</a>
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