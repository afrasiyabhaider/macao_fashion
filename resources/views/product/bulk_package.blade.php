@extends('layouts.onlyApp')
@section('title', __('sale.products'))

@section('content')
<section class="content-header">
    <h1>BULK Add Product List
        <h4>Bulk-Code : <b>{{$BulkId}}</b>  - <a href="{{url('products')}}" class="btn btn-md btn-success">GO Back To Products </a>  - <button class="btn btn-md btn-warning" type="button" onclick="window.print();return false;"><i class="fa fa-pencil">Print</i></button>  </h4>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
<div class="row">
	<table class="col-md-12 content" style="    margin-left: 32px;">
		<thead>
			<th>No</th>
			<th>Name</th>
			<th>Refference</th>
			<th>Barcode</th>
			<th>Supplier</th>
			<th>Unit</th>
			<th>Sell Price</th>
			<th>Category</th>
			<th>SubCategory</th>
			<th>Color</th>
			<th>Size</th>
			<th>Image</th>
		</thead>
		<tbody>
		  @php $i=0; @endphp
		  @foreach($product as $objProduct)
		  @php $i++; @endphp
		  <tr>
		      <td>{{$i}}</td>
		      <td>{{$objProduct->name  or '--'}}</td>
		      <td>{{$objProduct->refference  or '--'}}</td>
		      <td><b>{{$objProduct->sku  or '--'}}</b></td>
		      <td>{{$objProduct->supplier->name  or '--'}}</td>
		      <td>{{$objProduct->variations[0]->default_purchase_price  or '--'}}</td>
		      <td>{{$objProduct->variations[0]->sell_price_inc_tax  or '--'}}</td>
		      <td>{{$objProduct->category->name}}</td>
		      <td>{{$objProduct->sub_category->name or '--'}}</td>
		      <td>{{$objProduct->color->name  or '--'}}</td>
		      <td>{{$objProduct->size->name or '--'}} <br/>{{$objProduct->sub_size->name or '--'}}</td>
		      <td><img src="{{(empty($product->image))?url('img/default.png') : url('uploads/img/').$product->image}}" width="50px" height="50px"></td>
		  </tr>
		  @endforeach
		</tbody>
	</table>
 
  
</div>
 