@extends('layouts.app')
@section('title','Website Products')
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
                         </tr>
                         @foreach ($product_images as $item)
                         <tr>
                              <td colspan="2">
                                   <img src="{{asset('uploads/img/'.$item->image)}}"
                                        class="img-thumbnail img-responsive" style="width:100px" name="image">
                              </td>
                              <td>
                                   <strong>
                                        Image {{$loop->iteration}}
                                   </strong>
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
                    enctype="multipart/form-data">
                    @csrf
                    <div class="form-row">
                         <div class="col-sm-3">
                              <label for="file">
                                   <strong>Image 1</strong>
                              </label>
                              <br>
                              @if (isset($product_images[0]))
                              <img src="{{asset('uploads/img/'.$product_images[0]->image)}}" id="preview1"
                                   alt="Image 1 Preview Here" style="width:150px;height:150px"
                                   class="img-thumbnail img-fluid">
                              @else
                              <img src="{{asset('img/upload_image.png')}}" id="preview1" alt="Image 1 Preview Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <input type="file" class="form-control" name="image[]" id="image1"
                                   @if(isset($product_images[0])) style="display:none" @endif>
                         </div>
                         <div class="col-sm-3">
                              <label for="file">
                                   <strong>Image 2</strong>
                              </label>
                              <br>
                              @if (isset($product_images[1]))
                              <img src="{{asset('uploads/img/'.$product_images[1]->image)}}" id="preview2"
                                   alt="Image 2 Preview Here" style="width:150px;height:150px"
                                   class="img-thumbnail img-fluid">
                              @else
                              <img src="{{asset('img/upload_image.png')}}" id="preview2" alt="Image 2 Preview Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <input type="file" class="form-control" name="image[]" id="image2"
                                   @if(isset($product_images[1])) style="display:none" @endif>
                         </div>
                         <div class="col-sm-3">
                              <label for="file">
                                   <strong>Image 3</strong>
                              </label>
                              <br>
                              @if (isset($product_images[2]))
                              <img src="{{asset('uploads/img/'.$product_images[2]->image)}}" id="preview3"
                                   alt="Image 3 Preview Here" style="width:150px;height:150px"
                                   class="img-thumbnail img-fluid">
                              @else
                              <img src="{{asset('img/upload_image.png')}}" id="preview3" alt="Image 3 Preview Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              <input type="file" class="form-control" name="image[]" id="image3"
                                   @if(isset($product_images[2])) style="display:none" @endif>
                         </div>
                         <div class="col-sm-3">
                              <label for="file">
                                   <strong>Image 4</strong>
                              </label>
                              <br>
                              @if (isset($product_images[3]))
                              <img src="{{asset('uploads/img/'.$product_images[3]->image)}}" id="preview2"
                                   alt="Image 2 Preview Here" style="width:150px;height:150px"
                                   class="img-thumbnail img-fluid">
                              @else
                              <img src="{{asset('img/upload_image.png')}}" id="preview4" alt="Image 4 Preview Here"
                                   style="width:150px;height:150px" class="img-thumbnail img-fluid">
                              @endif
                              {{-- <img src="{{asset('img/upload_image.png')}}" id="preview4" alt="Image 4 Preview Here"
                              width="100px" height="100px" class="img-thumbnail img-fluid"> --}}
                              <input type="file" class="form-control" name="image[]" id="image4"
                                   @if(isset($product_images[3])) style="display:none" @endif>
                         </div>
                    </div>
                    <div class="form-row">
                         <div class="col-sm-12" style="padding-top:20px;text-align: center">
                              @if(!isset($product_images[0]) || !isset($product_images[1]) || !isset($product_images[2])
                              || !isset($product_images[3]))
                              <button type="submit" class="btn btn-success">
                                   Upload Images
                                   <i class="fa fa-upload"></i>
                              </button>
                              @else
                              <div class="alert alert-info">
                                   <h4>
                                        All images are Uploaded Please Remove any of them to upload new one
                                   </h4>
                              </div>
                              @endif
                         </div>
                    </div>
               </form>
          </div>
     </div>
     @endcomponent
</section>
@endsection
@section('javascript')
<script src="{{asset('js/imoViewer.js')}}"></script>
<script>
     $(function() {
               $('#image1').imoViewer({
                    'preview' : '#preview1'
               });
               $('#image2').imoViewer({
                    'preview' : '#preview2'
               });
               $('#image3').imoViewer({
                    'preview' : '#preview3'
               });
               $('#image4').imoViewer({
                    'preview' : '#preview4'
               });
          });
</script>
@endsection