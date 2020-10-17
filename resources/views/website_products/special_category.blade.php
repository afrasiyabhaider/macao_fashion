@extends('layouts.app')
@section('title','Website Products')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
		Special Categories
    </h1>
</section>

<!-- Main content -->
<section class="content">
	@component('components.widget', ['class' => 'box-primary'])
		<div class="row">
		<div class="col-sm-6">
			<h3 class="text-primary">
				Product Details
				<i class="fa fa-info-circle"></i>
			</h3>
				<table class="table table-bordered table-active text-center">
				<thead>
					<tr>
						<th>Image</th>
						<th>Name</th>
						<th>Refference</th>
						<th>Price</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<div class="col-md-12">
								@if ($product->image != null)
									<img src="{{asset('uploads/img/'.$product->image)}}" class="img-thumbnail img-responsive" style="width:100px" id="img-previewer" name="image">
								@else
									<img src="{{asset('img/default.png')}}" class="img-thumbnail img-responsive" style="width:100px" id="img-previewer" name="image">	    
								@endif
							</div>
						</td>
						<td>
							{{
								$product->name
							}}
						</td>
						<td>
							{{
								$product->refference
							}}
						</td>
						<td>
							{{-- <i class="fa fa-euro"></i> --}}
							{{$product->variations()->first()->dpp_inc_tax}}
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<h3 class="text-primary">
					Move to Special Category
					<i class="fa fa-sign-in"></i>
				</h3>
			</div>
		</div>
		@if ($errors->any())
			<div class="row">
				<div class="col-sm-6">
					<div class="alert alert-danger">
						<h4>
							Remove Following error
						</h4>
						<ul>
							@foreach ($errors->all() as $error)
								<li>{{ $error }}</li>
							@endforeach
						</ul>
					</div>
				</div>
			</div>
		@endif
		<form action="{{action('WebsiteController@addspecialCategories')}}" method="post" class="padding-10">
			@csrf
			<input type="hidden" name="p_id" value="{{$product->id}}">
			<div class="row margin-bottom-12">
				<div class="col-sm-4">
					<label class="container">Make Featured
						<input type="checkbox" value="1" name="featured" onclick="enableSaveButton();" id="featured" @if (!is_null($special_product) && $special_product->featured)
							checked="checked"
						@endif>
						<span class="checkmark" @if (!is_null($special_product) && $special_product->featured)
							checked="checked"
						@endif></span>
					</label>
				</div>
				<div class="col-sm-4">
					<label class="container">New Arrival
						<input type="checkbox" value="1" name="new_arrival" onclick="enableSaveButton();" id="new_arrival" @if (!is_null($special_product) && $special_product->new_arrival)
							checked="checked"
						@endif>
						<span class="checkmark" @if (!is_null($special_product) && $special_product->new_arrival)
							checked="checked"
						@endif></span>
					</label>
				</div>
			</div>
			<div class="row margin-bottom">
				<div class="col-sm-3">
					<label class="container">Sale
						<input type="checkbox" value="1" name="sale" onclick="enableSaveButton();" id="sale"  @if (!is_null($special_product) && $special_product->sale)
							checked="checked"
						@endif>
						<span class="checkmark"  @if (!is_null($special_product) && $special_product->sale)
							checked="checked"
						@endif></span>
					</label>
				</div>
				@php
					$ut = new \App\Utils\ProductUtil();
				// dd();
				@endphp
				<div class="col-sm-4" id="sale_value">
					{{-- <label>
						Sale Percentage
					</label> --}}
					<input type="text"  @if (!is_null($special_product) && $special_product->after_discount)
					value="{{$ut->num_f($special_product->after_discount)}}"
					@endif name="after_discount" class="form-control" id="sale_percent" placeholder="Enter Sale Price e.g 35,79" min="1">
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<textarea name="description" id="product_description" cols="30" rows="10" class="col-sm-6">
						<span disabled class="read-only">
							@if (!is_null($special_product))
								{{$special_product->description}}
							@endif
						</span>
					</textarea>
				</div>
			</div>
			<div class="row" style="margin-top:20px">
				<div class="col-sm-6 justify-content-center">
					<button type="submit" class="btn btn-success col-sm-3" id="submit_btn">
						Save
						<i class="fa fa-save"></i>
					</button>
				</div>
			</div>
		</form>
@endcomponent
</section>

@endsection
@section('javascript')
<style>
/* The container */
.container {
  display: block;
  position: relative;
  padding-left: 35px;
  margin-bottom: 12px;
  cursor: pointer;
  font-size: 18px;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

/* Hide the browser's default checkbox */
.container input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

/* Create a custom checkbox */
.checkmark {
  position: absolute;
  top: 0;
  left: 0;
  height: 25px;
  width: 25px;
  background-color: #eee;
}

/* On mouse-over, add a grey background color */
.container:hover input ~ .checkmark {
  background-color: #ccc;
}

/* When the checkbox is checked, add a blue background */
.container input:checked ~ .checkmark {
  background-color: #2196F3;
}

/* Create the checkmark/indicator (hidden when not checked) */
.checkmark:after {
  content: "";
  position: absolute;
  display: none;
}

/* Show the checkmark when checked */
.container input:checked ~ .checkmark:after {
  display: block;
}

/* Style the checkmark/indicator */
.container .checkmark:after {
  left: 9px;
  top: 5px;
  width: 5px;
  height: 10px;
  border: solid white;
  border-width: 0 3px 3px 0;
  -webkit-transform: rotate(45deg);
  -ms-transform: rotate(45deg);
  transform: rotate(45deg);
}
</style>
	<script src="{{asset('AdminLTE/plugins/ckeditor/ckeditor.js')}}"></script>
	<script type="text/javascript">
		$(function () {
			CKEDITOR.config.height = 120;
			CKEDITOR.replace('product_description');
		});
		$("#sale_value").hide();
		$("#submit_btn").attr("disabled",true);
		/**
		* It will display sale percentage value input if value
		* Existed in DB
		*
		*/
		if ($("#sale_percent").val()) {
			$("#sale_value").show(300);
			$("#sale_value>input").attr("required","required");
			$("#submit_btn").removeAttr("disabled");
		}
		/**
		* It will display sale percentage value input
		*
		*/
		$("#sale").click(function() {
			if($(this).is(":checked")) {
				$("#sale_value").show(300);
				$("#sale_value>input").attr("required","required");
			} else {
				$("#sale_value").hide(200);
				$("#sale_value>input").removeAttr("required");
			}
		});
		/**
		* It will check on Click if checked then save button *
		* will be enabled
		*
		*/
		function enableSaveButton() {
			if($("#featured").val() || $("#new_arrival").val() || $("#sale").val()) {
				$("#submit_btn").removeAttr("disabled");
			}else{
				$("#submit_btn").attr("disabled",true);
			}
		}
		/**
		* It will check on Load if value existed in DB then 
		* save button will be enabled
		*
		*/
		if($("#featured").val() || $("#new_arrival").val() || $("#sale").val()) {
			$("#submit_btn").removeAttr("disabled");
		}else{
			$("#submit_btn").attr("disabled",true);
		}
	</script>
@endsection