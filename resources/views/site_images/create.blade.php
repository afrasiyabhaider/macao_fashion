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
                         Site Banner Images <small>1970x800</small>
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
                    <div class="col-sm-6">
                         <form action="{{action('SiteImageController@storeSlider')}}" method="post" enctype="multipart/form-data" class="dropzone"
                              id="imageForm">
                              @csrf
                              <div class="fallback">
                                   <input name="file" type="file" />
                              </div>
                         </form>
                         <button class="btn btn-success" id="uploadImage">
                              Upload Images
                              <i class="fa fa-upload"></i>
                         </button>
                    </div>
                    <div class="col-sm-6">
                         <table class="table">
                              <thead>
                                   <tr>
                                        <th>
                                             Sr#
                                        </th>
                                        <th>
                                             Image
                                        </th>
                                        <th>
                                             Action
                                        </th>
                                   </tr>
                              </thead>
                              <tbody>
                                   @php
                                        $slider = App\SiteImage::where('image_for','slider')->get();
                                        $count=4;
                                   @endphp
                                   @foreach ($slider as $item)
                                        @php
                                             $count--;
                                        @endphp
                                        <tr>
                                             <td>
                                                  <strong>
                                                       {{$loop->iteration}}
                                                  </strong>
                                             </td>
                                             <td colspan="1">
                                                  <img src="{{asset('uploads/'.$item->image)}}" class="img-thumbnail img-responsive" style="width:100px"
                                                       name="image">
                                             </td>
                                             <td>
                                                  <form action="{{action('SiteImageController@destroySlider',$item->id)}}" method="post">
                                                       @csrf
                                                       @method('DELETE')
                                                       <button type="submit" class="btn btn-danger" title="Remove Image">
                                                            <i class="fa fa-trash"></i>
                                                       </button>
                                                  </form>
                                             </td>
                                        </tr>
                                   @endforeach
                              </tbody>
                         </table>
                         
                    </div>
               @endcomponent
          </div>
     </div>
     <div class="row">
          <div class="col-sm-12">
               @component('components.widget', ['class' => 'box-primary'])
                    <h3 class="text-primary">
                         Category Images <small>399x210</small>
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
                    <div class="col-sm-4">
                         @php
                              $cat1 = App\SiteImage::where('image_for','category_1')->first();
                         @endphp
                         <form action="{{action('SiteImageController@categoryImage')}}" method="post" enctype="multipart/form-data">
                              @csrf
                              <input type="hidden" name="type" value="category_1">
                              <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>Category 1</strong>
                              </label>
                              <br>
                              @if (isset($cat1))
                                   <img src="{{asset('uploads/'.$cat1->image)}}"  alt="Image Here" style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                                   <img src="{{asset('img/upload_image.png')}}" id="preview1" alt="Image Here" style="width:150px;height:150px" class="img-thumbnail img-fluid">
                                   <input type="file" class="custom-file" name="image" id="cat1">
                              </div>
                              <button class="btn btn-success mt-sm-5" type="submit">
                                   Upload Image
                                   <i class="fa fa-upload"></i>
                              </button>
                         </form>
                    </div>
                    <div class="col-sm-4">
                         @php
                              $cat2 = App\SiteImage::where('image_for','category_2')->first();
                         @endphp
                         <form action="{{action('SiteImageController@categoryImage')}}" method="post" enctype="multipart/form-data">
                              @csrf
                              <input type="hidden" name="type" value="category_2">
                              <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>Category 2</strong>
                              </label>
                              <br>
                              @if (isset($cat2))
                                   <img src="{{asset('uploads/'.$cat2->image)}}"  alt="Image Here" style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="preview2" alt="Image Here" style="width:150px;height:150px"
                                   class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="cat2">
                              </div>
                              <button class="btn btn-success mt-sm-5" type="submit">
                                   Upload Image
                                   <i class="fa fa-upload"></i>
                              </button>
                         </form>
                    </div>
                    <div class="col-sm-4">
                         @php
                              $cat3 = App\SiteImage::where('image_for','category_3')->first();
                         @endphp
                         <form action="{{action('SiteImageController@categoryImage')}}" method="post" enctype="multipart/form-data">
                              @csrf
                              <input type="hidden" name="type" value="category_3">
                              <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>Category 3</strong>
                              </label>
                              <br>
                              @if (isset($cat3))
                                   <img src="{{asset('uploads/'.$cat3->image)}}" id="preview3" alt="Image Here" style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="preview3" alt="Image Here" style="width:150px;height:150px"
                                   class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="cat3">
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
     <div class="row">
          <div class="col-sm-12">
               @component('components.widget', ['class' => 'box-primary'])
                    <h3 class="text-primary">
                         Promo Images <small>1970x480</small>
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
                    <div class="col-sm-6">
                         @php
                              $promo1 = App\SiteImage::where('image_for','promo_1')->first();
                         @endphp
                         <form action="{{action('SiteImageController@categoryImage')}}" method="post" enctype="multipart/form-data">
                              @csrf
                              <input type="hidden" name="type" value="promo_1">
                              <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>Promo 1</strong>
                              </label>
                              <br>
                              @if (isset($promo1))
                              <img src="{{asset('uploads/'.$promo1->image)}}" alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="promo_preview1" alt="Image Here" style="width:150px;height:150px"
                                   class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="promo1">
                              </div>
                              <button class="btn btn-success mt-sm-5" type="submit">
                                   Upload Image
                                   <i class="fa fa-upload"></i>
                              </button>
                         </form>
                    </div>
                    <div class="col-sm-6">
                         @php
                              $promo_2 = App\SiteImage::where('image_for','promo_2')->first();
                         @endphp
                         <form action="{{action('SiteImageController@categoryImage')}}" method="post" enctype="multipart/form-data">
                              @csrf
                              <input type="hidden" name="type" value="promo_2">
                              <div style="margin-bottom: 20px">
                              <label for="file">
                                   <strong>Promo 2</strong>
                              </label>
                              <br>
                              @if (isset($promo_2))
                              <img src="{{asset('uploads/'.$promo_2->image)}}"  alt="Image Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <img src="{{asset('img/upload_image.png')}}" id="promo_preview2" alt="Image Here" style="width:150px;height:150px"
                                   class="img-thumbnail img-fluid">
                              <input type="file" class="custom-file" name="image" id="promo2">
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
<script src="{{asset('plugins/dropzone/min/dropzone.min.js')}}"></script>
<script src="{{asset('AdminLTE/plugins/ckeditor/ckeditor.js')}}"></script>
<script src="{{asset('js/imoViewer.js')}}"></script>
<script>
     $(function() {
          $('#cat1').imoViewer({
               'preview' : '#preview1'
          });
          $('#cat2').imoViewer({
               'preview' : '#preview2'
          });
          $('#cat3').imoViewer({
               'preview' : '#preview3'
          });
          $('#promo1').imoViewer({
               'preview' : '#promo_preview1'
          });
          $('#promo2').imoViewer({
               'preview' : '#promo_preview2'
          });
     });
     //Images DropZone
     Dropzone.options.imageForm = {
          autoProcessQueue: false,
          uploadMultiple: true,
          parallelUploads: 8,
          maxFiles: "{{$count}}",
          maxFilesize: 2,
          acceptedFiles: 'image/*',
          addRemoveLinks: true,
          success:function(file, response)
          {
               window.location = window.location.href;
          },
          error:function(file, errorMessage)
          {
               window.alert('File can not be uploaded.');
               // window.location = window.location.href;
          },
          init: function (e) {
               var myDropzone = this;   
               $('#uploadImage').on("click", function() {
                    var res = myDropzone.processQueue(); // Tell Dropzone to process all queued files.
                    // setTimeout(function(){
                    //      window.location = window.location.href;
                    // },5000);
               });
               
          }
     };
</script>
@endsection