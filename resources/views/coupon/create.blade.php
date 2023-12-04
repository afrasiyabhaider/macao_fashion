@extends('layouts.app')
@section('title', __('coupon.add'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('coupon.add')</h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
{!! Form::open(['url' => action('CouponController@store'), 'method' => 'post', 
    'id' => 'product_add_form','class' => 'product_form', 'files' => true ]) !!}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('name', __('coupon.name') . ':*') !!}
              {!! Form::text('name', !empty($duplicate_product->name) ? $duplicate_product->name : null, ['class' => 'form-control',
              'placeholder' => __('coupon.name')]); !!}
          </div>
        </div>
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('barcode', __('coupon.barcode') . ':') !!} @show_tooltip(__('tooltip.sku'))
            {!! Form::number('barcode', null, ['class' => 'form-control',
              'placeholder' => __('coupon.barcode')]); !!}
          </div>
        </div>
        <div class="col-sm-4 hide">
          <div class="form-group">
            {!! Form::label('applicable', __('coupon.applicable') . ':') !!}
              {!! Form::select('applicable', ['any' => __('coupon.any'), 'one' => __('coupon.one')], !empty($duplicate_product->applicable) ? $duplicate_product->applicable : null, ['placeholder' => __('messages.please_select'), 'required','onChange'=>'isApplicable(this);', 'class' => 'form-control select2']); !!}
          </div>
        </div>
        
         

        <div class="col-sm-4 hide">
          <div class="form-group">
            {!! Form::label('type', __('coupon.type') . ':') !!}
              {!! Form::select('type',['fixed' => __('coupon.fixed'), 'percentage' => __('coupon.percentage')], !empty($duplicate_product->type) ? $duplicate_product->type : null, ['placeholder' => __('messages.please_select'), 'required', 'class' => 'form-control select2' ]); !!}
          </div>
        </div>
        <div class="col-sm-4"  >
          <div class="form-group">
            {!! Form::label('value',  __('coupon.value') . ':*') !!}  
            {!! Form::number('value', !empty($duplicate_product->value) ? $duplicate_product->value : null , ['class' => 'form-control', 'required',
            'placeholder' => __('coupon.value'), 'min' => '0']); !!}
          </div>
        </div>
        <div class="col-sm-4 hide" id="brandArea">
          <div class="form-group">
            {!! Form::label('brand_id', __('product.brand') . ':') !!}
            <div class="input-group">
              {!! Form::select('brand_id', $brands, !empty($duplicate_product->brand_id) ? $duplicate_product->brand_id : null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
            <span class="input-group-btn">
                <button type="button" @if(!auth()->user()->can('brand.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action('BrandController@create', ['quick_add' => true])}}" title="@lang('brand.add_brand')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
              </span>
            </div>
          </div>
        </div>

      
        <div class="clearfix"></div>

        <div class="col-sm-6">
          <div class="form-group">
            {!! Form::label('barcode_type', __('product.barcode_type') . ':*') !!}
              {!! Form::select('barcode_type', $barcode_types, !empty($duplicate_product->barcode_type) ? $duplicate_product->barcode_type : $barcode_default, ['class' => 'form-control select2', 'required']); !!}
          </div>
        </div>
         <div class="col-sm-6"  >
          <div class="form-group">
            {!! Form::label('start_date', 'Last Reload Date' . ':*') !!}  
            {!! Form::input('date','start_date',date('Y-m-d'),['class' => 'form-control']) !!}
          </div>
        </div> 
       
        <div class="clearfix"></div>
        <!-- ,,,,,product_id,,,,,created_by,,,isActive,isUsed -->

        <div class="col-sm-8">
          <div class="form-group">
            {!! Form::label('details', __('coupon.details') . ':') !!}
              {!! Form::textarea('details', !empty($duplicate_product->details) ? $duplicate_product->details : null, ['class' => 'form-control','id'=>'product_description']); !!}
          </div>
        </div>
        
      </div>
    @endcomponent
 

     
    <div class="row">
    <div class="col-sm-12">
      <input type="hidden" name="submit_type" id="submit_type">
      <div class="text-center">
      <div class="btn-group">
        
        <button type="submit" value="save_n_add_another" class="btn bg-maroon submit_product_form">@lang('lang_v1.save_n_add_another')</button>

        <button type="submit" value="submit" class="btn btn-primary submit_product_form">@lang('messages.save')</button>
      </div>
      
      </div>
    </div>
  </div>
{!! Form::close() !!}
  
</section>
<!-- /.content -->

@endsection

@section('javascript')
  @php $asset_v = env('APP_VERSION'); @endphp
  <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
  <script type="text/javascript">
    function isApplicable(type)
    { 
      if(type == 'one')
      {
        $("#brandArea").show();
      }
    }
  </script>
@endsection