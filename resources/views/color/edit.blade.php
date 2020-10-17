<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('ColorController@update', [$color->id]), 'method' => 'PUT', 'id' => 'color_edit_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'color.edit_brand' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __( 'color.brand_name' ) . ':*') !!}
          {!! Form::text('name', $color->name, ['class' => 'form-control', 'required', 'placeholder' => __( 'color.brand_name' )]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('description', __( 'color.short_description' ) . ':') !!}
          {!! Form::text('description', $color->description, ['class' => 'form-control','placeholder' => __( 'color.short_description' )]); !!}
      </div>
      <div class="form-group">
          <label>
              Select Color <span class="text-danger">*</span>
          </label>
          <input type="color" name="color_code" value="{{$color->color_code}}" required>
        </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->