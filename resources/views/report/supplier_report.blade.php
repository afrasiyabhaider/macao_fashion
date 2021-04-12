@extends('layouts.app')
@section('title','Supplier Report')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
     <h1>Supplier Report</h1>
</section>
<section class="content">
     <div class="row">
          <div class="col-md-12">
               @component('components.filters', ['title' => __('report.filters')])
               {!! Form::open(['url' => action('ReportController@supplier_report'), 'method' => 'get', 'id' =>
               'supplier_report_filter_form' ]) !!}
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
                         <table class="table table-bordered ajax_view table-striped dataTable" id="supplier_report_table">
                              <thead>
                                   <tr>
                                        <th>Supplier Name</th>
                                        <th>Total Sold</th>
                                        <th>Available Stock</th>
                                        <th>Sale Percent</th>
                                        <th>Total Pieces</th>
                                        {{-- <th>Transferred</th> --}}
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
<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
<script>
     supplier_report_table = $('#supplier_report_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{url("/reports/supplier-report")}}',
            data: function(d) {
                d.location_id = $('#location_id').val();
                d.from_date = $('#product_list_from_date').val();
                d.to_date = $('#product_list_to_date').val();
            },
        },
        pageLength: 100,
        lengthMenu: [
            [20, 50, 70, 100, -1],
            [20, 50, 70, 100, 'All'],
        ],
        aaSorting: [2, 'asc'],
        columns: [
            { data: 'supplier_name', name: 'sup.name' },
            { data: 'quantity_sold', name: 'quantity_sold',searchable:false },
            { data: 'quantity_available', name: 'quantity_available',searchable:false },
            { data: 'sale_percent', name: 'sale_percent',searchable:false },
            { data: 'total', name: 'total',searchable:false },
          //   { data: 'transfered', name: 'transfered' },
        ],
    });
    $(
        '#supplier_report_filter_form #location_id,#product_list_to_date'
    ).change(function() {
        supplier_report_table.ajax.reload();
    });
</script>
@endsection