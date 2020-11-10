@extends('layouts.app')
@section('title','Website Products')
@section('css')
<link rel="stylesheet" href="{{asset('plugins/dropzone/min/dropzone.min.css')}}">
@endsection
@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
     <h1>
          Special Categories
     </h1>
</section>

<!-- Main content -->
<section class="content">
     @component('components.widget', ['class' => 'box-primary'])
     <div class="row">
          <div class="col-sm-6">
               <h3 class="text-primary">
                    Product Details
                    <i class="fa fa-info-circle"></i>
               </h3>
               <table class="table table-bordered table-active text-center">
                    <thead>
                         <tr>
                              <th>Primary Image</th>
                              <th>Name</th>
                              <th>Refference</th>
                              <th>Price</th>
                              <th>Remove Image</th>
                         </tr>
                    </thead>
                    <tbody>
                         <tr>
                              <td>
                                   @if ($product->image != null)
                                   <img src="{{asset('uploads/img/'.$product->image)}}"
                                        class="img-thumbnail img-responsive" style="width:100px" name="image">
                                   @else
                                   <img src="{{asset('img/default.png')}}" class="img-thumbnail img-responsive"
                                        style="width:100px" name="image">
                                   @endif
                              </td>
                              <td>
                                   {{$product->name}}
                              </td>
                              <td>
                                   {{$product->refference}}
                              </td>
                              <td>
                                   {{$product->variations()->first()->dpp_inc_tax}}
                              </td>
                              <td>
                                   -
                              </td>
                         </tr>
                         @php
                             $count=4;
                         @endphp
                         @foreach ($product_images as $item)
                         @php
                             $count--;
                         @endphp
                         <tr>
                              <td colspan="1">
                                   <img src="{{asset('uploads/'.$item->image)}}" class="img-thumbnail img-responsive" style="width:100px"
                                        name="image">
                              </td>
                              <td>
                                   {{$product->name}}
                              </td>
                              <td>
                                   <strong>
                                        Image {{$loop->iteration}}
                                   </strong>
                              </td>
                              <td>
                                   <i class="fa fa-euro"></i>
                                   {{$product->variations()->first()->dpp_inc_tax}}
                              </td>
                              <td>
                                   <form action="{{action('WebsiteController@deleteImage',$item->id)}}" method="post">
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
     </div>
     @endcomponent
     @component('components.widget', ['class' => 'box-primary'])
     <div class="row">
          <div class="col-sm-12">
               <h3 class="text-primary">
                    Product Images
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
               <form action="{{action('WebsiteController@addImages',$product->id)}}" method="post"
                    enctype="multipart/form-data" class="dropzone" id="imageForm">
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
     </div>
     @endcomponent
</section>
@endsection
@section('javascript')
<script src="{{asset('plugins/dropzone/min/dropzone.min.js')}}"></script>
<script>
     Dropzone.options.imageForm = {
          autoProcessQueue: false,
          uploadMultiple: true,
          parallelUploads: 3,
          maxFiles: "{{$count}}",
          maxFilesize: 2,
          acceptedFiles: 'image/*',
          addRemoveLinks: true,
          init: function (e) {
          
          var myDropzone = this;
          
               $('#uploadImage').on("click", function() {
                    myDropzone.processQueue(); // Tell Dropzone to process all queued files.
                    // window.location = window.location.href;
               });
               
               // Event to send your custom data to your server
               myDropzone.on("sending", function(file, xhr, data) {
               
               // First param is the variable name used server side
               // Second param is the value, you can add what you what
               // Here I added an input value
               data.append("your_variable", $('#your_input').val());
               });
               
               }
          };
</script>
@endsection