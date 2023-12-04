<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action('GiftCardController@saveQuickProduct'), 'method' => 'post', 'id' => 'quick_add_product_form' ]) !!}

    <div class="modal-header">
	    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	      <h4 class="modal-title" id="modalTitle">@lang( 'gift.add' )</h4>
    </div>
    <div class="modal-body">
      <div class="row">
        <section class="content"> 
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                <div class="col-sm-4">
                  <div class="form-group">
                    {!! Form::label('name', __('gift.name') . ':*') !!}
                      {!! Form::text('name', !empty($duplicate_product->name) ? $duplicate_product->name : "GiftCard ", ['class' => 'form-control', 'required',
                      'placeholder' => __('gift.name')]); !!}
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    {!! Form::label('barcode', __('gift.barcode') . ':') !!} @show_tooltip(__('tooltip.sku'))
                    {!! Form::text('barcode', null, ['class' => 'form-control',
                      'placeholder' => __('gift.barcode')]); !!}
                  </div>
                </div>
                <input type="hidden" name="location_id"  value="{{$location_id}}">
                <div class="col-sm-4 hide">
                  <div class="form-group">
                    {!! Form::label('applicable', __('gift.applicable') . ':') !!}
                      {!! Form::select('applicable', ['any' => __('gift.any'), 'one' => __('gift.one')], !empty($duplicate_product->applicable) ? $duplicate_product->applicable : null, ['placeholder' => __('messages.please_select'), 'required','onChange'=>'isApplicable(this);', 'class' => 'form-control select2']); !!}
                  </div>
                </div>
                
                 

                <div class="col-sm-4 hide">
                  <div class="form-group">
                    {!! Form::label('type', __('gift.type') . ':') !!}
                      {!! Form::select('type',['fixed' => __('gift.fixed'), 'percentage' => __('gift.percentage')], !empty($duplicate_product->type) ? $duplicate_product->type : null, ['placeholder' => __('messages.please_select'), 'required', 'class' => 'form-control select2' ]); !!}
                  </div>
                </div>
                <div class="col-sm-4"  >
                  <div class="form-group">
                    {!! Form::label('value',  __('gift.value') . ':*') !!}  
                    {!! Form::number('value', !empty($duplicate_product->value) ? $duplicate_product->value : null , ['class' => 'form-control', 'required',
                    'placeholder' => __('gift.value'), 'min' => '0']); !!}
                  </div>
                </div> 
                <div class="clearfix"></div>

                <div class="col-sm-4">
                  <div class="form-group">
                    {!! Form::label('barcode_type', __('product.barcode_type') . ':*') !!}
                      {!! Form::select('barcode_type', $barcode_types, !empty($duplicate_product->barcode_type) ? $duplicate_product->barcode_type : $barcode_default, ['class' => 'form-control select2', 'required']); !!}
                  </div>
                </div>
                 <div class="col-sm-4"  >
                  <div class="form-group">
                    {!! Form::label('start_date', 'Last Reload Date' . ':*') !!}  
                    {!! Form::input('date','start_date',date('Y-m-d'),['class' => 'form-control']) !!}
                  </div>
                </div>
                <div class="col-sm-4"  >
                  <div class="form-group">
                    {!! Form::label('expiry_date',  __('gift.expiry_date') . ':*') !!}  
                    {!! Form::input('date','expiry_date',date('Y-m-d',strtotime('+1 year')),['class' => 'form-control']) !!}
                  </div>
                </div>
               
                <div class="clearfix"></div>
                <div class="col-sm-8">
                  <div class="form-group">
                    {!! Form::label('details', __('gift.details') . ':') !!}
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
            }
        });
        return false;
      }
    });
  });
</script>