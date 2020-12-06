@extends('layouts.app')
@section('title','Website Products')
@section('css')
<link rel="stylesheet" href="{{asset('plugins/dropzone/min/dropzone.min.css')}}">
@endsection
@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
     <h1>
          Banner Images
     </h1>
</section>

<!-- Main content -->
<section class="content">
     <div class="row">
          <div class="col-sm-12">
               @component('components.widget', ['class' => 'box-primary'])
               <h3 class="text-primary">
                    Pages Images <small>1970x400</small>
                    <i class="fa fa-image"></i>
               </h3>
               @if ($errors->any())
               <div class="alert alert-danger">
                    <ul>
                         @foreach ($errors->all() as $error)
                         <li>{{ $error }}</li>
                         @endforeach
                    </ul>
               </div>
               @endif
               <div class="col-sm-4" style="margin-top:30px">
                    @php
                         $promo1 = App\SiteImage::where('image_for','ACCESSOIRE')->first();
                    @endphp
                    <form action="{{action('SiteImageController@categoryImage')}}" method="post"
                         enctype="multipart/form-data">
                         @csrf
                         <input type="hidden" name="type" value="ACCESSOIRE">
                         <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>ACCESSOIRE</strong>
                              </label>
                              <br>
                              @if (isset($promo1))
                              <img src="{{asset('uploads/'.$promo1->image)}}" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="promo_preview1" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="promo1">
                         </div>
                         <button class="btn btn-success mt-sm-5" type="submit">
                              Upload Image
                              <i class="fa fa-upload"></i>
                         </button>
                    </form>
               </div>
               <div class="col-sm-4" style="margin-top:30px">
                    @php
                         $promo2 = App\SiteImage::where('image_for','BAS COLLANT')->first();
                    @endphp
                    <form action="{{action('SiteImageController@categoryImage')}}" method="post"
                         enctype="multipart/form-data">
                         @csrf
                         <input type="hidden" name="type" value="BAS COLLANT">
                         <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>BAS COLLANT</strong>
                              </label>
                              <br>
                              @if (isset($promo2))
                              <img src="{{asset('uploads/'.$promo2->image)}}" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="promo_preview2" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="promo2">
                         </div>
                         <button class="btn btn-success mt-sm-5" type="submit">
                              Upload Image
                              <i class="fa fa-upload"></i>
                         </button>
                    </form>
               </div>
               <div class="col-sm-4" style="margin-top:30px">
                    @php
                         $promo3 = App\SiteImage::where('image_for','CEINTURE')->first();
                    @endphp
                    <form action="{{action('SiteImageController@categoryImage')}}" method="post"
                         enctype="multipart/form-data">
                         @csrf
                         <input type="hidden" name="type" value="CEINTURE">
                         <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>CEINTURE</strong>
                              </label>
                              <br>
                              @if (isset($promo3))
                              <img src="{{asset('uploads/'.$promo3->image)}}" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="promo_preview3" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="promo3">
                         </div>
                         <button class="btn btn-success mt-sm-5" type="submit">
                              Upload Image
                              <i class="fa fa-upload"></i>
                         </button>
                    </form>
               </div>
               <div class="col-sm-4" style="margin-top:30px">
                    @php
                         $promo4 = App\SiteImage::where('image_for','FOULARD')->first();
                    @endphp
                    <form action="{{action('SiteImageController@categoryImage')}}" method="post"
                         enctype="multipart/form-data">
                         @csrf
                         <input type="hidden" name="type" value="FOULARD">
                         <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>FOULARD</strong>
                              </label>
                              <br>
                              @if (isset($promo4))
                              <img src="{{asset('uploads/'.$promo4->image)}}" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="promo_preview4" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="promo4">
                         </div>
                         <button class="btn btn-success mt-sm-5" type="submit">
                              Upload Image
                              <i class="fa fa-upload"></i>
                         </button>
                    </form>
               </div>
               <div class="col-sm-4" style="margin-top:30px">
                    @php
                         $promo5 = App\SiteImage::where('image_for','SACS')->first();
                    @endphp
                    <form action="{{action('SiteImageController@categoryImage')}}" method="post"
                         enctype="multipart/form-data">
                         @csrf
                         <input type="hidden" name="type" value="SACS">
                         <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>SACS</strong>
                              </label>
                              <br>
                              @if (isset($promo5))
                              <img src="{{asset('uploads/'.$promo5->image)}}" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="promo_preview5" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="promo5">
                         </div>
                         <button class="btn btn-success mt-sm-5" type="submit">
                              Upload Image
                              <i class="fa fa-upload"></i>
                         </button>
                    </form>
               </div>
               <div class="col-sm-4" style="margin-top:30px">
                    @php
                         $promo6 = App\SiteImage::where('image_for','WOMEN')->first();
                    @endphp
                    <form action="{{action('SiteImageController@categoryImage')}}" method="post"
                         enctype="multipart/form-data">
                         @csrf
                         <input type="hidden" name="type" value="WOMEN">
                         <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>WOMEN</strong>
                              </label>
                              <br>
                              @if (isset($promo6))
                              <img src="{{asset('uploads/'.$promo6->image)}}" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="promo_preview6" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="promo6">
                         </div>
                         <button class="btn btn-success mt-sm-5" type="submit">
                              Upload Image
                              <i class="fa fa-upload"></i>
                         </button>
                    </form>
               </div>
               <div class="col-sm-4" style="margin-top:30px">
                    @php
                         $promo7 = App\SiteImage::where('image_for','TOP')->first();
                    @endphp
                    <form action="{{action('SiteImageController@categoryImage')}}" method="post"
                         enctype="multipart/form-data">
                         @csrf
                         <input type="hidden" name="type" value="TOP">
                         <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>TOP</strong>
                              </label>
                              <br>
                              @if (isset($promo7))
                              <img src="{{asset('uploads/'.$promo7->image)}}" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="promo_preview7" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="promo7">
                         </div>
                         <button class="btn btn-success mt-sm-5" type="submit">
                              Upload Image
                              <i class="fa fa-upload"></i>
                         </button>
                    </form>
               </div>
               <div class="col-sm-4" style="margin-top:30px">
                    @php
                         $promo8 = App\SiteImage::where('image_for','ROBE')->first();
                    @endphp
                    <form action="{{action('SiteImageController@categoryImage')}}" method="post"
                         enctype="multipart/form-data">
                         @csrf
                         <input type="hidden" name="type" value="ROBE">
                         <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>ROBE</strong>
                              </label>
                              <br>
                              @if (isset($promo8))
                              <img src="{{asset('uploads/'.$promo8->image)}}" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="promo_preview8" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="promo8">
                         </div>
                         <button class="btn btn-success mt-sm-5" type="submit">
                              Upload Image
                              <i class="fa fa-upload"></i>
                         </button>
                    </form>
               </div>
               <div class="col-sm-4" style="margin-top:30px">
                    @php
                         $promo9 = App\SiteImage::where('image_for','VESTE')->first();
                    @endphp
                    <form action="{{action('SiteImageController@categoryImage')}}" method="post"
                         enctype="multipart/form-data">
                         @csrf
                         <input type="hidden" name="type" value="VESTE">
                         <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>VESTE</strong>
                              </label>
                              <br>
                              @if (isset($promo9))
                                   <img src="{{asset('uploads/'.$promo9->image)}}" alt="Image Here" style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="promo_preview9" alt="Image Here" style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="promo9">
                         </div>
                         <button class="btn btn-success mt-sm-5" type="submit">
                              Upload Image
                              <i class="fa fa-upload"></i>
                         </button>
                    </form>
               </div>
               <div class="col-sm-4" style="margin-top:30px">
                    @php
                         $promo10 = App\SiteImage::where('image_for','BAH')->first();
                    @endphp
                    <form action="{{action('SiteImageController@categoryImage')}}" method="post"
                         enctype="multipart/form-data">
                         @csrf
                         <input type="hidden" name="type" value="BAH">
                         <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>BAH</strong>
                              </label>
                              <br>
                              @if (isset($promo10))
                                   <img src="{{asset('uploads/'.$promo10->image)}}" alt="Image Here" style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="promo_preview10" alt="Image Here" style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="promo10">
                         </div>
                         <button class="btn btn-success mt-sm-5" type="submit">
                              Upload Image
                              <i class="fa fa-upload"></i>
                         </button>
                    </form>
               </div>
               <div class="col-sm-4" style="margin-top:30px">
                    @php
                         $promo11 = App\SiteImage::where('image_for','CHEMISE')->first();
                    @endphp
                    <form action="{{action('SiteImageController@categoryImage')}}" method="post"
                         enctype="multipart/form-data">
                         @csrf
                         <input type="hidden" name="type" value="CHEMISE">
                         <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>CHEMISE</strong>
                              </label>
                              <br>
                              @if (isset($promo11))
                                   <img src="{{asset('uploads/'.$promo11->image)}}" alt="Image Here" style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="promo_preview11" alt="Image Here" style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="promo11">
                         </div>
                         <button class="btn btn-success mt-sm-5" type="submit">
                              Upload Image
                              <i class="fa fa-upload"></i>
                         </button>
                    </form>
               </div>
               <div class="col-sm-4" style="margin-top:30px">
                    @php
                         $promo12 = App\SiteImage::where('image_for','ENSAMBLE')->first();
                    @endphp
                    <form action="{{action('SiteImageController@categoryImage')}}" method="post"
                         enctype="multipart/form-data">
                         @csrf
                         <input type="hidden" name="type" value="ENSAMBLE">
                         <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>ENSAMBLE</strong>
                              </label>
                              <br>
                              @if (isset($promo12))
                                   <img src="{{asset('uploads/'.$promo12->image)}}" alt="Image Here" style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="promo_preview12" alt="Image Here" style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="promo12">
                         </div>
                         <button class="btn btn-success mt-sm-5" type="submit">
                              Upload Image
                              <i class="fa fa-upload"></i>
                         </button>
                    </form>
               </div>
               @endcomponent
          </div>
     </div>
</section>
@endsection
@section('javascript')
<script src="{{asset('js/imoViewer.js')}}"></script>
<script>
     $(function() {
          $('#promo1').imoViewer({
               'preview' : '#promo_preview1'
          });
          $('#promo2').imoViewer({
               'preview' : '#promo_preview2'
          });
          $('#promo3').imoViewer({
               'preview' : '#promo_preview3'
          });
          $('#promo4').imoViewer({
               'preview' : '#promo_preview4'
          });
          $('#promo5').imoViewer({
               'preview' : '#promo_preview5'
          });
          $('#promo6').imoViewer({
               'preview' : '#promo_preview6'
          });
          $('#promo7').imoViewer({
               'preview' : '#promo_preview7'
          });
          $('#promo8').imoViewer({
               'preview' : '#promo_preview8'
          });
          $('#promo9').imoViewer({
               'preview' : '#promo_preview9'
          });
          $('#promo10').imoViewer({
               'preview' : '#promo_preview10'
          });
          $('#promo11').imoViewer({
               'preview' : '#promo_preview11'
          });
          $('#promo12').imoViewer({
               'preview' : '#promo_preview12'
          });
     });
</script>
@endsection