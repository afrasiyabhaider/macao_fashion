<div class="modal-dialog" role="document">
  <div class="modal-content">

    <!--{!! Form::open(['url' => action('ProductNameCategoryController@storeExcell'), 'method' => 'post', 'id' => 'ProductNameCategory_add_form' ]) !!}-->
     <form class="form-horizontal" method="POST" action="{{action('ProductNameCategoryController@storeExcell')}}" enctype="multipart/form-data">
                            {{ csrf_field() }}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'category.add_category' )</h4>
    </div>

    <div class="modal-body">
        <a class="btn btn-md btn-warning" href='http://macaobe.com/uploads/template_product_name.csv'>DOWNLOAD EXCEL TEMPLATE (CSV) </a>
      <div class="form-group">
       
        <input id="csv_file" type="file" class="form-control" name="csv_file" required>

          
          <input type="submit" class="btn btn-md btn-success col-md-12" value="UPLOAD THIS " /> 
      </div>
       
       
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>
    </form>

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->