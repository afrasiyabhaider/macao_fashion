@php
    $blouse = \App\Category::where('name','BLOUSE')->first()->id;
    $robe = \App\Category::where('name','ROBE')->first()->id;
    $sacs = \App\Category::where('name','SACS')->first()->id;
    $cat1 = App\SiteImage::where('image_for','category_1')->first();
    $cat2 = App\SiteImage::where('image_for','category_2')->first();
    $cat3 = App\SiteImage::where('image_for','category_3')->first();
@endphp

<div class="banners-container mb-4 mb-lg-6 mb-xl-8">
     <div class="container">
          <div class="row no-gutters">
               <div class="col-md-4">
               <div class="banner">
                    <div class="banner-content text-light">
                         <h3 class="banner-title text-light">BLOUSE</h3>

                         <a href="{{url('products/category/'.encrypt($blouse))}}" class="btn">View</a>
                    </div><!-- End .banner-content -->
                    <a href="#">
                         @if($cat1)
                              <img src="{{asset('uploads/'.$cat1->image)}}" alt="banner">
                         @else
                              <img src="{{asset('site_assets/images/banners/banner-3.jpg')}}" alt="banner">
                         @endif
                    </a>
               </div><!-- End .banner -->
               </div><!-- End .col-md-4 -->
               <div class="col-md-4">
               <div class="banner">
                    <div class="banner-content text-light">
                         <h3 class="banner-title text-light">ROBE</h3>

                         <a href="{{url('products/category/'.encrypt($robe))}}" class="btn">View</a>
                    </div><!-- End .banner-content -->
                    <a href="">
                         @if($cat2)
                              <img src="{{asset('uploads/'.$cat2->image)}}" alt="banner">
                         @else
                              <img src="{{asset('site_assets/images/banners/banner-3.jpg')}}" alt="banner">
                         @endif
                    </a>
               </div><!-- End .banner -->
               </div><!-- End .col-md-4 -->
               <div class="col-md-4">
               <div class="banner">
                    <div class="banner-content text-light">
                         <h3 class="banner-title text-light">SACS</h3>

                         <a href="{{url('products/category/'.encrypt($sacs))}}" class="btn">View</a>
                    </div><!-- End .banner-content -->
                    <a href="#">
                         @if($cat3)
                              <img src="{{asset('uploads/'.$cat3->image)}}" alt="banner">
                         @else
                              <img src="{{asset('site_assets/images/banners/banner-3.jpg')}}" alt="banner">
                         @endif
                    </a>
               </div><!-- End .banner -->
               </div><!-- End .col-md-4 -->
          </div><!-- End .row -->
     </div><!-- End .container -->
</div><!-- End .banners-container -->
