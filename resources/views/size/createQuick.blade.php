<div class="modal-dialog" role="document">
  <div class="modal-content">
    @php
    if(!empty($parent_sizes))
    {
      $formName = "sub_size_add_form";
    }else
    {
      $formName = "size_add_form";
    }
    @endphp

    {!! Form::open(['url' => action('SizesController@store'), 'method' => 'post', 'id' => $formName ]) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'size.add_size' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __( 'size.size_name' ) . ':*') !!}
          {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'size.size_name' )]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('short_code', __( 'size.code' ) . ':') !!}
          {!! Form::text('short_code', null, ['class' => 'form-control', 'placeholder' => __( 'size.code' )]); !!}
          <p class="help-block">{!! __('lang_v1.size_code_help') !!}</p>
      </div>
      @if(!empty($parent_sizes))
        <div class="form-group">
            <div class="checkbox">
              <label>
                 {!! Form::checkbox('add_as_sub_cat', 1, false,[ 'class' => 'toggler', 'data-toggle_id' => 'parent_cat_div' ]); !!} @lang( 'size.add_as_sub_size' )
              </label>
            </div>
        </div>
        <div class="form-group hide" id="parent_cat_div">
          {!! Form::label('parent_id', __( 'size.select_parent_size' ) . ':') !!}
          {!! Form::select('parent_id', $parent_sizes, null, ['class' => 'form-control']); !!}
        </div>
      @endif
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->