@extends('layouts.app')
@section('title', __('gift.edit'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('gift.edit')</h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
{!! Form::open(['url' => action('CouponController@update' , [$product->id] ), 'method' => 'PUT', 'id' => 'product_add_form',
        'class' => 'product_form', 'files' => true ]) !!}
    <input type="hidden" id="product_id" value="{{ $product->id }}">

    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('name', __('gift.name') . ':*') !!}
                  {!! Form::text('name', $product->name, ['class' => 'form-control', 'required',
                  'placeholder' => __('gift.name')]); !!}
              </div>
            </div>

             <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('barcode', __('gift.barcode') . ':') !!} @show_tooltip(__('tooltip.sku'))
            {!! Form::text('barcode',  $product->barcode, ['class' => 'form-control',
              'placeholder' => __('gift.barcode')]); !!}
          </div>
        </div>
        <div class="col-sm-4 hide">
          <div class="form-group">
            {!! Form::label('applicable', __('gift.applicable') . ':') !!}
              {!! Form::select('applicable', ['any' => __('gift.any'), 'one' => __('gift.one')], $product->applicable, ['placeholder' => __('messages.please_select'), 'required', 'class' => 'form-control select2']); !!}
          </div>
        </div>

        <div class="col-sm-4 hide">
          <div class="form-group">
            {!! Form::label('type', __('gift.type') . ':') !!}
              {!! Form::select('type',['fixed' => __('gift.fixed'), 'percentage' => __('gift.percentage')], $product->applicable, ['placeholder' => __('messages.please_select'), 'required', 'class' => 'form-control select2' ]); !!}
          </div>
        </div>
        <div class="col-sm-4"  >
          <div class="form-group">
            {!! Form::label('value',  __('gift.value') . ':*') !!}  
            {!! Form::number('value', $product->value, ['class' => 'form-control', 'required',
            'placeholder' => __('gift.value'), 'min' => '0']); !!}
          </div>
        </div>
         

            <div class="clearfix"></div>
            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('barcode_type', __('product.barcode_type') . ':*') !!}
                  {!! Form::select('barcode_type', $barcode_types, $product->barcode_type, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'required']); !!}
              </div>
            </div>
            <div class="col-sm-4"  >
              <div class="form-group">
                {!! Form::label('start_date', 'Last Reload Date' . ':*') !!}  
                {!! Form::input('date','start_date',date('Y-m-d',strtotime($product->start_date)),['class' => 'form-control']) !!}
              </div>
            </div>
            <div class="col-sm-4"  >
              <div class="form-group">
                {!! Form::label('expiry_date',  __('gift.expiry_date') . ':*') !!}  
                {!! Form::input('date','expiry_date',date('Y-m-d',strtotime($product->expiry_date)),['class' => 'form-control']) !!}
              </div>
            </div>

            <div class="clearfix"></div>

            <div class="col-sm-8">
              <div class="form-group">
                {!! Form::label('details', __('gift.details') . ':') !!}
                  {!! Form::textarea('details', $product->details, ['class' => 'form-control','id'=>'product_description']); !!}
              </div>
            </div>

            <div class="col-sm-4">
              <div class="col-sm-12">
                <div class="form-group">
                  {!! Form::label('consume_date',  __('gift.consume_date') . ':*') !!}  
                  {!! Form::input('text','consume_date',$product->consume_date,['class' => 'form-control']) !!}
                </div>
              </div>
               <div class="col-sm-12">
                <div class="form-group">
                {!! Form::label('isActive', __('gift.isActive') . ':*') !!}
                  {!! Form::select('isActive', ['active' => 'Active', 'expired'=> 'Expired', 'consumed'=> 'Consumeded', 'cancell'=> 'Cancelled'], $product->isActive, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'required']); !!}
                </div>
              </div>
            </div>

            </div>
    @endcomponent

     

  <div class="row">
    <input type="hidden" name="submit_type" id="submit_type">
        <div class="col-sm-12">
          <div class="text-center">
            <div class="btn-group">
              <button type="submit" value="save_n_add_another" class="btn bg-maroon submit_product_form">@lang('lang_v1.update_n_add_another')</button>

              <button type="submit" value="submit" class="btn btn-primary submit_product_form">@lang('messages.update')</button>
            </div>
          </div>
        </div>
  </div>
{!! Form::close() !!}
</section>
<!-- /.content -->

@endsection

@section('javascript')
  <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
@endsection