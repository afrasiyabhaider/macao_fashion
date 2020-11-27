@php
$categories = \App\Category::catAndSubCategories(1);
$ut = new \App\Utils\ProductUtil();
@endphp

{{-- {{$categories[2]['name']}} --}}
{{-- header-transparent bg-dark --}}
<header class="header">
     <div class="header-middle sticky-header">
          <div class="container">
               <div class="header-left">
                    @php
                    $logo = App\Business::first()->logo;
                    @endphp
                    <a href="{{url('/')}}">
                         <img src="{{asset('uploads/business_logos/'.$logo)}}" alt="{{config('app.name')}}"
                              style="height:80px">
                    </a>
               </div><!-- End .header-left -->

               <div class="header-right">
                    <div class="row header-row header-row-top">
                         <div class="header-dropdown dropdown-expanded">
                              <a href="#">Links</a>
                              <div class="header-menu">
                                   <ul>
                                        {{-- <li><a href="my-account.html">MY ACCOUNT </a></li>
                              <li><a href="#">DAILY DEAL</a></li>
                              <li><a href="#">MY WISHLIST </a></li> --}}
                                        <li>
                                             <a href="{{url('/login')}}" class="text-light">
                                                  SIGN IN
                                             </a>
                                        </li>
                                        <li>
                                             <a href="{{url('/cart/view')}}" class="text-light">
                                                  CART
                                                  @if (Cart::count())
                                                      ({{Cart::count()}})
                                                  @endif
                                             </a>
                                        </li>
                                   </ul>
                              </div><!-- End .header-menu -->
                         </div><!-- End .header-dropown -->

                         {{-- Searchbar --}}

                         {{-- <div class="header-search">
                         <a href="#" class="search-toggle" role="button"><i class="icon-magnifier"></i></a>
                         <div class="header-search-wrapper">
                              <form action="#" method="get">
                              <input type="search" class="form-control" name="q" id="q" placeholder="Search..." required>
                              <button class="btn" type="submit"><i class="icon-magnifier"></i></button>
                              </form>
                         </div><!-- End .header-search-wrapper -->
                    </div><!-- End .header-search --> --}}
                    </div><!-- End .header-row -->

                    <div class="row header-row header-row-bottom">
                         <nav class="main-nav">
                              <ul class="menu sf-arrows">
                                   {{-- <li class="active"><a href="index.html">Home</a></li> --}}
                                   @if (!Request::is('/'))
                                   <li>
                                        <a href="{{url('/')}}">
                                             Home
                                        </a>
                                   </li>

                                   @endif
                                   <li>
                                        <a href="{{url('product/list')}}">
                                             All Products
                                        </a>
                                   </li>
                                   @foreach ($categories as $parent)
                                        @if ($loop->iteration <= 8 && $parent['name']== "ACCESSOIRE") <li>
                                        <a href="#" class="sf-with-ul">
                                             {{$parent['name']}}
                                        </a>

                                        <ul>
                                             <li>
                                                  <a href="{{url('products/category/'.encrypt($parent['id']))}}">
                                                       {{$parent['name']}}
                                                  </a>
                                             </li>
                                             <hr>
                                             @if (!empty($parent['sub_categories']))
                                             @foreach ($parent['sub_categories'] as $sub_category)
                                             @php
                                                  $categories = App\Product::where('sub_category_id',$sub_category['id'])->pluck('refference');

                                                  $count = App\WebsiteProducts::whereIn('refference',$categories)->count('product_id');

                                             @endphp
                                             <li>
                                                  <a href="{{url('products/category/'.encrypt($sub_category['id']))}}">
                                                       {{$sub_category['name']}}
                                                       [{{$count}}]
                                                  </a>
                                             </li>
                                             @endforeach
                                             @endif
                                        </ul>
                                        </li>
                                        @endif
                                        @endforeach
                                   <li>
                                        <a href="#" class="sf-with-ul">
                                             Women
                                        </a>
                                        <ul>
                                             <li>
                                                  <a href="{{url('products/category/'.encrypt(2))}}">
                                                       Women
                                                  </a>
                                             </li>
                                             <hr>
                                             <li>
                                                  <a href="{{url('products/category/'.encrypt("top"))}}">
                                                       TOP
                                                  </a>
                                             </li>
                                             <li>
                                                  <a href="{{url('products/category/'.encrypt("robe"))}}">
                                                       ROBE
                                                  </a>
                                             </li>
                                             <li>
                                                  <a href="{{url('products/category/'.encrypt("veste"))}}">
                                                       VESTE
                                                  </a>
                                             </li>
                                             <li>
                                                  <a href="{{url('products/category/'.encrypt("bah"))}}">
                                                       BAH
                                                  </a>
                                             </li>
                                             <li>
                                                  <a href="{{url('products/category/'.encrypt(48))}}">
                                                       CHEMISE
                                                  </a>
                                             </li>
                                             <li>
                                                  <a href="{{url('products/category/'.encrypt(35))}}">
                                                       ENSAMBLE
                                                  </a>
                                             </li>
                                        </ul>
                                   </li>
                              </ul>
                         </nav>

                         <button class="mobile-menu-toggler" type="button">
                              <i class="icon-menu"></i>
                         </button>

                         {{-- Languages --}}
                         {{-- <div class="header-dropdowns">
                         <div class="header-dropdown">
                              <a href="#">USD</a>
                              <div class="header-menu">
                              <ul>
                                   <li><a href="#">EUR</a></li>
                                   <li><a href="#">USD</a></li>
                              </ul>
                              </div><!-- End .header-menu -->
                         </div><!-- End .header-dropown -->

                         <div class="header-dropdown">
                              <a href="#">ENG</a>
                              <div class="header-menu">
                              <ul>
                                   <li><a href="#">ENG</a></li>
                                   <li><a href="#">SPA</a></li>
                                   <li><a href="#">FRE</a></li>
                              </ul>
                              </div><!-- End .header-menu -->
                         </div><!-- End .header-dropown -->
                    </div><!-- End .header-dropdowns --> --}}

                         {{-- Cart --}}
                         {{-- <div class="dropdown cart-dropdown">
                         <a href="#" class="dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-display="static">
                              <span class="dropdown-cart-icon">
                              <span class="cart-count">2</span>
                              </span>
                              <span class="dropdown-cart-text">Items</span>
                         </a>

                         <div class="dropdown-menu" >
                              <div class="dropdownmenu-wrapper">
                              <div class="dropdown-cart-products">
                                   <div class="product">
                                        <figure class="product-image-container">
                                             <a href="product.html" class="product-image">
                                                  <img src="{{asset('site_assets/images/products/cart/product-1.jpg')}}"
                         alt="product">
                         </a>
                         </figure>

                         <div class="product-details">
                              <h4 class="product-title">
                                   <a href="product.html">Men Sunglasses</a>
                              </h4>

                              <span class="cart-product-info">
                                   <span class="cart-product-qty">1</span>
                                   x $60.00
                              </span>
                         </div><!-- End .product-details -->

                         <a href="#" class="btn-remove" title="Remove Product"><i class="icon-cancel"></i></a>
                    </div><!-- End .product -->

                    <div class="product">
                         <figure class="product-image-container">
                              <a href="product.html" class="product-image">
                                   <img src="{{asset('site_assets/images/products/cart/product-2.jpg')}}" alt="product">
                              </a>
                         </figure>
                         <div class="product-details">
                              <h4 class="product-title">
                                   <a href="product.html">Woman Fashion Blue</a>
                              </h4>

                              <span class="cart-product-info">
                                   <span class="cart-product-qty">1</span>
                                   x $80.00
                              </span>
                         </div><!-- End .product-details -->
                         <a href="#" class="btn-remove" title="Remove Product"><i class="icon-cancel"></i></a>
                    </div><!-- End .product -->
               </div><!-- End .cart-product -->

               <div class="dropdown-cart-total">
                    <span>SubTotal:</span>

                    <span class="cart-total-price">$140.00</span>
               </div><!-- End .dropdown-cart-total -->

               <div class="dropdown-cart-action">
                    <a href="cart.html" class="btn btn-primary">View Cart</a>
                    <a href="checkout-shipping.html" class="btn btn-outline-primary">Checkout</a>
               </div><!-- End .dropdown-cart-total -->
          </div><!-- End .dropdownmenu-wrapper -->
     </div><!-- End .dropdown-menu -->
     </div><!-- End .dropdown --> --}}
     </div><!-- End .header-row -->
     </div><!-- End .header-right -->
     </div><!-- End .container -->
     </div><!-- End .header-middle -->
</header><!-- End .header -->