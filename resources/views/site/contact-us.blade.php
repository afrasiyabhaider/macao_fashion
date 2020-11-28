@extends('site.layout.app')
@section('title')
     Contact Us
@endsection
@section('content')
<div class="container">
     <nav aria-label="breadcrumb" class="breadcrumb-nav">
          <div class="container">
               <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{url('/')}}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Contact Us</li>
               </ol>
          </div><!-- End .container -->
     </nav>
     <div class="row">
          <div class="col-sm-6">
               <h2 class="light-title">Contact <strong>Details</strong></h2>
     
               <div class="contact-info">
                    <div class="mb-sm-5">
                         <i class="icon-home"></i>
                         <p class="font-weight-bold text-capitalize">Macao cora lalouviere</p>
                         <p>
                         <a href="tel:00 32 64 67 73 58">00 32 64 67 73 58</a>
                         </p>
                         <p>
                              Rue franco belge 228,7100,lalouvier
                         </p>
                    </div>
                    <div class="mb-sm-5">
                         <i class="icon-home"></i>

                         <p class="font-weight-bold text-capitalize">Macao shopping douaire</p>
                         <p>
                              <a href="tel:010 454225">
                                   {{-- <i class="icon-phone-1"></i> --}}
                                   010 454225
                              </a>
                         </p>
                         <p>
                              Avenue de douaire 1340,Ottignies-Louvain-la-Neuve
                         </p>
                    </div>
                    <div>
                         <i class="icon-home"></i>
                         <p class="font-weight-bold text-capitalize">Macao belle Ile liege</p>
                         <p>
                              <a href="tel:00 32 485 15 25 64">00 32 485 15 25 64</a>
                         </p>
                         <p>
                              Quai des Vennes 1,4020,Liège
                         </p>
                    </div>
               </div><!-- End .contact-info -->
          </div><!-- End .col-sm-6 -->
          <div class="col-sm-6">
               <h2 class="light-title">Write <strong>Us</strong></h2>
     
               <form action="{{action('website\SiteController@sendMail')}}" method="POST">
                    @csrf
                    <div class="form-group required-field">
                         <label for="contact-name">Name</label>
                         <input type="text" class="form-control" id="contact-name" name="contact-name" required>
                    </div><!-- End .form-group -->
     
                    <div class="form-group required-field">
                         <label for="contact-email">Email</label>
                         <input type="email" class="form-control" id="contact-email" name="contact-email" required>
                    </div><!-- End .form-group -->
     
                    <div class="form-group">
                         <label for="contact-phone">Phone Number</label>
                         <input type="tel" class="form-control" id="contact-phone" name="contact-phone">
                    </div><!-- End .form-group -->
     
                    <div class="form-group required-field">
                         <label for="contact-message">What’s on your mind?</label>
                         <textarea cols="30" rows="1" id="contact-message" class="form-control" name="contact-message"
                              required></textarea>
                    </div><!-- End .form-group -->
     
                    <div class="form-footer">
                         <button type="submit" class="btn btn-primary">Submit</button>
                    </div><!-- End .form-footer -->
               </form>
          </div><!-- End .col-sm-6 -->
     </div><!-- End .row -->
</div>
@endsection