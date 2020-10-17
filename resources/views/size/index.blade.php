@extends('layouts.app')
@section('title', 'Sizes')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'size.sizes' )
        <small>@lang( 'size.manage_your_sizes' )</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'size.manage_your_sizes' )])
        @can('size.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal" data-container=".size_modal"
                    data-href="{{action('SizeController@create')}}" 
                    >
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
            @endslot
        @endcan
        @can('size.view')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="size_table">
                    <thead>
                        <tr>
                            <th>@lang( 'size.category' )</th>
                            <th>@lang( 'size.code' )</th>
                            <th>@lang( 'messages.action' )</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade size_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection
