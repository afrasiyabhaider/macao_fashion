<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action('CouponController@saveQuickProduct'), 'method' => 'post', 'id' => 'quick_add_product_form' ]) !!}

    <div class="modal-header">
	    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	      <h4 class="modal-title" id="modalTitle">@lang( 'coupon.add' ) </h4>
    </div>
    <div class="modal-body">
      <div class="row">
        <section class="content"> 
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('name', __('coupon.name') . ':*') !!}
              {!! Form::text('name', !empty($duplicate_product->name) ? $duplicate_product->name : "Coupon ", ['class' => 'form-control',
              'placeholder' => __('coupon.name')]); !!}
          </div>
        </div>
        <input type="hidden" name="location_id"  value="{{$location_id}}" required>
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('barcode', __('coupon.barcode') . ':') !!} @show_tooltip(__('tooltip.sku'))
            {{-- {!! Form::text('barcode', $RandomId, ['class' => 'form-control', 'required', --}}
            {!! Form::text('barcode', null, ['class' => 'form-control', 
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
          
        </section>
        <!-- /.content -->
         
    </div>
    <div class="modal-footer">
      <button type="submit" class="btn btn-primary" id="submit_quick_product">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script type="text/javascript">
  $(document).ready(function(){

    CKEDITOR.config.height = 60;
    CKEDITOR.replace('product_description');
    $("form#quick_add_product_form").validate({
      rules: {
          sku: {
              remote: {
                  url: "/products/check_product_sku",
                  type: "post",
                  data: {
                      sku: function() {
                          return $( "#sku" ).val();
                      },
                      product_id: function() {
                          if($('#product_id').length > 0 ){
                              return $('#product_id').val();
                          } else {
                              return '';
                          }
                      },
                  }
              }
          },
          expiry_period:{
              required: {
                  depends: function(element) {
                      return ($('#expiry_period_type').val().trim() != '');
                  }
              }
          }
      },
      messages: {
          sku: {
              remote: LANG.sku_already_exists
          }
      },
      submitHandler: function (form) {
        
        var form = $("form#quick_add_product_form");
        var url = form.attr('action');
        form.find('button[type="submit"]').attr('disabled', true);
        $.ajax({
            method: "POST",
            url: url,
            dataType: 'json',
            data: $(form).serialize(),
            success: function(data){
                $('.quick_add_product_modal').modal('hide');
                if( data.success){
                    toastr.success(data.msg);
                    if (typeof get_purchase_entry_row !== 'undefined') {
                      get_purchase_entry_row( data.product.id, 0 );
                    }
                    $(document).trigger({type: "quickProductAdded", 'product': data.product, 'variation': data.variation });
                } else {
                    toastr.error(data.msg);
                }
            },
             error: function(data){
                var errors = data.responseJSON;
                $.each( errors.errors, function( key, value ) {
                  toastr.error(value[0]);
                });
            }
        });
        return false;
      }
    });
  });
</script>