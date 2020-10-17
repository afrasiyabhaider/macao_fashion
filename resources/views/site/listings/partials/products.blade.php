@php
    $location_id = \App\BusinessLocation::where('name','Web Shop')->orWhere('name','webshop')->orWhere('name','web shop')->orWhere('name','Website')->orWhere('name','website')->orWhere('name','MACAO WEBSHOP')->first()->id;
    
    $products = App\VariationLocationDetails::where('location_id','=',$location_id)->where('qty_available','>',0)->join('products as p','p.id','=','variation_location_details.product_id')->groupBy('p.refference')->orderBy('p.created_at','Desc')->paginate(12);

//     dd($products);
@endphp
<div class="page-header align-items-end" style="background-image: url(../../site_assets/images/page-header-bg-2.jpg)">
     <div class="container">
     {{-- <img src="site_assets/images/page-header-img.png" alt="image"> --}}
     </div>
     <!-- End .container -->
</div>
<div class="container products-body">
     {{-- <div class="row">
          <div class="col-lg-9 main-content"> --}}
               <div class="row">
                    <div class="col-12">
                         <hr>
                              <h2 class="text-center">
                                   <i class="fa fa-shopping-bag"></i>
                                   All Products
                              </h2>
                         <hr>
                         <p>
                              {{$products->count()}} Product(s) Found
                         </p>
                    </div>
               </div>
               <div class="row row-sm">
                    @foreach ($products as $item)
                         <div class="col-6 col-sm-3">
                              <div class="product-default inner-quickview inner-icon">
                                   <figure>
                                        <a href="{{url('product/'.encrypt($item->products()->first()->id).'/detail')}}">
                                                  @php
                                                  $images = App\ProductImages::where('refference',$item->products()->first()->refference)->get();
                                                  @endphp
                                                  @if (!is_null($images) && $images->count() > 0)
                                                  <img src="{{asset('uploads/img/'.$images[0]->image)}}" style="height:300px;width:300px" class="img-thumbnail">
                                                  @if (isset($images[1]) && $images[1])
                                                       <img src="{{asset('uploads/img/'.$images[1]->image)}}" style="height:300px;width:300px" class="img-thumbnail">
                                                  @endif
                                             @else
                                                  <img src="{{asset('img/product-placeholder-1.jpg')}}" id="preview1" alt="Image 1 Preview Here" style="height:300px;width:300px" class="img-thumbnail">
                                                  <img src="{{asset('img/product-placeholder-2.jpg')}}" id="preview1" alt="Image 1 Preview Here" style="height:300px;width:300px" class="img-thumbnail">
                                             @endif
                                        </a>
                                        {{-- <div class="label-group">
                                             <div class="product-label label-cut">-20%</div>
                                        </div> --}}
                                        <div class="btn-icon-group">
                                             <button class="btn-icon btn-add-cart" data-toggle="modal" data-target="#addCartModal"><i class="icon-bag"></i></button>
                                        </div>
                                        <a href="{{url('product/'.encrypt($item->products()->first()->id).'/detail')}}" class="btn-quickview" title="Quick View">View Details</a> 
                                   </figure>
                                   <div class="product-details">
                                        <div class="category-wrap">
                                             <div class="category-list">
                                                  <a href="category.html" class="product-category">{{$item->products()->first()->category()->first()['name']}}</a>
                                             </div>
                                             <a href="#" class="btn-icon-wish"><i class="icon-heart"></i></a>
                                        </div>
                                        {{-- <h2 class="product-title">
                                             <a href="{{url('product/'.encrypt($item->products()->first()->id).'/detail')}}">{{$item->products()->first()->name}}</a>
                                        </h2> --}}
                                        <h2>
                                             <a href="{{url('product/'.encrypt($item->products()->first()->id).'/detail')}}">{{$item->products()->first()->name}}</a>
                                        </h2>
                                        <span>
                                             Product Code:
                                             {{
                                                  $item->products()->first()->refference
                                             }}
                                        </span>
                                        {{-- <div class="ratings-container">
                                             <div class="product-ratings">
                                                  <span class="ratings" style="width:100%"></span><!-- End .ratings -->
                                                  <span class="tooltiptext tooltip-top"></span>
                                             </div><!-- End .product-ratings -->
                                        </div><!-- End .product-container --> --}}
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
                    {{-- <div class="toolbox-item toolbox-show">
                         <label>Show:</label>

                         <div class="select-custom">
                              <select name="count" class="form-control">
                              <option value="9">9 Products</option>
                              <option value="18">18 Products</option>
                              <option value="27">27 Products</option>
                              </select>
                         </div>
                         <!-- End .select-custom -->
                    </div> --}}
                    <!-- End .toolbox-item -->
                    <div class="pagination">
                         {{
                              $products->links()
                         }}
                    </div>
                    {{-- <ul class="pagination">
                         <li class="page-item disabled">
                              <a class="page-link page-link-btn" href="#"><i class="icon-angle-left"></i></a>
                         </li>
                         <li class="page-item active">
                              <a class="page-link" href="#">1 <span class="sr-only">(current)</span></a>
                         </li>
                         <li class="page-item"><a class="page-link" href="#">2</a></li>
                         <li class="page-item"><a class="page-link" href="#">3</a></li>
                         <li class="page-item"><a class="page-link" href="#">4</a></li>
                         <li class="page-item"><a class="page-link" href="#">5</a></li>
                         <li class="page-item"><span class="page-link">...</span></li>
                         <li class="page-item">
                              <a class="page-link page-link-btn" href="#"><i class="icon-angle-right"></i></a>
                         </li>
                    </ul> --}}
               </nav>
          {{-- </div> --}}
          <!-- End .col-lg-9 -->

          {{-- @include('site.listings.partials.filters') --}}
          <!-- End .col-lg-3 -->
     {{-- </div> --}}
</div>