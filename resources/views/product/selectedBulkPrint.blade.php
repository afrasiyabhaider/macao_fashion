@extends('layouts.onlyApp')
@section('title', __('sale.products'))
<style type="text/css">
	.heh{
		height: 118px;
		width: 31% !important;
		margin : 10px 8px 0 8px;
	}
	.showV{
		visibility: visible;
	}
	.hideV{
		visibility: hidden;
	}
	.jumbotron{
		padding-left: 20px;
		padding-top: 10px !important;
		background-color: whitesmoke !important;
	}
</style>
@section('content')
<section class="content-header">
	<div class="jumbotron">
		<div class="p-5">
			<h1 class="display-2">
				Print Product Barcodes 
				<i class="fa fa-barcode"></i>
			</h1>
			<a href="{{url()->previous()}}" class="btn btn-md btn-primary">
				<i class="fa fa-arrow-circle-left"></i>
				Go Back
			</a>
			<button class="btn btn-md btn-success" onclick="window.print();return false;">
				<i class="fa fa-print"></i>
				Print
			</button> 
			<div class="row">
				<div class="col-md-12">
					<h3>
						Printing Options
					</h3>
					<div class="col-md-1">
						<input type="checkbox" data-id="name" value="" class=" checkPrint" checked="false"> Name
					</div>
					{{-- <div class="col-md-2"><input type="checkbox" data-id="price" value="" class=" checkPrint" checked="false"> Price</div> --}}
					<!-- <div class="col-md-2"><input type="checkbox" data-id="sku" value="" class=" checkPrint" checked="false"> Barcode</div> -->
					<div class="col-md-1">
						<input type="checkbox" data-id="refference" value="" class=" checkPrint" checked="false"> Refference
					</div>
					<div class="col-md-1">
						<input type="checkbox" data-id="size" value="" class=" checkPrint" checked="false"> Size</div>
					<div class="col-md-1">
						<input type="checkbox" data-id="color" value="" class=" checkPrint" checked="false"> Color
					</div>
					{{-- <div class="col-md-1">
						<input type="checkbox" data-id="cat" value="" class=" checkPrint" checked="false">  Category
					</div> --}}
					<div class="col-md-1">
						<input type="checkbox" data-id="subcat" value="" class=" checkPrint" checked="false"> SubCategory
					</div>
				</div>
			</div>
		</div>
	</div>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
	<div class="row">
		<div class="col-md-4 col-xs-4 heh mt-sm-3">
			{{$location}}
		</div>
		@php $i=0; @endphp
		@foreach($product as $objProduct)
			@php $i++; @endphp
			@for($j=0;$j<$objProduct['count'];$j++)
				<div class="col-md-4 col-xs-4 heh mt-sm-3">
					<div class="">
						<div class="col-xs-9 text-left"   style="font-size: 12px" >
							<strong class="printList" data-id="subcat">{{$objProduct['category']}}</strong>-
							<strong class="printList" data-id="name">{{$objProduct['name']}} </strong>
						</div>
						<div  class="col-xs-3 printList text-right" data-id="size"  style="font-size: 12px"> 
							{{$objProduct['size'] }} 
						</div>

					</div>
					<div class="col-md-12 col-xs-12 col-sm-12">
						<img style="width: 100%"  src="data:image/png;base64,{{DNS1D::getBarcodePNG($objProduct['barcode'], 'C128', 2,30,array(39, 48, 54), false)}}">
						@php
							$barcodeArr = str_split($objProduct['barcode'], 1);
						@endphp
						<center class='barcodetc' style='word-spacing: 5px;font-size: 15px;font-weight: bold;'>
						@foreach($barcodeArr As $b)
							<span >{{$b}}</span>
						@endforeach
					</center>
					</div>
					
					<div class="col-xs-12">
						<div class="col-xs-5 printList" data-id="price" style="font-weight: bolder; font-size: 12px">
							{{-- <span>
								â‚¬ 
								</span> --}}
							{{-- i.fa.fa-euro-sign --}}
							{{-- @if($objProduct->max_price != $objProduct->min_price && $objProduct->type == "variable") 
							-  <span class="display_currency" data-currency_symbol="true" >
								{{$objProduct->max_price}}
							</span> 
							@else  --}}
							{{-- â‚¬  --}}
								{{-- <span class="display_currency" data-currency_symbol="true">
                                        </span>  --}}
                                        €
                                        {{$objProduct['price']}}
							{{-- @endif --}}
						</div>
						<div class="col-xs-3 printList text-center" data-id="color"  style="font-size: 12px"> 
							{{$objProduct['color']}} 
						</div>
						<div class="col-xs-4 text-center printList" data-id="refference" style="font-size: 12px">
							{{$objProduct['refference'] }}
						</div>
						{{-- <div  class="col printList text-right" data-id="subcat" style="font-size: 14px">
							{{$objProduct->sub_category}}
						</div> --}}
						{{-- <div  class="col-md-3 col-xs-6 pull-left printList text-right" data-id="cat">
							{{$objProduct->category  or ' '}}
						</div> --}}
					</div>

					
					
					{{-- <!--	<div class="col-md-6  col-xs-5 pull-left printList hide"  data-id="qty">Qty : {{$objProduct->current_stock  or ' '}}  </div>--> --}}
				</div>
			@endfor 
			{{-- @if ($loop->iteration%3 == 0)
				</div>
				<div class="row" style="margin-top: 20px">
			@endif --}}
		@endforeach 
	</div> 
</section>
<script src="{{url('/')}}/AdminLTE/plugins/jQuery/jquery-2.2.3.min.js?v=36"></script>
<script src="{{url('/')}}/plugins/jquery-ui/jquery-ui.min.js?v=36"></script>
 <script type="text/javascript">
  
 	$(document).ready(function(){
	  $(".checkPrint").click(function(){
	  	var DontShowId = $(this).attr("data-id");
	  	var DontShowChecked = $(this).prop("checked");
	    $(".printList").each(function(){
	    	var  ShowId = $(this).attr("data-id");
	    	if(ShowId == DontShowId)
	    	{
	    		if(DontShowChecked)
	    		{
	    			$(this).addClass("showV").removeClass("hideV");
	    		}else
	    		{
	    			$(this).addClass("hideV").removeClass("showV");
	    		}
	    	}
	    });
	  });
	});
 </script> 
