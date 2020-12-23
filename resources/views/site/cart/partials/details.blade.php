<div class="col-lg-8">
     <div class="cart-table-container">
          @if ($cart)
               <table class="table table-cart">
                    <thead>
                         <tr>
                              <th class="product-col">Product</th>
                              <th class="price-col">Price</th>
                              <th class="qty-col">Qty</th>
                              <th>Subtotal</th>
                         </tr>
                    </thead>
                    <tbody>
                         @foreach ($cart as $key=>$item)
                         <tr class="product-row">
                              <td class="product-col">
                                   @php
                                   $img = App\ProductImages::where('product_id',$item['options']['product_id'])->first();
                                   if(!empty($img)){
                                   $image = 'uploads/'.$img->image;
                                   }else{
                                   $image = 'img/product-placeholder-1.jpg';
                                   }
                                   @endphp
                                   <figure class="product-image-container">
                                        {{-- <a href="{{url('product/'.encrypt($item['options']['product_id']).'/details')}}" class="product-image"> --}}
                                             <img src="{{asset($image)}}" alt="product" height="150px">
                                        {{-- </a> --}}
                                   </figure>
                                   <h2 class="product-title h2">
                                        <a href="{{url('product/'.encrypt($item['options']['product_id']).'/detail')}}">
                                             {{$item['name']}}
                                        </a>
                                        @php
                                             $size = App\Size::find($item['options']['size'])->name;
                                             $color = App\Color::find($item['options']['color'])->name;
                                        @endphp
                                        <br>
                                        <span class="h5">
                                             {{$size}} - {{$color}}
                                        </span>
                                   </h2>
                              </td>
                              <td>
                                   <i class="fa fa-euro-sign"></i>{{$item['price']}}
                              </td>
                              <td>
                                   <input class="vertical-quantity form-control" type="text" data-rowid="{{$item['rowId']}}" name="qty" value="{{$item['qty']}}" onchange="updateQuantity(this);" min="0">
                              </td>
                              <td>
                                   <i class="fa fa-euro-sign"></i>
                                   {{$item['subtotal']}}
                              </td>
                         </tr>
                         <tr class="product-action-row">
                              <td colspan="4" class="clearfix">
                                   <div class="float-left">
                                        <a href="{{url('cart/remove/'.$item['rowId'])}}" class="btn-move" title="Remove Product">
                                             Remove Product
                                        </a>
                                   </div><!-- End .float-left -->
                                   <div class="float-right">
                                        <a href="{{url('cart/remove/'.$item['rowId'])}}" title="Remove Product" class="btn-remove">
                                             <span class="sr-only">Remove</span>
                                        </a>
                                   </div><!-- End .float-right -->
                              </td>
                         </tr>
                         @endforeach
                    </tbody>
                    <tfoot>
                         <tr>
                              <td colspan="4" class="clearfix">
                                   <div class="float-left">
                                        <a href="{{url('/')}}" class="btn btn-outline-secondary">Continue Shopping</a>
                                   </div><!-- End .float-left -->

                                   <div class="float-right">
                                        <a href="{{url('cart/empty')}}" class="btn btn-outline-secondary btn-clear-cart">Clear Shopping Cart</a>
                                        {{-- <a href="#" class="btn btn-outline-secondary btn-update-cart">
                                             Update Shopping Cart
                                        </a> --}}
                                   </div><!-- End .float-right -->
                              </td>
                         </tr>
                    </tfoot>
               </table>
          @else
               <h1 class="alert alert-info">
                    Cart Empty
               </h1>
          @endif
     </div><!-- End .cart-table-container -->

     {{-- <div class="cart-discount">
          <h4>Apply Discount Code</h4>
          <form action="#">
               <div class="input-group">
                    <input type="text" class="form-control form-control-sm" placeholder="Enter discount code" required>
                    <div class="input-group-append">
                         <button class="btn btn-sm btn-primary" type="submit">Apply Discount</button>
                    </div>
               </div><!-- End .input-group -->
          </form>
     </div><!-- End .cart-discount --> --}}
</div><!-- End .col-lg-8 -->