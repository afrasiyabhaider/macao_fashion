{{-- 
 @php
     $categories = \App\Category::catAndSubCategories(1);
 @endphp

 @dd($categories); --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>
        @yield('title') - {{config('app.site')}}
    </title>

    <meta name="keywords" content="macao,belgium,shopping" />
    <meta name="description" content="macaobe shop">
    <meta name="author" content="Afrasiyab haider">
        
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{asset('site_assets/images/icons/favicon.ico')}}">
    
    <script type="text/javascript">
        WebFontConfig = {
            google: { families: [ 'Open+Sans:300,400,600,700,800','Poppins:300,400,500,600,700','Segoe Script:300,400,500,600,700' ] }
        };
        (function(d) {
            var wf = d.createElement('script'), s = d.scripts[0];
            wf.src = 'site_assets/js/webfont.js';
            wf.async = true;
            s.parentNode.insertBefore(wf, s);
        })(document);
    </script>
    
    <!-- Plugins CSS File -->
    <link rel="stylesheet" href="{{asset('site_assets/fontawesome/css/all.min.css')}}">
    <link rel="stylesheet" href="{{asset('plugins/tailselect/css/modern/tail.select-light.css')}}">
    
    @yield('css')
    <!-- Main CSS File -->
    <link rel="stylesheet" href="{{asset('site_assets/css/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/app.css')}}">
    <link rel="stylesheet" href="{{asset('site_assets/css/style.min.css')}}">
</head>
<body>
    <div class="page-wrapper">
        
        @include('site.layout.partials.top_nav')
        {{-- Main Content starts Here --}}
        <main class="main ">
            @yield('content')
        </main>
        {{-- Main Content ends Here --}}
        
        
        <!-- End .footer -->
        @include('site.layout.partials.footer')
    </div><!-- End .page-wrapper -->

    @include('site.layout.partials.mobile_menu')

    {{-- News Letter --}}
    {{-- <div class="newsletter-popup mfp-hide" id="newsletter-popup-form" style="background-image: url(assets/images/newsletter_popup_bg.jpg)">
        <div class="newsletter-popup-content">
            <img src="assets/images/logo-black.png" alt="Logo" class="logo-newsletter">
            <h2>BE THE FIRST TO KNOW</h2>
            <p>Subscribe to the Porto eCommerce newsletter to receive timely updates from your favorite products.</p>
            <form action="#">
                <div class="input-group">
                    <input type="email" class="form-control" id="newsletter-email" name="newsletter-email" placeholder="Email address" required>
                    <input type="submit" class="btn" value="Go!">
                </div><!-- End .from-group -->
            </form>
            <div class="newsletter-subscribe">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" value="1">
                        Don't show this popup again
                    </label>
                </div>
            </div>
        </div><!-- End .newsletter-popup-content -->
    </div><!-- End .newsletter-popup --> --}}
    
    <!-- Add Cart Modal -->
    <div class="modal fade" id="addCartModal" tabindex="-1" role="dialog" aria-labelledby="addCartModal" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-body add-cart-box text-center">
            <p>You've just added this product to the<br>cart:</p>
            <h4 id="productTitle"></h4>
            <img src="" id="productImage" width="100" height="100" alt="adding cart image">
            <div class="btn-actions">
                <a href="cart.html"><button class="btn-primary">Go to cart page</button></a>
                <a href="#"><button class="btn-primary" data-dismiss="modal">Continue</button></a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <a id="scroll-top" href="#top" title="Top" role="button"><i class="icon-angle-up"></i></a>

    <!-- Plugins JS File -->
    <script src="{{asset('site_assets/js/jquery.min.js')}}"></script>
    @include('sweetalert::alert')
    <script src="{{asset('site_assets/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('site_assets/js/plugins.min.js')}}"></script>
    <script src="{{asset('site_assets/fontawesome/js/all.min.js')}}"></script>
    <script src="{{asset('plugins/tailselect/js/tail.select.min.js')}}"></script>
    <script src="{{asset('js/app.js')}}"></script>
    {{-- <script src="{{ asset('js/share.js') }}"></script> --}}
    <script>
        $(function () {
            tail.select('.select2',{
                search: true,
            });
        });
    </script>
    @yield('scripts')
    <!-- Main JS File -->
    <script src="{{asset('site_assets/js/main.js')}}"></script>
</body>
</html>