@php
$location_id = App\BusinessLocation::where('name','Web Shop')->orWhere('name','webshop')->orWhere('name','web
shop')->orWhere('name','Website')->orWhere('name','website')->orWhere('name','MACAO WEBSHOP')->pluck('id');

$all_web_products = App\WebsiteProducts::join('products as p','p.id','=','website_products.product_id')->orderBy('p.created_at','Desc')->get();
// $all_web_products = App\VariationLocationDetails::where('qty_available', '>',
// 0)->join('products as
//
// p',p.id','=','variation_location_details.product_id')->groupBy('p.refference')->orderBy('p.created_at','Desc')->get();
// dd($all_web_products);
@endphp
<div class="row">
     <div class="col-12">
          <a href="{{url('product/list')}}" class=" float-right">
               View All
               <i class="fa fa-angle-double-right"></i>
          </a>
     </div>
</div>
<div class="owl-carousel owl-theme new-products">
     @if ($all_web_products->count())
          @foreach ($all_web_products as $item)
               @if ($loop->iteration <= 20) 
                    <div class="product-default inner-quickview inner-icon">
                         <figure>
                              <a href="{{url('product/'.encrypt($item->products()->first()->id).'/detail')}}">
                                   @php
                                   $images = App\ProductImages::where('refference',$item->products()->first()->refference)->get();
                                   @endphp
                                   @if (!is_null($images) && $images->count() > 0)
                                   <img src="{{asset('uploads/'.$images[0]->image)}}" style="height:300px;width:300px"
                                        class="img-thumbnail">
                                   @if (isset($images[1]) && $images[1])
                                   <img src="{{asset('uploads/'.$images[1]->image)}}" style="height:300px;width:300px"
                                        class="img-thumbnail">
                                   @endif
                                   @else
                                   <img src="{{asset('img/product-placeholder-1.jpg')}}" id="preview1" alt="Image 1 Preview Here"
                                        style="height:300px;width:300px" class="img-thumbnail">
                                   <img src="{{asset('img/product-placeholder-2.jpg')}}" id="preview1" alt="Image 1 Preview Here"
                                        style="height:300px;width:300px" class="img-thumbnail">
                                   @endif
                              </a>
                              <a href="{{url('product/'.encrypt($item->products()->first()->id).'/detail')}}" class="btn-quickview"
                                   title="Quick View">View Details</a>
                         </figure>
                         <div class="product-details">
                              <div class="category-wrap">
                                   <div class="category-list">
                                        <a href="category.html"
                                             class="product-category">{{$item->products()->first()->category()->first()['name']}}</a>
                                   </div>
                                   {{-- <a href="#" class="btn-icon-wish"><i class="icon-heart"></i></a> --}}
                              </div>
                              <h2>
                                   <a href="{{url('product/'.encrypt($item->products()->first()->id).'/detail')}}">
                                        {{$item->products()->first()->name}}
                                   </a>
                              </h2>
                              <span>
                                   Product Code:
                                   {{
                                        $item->products()->first()->refference
                                   }}
                              </span>
                              <div class="price-box">
                                   <span class="product-price">
                                        <i class="fa fa-euro-sign"></i>
                                        {{
                                             $ut->num_f($item->products()->first()->variations()->first()['sell_price_inc_tax'])
                                        }}
                                   </span>
                              </div><!-- End .price-box -->
                         </div><!-- End .product-details -->
                    </div>
               @endif
          @endforeach
     @else
</div>
<div class="mt-5 mb-5">
     <div class="col-12">
          <div class="alert alert-info col-12">
               <h3>
                    No Product Found
               </h3>
               <p>
                    0 product found.
                    Please<strong><a href="{{url('product/list')}}">click here</a></strong>
                    to view all products
               </p>
          </div>
     </div>
</div>
@endif
{{-- </div> --}}