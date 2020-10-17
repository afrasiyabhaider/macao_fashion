@extends('layouts.app')
@section('title','Sale Report N/S')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
     <h1>Sale Report (No Sizes)</h1>
</section>
<section class="content">
     <div class="row">
          <div class="col-md-12">
               @component('components.filters', ['title' => __('report.filters')])
               {!! Form::open(['url' => action('ReportController@product_first_report'), 'method' => 'get', 'id' =>
               'sub_category_filter_form' ]) !!}
               <div class="col-md-3">
                    <div class="form-group">
                         {!! Form::label('category_id', 'Category' . ':') !!}
                         {!! Form::select('category_id', $categories, null, ['class' => 'form-control select2',
                         'style' => 'width:100%']); !!}
                    </div>
               </div>
               <div class="col-md-3">
                    <div class="form-group">
                         {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                         {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                         'style' => 'width:100%']); !!}
                    </div>
               </div>
               <div class="row" id="location_filter">
                    <div class="form-group col-md-3">
                         {!! Form::label('from_date', ' From Date:') !!}
                         <input type="date" name="product_list_from_date" value="{{date('Y-m-d')}}"
                              id="product_list_from_date" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                         {!! Form::label('to_date', ' To Date:') !!}
                         <input type="date" name="product_list_to_date" id="product_list_to_date" value=""
                              class="form-control">
                    </div>
               </div>
               {!! Form::close() !!}
               @endcomponent
          </div>
     </div>
     <div class="row" style="margin-top: 20px;">
          @component('components.widget', ['class' => 'box-primary'])
          <div class="col-md-12">
               <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="sub_category_table">
                         <thead>
                              <tr>
                                   <th>Image</th>
                                   <th>Name</th>
                                   <th>Reffernces</th>
                                   <th>Available Stock</th>
                                   <th>Total Sold</th>
                                   <th>Sizes</th>
                                   <th>Total</th>
                                   <th>Transferred</th>
                              </tr>
                         </thead>
                    </table>
               </div>
          </div>
          @endcomponent
     </div>
</section>
@endsection
@section('javascript')
<script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
<script>
     sub_category_table = $('#sub_category_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{url("/reports/product-first-report")}}',
            data: function(d) {
                d.location_id = $('#location_id').val();
                d.from_date = $('#product_list_from_date').val();
                d.to_date = $('#product_list_to_date').val();
                d.category_id = $('#category_id').val();
            },
        },
        pageLength: 50,
        lengthMenu: [
            [20, 50, 70, 100, -1],
            [20, 50, 70, 100, 'All'],
        ],
        aaSorting: [2, 'asc'],
        columns: [
            { data: 'image', sortable: false, searchable: false },
            { data: 'name', name: 'products.name' },
            { data: 'num_of_refference', name: 'num_of_refference' },
            { data: 'quantity_available', name: 'quantity_available' },
            { data: 'total_sold', name: 'total_sold' },
            { data: 'num_of_sub_sizes', name: 'num_of_sub_sizes' },
            { data: 'total', name: 'total' },
            { data: 'transfered', name: 'transfered' },
        ],
    });
    $(
        '#sub_category_filter_form #location_id,#product_list_to_date,#category_id'
    ).change(function() {
     sub_category_table.ajax.reload();
    });
</script>
@endsection