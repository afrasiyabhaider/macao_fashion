@php
    $location_id = \App\BusinessLocation::where('name','Web Shop')->orWhere('name','webshop')->orWhere('name','web shop')->orWhere('name','Website')->orWhere('name','website')->orWhere('name','MACAO WEBSHOP')->first()->id;
    
//     $products = App\VariationLocationDetails::where('qty_available','>',0)->join('products as p','p.id','=','variation_location_details.product_id')->groupBy('p.refference')->orderBy('p.created_at','Desc')->paginate(12);
// dd("Hello");
     $products = App\WebsiteProducts::join('products as p','p.id','=','website_products.product_id')->orderBy('p.created_at','Desc')->paginate(12);

@endphp
<div class="page-header align-items-end" style="background-image: url(../../site_assets/images/page-header-bg-2.jpg)">
     <div class="container">
     {{-- <img src="site_assets/images/page-header-img.png" alt="image"> --}}
     </div>
     <!-- End .container -->
</div>
<div class="container products-body">
     <div class="row">
          <div class="col-lg-9 main-content">
               <div class="row">
                    <div class="col-12">
                         <hr>
                              <h2 class="text-center">
                                   <i class="fa fa-shopping-bag"></i>
                                   All Products
                              </h2>
                         <hr>
                         <p>
                              {{$products->count() * $products->currentPage()}} of {{$products->total()}} Product(s)
                         </p>
                    </div>
               </div>
               @include('site.listings.partials.filters')
               <div class="row row-sm">
                    @foreach ($products as $item)
                         <div class="col-6 col-sm-3">
                              <div class="product-default inner-quickview inner-icon" style="padding: 30px">
                                   <figure>
                                   <a href="{{url('product/'.encrypt($item->products()->first()->id).'/detail')}}">
                                             @php
                                             $images = App\ProductImages::where('refference',$item->products()->first()->refference)->get();
                                             @endphp
                                             @if (!is_null($images) && $images->count() > 0)
                                                  <img src="{{asset('uploads/'.$images[0]->image)}}" style="height:300px;width:250px;padding:30px" class="img-thumbnail">
                                                  @if(isset($images[1]) && $images[1])
                                                       <img src="{{asset('uploads/'.$images[1]->image)}}" style="height:300px;width:250px;padding:30px" class="img-thumbnail">
                                                  @endif
                                             @else
                                                  <img src="{{asset('img/product-placeholder-1.jpg')}}" id="preview1" alt="Image 1 Preview Here" style="height:300px;width:300px;padding:30px" class="img-thumbnail">
                                                  <img src="{{asset('img/product-placeholder-2.jpg')}}" id="preview1" alt="Image 1 Preview Here" style="height:300px;width:300px;padding:30px" class="img-thumbnail">
                                             @endif
                                        </a>
                                        {{-- <div class="btn-icon-group">
                                             <button class="btn-icon btn-add-cart" data-toggle="modal" data-target="#addCartModal"><i class="icon-bag"></i></button>
                                             <a href="#" class="btn-icon btn-icon-wish"><i class="icon-heart"></i></a>
                                        </div> --}}
                                        <a href="{{url('product/'.encrypt($item->products()->first()->id).'/detail')}}" class="btn-quickview" title="Quick View">View Details</a> 
                                   </figure>
                                   <div class="product-details">
                                        <div class="category-wrap">
                                             <div class="category-list">
                                                  <a href="category.html" class="product-category">{{$item->products()->first()->category()->first()['name']}}</a>
                                             </div>
                                        </div>
                                        <h2>
                                             <a href="{{url('product/'.encrypt($item->products()->first()->id).'/detail')}}">{{$item->products()->first()->name}}</a>
                                        </h2>
                                        <span>
                                             Sub Category:
                                             {{
                                                  $item->products->sub_category()->first()->name
                                             }}
                                        </span>
                                        <div class="price-box">
                                             <span class="product-price">
                                                  <i class="fa fa-euro-sign"></i>
                                                  {{
                                                  $item->products()->first()->variations()->first()['sell_price_inc_tax']
                                                  }}
                                             </span>
                                        </div><!-- End .price-box -->
                                   </div>
                                   <!-- End .product-details -->
                              </div>
                         </div>
                    @endforeach
                    <!-- End .col-md-4 -->
               </div>
               <!-- End .row -->

               <nav class="toolbox toolbox-pagination">
                    <div class="pagination">
                         {{
                              $products->links()
                         }}
                    </div>
               </nav>
          </div>
          <!-- End .col-lg-9 -->
          @include('site.listings.partials.mobile_filters')
          <!-- End .col-lg-3 -->
     </div>
</div>