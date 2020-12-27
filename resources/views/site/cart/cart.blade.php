@extends('site.layout.app')
@section('title')
Cart
@endsection
@section('content')
<nav aria-label="breadcrumb" class="breadcrumb-nav">
     <div class="container">
          <ol class="breadcrumb">
               <li class="breadcrumb-item"><a href="{{url('/')}}">Home</a></li>
               <li class="breadcrumb-item active" aria-current="page">Shopping Cart</li>
          </ol>
     </div><!-- End .container -->
</nav>
<div class="container">
     <div class="row">
          @include('site.cart.partials.details')
          @include('site.cart.partials.summary')
     </div>
</div>
@endsection
@section('scripts')
{{-- <script src="https://code.jquery.com/jquery-1.11.2.min.js"></script> --}}
<script src="https://demo.vivapayments.com/web/checkout/js"></script>
<script>
     function updateQuantity(id) {
          var url = "{{url('cart/update/')}}"+"/"+$(id).attr("data-rowid");
          var updateUrl = url+"/"+$(id).val();
          window.location.href = updateUrl;
     }
</script>
@endsection