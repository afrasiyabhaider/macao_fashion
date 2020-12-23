@php
$ut = new \App\Utils\ProductUtil();
@endphp
@extends('site.layout.app')
@section('title')
Product Detail
@endsection
@section('content')
<div>
     <div class="container pt-sm-5">
          <div class="row pt-sm-5">
               <div class="col-lg-9">
                    <div class="product-single-container product-single-default">
                         <div class="row">
                              <div class="col-lg-7 col-md-6 product-single-gallery">
                                   <div class="product-slider-container product-item">
                                        <div class="product-single-carousel owl-carousel owl-theme">
                                             @if ($images->count() > 0)
                                             @foreach ($images as $item)
                                             <div class="product-item ">
                                                  <img class="product-single-image"
                                                       src="{{asset('uploads/'.$item->image)}}"
                                                       data-zoom-image="{{asset('uploads/'.$item->image)}}"
                                                       style="width:200px;height:300px">
                                             </div>
                                             @endforeach
                                             @else
                                             <div class="product-item ">
                                                  <img class="product-single-image"
                                                       src="{{asset('img/product-placeholder-1.jpg')}}"
                                                       data-zoom-image="{{asset('img/product-placeholder-1.jpg')}}"
                                                       style="width:400px;height:400px">
                                             </div>
                                             <div class="product-item ">
                                                  <img class="product-single-image"
                                                       src="{{asset('img/product-placeholder-2.jpg')}}"
                                                       data-zoom-image="{{asset('img/product-placeholder-2.jpg')}}"
                                                       style="width:400px;height:400px">
                                             </div>
                                             <div class="product-item ">
                                                  <img class="product-single-image"
                                                       src="{{asset('img/product-placeholder-3.jpg')}}"
                                                       data-zoom-image="{{asset('img/product-placeholder-3.jpg')}}"
                                                       style="width:400px;height:400px">
                                             </div>
                                             @endif
                                        </div>
                                        <!-- End .product-single-carousel -->
                                        <span class="prod-full-screen">
                                             <i class="fa fa-expand fa-2x icon-plus">

                                             </i>
                                        </span>
                                   </div>
                                   <div class="prod-thumbnail row owl-dots" id='carousel-custom-dots'>
                                        @if ($images->count() > 0)
                                        @foreach ($images as $item)
                                        <div class="col-3 owl-dot">
                                             <img src="{{asset('uploads/'.$item->image)}}"
                                                  style="width:85px;height:85px">
                                        </div>
                                        @endforeach
                                        @else
                                        <div class="col-3 owl-dot">
                                             <img src="{{asset('img/product-placeholder-1.jpg')}}"
                                                  style="width:85px;height:85px">
                                        </div>
                                        <div class="col-3 owl-dot">
                                             <img src="{{asset('img/product-placeholder-2.jpg')}}"
                                                  style="width:85px;height:85px">
                                        </div>
                                        <div class="col-3 owl-dot">
                                             <img src="{{asset('img/product-placeholder-3.jpg')}}"
                                                  style="width:85px;height:85px">
                                        </div>
                                        @endif
                                   </div>
                              </div><!-- End .col-lg-7 -->
                              {{-- Add to cart form --}}
                              <div class="col-lg-5 col-md-6">
                                   <form action="{{action('website\CartController@addToCart')}}" method="post">
                                        @csrf
                                        <div class="product-single-details">
                                             <h1 class="product-title">
                                                  {{
                                                  $product->name
                                             }}
                                             </h1>

                                             {{-- <div class="ratings-container">
                                             <div class="product-ratings">
                                                  <span class="ratings" style="width:60%"></span><!-- End .ratings -->
                                             </div><!-- End .product-ratings -->

                                             <a href="#" class="rating-link">( 6 Reviews )</a>
                                             </div><!-- End .product-container --> --}}

                                             <div class="price-box">
                                                  @if (!is_null($special_category) && $special_category->sale == "1")
                                                  <span class="old-price">
                                                       <i class="fa fa-euro-sign"></i>
                                                       {{
                                                            $ut->num_f($product->variations()->first()['sell_price_inc_tax'])
                                                       }}
                                                  </span>
                                                  <span class="product-price">
                                                       <i class="fa fa-euro-sign"></i>
                                                       {{
                                                            $ut->num_f($special_category->after_discount)
                                                       }}
                                                  </span>
                                                  @else
                                                  <span class="product-price">
                                                       <i class="fa fa-euro-sign"></i>
                                                       {{
                                                            $ut->num_f($product->variations()->first()['sell_price_inc_tax'])
                                                       }}
                                                  </span>
                                                  @endif
                                                  {{-- <p>
                                                       Product Code:
                                                       {{
                                                       $product->refference
                                                  }}
                                                  </p> --}}
                                             </div><!-- End .price-box -->

                                             <input type="hidden" name="refference" value="{{$product->refference}}"
                                                  id="refference">
                                             <input type="hidden" name="product_id" value="{{$product->id}}"
                                                  id="product_id">

                                             <div class="product-filters-container">
                                                  <div class="">
                                                       <label>Colors:</label>
                                                       @if ($colors->count())
                                                       @foreach ($colors as $item)
                                                       <div class="row ml-5 mt-1">
                                                            <div class="col-12">
                                                                 {{-- <div style="width:50px; border:1px solid black; background-color: {{$item->color_code}}"
                                                                 class="float-left">&nbsp;</div> --}}
                                                            {{-- <input type="radio" name="color" value="{{$item->id}}"
                                                            class="mt-1"> --}}
                                                            <div class="custom-control custom-radio">
                                                                 <div style="width:50px; border:1px solid black; background-color: {{$item->color_code}}"
                                                                      class="float-left">&nbsp;</div>
                                                                 <input type="radio" id="customRadio{{$item->id}}" name="color" @if (old('color') == $item->id) checked @endif value="{{$item->id}}" class="custom-control-input" checked>
                                                                 <label class="custom-control-label"
                                                                      for="customRadio{{$item->id}}">
                                                                      {{$item->name}}
                                                                 </label>
                                                            </div>
                                                            {{-- <span class="pt-1">
                                                            {{$item->name}}
                                                            </span> --}}
                                                       </div>
                                                  </div>
                                                  @endforeach
                                                  @else
                                                  <span>
                                                       No Color Available for this Product
                                                  </span>
                                                  @endif
                                                  {{-- <select name="color" class="form-control col-6 select2 change-filter">
                                                       <optgroup>
                                                            <option value="0">
                                                                 Choose Color
                                                            </option>
                                                            @foreach ($colors as $item)
                                                       <option value="{{$item->id}}">
                                                  {{$item->name}}
                                                  </option>
                                                  @endforeach
                                                  </optgroup>
                                                  </select> --}}
                                             </div><!-- End .product-single-filter -->
                                        </div>

                                        <div class="product-filters-container mt-2">
                                             <div class="product-single-filter">
                                                  <label>Sizes:</label>
                                                  <select id="size" name="size"
                                                       class="form-control col-6 select2 change-filter">
                                                       <option value="0">
                                                            Choose Size
                                                       </option>
                                                       @foreach ($sizes as $item)
                                                       <option value="{{$item->id}}" @if (old('size') == $item->id)
                                                       selected
                                                       @endif>
                                                            {{$item->name}}
                                                       </option>
                                                       @endforeach
                                                  </select>
                                             </div><!-- End .product-single-filter -->
                                        </div>
                                        <!-- End .product-filters-container -->
                                        <strong id="old_qty" class="@if ($web_product->sum('qty_available') < 3)
                                             text-danger 
                                                  @endif">
                                             In Stock:
                                             {{
                                                       $web_product->sum('qty_available')
                                                  }} Items
                                        </strong>
                                        <strong id="new_qty">

                                        </strong>
                                        <div class="product-action product-all-icons pt-5">
                                             <button type="submit" class="paction add-cart text-dark" title="Add to Cart">
                                                  <span>Add to Cart</span>
                                             </button>
                                        </div><!-- End .product-action -->

                                        {{-- @dd(Share::currentPage()->facebook()) --}}
                                        <div class="product-single-share">
                                             {{-- <label>Share:</label> --}}
                                             {{-- {!! Share::currentPage()->twitter()->facebook() !!} --}}
                                             <!-- www.addthis.com share plugin-->
                                             {{-- @dd(Share::currentPage()->twitter()->facebook()->pinterest()) --}}
                                             {{-- <p>
                                                  Here
                                             </p> --}}
                                             {{-- <div class="addthis_inline_share_toolbox">
                                                  <i class="fab fa-facebook"></i>
                                                  <p>{!!  Share::currentPage()!!}</p>
                                             </div> --}}
                                        </div><!-- End .product single-share -->
                                   </div><!-- End .product-single-details -->
                              </form>
                         </div><!-- End .col-lg-5 -->
                    </div><!-- End .row -->
               </div><!-- End .product-single-container -->

               @if (!is_null($special_category))
               <div class="product-single-tabs">
                    <ul class="nav nav-tabs" role="tablist">
                         <li class="nav-item">
                              <a class="nav-link active" id="product-tab-desc" data-toggle="tab"
                                   href="#product-desc-content" role="tab" aria-controls="product-desc-content"
                                   aria-selected="true">Description</a>
                         </li>
                    </ul>
                    <div class="tab-content">
                         <div class="tab-pane fade show active" id="product-desc-content" role="tabpanel"
                              aria-labelledby="product-tab-desc">
                              <div class="product-desc-content">
                                   {!!
                                   $special_category->description
                                   !!}
                              </div><!-- End .product-desc-content -->
                         </div><!-- End .tab-pane -->
                    </div><!-- End .tab-content -->
               </div>
               @endif
               <!-- End .product-single-tabs -->
          </div><!-- End .col-lg-9 -->

          <div class="sidebar-overlay"></div>
          <div class="sidebar-toggle"><i class="icon-sliders"></i></div>
          <aside class="sidebar-product col-lg-3 padding-left-lg mobile-sidebar">
               <div class="sidebar-wrapper">
                    <div class="widget widget-brand">
                         @php
                         $logo = App\Business::first()->logo;
                         @endphp
                         <a href="{{url('/')}}">
                              <img src="{{asset('uploads/business_logos/'.$logo)}}" alt="brand name">
                         </a>
                    </div><!-- End .widget -->

                    <div class="widget widget-info">
                         <ul>
                              <li>
                                   <i class="icon-shipping"></i>
                                   <h4>FREE<br>SHIPPING</h4>
                              </li>
                              <li>
                                   <i class="icon-us-dollar"></i>
                                   <h4>100% MONEY<br>BACK GUARANTEE</h4>
                              </li>
                              <li>
                                   <i class="icon-online-support"></i>
                                   <h4>ONLINE<br>SUPPORT 24/7</h4>
                              </li>
                         </ul>
                    </div><!-- End .widget -->

                    <div class="widget widget-featured">
                         <h3 class="widget-title">Featured Products</h3>

                         <div class="widget-body">
                              <div class="owl-carousel widget-featured-products">
                                   @if (!is_null($featured) && $featured->count() > 0)
                                   @foreach ($featured as $item)

                                   {{-- @dd(asset('uploads/'.$item->products()->first()->image)) --}}

                                   <div class="featured-col">
                                        <div class="product-default left-details product-widget">
                                             <figure>
                                                  <a
                                                       href="{{url('product/'.encrypt($item->products()->first()->id).'/detail')}}">
                                                       @php
                                                       $images =
                                                       App\ProductImages::where('refference',$item->products()->first()->refference)->get();
                                                       @endphp
                                                       @if(!is_null($images) && $images->count() > 0)
                                                       <img src="{{asset('uploads/'.$images[0]->image)}}"
                                                            style="height:80px;width:80px">
                                                       @else
                                                       <img src="{{asset('img/product-placeholder-1.jpg')}}"
                                                            id="preview1" alt="Image 1 Preview Here"
                                                            style="height:85px;width:85px">
                                                       @endif
                                                  </a>
                                             </figure>
                                             <div class="product-details">
                                                  <h2 class="product-title">
                                                       <a href="product.html">
                                                            {{$item->products()->first()->name}}
                                                       </a>
                                                  </h2>
                                                  <div class="price-box">
                                                       {{-- <span class="product-price">$49.00</span> --}}
                                                       <small>
                                                            Code:
                                                            {{
                                                                 $item->products()->first()->refference
                                                            }}
                                                       </small>
                                                       <span class="product-price">
                                                            <br>
                                                            <i class="fa fa-euro-sign"></i>
                                                            {{
                                                       $ut->num_f($item->products()->first()->variations()->first()['sell_price_inc_tax'])
                                                  }}
                                                       </span>

                                                  </div><!-- End .price-box -->
                                             </div><!-- End .product-details -->
                                        </div>
                                   </div><!-- End .featured-col -->
                                   @endforeach
                                   @endif
                              </div><!-- End .widget-featured-slider -->
                         </div><!-- End .widget-body -->
                    </div><!-- End .widget -->
                    <div class="widget widget-banner">
                         <div class="banner banner-image">
                              <a href="#">
                                   <img src="{{asset('site_assets/images/banners/banner-sidebar.jpg')}}"
                                        alt="Banner Desc">
                              </a>
                         </div><!-- End .banner -->
                    </div><!-- End .widget -->
               </div>
          </aside><!-- End .col-md-3 -->
     </div><!-- End .row -->
</div><!-- End .container -->
<div class="featured-section">
     <div class="container">
          <h2 class="carousel-title text-center">
               Products
          </h2>
          <hr>
          @include('site.partials.all_products')
          <div class="owl-carousel owl-theme new-products">
          </div><!-- End .featured-products -->
     </div><!-- End .container -->
</div><!-- End .featured-section -->
</div><!-- End .main -->
@endsection
@section('scripts')
<script>
     $(function () {
               /**
               * This function will remove previously populated Data in Options 
               * of Select.Tail through Json data
               *
               **/
               function removeOptions(select) {
                    $.each(select, function(i, d) {
                         select.config("disabled", false);
                         select.query();
                    });
               }
               var base_url = "{{url('/')}}";

              $(".change-filter").change(function () {
                   var size = $("select[name=size]").val();
                   var ref = $("#refference").val();
                    $("#old_qty").show();
                    $("#new_qty").hide();
                    if (size != 0) {
                         $.ajax({
                              url: base_url+"/product/"+ref+"/size/"+size,
                              type: "GET",
                              dataType: "json",
                              success: function (res) {
                                   // console.log(res);
                                   $("#old_qty").hide();
                                   $("#new_qty").show();
                                   if (res.qty > 2) {
                                        var span = "<span> In Stock: "+parseInt(res.qty)+" Pc(s)</span>";
                                   }else if(res.qty<=2 && res.qty != 0){
                                        var span = '<span class="text-danger"> In Stock: '+parseInt(res.qty)+' Pc(s) </span>';
                                   }else{
                                        var span = '<span class="text-danger"> Out of Stock: '+parseInt(res.qty)+' Pc(s)</span>';
                                   }
                                   $("#new_qty").html(span);

                                   var select = tail.select("select[name=color]");
                                   removeOptions(select);
                                   $.each(res.color, function(i, d) {
                                        select.options.add(d.id, d.name);
                                        select.query();
                                   });
                              }
                         });
                    }
                         
              });

              /**
              * Below is old Filter which gets quantity of products on the basis of
              * Size and color
              *
              **/
          //     $(".change-filter_old").change(function () {
          //          var color = $("input[name=color]").val();
          //      //     var color = $("select[name=color]").val();
          //          var size = $("select[name=size]").val();
          //          var ref = $("#refference").val();
          //      //     console.log(size);
          //      //     console.log(color);
          //      //     console.log(ref);

          //           $("#old_qty").show();
          //           $("#new_qty").hide();
          //           if (color != 0 && size != 0) {
          //                $.ajax({
          //                     url: base_url+"/product/"+ref+"/color/"+color+"/size/"+size,
          //                     type: "GET",
          //                     dataType: "json",
          //                     success: function (res) {
          //                          // console.log(res);
          //                          $("#old_qty").hide();
          //                          $("#new_qty").show();
          //                          if (res.qty > 2) {
          //                               var span = "<span> In Stock: "+parseInt(res.qty)+" Pc(s)</span>";
          //                          }else if(res.qty<=2 && res.qty != 0){
          //                               var span = '<span class="text-danger"> In Stock: '+parseInt(res.qty)+' Pc(s) </span>';
          //                          }else{
          //                               var span = '<span class="text-danger"> Out of Stock: '+parseInt(res.qty)+' Pc(s)</span>';
          //                          }
          //                          $("#new_qty").html(span);
          //                     }
          //                });
          //           }else if (color == 0 && size != 0) {
          //                $.ajax({
          //                     url: base_url+"/product/"+ref+"/size/"+size,
          //                     type: "GET",
          //                     dataType: "json",
          //                     success: function (res) {
          //                          // console.log(res);
          //                          $("#old_qty").hide();
          //                          $("#new_qty").show();
          //                          if (res.qty > 2) {
          //                               var span = "<span> In Stock: "+parseInt(res.qty)+" Pc(s)</span>";
          //                          }else if(res.qty<=2 && res.qty != 0){
          //                               var span = '<span class="text-danger"> In Stock: '+parseInt(res.qty)+' Pc(s) </span>';
          //                          }else{
          //                               var span = '<span class="text-danger"> Out of Stock: '+parseInt(res.qty)+' Pc(s)</span>';
          //                          }
          //                          $("#new_qty").html(span);

          //                          var select = tail.select("select[name=color]");
          //                          removeOptions(select);
          //                          $.each(res.color, function(i, d) {
          //                               select.options.add(d.id, d.name);
          //                               select.query();
          //                          });
          //                     }
          //                });
          //           }else if(color !=0 && size==0){
          //                $.ajax({
          //                     url: base_url+"/product/"+ref+"/color/"+color,
          //                     type: "GET",
          //                     dataType: "json",
          //                     success: function (res) {
          //                          // console.log(res);
          //                          $("#old_qty").hide();
          //                          $("#new_qty").show();
          //                          if (res.qty > 2) {
          //                               var span = "<span> In Stock: "+parseInt(res.qty)+" Pc(s)</span>";
          //                          }else if(res.qty<=2 && res.qty != 0){
          //                               var span = '<span class="text-danger"> In Stock: '+parseInt(res.qty)+' Pc(s)</span>';
          //                          }else{
          //                               var span = '<span class="text-danger"> Out of Stock: '+parseInt(res.qty)+' Pc(s)</span>';
          //                          }
          //                          $("#new_qty").html(span);
          //                          var select = tail.select("#size");
          //                          removeOptions(select);
          //                          $.each(res.sizes, function(i, d) {
          //                               select.options.add(d.id, d.name);
          //                               select.query();
          //                          });
          //                     }
          //                });
          //           }
          //     });
          }); // $.ready()
</script>
@endsection