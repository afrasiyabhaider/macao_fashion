@extends('layouts.app')
@section('title', 'Product Name Categories')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Product Name Categories
        <small>Manage Your Product Name Categories</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'category.manage_your_categories' )])
        @can('category.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal" 
                    data-href="{{action('ProductNameCategoryController@create')}}" 
                    data-container=".ProductNameCategory_modal">
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
                
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal" 
                    data-href="{{action('ProductNameCategoryController@addExcell')}}" 
                    data-container=".ProductNameCategory_modal">
                    <i class="fa fa-plus"></i> Upload Excell</button>
                </div>
            @endslot
        @endcan
        @can('category.view')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="ProductNameCategory_table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Row</th>
                            <th>@lang( 'messages.action' )</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade ProductNameCategory_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection
