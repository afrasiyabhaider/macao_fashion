@extends('site.layout.app')
@section('title')
     Cart
@endsection
@section('content')
<div class="container pt-sm-5">
     <div class="row bg-light">
          <div class="col-12 p-sm-5">
               <h1 class="text-center">
                    Cart
               </h1>
          </div>
     </div>
     <div class="row">
          <div class="col-12">
               <table class="table">
                    <thead>
                         <tr>
                              <th>#</th>
                              <th>Name</th>
                              <th>Size</th>
                              <th>Color</th>
                              <th>Unit Price</th>
                              <th>Qty</th>
                              <th>Sub Total</th>
                         </tr>
                    </thead>
                    <tbody>
                         @foreach ($cart as $key=>$item)
                             <tr>
                                   <td>{{$loop->iteration}}</td>
                                   <td>
                                        {{
                                             $item['name']
                                        }}
                                   </td>
                                   <td>
                                        @php
                                            $size = App\Size::find($item['options']['size'])->name;
                                        @endphp
                                        {{
                                             $size
                                        }}
                                   </td>
                                   <td>
                                        @php
                                            $color = App\Color::find($item['options']['color'])->name;
                                        @endphp
                                        {{
                                             $color
                                        }}
                                   </td>
                                   <td>
                                        {{
                                             $item['price']
                                        }}
                                   </td>
                                   <td>
                                        {{
                                             $item['qty']
                                        }}
                                   </td>
                                   <td>
                                        {{
                                             $item['subtotal']
                                        }}
                                   </td>
                              </tr>
                         @endforeach
                    </tbody>
               </table>
          </div>
     </div>
</div>
@endsection
@section('scripts')
@endsection