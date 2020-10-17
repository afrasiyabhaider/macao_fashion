<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('ProductNameCategoryController@store'), 'method' => 'post', 'id' => 'ProductNameCategory_add_form' ]) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'category.add_category' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('row_no', 'Row No:*') !!}
          {!! Form::text('row_no', null, ['class' => 'form-control', 'required', 'placeholder' => '1 or 2 or 3 ..?']); !!}
      </div>
      <div class="form-group">
        {!! Form::label('name', __( 'category.category_name' ) . ':*') !!}
          {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'category.category_name' )]); !!}
      </div>
 
       
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->