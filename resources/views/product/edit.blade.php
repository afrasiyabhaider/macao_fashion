@extends('layouts.app')
@section('title', __('Update Product'))

@section('content')
<style type="text/css">
  .border-color {
    border-color: red;
  }

  .custom-form-control {
    border-radius: 0;
    box-shadow: none;
    border-color: #d2d6de;
    display: block;
    width: 86px;
    height: 28px;
    padding: 0px 3px;
    line-height: 1.42857143;
    color: #555;
    background-color: #fff;
    background-image: none;
    border: 1px solid #ccc;
  }
</style>

<!-- Main content -->
<section class="content">
  @if ($errors->any())
  <div class="container">
    <div class="row">
      <div class="col-sm-6">
        <div>
          <div class="alert alert-danger alert-dismissable">
            <button type="button " class="close float-right" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">
                <i class="fa fa-2x fa-times-circle"></i>
              </span>
            </button>
            <h4>
              Must Remove Following Errors
            </h4>
            <ul>
              @foreach ($errors->all() as $item)
              <li>
                {{ $item}}
              </li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif
  {!! Form::open(['url' => action('ProductController@bulkUpdate'), 'method' => 'post',
  'id' => 'product_update_form','class' => 'product_update_form', 'files' => true ]) !!}
  <input type="hidden" name="product_id" value="{{$product->id}}" id="product_id">
    @php
        if (session()->get('location_id')) {
            $loc = session()->get('location_id');
          }else{
            $loc = $product->variation_location_details()->first()->location_id;
          }
    @endphp
  <input type="hidden" name="location_id" id="location_id" value="{{$loc}}" id="product_id">
  <div class="row">
    <div class="col-sm-8">
      <h3 class="text-muted">
        Update Product
      </h3>
      @component('components.widget', ['class' => 'box-primary'])
      <div class="row mb-5">
        <div class="col-sm-4">
          <label>Supplier</label>
          <input type="hidden" name="hidden_supplier_id" id="hidden_supplier_id" value="0">
          <select name="supplier" id="supplier_id" onchange="getSupplierDetails();" class="select2 form-control">
            <optgroup>
              <option value="0">
                Please Select Supplier
              </option>
              @foreach ($suppliers as $key=>$value)
              <option value="{{$key}}" @if ($product->supplier_id == $key)
                selected
                @endif>
                {{
                                                  $value
                                             }}
              </option>
              @endforeach
            </optgroup>
          </select>
        </div>
        <div class="col-sm-4">
          <label>Category</label>
          <select name="category" id="category_id" class="select2 form-control">
            <optgroup>
              <option value="0">
                Please Select Category
              </option>
              @foreach ($categories as $key=>$value)
              <option value="{{$key}}" @if ($product->category_id == $key)
                selected
                @endif>
                {{$value}}
              </option>
              @endforeach
            </optgroup>
          </select>
        </div>
        <div class="col-sm-4">
          <label>Sub-Category</label>
          <select name="sub_category" id="sub_category_id" class="select2 form-control">
            <optgroup>
              <option value="0">
                Please Select Sub-Category
              </option>
              @foreach ($sub_categories as $key=>$value)
              <option v<option value="{{$key}}" @if ($product->sub_category_id == $key)
                selected
                @endif>
              {{$value}}
              </option>
              @endforeach
            </optgroup>
          </select>
        </div>
      </div>
      <div class="row" style="margin-top: 20px">
        <div class="col-sm-4">
          @if ($product->image != null)
          <img src="{{asset('uploads/img/'.$product->image)}}" class="img-thumbnail img-responsive" style="width:200px"
            id="img-previewer" name="image">
          @else
          <img src="{{asset('img/default.png')}}" class="img-thumbnail img-responsive" style="width:200px"
            id="img-previewer" name="image">
          @endif
          <div class="form-group">
            <label>
              Product Image:
            </label>
            <input type="file" name="file[]" id="img-input" value="{{$product->image}}">
            <small>
              <span class="help-block">
                @lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)]) <br>
                @lang('lang_v1.aspect_ratio_should_be_1_1')
              </span>
            </small>
          </div>
        </div>
        <div class="col-sm-4">
          <label>Product Name *</label>
          <input type="text" name="product_name" value="{{$product->name}}" class="req form-control" id="product_name" readonly required>
        </div>
        <div class="col-sm-4">
          <label>Refference * @show_tooltip(__('tooltip.sku'))</label>
          <input type="text" name="refference" value="{{$product->refference}}" id="refference_id"
            class="req form-control @error('refference') is-invalid @enderror" required>
        </div>
        @php
        $ut = new \App\Utils\ProductUtil();
        // dd();
        @endphp
        <div class="col-sm-4" style="margin-top: 30px">
          <label>Unit Price * </label>
          <input name="unit_price" required="true" type="text" class="req form-control col-12"
            value="{{$ut->num_f($product->variations()->first()->dpp_inc_tax)}}" id="unit_price"
            onchange="changeUnitPrice(this);">
        </div>
        <div class="col-sm-4" style="margin-top: 30px">
          <label>Sale Price * </label>
          <input name="custom_price" required="true" type="text" class="req form-control col-12"
            value="{{$ut->num_f($product->variations()->first()->sell_price_inc_tax)}}" id="sale_price"
            onchange="DittoThis(this,'profit_percent')">
        </div>
        <div class="col-sm-4" style="margin-top: 30px">
          <label>Barcode</label>
          <input required="true" type="text" class=" form-control col-12" value="{{$product->sku}}" id="sku" name="sku">
        </div>
        <div class="col-sm-4" style="margin-top: 30px">
          <label>Description</label>
          <input type="text" class=" form-control col-12" value="{{$product->description}}" id="description"
            name="description">
        </div>
      </div>
      <div class="row">
        <div class="col-sm-4"></div>
        
      </div>
      <div class="row">
        <div class="col-sm-4">
          {{-- <input name="custom_price" required="true" type="text" class="req form-control col-12" value="{{$product->color()->first()->name}}"
          id="unit_price" onchange="DittoThis(this,'single_dsp')"> --}}
          <label>Old Quantity *(Already Exists)</label>
          @php
              if (session()->get('location_id')) {
                $qty = (int)$product->variation_location_details()->where('location_id',session()->get('location_id'))->first()->qty_available;
              }else{
                $qty = (int)$product->variation_location_details()->where('location_id',1)->first()->qty_available;
              }
          @endphp
          <input name="quantity" required="true" type="number" class="req form-control col-12 @if($qty < 1)bg-red @endif"
            value="{{$qty}}" id="qty_id">
        </div>
        <div class="col-sm-4">
          <label>New Quantity * (This will be Printed)</label>
          <input name="new_quantity" autofocus type="number" class="req form-control col-12 "
            placeholder="Enter New quantity" id="print_qty_id">
        </div>
        <div class="col-sm-4">
          <label>Size *</label>
          {{-- <input name="custom_price" required="true" type="text" class="req form-control col-12" value="{{$product->sub_size()->first()->name}}"
          id="unit_price"> --}}
          <select name="size" id="sizes_id" class="select2 form-control">
            <optgroup>
              <option value="0">
                Please Select
              </option>
              @foreach ($sizes as $key=>$item)
              <option value="{{$item->id}}" @if ($item->id == $product->sub_size_id)
                selected
                @endif>
                {{$item->name}}
              </option>
              @endforeach
            </optgroup>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-4">
          <label>Color *</label>
            <select name="color" id="color_id" class="select2 form-control">
              <optgroup>
                <option value="0">
                  Please Select
                </option>
                @foreach ($colors as $key=>$item)
                <option value="{{$key}}" @if ($key==$product->color()->first()->id)
                  selected
                  @endif>
                  {{$item}}
                </option>
                @endforeach
              </optgroup>
            </select>
        </div>
        <div class="col-sm-6">
          <button type="submit" class="btn btn-success col-12 fa-2x" style="width:150px;padding:10px;font-size:20px;margin-top: 20px;"
            id="btnSubmit">
            <i class="fa fa-save"></i>
            Edit
          </button>
          <button class="btn btn-success col-12 fa-2x" onclick="updateAll(this);"
            style="width:150px;padding:10px;font-size:20px;margin-top: 20px;" type="button">
            <i class="fa fa-globe"></i>
            Update All
          </button>
        </div>
      </div>
      <div class="row" style="margin-top: 30px">
        <div class="col-sm-12">
          <input type="hidden" name="submit_type" id="submit_type">
          <div class="text-center row">
            <div class="btn-group">
              {{-- <a href="{{url('products/bulk_add')}}" class="btn btn-info">
              <i class="fa fa-plus"></i>
              Add New Product
              </a> --}}
              {{-- onclick="addThis();" --}}
              {{-- <button type="submit" class="btn btn-success col-12 fa-2x" style="width:150px;padding:10px;font-size:20px"
                id="btnSubmit">
                <i class="fa fa-save"></i>
                @lang('messages.update')
              </button> --}}
            </div>
          </div>
        </div>
      </div>
      @endcomponent
    </div>
    <div class="col-sm-4">
      {{-- <h1>Hello There</h1> --}}
      {{-- box-widget --}}
      <h3 class="text-muted">Choose Product</h3>
      <div class="box box-primary">
        <div class="box-header with-border">

          <div class="">
            <div class="col-12">
              @if(!empty($noRefferenceProducts))
              <select class="select2" id="product_category" style="width:45% !important">
                <option value="all">@lang('lang_v1.all_category')</option>
                @foreach($noRefferenceProducts as $noRefference)
                <option value="{{$noRefference['id']}}">
                  {{$noRefference['name']}}
                </option>
                @endforeach
                @foreach($noRefferenceProducts as $category)
                @if(!empty($category['sub_categories']))
                <optgroup label="{{$category['name']}}">
                  @foreach($category['sub_categories'] as $sc)
                  <i class="fa fa-minus"></i>
                  <option value="{{$sc['id']}}">{{$sc['name']}}</option>
                  @endforeach
                </optgroup>
                @endif
                @endforeach
              </select>
              @endif

              @if(!empty($suppliers))
              &nbsp;
              <select class="select2" id="supplier" style="width:45% !important">
                <option value="all">All Suppliers</option>
                @foreach($suppliers as $key=>$noRefference)
                <option value="{{$key}}" @if ($key==$product->supplier_id)
                  selected
                  @endif>
                  {{$noRefference}}
                </option>
                @endforeach
              </select>
              @endif
            </div>
          </div>
          <div class="col-12 mt-10">
            @if(!empty($business_locations))
            &nbsp;
            {{-- @dd(session()->get('location_id')) --}}
              <select class="select2" id="location_id" style="width:45% !important">
                {{-- <option value="all">All Locations</option> --}}
                @foreach($business_locations as $key=>$noRefference)
                <option value="{{$key}}"@if (session()->get('location_id') && $key == session()->get('location_id'))
                  selected
                  @endif>
                   
                  {{$noRefference}}
                </option>
                @endforeach
              </select>
              @endif
          </div>



          <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
          </div>

          <!-- /.box-tools -->
        </div>
        <!-- /.box-header -->
        <input type="hidden" id="suggestion_page" value="1">
        <div class="box-body">
          <div class="row">
            <div class="col-sm-12">
              <div class="eq-height-row" id="product_list_body"></div>
            </div>
            <div class="col-sm-12 text-center" id="suggestion_page_loader" style="display: none;">
              <i class="fa fa-spinner fa-spin fa-2x"></i>
            </div>
          </div>
        </div>
        <!-- /.box-body -->
      </div>
      {{-- @include('sale_pos.partials.product_list_box') --}}
    </div>
  </div>
  {{-- <div class="row">
			<div class="col-sm-12">
				<input type="hidden" name="submit_type" id="submit_type">
				<div class="text-center row">
					<div class="btn-group">
						<!--<button type="button"   class="btn bg-maroon "  onclick="clearAll();">Clear & Move NEXT</button> -->

						<button type="button" class="btn btn-success col-12" onclick="addThis();">
							<i class="fa fa-save"></i>
							@lang('messages.update')</button>
						<button type="submit"  class="btn btn-primary hide" id="btnSubmit">
							<i class="fa fa-save"></i>
							@lang('messages.update')
						</button>
					</div>
				</div>
			</div>
		</div> --}}
  {{-- 
	<hr/>
	<div class="row box box-primary" id="c"> 
      <div class="col-sm-12">
          <div class="col-sm-1"><b>No</b></div>
          <div class="col-sm-1"><b>Supplier</b></div>
          <div class="col-sm-1"><b>Category</b></div>
          <div class="col-sm-1"><b>SubCategory</b></div>
          <div class="col-sm-1"><b>Unit</b></div>
          <div class="col-sm-1"><b>Name</b></div>
          <div class="col-sm-1"><b>Refference</b></div>
          <div class="col-sm-1"><b>Unit Price</b></div>
          <div class="col-sm-1"><b>Sale Price</b></div>
          <div class="col-sm-1"><b>Color</b></div>
          <div class="col-sm-1"><b>Qty</b></div>
          <div class="col-sm-1"><b>Size</b></div>
          <div class="col-sm-1"><b>Image</b></div>
          <div class="col-sm-1"><b>Action</b></div>
      </div>
    </div>
    <div class="row box box-primary" id="bulk_product_home"> 
      
	</div> --}}

  {!! Form::close() !!}

</section>
<!-- /.content -->
<!-- quick product modal -->
<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" id="btnClose">&times;</button>
        <h4 class="modal-title">Choose Size</h4>
      </div>
      <div class="row" style="margin: 10px;">
        <div class="col-sm-12">
          @foreach($sizes as $id => $objSize)
          <div class="col-sm-4">
            <input type="radio" id="btnSize_{{$objSize->id}}" name="chooseSize" class="" value="{{$objSize->id}}">
            {{-- onclick="getSizes({{$objSize->id}})" --}}
            <label class="custom-control-label" for="btnSize_{{$objSize->id}}">{{$objSize->name}} </label>
          </div>
          @endforeach
        </div>
      </div>
      <div class="col-sm-12 @if(!session('business.enable_brand')) hide @endif">
        <div class="form-group">
          {!! Form::label('color_id', __('product.color') . ':') !!}
          <div class="input-group">
            {!! Form::select('color_idc', $colors, null, ['placeholder' => __('messages.please_select'), 'class' => 'req
            form-control','id' => 'color_idc', 'required' => 'true']); !!}
            <span class="input-group-btn">
              <button type="button" @if(!auth()->user()->can('color.create')) disabled @endif class="btn btn-default
                bg-white btn-flat btn-modal" data-href="{{action('ColorController@create', ['quick_add' => true])}}"
                title="@lang('color.add_brand')" data-container=".view_modal"><i
                  class="fa fa-plus-circle text-primary fa-lg"></i></button>
            </span>
          </div>
        </div>
      </div>
      {{-- <div class="row" style="margin: 10px;">
			<div class="col-sm-12">
				@foreach($sizes as $id => $objSize)
					<button type="button" class="col-sm-4 btn btn-md btn-danger" id="btnSize_{{$objSize->id}}"
      onclick="getSizes({{$objSize->id}})">{{$objSize->name}}</button>
      @endforeach
    </div>
  </div> --}}
  <div class="row" id="sizeArea">
    <div class="col-sm-12">
      <div class="col-sm-4"><b>Size</b></div>
      <div class="col-sm-4"><b>Sub-Size</b></div>
      <div class="col-sm-3"><b>Qty</b></div>
      <div class="col-sm-1"><b>X</b></div>
    </div>
  </div>
  <div class="modal-footer">
    <!-- <button type="button" class="btn btn-danger text-left " style="float: left;" onclick="AddSize();" >+</button> -->
    <button type="button" class="btn btn-success" onclick="addAnother();" data-dismiss="modal">Add This</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
  </div>
</div>

</div>
</div>


@endsection

@section('javascript')
@php $asset_v = env('APP_VERSION'); @endphp
<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
  var url = {!! json_encode(url('')) !!};
     $(document).ready(function (){
    // $("#refference_id").focus();
    $("#location_id").select().change();
    $("#category_id").select().change();
    setTimeout(function () {
      $('#sub_category_id').val('{{$product->sub_category_id}}');
      $('#sub_category_id').select2().change();
    },1000);
		// console.log(url);
		// addAnother();
		var supplier_id = {!! json_encode($product->supplier_id) !!};
		var supplier_id = {!! json_encode($product->supplier_id) !!};
		// $("#product_update_id").val({!! json_encode($product->id) !!});
		// getSupplierDetailsOnload(supplier_id);
		// getDataOnLoad();

		//     window.onbeforeunload = function() {
//         return "Do you really want to leave this page?";
//         //if we return nothing here (just calling return;) then there will be no pop-up question at all
//         //return;
// 	  }
    });

    function updateAll(e) {
      var form = $("#product_update_form");
      event.preventDefault();
      form.attr('action','{{url("products/update-all")}}');
      form.submit();
    }

     objPNC = <?=$pnc?>;
     rowSize = 0;
     function editPnc(x)
     {
		if(x < 1){
			alert("Please Enter Valid Positve Row No ");return(false);
		}
		var index = x -1;
		if( index < objPNC.length-1)
		{
			pncRow = index;
		var arrName = objPNC[pncRow].split("@");
		$("#name").val(arrName[1]);
		$("#name_id").val(arrName[0]);

		}else
		{
			alert("Row Series not Found \n Your Product Series Last Row is "+objPNC.length-1);
			return(false);
		}
     }
     $("#sub_category_id").change(function () {
		// $("#refference_id").focus();
        $("#print_qty_id").focus();
     });
     function changeUnitPrice(obj)
     {
		//  console.log(obj.value);
        if(obj.value < 1)
        {
          alert("Please Enter Positve Value");
		  return(false);
        }
        $("#single_dpp").val(obj.value).trigger("change");
        // console.log($("#single_dpp").val());
     }
     function setColorWithSize(row)
     {  

       $("#datasize_"+row).attr("data-color",$("#color_id_"+row+" option:selected").val());
       $("#datasize_"+row).attr("data-color-name",$("#color_id_"+row+" option:selected").text());
      alert($("#datasize_"+row).attr("data-color"));
      alert($("#datasize_"+row).attr("data-color-name"));

     }
     function setValue(obj)
     {
      obj.defaultValue = obj.value;
     }
	$("#color_idc").change(function(){
		var chooseSizeRadio = $("input[name='chooseSize']").is(':checked')
		if(chooseSizeRadio){
			// console.log($("input[name='chooseSize']:checked").val());
			sizeId = $("input[name='chooseSize']:checked").val();
			name = sizeId;
		}else{
			alert("Please Select Size First "); 
			return(false);
		}
		var html = $("#sizeArea").html(); 
		$.ajax({
			type:'GET',
			url:url+'/sizes/getSubSize/'+name, 
			success:function(data){
				if(data.success)
				{ 
					var obj = data.msg;
					size_idtext = $("#btnSize_"+sizeId).html();
					
					for (i = 0  ; i < obj.length; i++) {
					rowSize++ ;
					html += "<div class=' col-sm-12' id='sizeRow_"+rowSize+"'> ";
					html += "<div class=' col-sm-2'><select class='form-control' readonly><option value='"+sizeId+"'>"+size_idtext+"</option></select></div>";
					html += "<div class=' col-sm-2'><select class='form-control'  readonly><option value='"+obj[i]['id']+"'>"+obj[i]['name']+"</option></select></div>";
					html += "<div class=' col-sm-2'><select onchange='setColorWithSize("+i+");' class='form-control' form='product_add_form'  readonly required id='color_id_"+i+"'> <option selected='selected' value='"+$("#color_idc option:selected").val()+"'>"+$("#color_idc option:selected").text()+"</option></select></div>";

					html += "<div class=' col-sm-3'><input tab-index='"+i+"' onChange='setValue(this);' type='number' data-size='"+sizeId+"' data-size-name='"+size_idtext+"' data-size-sub='"+obj[i]['id']+"' data-size-sub-name='"+obj[i]['name']+"' data-color='"+$("#color_idc option:selected").val()+"' data-color-name='"+$("#color_idc option:selected").text()+"' class='form-control sizeQty'  value='1'  id='datasize_"+i+"'/></div>";

					html += "<div class=' col-sm-1'><button onclick='removeSize("+rowSize+")' class='btn btn-sm btn-danger'>X</button></div>";
					html += "</div>";
					}
					$("#sizeArea").html(html);

				}else
				{
					alert(" "+data.msg);
					$("#amount_"+rowIndex).val(0).change();
					$("#note_"+rowIndex).val('');
			ResetFields(rowIndex);
				}
			}
		});
	});
     function getSizes(sizeId)
     {
      if($("#color_idc").val() == "")
      {
        alert("Please Select Color First "); 
        return(false);
      }
      name = sizeId;
      var html = $("#sizeArea").html(); 
      $.ajax({
           type:'GET',
           url: url+'/sizes/getSubSize/'+name, 
           success:function(data){
              if(data.success)
              { 
                var obj = data.msg;
                size_idtext = $("#btnSize_"+sizeId).html();
                   
                for (i = 0  ; i < obj.length; i++) {
                  rowSize++ ;
                   html += "<div class=' col-sm-12' id='sizeRow_"+rowSize+"'> ";
                   html += "<div class=' col-sm-2'><select class='form-control' readonly><option value='"+sizeId+"'>"+size_idtext+"</option></select></div>";
                   html += "<div class=' col-sm-2'><select class='form-control'  readonly><option value='"+obj[i]['id']+"'>"+obj[i]['name']+"</option></select></div>";
                   html += "<div class=' col-sm-2'><select onchange='setColorWithSize("+i+");' class='form-control' form='product_add_form'  readonly required id='color_id_"+i+"'> <option selected='selected' value='"+$("#color_idc option:selected").val()+"'>"+$("#color_idc option:selected").text()+"</option></select></div>";

                   html += "<div class=' col-sm-3'><input tab-index='"+i+"' onChange='setValue(this);' type='number' data-size='"+sizeId+"' data-size-name='"+size_idtext+"' data-size-sub='"+obj[i]['id']+"' data-size-sub-name='"+obj[i]['name']+"' data-color='"+$("#color_idc option:selected").val()+"' data-color-name='"+$("#color_idc option:selected").text()+"' class='form-control sizeQty'  value='1'  id='datasize_"+i+"'/></div>";

                   html += "<div class=' col-sm-1'><button onclick='removeSize("+rowSize+")' class='btn btn-sm btn-danger'>X</button></div>";
                   html += "</div>";
                }
                  $("#sizeArea").html(html);

              }else
              {
                alert(" "+data.msg);
                $("#amount_"+rowIndex).val(0).change();
                $("#note_"+rowIndex).val('');
          ResetFields(rowIndex);
              }
           }
        });
     }
     var reffCount  = <?=$refferenceCount;?>;
     var pad = "0000"
     function getSupplierDetails()
     {
      name = $("#supplier_id option:selected").val();
	//  console.log(name);
      $.ajax({
           type:'GET',
           url: '/sizes/getSupplierDetails/'+name, 
           success:function(data){
              $("#temp_reff").val(data); 
              var n = reffCount;
              var result = (pad+n).slice(-pad.length);
              // console.log('Refference : '+data+result);
              $("#refference_id").val("");
              $("#refference_id").val(data+result);
              $("#print_qty_id").focus();
            //
                
           }
        });

        $("#print_qty_id").focus();
     }
     function getSupplierDetailsOnload(id)
     {
      $.ajax({
           type:'GET',
           url:url+'/sizes/getSupplierDetails/'+id, 
           success:function(data){
            $("#temp_reff").val(data); 
            var n = reffCount;
            var result = (pad+n).slice(-pad.length);
            // console.log('Refference : '+result);
            $("#refference_id").val(data+result );
            //
                
           }
        });
        $("#print_qty_id").focus();
     }
     function updateRefference()
     { 
      var n = reffCount;
            var result = (pad+n).slice(-pad.length);
      $("#refference").val($("#temp_reff").val()+result);
     }
     function AddSize()
     {
      var size_id = $("#add_size_id option:selected").val();
      var size_idtext = $("#add_size_id option:selected").text();
      var sub_size_id = $("#add_sub_size_id option:selected").val();
      var sub_size_idtext = $("#add_sub_size_id option:selected").text();
      if(size_id == undefined || size_id == "" || sub_size_id == undefined || sub_size_id == "" )
      {
        alert("You have to Select Size and Sub Size ");return(false);
      }

      if($("#add_qty").val() == "0" || $("#add_qty").val() == "")
      {
        alert("You have to Add Qty ");return(false);
      }
      

      $("#sizeArea").append(html);
      $("#add_qty").val("0").focus();
     }
     function removeSize(row)
     {
      $("#sizeRow_"+row).remove();
     }
    function addThis()
    {
      if($(".bulkProducts").length <= 0)
      {
        alert("Please Add Some Product First Then Click Save");return(false);
      }
      $(".req").each(function() {
          $(this).removeAttr("required");
      });
      
      $("#btnSubmit").click();
    }
    function DittoThis(obj,target)
    {
      $("#"+target).val(obj.value);
      $("#profit_percent").val(0);
	//  console.log($("#profit_percent").value);
      $("#"+target).change();
    }

    function clearAll(IsAnother = 0)
    {
      $(".sizeQty").val("0");
      countSize = 0;
      $("#sizeArea").empty();
      var fieldsArr = ["supplier_id", "brand_id", "category_id", "name", "sku","upload_image","unit_price"];
      var notIncludeArr = ["single_dpp", "single_dpp_inc_tax", "single_dsp", "single_dsp_inc_tax", "profit_percent","upload_image"];
      var ignoreArr = ["supplier_id", "brand_id", "category_id", "sku", "name", "unit_price","custom_price"];


      $(".req").each(function() {
        if(fieldsArr.includes($(this).attr("id")) && IsAnother)
        {

        }else
        {
          if(fieldsArr.includes($(this).attr("id")))
          {
            if(!ignoreArr.includes($(this).attr("id")))
            {
              $.trim($(this).val(null));
            }
          }else
          {
            if($(this).attr("id") != "refference")
            {
              if(!ignoreArr.includes($(this).attr("id")))
              {
                $.trim($(this).val(""));
              }
            }
          }
        }

      });
      $(".fileinput-remove").click();
      $("#name").focus(); 
      if(objPNC[pncRow] == undefined)
      { 
         $("#name").val("");
         $("#name_id").val(0); 
      }else
      {
        var arrName = objPNC[pncRow].split("@");
         $("#name").val(arrName[1]);
         $("#name_id").val(arrName[0]); 
      }
      
       updateRefference();
    }
    function removeThisRow(row)
    {
      $("#product_row_"+row).remove();
      pncRow--;
      // reffCount--;
    }
    var row =1;
    var lastBG = " padding: 10px; ";
    var pncRow = 0;
    function addAnotherSize()
    {
      addAnother(1);
    }
    var IsAnother =0;
    /**
    * Below function is responsible for adding data in div #bulk_product_home
    *
    **/
    function addAnother(WantIsAnother = 0)
    {
      $("#product_add_form :input.redborder").removeClass("redborder");
      // Check all required fields have text, you can even check other values
      var isErrorFree = true;
      var emptyFields = "";
      $(".req").each(function() {
          if ($.trim($(this).val()) == ""){
            $(this).addClass("redborder");
            isErrorFree = false;
            emptyFields += $(this).attr("id")+"\n";
          } 
      });
      QtyErrorFree = false;
      $(".sizeQty").each(function() {
          if ($.trim($(this).val()) > "0"){ 
            QtyErrorFree = true;
          } 
      });
      if(!QtyErrorFree)
      {
        alert("Please Choose Size and Qty");return(false);
      }

      if(!isErrorFree)
      {
        alert("Please Fill All Required Fields \n"+emptyFields); return(false);
      }
        Style = " style='padding: 10px; '";
		// 3c8dbc
      if(row%2 == 0 ) Style = "style='background-color:#45b9d6;padding: 10px;color:white'";
      if(IsAnother) Style = lastBG;
      lastBG = Style;
      var html = '<div class="col-sm-12 " '+Style+' id="product_row_'+row+'"> ';
      html += '<div class="col-sm-1">'+row+'</div>';
      var fieldsArr = ["supplier_id", "unit_id", "unit_price", "category_id" , "name" , "refference", "sku", "custom_qty", "custom_price", "color_id", "size_id","upload_image"];
      size = 1; 

      $(".req").each(function() { 
     //    console.log($(this).attr("id"));
        if(fieldsArr.includes($(this).attr("id")))
        {
          if($(this).attr("type") == undefined)
          {
            if($(this).attr("id") == "category_id")
            {
              html += ' <div class="col-sm-'+size+'"><select class="custom-form-control" name="'+$(this).attr("id")+'[]" title="'+$(this).attr("id")+'"><option value="'+$(this).val()+'">'+$( "#"+$(this).attr("id")+" option:selected" ).text()+'</option></select></div>';
              html += ' <div class="col-sm-'+size+'"><select class="custom-form-control" name="sub_category_id[]" title="sub_category_id"><option value="'+$("#sub_category_id").val()+'">'+$( "#sub_category_id option:selected" ).text()+'</option></select></div>';
            }else
            {
              html += ' <div class="col-sm-'+size+'"><select class="custom-form-control" name="'+$(this).attr("id")+'[]" title="'+$(this).attr("id")+'"><option value="'+$(this).val()+'">'+$( "#"+$(this).attr("id")+" option:selected" ).text()+'</option></select></div>';

            }

          }else
          {
			// Custom_price
             html += ' <div class="col-sm-'+size+'"><input title="'+$(this).attr("id")+'" name="'+$(this).attr("id")+'[]" type="'+$(this).attr("type")+'" class="custom-form-control" value="'+$(this).val()+'"/></div>';
          }
           
        }else
        {
          html += ' <div class="col-sm-'+size+' hide ss '+$(this).attr("id")+'"><input  name="'+$(this).attr("id")+'[]" type="text" class="custom-form-control" value="'+$(this).val()+'"/></div>';

        }
         size = 1; 
      });

      var tempHTML = html;
      picRow = row;
      $(".sizeQty").each(function() {
          if ($.trim($(this).val()) > "0"){ 
            html = tempHTML;
            html += ' <div class="col-sm-'+size+'"><select title="Color" class="custom-form-control" name="color_id[]"><option value="'+$(this).attr("data-color")+'">'+$(this).attr("data-color-name")+'</option></select> </div>'; 

            html += ' <div class="col-sm-'+size+'"><input class="custom-form-control bulkProducts" title="QTY" name="qty[]" type="number" value="'+$(this).val()+'" /> </div>';
		//  <select title="SIZE" class="custom-form-control" name="size_id[]"><option value="'+$(this).attr("data-size")+'">'+$(this).attr("data-size-name")+'</option></select>
            html += '<div class="col-sm-'+size+'"><input type="hidden" name="size_id[]" value="'+$(this).val()+'"><select title="Sub Size" class="custom-form-control" name="sub_size_id[]"><option value="'+$(this).attr("data-size-sub")+'">'+$(this).attr("data-size-sub-name")+'</option></select></div>'; 
		if ($(".file-preview-image").attr("src")==undefined) {
			if($("#product_image").attr("src")==undefined )
			{
			var file = ""; 
			html += ' <div class="col-sm-1"><img src="{{asset("img/default.png")}}" width="86px" height="28px" /> <span class="hide" id="file_'+picRow+'"></span></div>';
			}else
			{
			var file = $("#upload_image").clone();
			//     .file-preview-image
			file.attr("name","file[]");
			html += ' <div class="col-sm-1"><img src="'+$("#product_image").attr("src")+'" width="86px" /> <span class="hide" id="'+row+'_file_'+picRow+'"></span></div>';
			}	
		}else{
			var file = $("#upload_image").clone();
			//     .file-preview-image
			file.attr("name","file[]");
			html += ' <div class="col-sm-1"><img src="'+$(".file-preview-image").attr("src")+'" width="86px" /> <span class="hide" id="'+row+'_file_'+picRow+'"></span></div>';
		}
             html += '<div class="col-sm-1""><button class="btn btn-danger" onclick="removeThisRow('+row+');"><i class="fa fa-trash"></i></button></div>';
               
              html += ' <div class="clearfix"></div></div>';
              PreviousHTML = $("#bulk_product_home").html();

              $("#bulk_product_home").prepend(html);
              $("#"+row+"_file_"+picRow).append(file);
          } 
          picRow++;
      }); 
      row++;
	//  Changed
	/**
	* Clear Product Details
	*
	*/
     //  IsAnother = 0;
     //  if(WantIsAnother) IsAnother=1;
     //  pncRow++;
     //  reffCount++;

     //  if(IsAnother)
     //  {
     //    clearAll(1);

     //  }else
     //  {
     //    clearAll();
     //  }
    }
    /**
    * Below function is responsible for adding data in div #bulk_product_home on 
    * page load
    *
    **/
    function addDataOnLoad(supplier_id = null,unit_id = null, unit_price = null,category_id = null, name =  null, refference = null,sku = null, custom_qty = null, custom_price = null, color_id = null, size_id = null, upload_image = null)
    {
      $("#product_add_form :input.redborder").removeClass("redborder");
      // Check all required fields have text, you can even check other values
      var isErrorFree = true;
      var emptyFields = "";
     //  $(".req").each(function() {
     //      if ($.trim($(this).val()) == ""){
     //        $(this).addClass("redborder");
     //        isErrorFree = false;
     //        emptyFields += $(this).attr("id")+"\n";
     //      } 
     //  });
     //  QtyErrorFree = false;
     //  $(".sizeQty").each(function() {
     //      if ($.trim($(this).val()) > "0"){ 
     //        QtyErrorFree = true;
     //      } 
     //  });
     //  if(!QtyErrorFree)
     //  {
     //    alert("Please Choose Size and Qty");return(false);
     //  }

     //  if(!isErrorFree)
     //  {
     //    alert("Please Fill All Required Fields \n"+emptyFields); return(false);
     //  }
        Style = " style='padding: 10px; '";
		// 3c8dbc
      if(row%2 == 0 ) Style = "style='background-color:#45b9d6;padding: 10px;color:white'";
      if(IsAnother) Style = lastBG;
      lastBG = Style;
      var html = '<div class="col-sm-12 " '+Style+' id="product_row_'+row+'"> ';
      html += '<div class="col-sm-1">'+row+'</div>';
      var fieldsArr = ["supplier_id", "unit_id", "unit_price", "category_id" , "name" , "refference", "sku", "custom_qty", "custom_price", "color_id", "size_id","upload_image"];
      size = 1; 

      $(".req").each(function() { 
     //    console.log($(this).attr("id"));
        if(fieldsArr.includes($(this).attr("id")))
        {
          if($(this).attr("type") == undefined)
          {
            if($(this).attr("id") == "category_id")
            {
              html += ' <div class="col-sm-'+size+'"><select class="custom-form-control" name="'+$(this).attr("id")+'[]" title="'+$(this).attr("id")+'"><option value="'+$(this).val()+'">'+$( "#"+$(this).attr("id")+" option:selected" ).text()+'</option></select></div>';
              html += ' <div class="col-sm-'+size+'"><select class="custom-form-control" name="sub_category_id[]" title="sub_category_id"><option value="'+$("#sub_category_id").val()+'">'+$( "#sub_category_id option:selected" ).text()+'</option></select></div>';
            }else
            {
              html += ' <div class="col-sm-'+size+'"><select class="custom-form-control" name="'+$(this).attr("id")+'[]" title="'+$(this).attr("id")+'"><option value="'+$(this).val()+'">'+$( "#"+$(this).attr("id")+" option:selected" ).text()+'</option></select></div>';

            }

          }else
          {
			// custom_price
             html += ' <div class="col-sm-'+size+'"><input title="'+$(this).attr("id")+'" name="'+$(this).attr("id")+'[]" type="'+$(this).attr("type")+'" class="custom-form-control" value="'+$(this).val()+'"/></div>';
          }
           
        }else
        {
          html += ' <div class="col-sm-'+size+' hide ss '+$(this).attr("id")+'"><input  name="'+$(this).attr("id")+'[]" type="text" class="custom-form-control" value="'+$(this).val()+'"/></div>';

        }
         size = 1; 
      });

      var tempHTML = html;
      picRow = row;
      $(".sizeQty").each(function() {
          if ($.trim($(this).val()) > "0"){ 
            html = tempHTML;
            html += ' <div class="col-sm-'+size+'"><select title="Color" class="custom-form-control" name="color_id[]"><option value="'+$(this).attr("data-color")+'">'+$(this).attr("data-color-name")+'</option></select> </div>'; 

            html += ' <div class="col-sm-'+size+'"><input class="custom-form-control bulkProducts" title="QTY" name="qty[]" type="number" value="'+$(this).val()+'" /> </div>';
	//  <select title="SIZE" class="custom-form-control" name="size_id[]"><option value="'+$(this).attr("data-size")+'">'+$(this).attr("data-size-name")+'</option></select>
            html += '<div class="col-sm-'+size+'"><input type="hidden" name="size_id[]" value="'+$(this).val()+'"><select title="Sub Size" class="custom-form-control" name="sub_size_id[]"><option value="'+$(this).attr("data-size-sub")+'">'+$(this).attr("data-size-sub-name")+'</option></select></div>'; 
		if ($(".file-preview-image").attr("src")==undefined) {
			if($("#product_image").attr("src")==undefined )
			{
			var file = ""; 
			html += ' <div class="col-sm-1"><img src="{{asset("img/default.png")}}" width="86px" height="28px" /> <span class="hide" id="file_'+picRow+'"></span></div>';
			}else
			{
			var file = $("#upload_image").clone();
			//     .file-preview-image
			file.attr("name","file[]");
			html += ' <div class="col-sm-1"><img src="'+$("#product_image").attr("src")+'" width="86px" /> <span class="hide" id="'+row+'_file_'+picRow+'"></span></div>';
			}	
		}else{
			var file = $("#upload_image").clone();
			//     .file-preview-image
			file.attr("name","file[]");
			html += ' <div class="col-sm-1"><img src="'+$(".file-preview-image").attr("src")+'" width="86px" /> <span class="hide" id="'+row+'_file_'+picRow+'"></span></div>';
		}
             html += '<div class="col-sm-1""><button class="btn btn-danger" onclick="removeThisRow('+row+');"><i class="fa fa-trash"></i></button></div>';
               
              html += ' <div class="clearfix"></div></div>';
              PreviousHTML = $("#bulk_product_home").html();

              $("#bulk_product_home").prepend(html);
              $("#"+row+"_file_"+picRow).append(file);
          } 
          picRow++;
      }); 
      row++;
	//  Changed
	/**
	* Clear Product Details
	*
	*/
     //  IsAnother = 0;
     //  if(WantIsAnother) IsAnother=1;
     //  pncRow++;
     //  reffCount++;

     //  if(IsAnother)
     //  {
     //    clearAll(1);

     //  }else
     //  {
     //    clearAll();
     //  }
    }

    function openPrintProducts()
    {
      link = "<?=url('products');?>";
     window.open(link, "_blank", "toolbar=yes,scrollbars=yes,resizable=yes,top=500,left=200,width=1200,height=800");
    }


//     window.onbeforeunload = function() {
//         return "Do you really want to leave this page?";
//         //if we return nothing here (just calling return;) then there will be no pop-up question at all
//         //return;
// 	  }
	  
	// Get Products on right side
		function get_product_suggestion_list(category_id, supplier_id, location_id, url = null) {

			if($('div#product_list_body').length == 0) {
				return false;
			}
			var app_url = {!! json_encode(url('/')) !!}
			if (url == null) {
				url = app_url+'/sells/pos/get-product-refference-suggestion';
			}
			$('#suggestion_page_loader').fadeIn(700);
			var page = $('input#suggestion_page').val();
			if (page == 1) {
				$('div#product_list_body').html('');
			}
			if ($('div#product_list_body').find('input#no_products_found').length > 0) {
				$('#suggestion_page_loader').fadeOut(700);
				return false;
			}
			// console.log("Category Id : " + category_id);
			// console.log("Supplier Id : " + supplier_id);
			$.ajax({
				method: 'GET',
				url: url,
				data: {
					category_id: category_id,
					supplier_id: supplier_id,
					location_id: location_id,
					page: page,
				},
				dataType: 'html',
				success: function(result) {
					// console.log(result);
					$('div#product_list_body').append(result);
					$('#suggestion_page_loader').fadeOut(700);
				},
			});
		}
	// });
	$('div#product_list_body').on('scroll', function() {
		if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
			var page = parseInt($('#suggestion_page').val());
			page += 1;
			$('#suggestion_page').val(page);
			var location_id = $('select#location_id').val();
			var category_id = $('select#product_category').val();
			var brand_id = $('select#supplier_id').val();

			get_product_suggestion_list(category_id, brand_id, location_id);
		}
	});

	get_product_suggestion_list(
        $('select#product_category').val(),
        $('select#supplier').val(),
        $('select#location_id').val(),
        null
	);
	$('select#product_category, select#supplier, select#location_id').on('change', function(e) {
        $('input#suggestion_page').val(1);
        var location_id = $('select#location_id').val();
        if (location_id != '' || location_id != undefined) {
            get_product_suggestion_list(
                $('select#product_category').val(),
                $('select#supplier').val(),
                $('select#location_id').val(),
                null
            );
        }
    });

    // Start Here
    $(document).on('click', 'div.product_box', function() {
          //Check if location is not set then show error message.
        if ($('select#location_id').val() == '') {
            toastr.warning(LANG.select_location);
        } else {
            pos_product_row($(this).data('variation_id'),$("select#location_id").val());
        }
    });
	function pos_product_row(variation_id,location_id) {
		// console.log(variation_id);
		//Get item addition method
		var item_addtn_method = 0;
		var add_via_ajax = true;
		if (item_addtn_method == 0) {
			add_via_ajax = true;
		} else {
			var is_added = false;
		}

		if (add_via_ajax) {
			// console.log("Variation ID : "+variation_id);
			$.ajax({
          method: 'GET',
          // SellPosController @ line 2484

          url:url+ '/sells/pos/get_bulk_product_detail/' + variation_id+'/'+location_id,
          async: false,
          // data: {
          // 	// product_row: product_row,
          // 	customer_id: customer_id,
          // 	is_direct_sell: is_direct_sell,
          // 	price_group: price_group,
          // },
          dataType: 'json',
          success: function(result) {
            if (result != 'null') {
              // console.log(result.category);
              if (result.product.supplier_id) {
                var selected_value = result.product.supplier_id;
                // Change Select2 Option on Ajax Data retrival
                $('#supplier_id').val(selected_value);
                $('#supplier_id').select2().trigger('change');
                $("#hidden_supplier_id").val(result.product.supplier_id);
              }else{
                $("#supplier_id").val(0).change();
                toastr.error('Supplier not found. Please select manually.');

              }
              // $("#category_id").val(result.product.category_id).change();
              // $("#sub_category_id").val(result.product.sub_category_id).change();

              $("#category_id").val(result.product.category_id);
              $("#category_id").select2().change();
              //Changing Sub Category
              setTimeout(function () {
                $("#sub_category_id").val(result.product.sub_category_id);
                $("#sub_category_id").select2().change();
              },1000);

              // toastr.success('Please select Sub-Category manually.');
              if (result.product.category_id == null) {
                toastr.error('Category and Sub-Category not found. Please select manually.');
              }
              
              // .attr('selected',true);
              var img = result.product.image;
              if (img == null) {
                img = url+'/img/default.png';
              }else{
                img = url+'/uploads/img/'+img;
              }
              $("#product_name").val(result.product.name);
              $("#sku").val(result.product.sku);
              $("#description").val(result.product.description);
              $("#product_id").val(result.product.id);
              $("#img-previewer").attr("src",img);
              // $("#img-input").val(img);
              $("#name_id").val(0); //important
              // console.log("Getting Ref : "+result.product.refference);
              // console.log("Before Ref: "+$("#refference_id").val());
              // $("#refference_id").val(result.product.refference);
              setTimeout(function () {
                $("#refference_id").val(result.product.refference);
              },1000);
              // console.log("After Ref: "+$("#refference_id").val());
              // console.log("Ref : "+result.product.refference);
              $("#unit_price").val(result.unit_price);
              // $("#unit_price").val(result.product_price.dpp_inc_tax);
              // console.log(result);
              $("#single_dpp").val(result.single_dpp).trigger("change");
              // $("#single_dpp").val(result.product_price.dpp_inc_tax).trigger("change");
              // {{$ut->num_f($product->variations()->first()->dpp_inc_tax)}}
              // result.product_price.sell_price_inc_tax
              
              $("#sale_price").val(result.sale_price);
              $("#color_id").val(result.color.id).change();
              var qty = parseInt(result.variation_location_details.qty_available);
              if (qty<1) {
                $("#qty_id").addClass('bg-red')
              }else{
                $("#qty_id").removeClass('bg-red')
              }
              $("#qty_id").val(qty);
              $("#location_id").val(result.variation_location_details.location_id);
              $("#sizes_id").val(result.sub_size.id).change();
              // var product = result.product;
              // var supplier = result.supplier;
              // var price = result.product_price;
              // var purchase_line = result.purchase_lines;
              // Add Data into 
              // addDataOnLoad(supplier.id,product.unit_id,price.dpp_inc_tax,result.category.id,product.name,product.refference,product.sku,purchase_line.quantity,price.sell_price_inc_tax,product.color_id,product.size_id,product.image);

              // DittoThis(result.product_price.sell_price_inc_tax,result.product_price.dpp_inc_tax);
              
              
            } else {
              toastr.error('No record found. Please try another product or insert record manually.');
            }
            
          },
			});
		}
	}

	function getDataOnLoad() {
		var variation_id = $("#product_variation_id").val();
		// console.log("Var Id: "+ variation_id);
		$.ajax({
				method: 'GET',
				// SellPosController @ line 2484

				url:url+ '/sells/pos/get_bulk_product_detail/' + variation_id,
				async: false,
				// data: {
				// 	// product_row: product_row,
				// 	customer_id: customer_id,
				// 	is_direct_sell: is_direct_sell,
				// 	price_group: price_group,
				// },
				dataType: 'json',
				success: function(result) {
					// console.log(result);
					if (result != "null") {
						var product = result.product;
						var supplier = result.supplier;
            // cosnole.log("Below Supplier = "+supplier);
						var price = result.product_price;
						var purchase_line = result.purchase_lines;
						// Add Data into 
						addDataOnLoad(supplier.id,product.unit_id,price.dpp_inc_tax,result.category.id,product.name,product.refference,product.sku,purchase_line.quantity,price.sell_price_inc_tax,product.color_id,product.size_id,product.image);
            $("#location_id").val(result.variation_location_details.location_id);
					}
				},
			});
	}

</script>

@endsection