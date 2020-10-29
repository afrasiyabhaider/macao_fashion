@php
    $location = App\SalePriority::first();
    $data['priority'][1] = $location->priority_1;
    $data['priority'][2] = $location->priority_2;
    $data['priority'][3] = $location->priority_3;
    $data['priority'][4] = $location->priority_4;
//     $i=1;
//     dd($data["priority_$i"],$location);
@endphp
@extends('layouts.app')
@section('title','Sale Priority')
@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
     <h1>
          Sale Priority
     </h1>
</section>

<!-- Main content -->
<section class="content">
     @component('components.widget', ['class' => 'box-primary'])
     <div class="row">
          <div class="col-sm-12">
               <h3 class="text-primary">
                    Sale Priority
                    <i class="fa fa-sort"></i>
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
               <form action="{{action('WebsiteController@setPriority')}}" method="post">
                    @csrf
                    @for ($i=0;$i<4;$i++)
                        <div class="form-row">
                             <div class="col-sm-12">
                             <label for="">
                                  Priority {{$i+1}}
                             </label>
                              <select name="location[]" id="" class="select2 form-control">
                                   <option value="0">
                                        Select Location for {{$i}} Priority
                                   </option>
                                   @foreach ($locations as $item)
                                   
                                        <option @if (isset($data['priority'][$i+1]) && ($data['priority'][$i+1] == $item->id))
                                            selected
                                        @endif value="{{$item->id}}">
                                             {{$item->name}}
                                        </option>
                                   @endforeach
                              </select>
                             </div>
                        </div>
                    @endfor
                    <div class="form-row">
                         <div class="col-sm-12" >
                              <button class="btn btn-success col-sm-2" style="margin-top: 20px">
                                   Save
                              </button>
                         </div>
                    </div>
               </form>
          </div>
     </div>
     @endcomponent
</section>
@endsection
@section('javascript')
@endsection