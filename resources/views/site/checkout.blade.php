<html>
<head>
     <title>Check Out|{{ config('app.name') }}</title>
</head>
<body>
     <form id="myform" action="{{ url('checkout') }}" method="post">
          <button type="button" data-vp-publickey="{{ $publicKey }}" data-vp-baseurl="{{ $baseUrl }}" data-vp-lang="en"
               data-vp-amount="1000" data-vp-description="My product">
          </button>
     </form>

     <script src="https://code.jquery.com/jquery-1.11.2.min.js"></script>
     <script src="https://demo.vivapayments.com/web/checkout/js"></script>
</body>

</html>