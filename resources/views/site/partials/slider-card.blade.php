@php
    $blouse = \App\Category::where('name','BLOUSE')->first()->id;
    $robe = \App\Category::where('name','ROBE')->first()->id;
    $sacs = \App\Category::where('name','SACS')->first()->id;
@endphp

<div class="banners-container mb-4 mb-lg-6 mb-xl-8">
     <div class="container">
          <div class="row no-gutters">
               <div class="col-md-4">
               <div class="banner">
                    <div class="banner-content">
                         <h3 class="banner-title">BLOUSE</h3>

                         <a href="{{url('products/category/'.encrypt($blouse))}}" class="btn">View</a>
                    </div><!-- End .banner-content -->
                    <a href="#">
                         <img src="{{asset('site_assets/images/banners/banner-1.jpg')}}" alt="banner">
                    </a>
               </div><!-- End .banner -->
               </div><!-- End .col-md-4 -->
               <div class="col-md-4">
               <div class="banner">
                    <div class="banner-content">
                         <h3 class="banner-title">ROBE</h3>

                         <a href="{{url('products/category/'.encrypt($robe))}}" class="btn">View</a>
                    </div><!-- End .banner-content -->
                    <a href="">
                         <img src="{{asset('site_assets/images/banners/banner-2.jpg')}}" alt="banner">
                    </a>
               </div><!-- End .banner -->
               </div><!-- End .col-md-4 -->
               <div class="col-md-4">
               <div class="banner">
                    <div class="banner-content">
                         <h3 class="banner-title">SACS</h3>

                         <a href="{{url('products/category/'.encrypt($sacs))}}" class="btn">View</a>
                    </div><!-- End .banner-content -->
                    <a href="#">
                         <img src="{{asset('site_assets/images/banners/banner-3.jpg')}}" alt="banner">
                    </a>
               </div><!-- End .banner -->
               </div><!-- End .col-md-4 -->
          </div><!-- End .row -->
     </div><!-- End .container -->
</div><!-- End .banners-container -->
