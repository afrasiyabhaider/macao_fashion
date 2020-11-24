<div class="box box-widget">
     <div class="box-header with-border">
          <div class="row">
               <div class="col-sm-6">
                    @if(!empty($categories))
                    {{-- @dd($categories) --}}
                         {!! Form::label('category_id', __('product.category') . ':') !!}
                         <select class="select2" id="category_id" style="width:100% !important" onchange="get_product_suggestion_list();">
               
                              <option value="all">@lang('lang_v1.all_category')</option>
               
                              @foreach($categories as $id=>$category)
                                   <option value="{{$id}}">{{$category}}</option>
                              @endforeach
               
                              {{-- @foreach($categories as $category)
                                   @if(!empty($category['sub_categories']))
                                   <optgroup label="{{$category['name']}}">
                                        @foreach($category['sub_categories'] as $sc)
                                        <i class="fa fa-minus"></i>
                                        <option value="{{$sc['id']}}">{{$sc['name']}}</option>
                                        @endforeach
                                   </optgroup>
                                   @endif
                              @endforeach --}}
                         </select>
                    @endif
               </div>
               <div class="col-sm-6">
                    <div class="form-group">
                         {!! Form::label('sub_category_id', __('product.subcategory') . ':') !!}
                         {!! Form::select('sub_category_id', [], null, ['class' => 'form-control select2', 'style' => 'width:100%',
                         'id' => 'sub_category_id', 'placeholder' => __('lang_v1.all'),'onchange'=>'get_product_suggestion_list();']); !!}
                    </div>
               </div>
          </div>
          {{-- <input type="text" name="search" id="search_box" class="form-control" placeholder="Search...."
               style="width: 75%;margin-top: 10px;"> --}}



          <div class="box-tools pull-right">
               <button type="button" class="btn btn-box-tool" data-widget="collapse"><i
                         class="fa fa-minus"></i></button>
          </div>

          <!-- /.box-tools -->
     </div>
     <!-- /.box-header -->
     <input type="hidden" id="suggestion_page" value="1">
     <div class="box-body">
          <div class="row">
               <div class="col-md-12">
                    <div class="eq-height-row" id="product_list_body"></div>
               </div>
               <div class="col-md-12 text-center" id="suggestion_page_loader" style="display: none;">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
               </div>
          </div>
     </div>
     <!-- /.box-body -->
</div>