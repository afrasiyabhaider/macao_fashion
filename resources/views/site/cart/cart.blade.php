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
<script type="text/javascript" src="https://demo.vivapayments.com/web/checkout/v2/js"></script>
<script>
     $(document).ready(function () {
     VivaPayments.cards.setup({
     baseURL: '{{ $baseUrl }}',
     authToken: '{{ $accessToken }}',
     cardHolderAuthOptions: {
     cardHolderAuthPlaceholderId: 'threed-pane',
     cardHolderAuthInitiated: function () {
     $('#threed-pane').show();
     },
     cardHolderAuthFinished: function () {
     $('#threed-pane').hide();
     }
     },
     installmentsHandler: function (response) {
     if (!response.Error) {
     if (response.MaxInstallments == 0)
          return;
     $('#js-installments').show();
          for (i = 1; i <= response.MaxInstallments; i++) { 
               $('#js-installments').append($("<option>").val(i).text(i));
          }
          }
          else {
          alert(response.Error);
          }
          }
          });
     
          $('#submit').on('click', function (evt) {
          evt.preventDefault();
          VivaPayments.cards.requestToken({
          amount: 3600
          }).done(function (data) {
          console.log(data);
          $('#charge-token').val(data.chargeToken);
          $('#payment-form').submit();
          });
          });
     
     });
     function updateQuantity(id) {
          var url = "{{url('cart/update/')}}"+"/"+$(id).attr("data-rowid");
          var updateUrl = url+"/"+$(id).val();
          window.location.href = updateUrl;
     }
</script>
@endsection