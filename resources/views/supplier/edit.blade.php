<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('SupplierController@update', [$supplier->id]), 'method' => 'PUT', 'id' => 'supplier_edit_form' ]) !!}

     <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">@lang( 'supplier.edit_brand' )</h4>
     </div>

     <div class="modal-body">
          <div class="form-group">
               {!! Form::label('name', __( 'supplier.brand_name' ) . ':*') !!}
               {!! Form::text('name', $supplier->name, ['class' => 'form-control', 'required', 'placeholder' => __( 'supplier.supplier_name' )]); !!}
          </div>

          <div class="form-group">
          {!! Form::label('description', __( 'supplier.short_description' ) . ':') !!}
               {!! Form::text('description', $supplier->description, ['class' => 'form-control','placeholder' => __( 'supplier.short_description' )]); !!}
          </div>
     </div>

     <div class="modal-footer">
          <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
     </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->