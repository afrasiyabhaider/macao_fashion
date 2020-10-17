@extends('layouts.app')
@section('title', 'colors')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'color.brands' )
        <small>@lang( 'color.manage_your_brands' )</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'color.all_your_brands' )])
        @can('color.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal" data-container=".colors_modal"
                        data-href="{{action('ColorController@create')}}" 
                        >
                        <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
            @endslot
        @endcan
        @can('color.view')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="color_table">
                    <thead>
                        <tr>
                            <th>@lang( 'color.brands' )</th>
                            <th>Color Code</th>
                            {{-- <th>@lang( 'color.note' )</th> --}}
                            <th>@lang( 'messages.action' )</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade colors_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection
