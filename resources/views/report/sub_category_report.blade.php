@extends('layouts.app')
@section('title','Sub-Category Report')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
     <h1>Sub-Category Report</h1>
</section>
<section class="content">
     <div class="row">
          <div class="col-md-12">
               @component('components.filters', ['title' => __('report.filters')])
               {!! Form::open(['url' => action('ReportController@sub_category_report'), 'method' => 'get', 'id' =>
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
                    {{-- <div class="form-group col-md-3">
                         {!! Form::label('from_date', ' From Date:') !!}
                         <input type="date" name="product_list_from_date" value="{{date('Y-m-d')}}"
                              id="product_list_from_date" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                         {!! Form::label('to_date', ' To Date:') !!}
                         <input type="date" name="product_list_to_date" id="product_list_to_date" value=""
                              class="form-control">
                    </div> --}}
                    <div class="col-md-3">
                         <div class="form-group">
 
                             {!! Form::label('product_sr_date_filter', 'Date:') !!}
                             {!! Form::text('date_range', null, [
                                 'placeholder' => __('lang_v1.select_a_date_range'),
                                 'class' => 'form-control',
                                 'id' => 'product_purchase_date_filter',
                                 'readonly',
                             ]) !!}
                         </div>
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
                                   <th>Name</th>
                                   <th>Available Stock</th>
                                   <th>Total Sold</th>
                                   <th>Total</th>
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
{{-- <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script> --}}
<script>
     sub_category_table = $('#sub_category_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{url("/reports/subcategory-report")}}',
            data: function(d) {
                d.location_id = $('#location_id').val();
                d.category_id = $('#category_id').val();
                    var start = '';
                    var end = '';
                    // var start = $.datepicker.formatDate('yy-mm-dd', new Date());
                    // var end = $.datepicker.formatDate('yy-mm-dd', new Date());
                    // if ($('#product_purchase_date_filter').val()) {
                    // start = $('input#product_purchase_date_filter')
                    //     .data('daterangepicker')
                    //     .startDate.format('YYYY-MM-DD');
                    // end = $('input#product_purchase_date_filter')
                    //     .data('daterangepicker')
                    //     .endDate.format('YYYY-MM-DD');
                    // }
                if ($('#product_purchase_date_filter').val()) {
                    start = $('input#product_purchase_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#product_purchase_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }
                d.from_date = start;
                d.to_date = end;
               //  d.from_date = $('#product_list_from_date').val();
               //  d.to_date = $('#product_list_to_date').val();
            },
        },
        pageLength: 50,
        lengthMenu: [
            [20, 50, 70, 100, -1],
            [20, 50, 70, 100, 'All'],
        ],
        aaSorting: [2, 'asc'],
        columns: [
            { data: 'sub_category_name', name: 'sub_category_name' },
            { data: 'stock', name: 'stock' },
            { data: 'total_sold', name: 'total_sold' },
            { data: 'total', name: 'total' },
          //   { data: 'transfered', name: 'transfered' },
        ],
    });
    if ($('#product_purchase_date_filter').length == 1) {
            var purchasedateRangeSettings = {
                ranges: ranges,
                startDate: moment().subtract(365, 'days'),
                endDate: moment(),
                locale: {
                    cancelLabel: LANG.clear,
                    applyLabel: LANG.apply,
                    customRangeLabel: LANG.custom_range,
                    format: moment_date_format,
                    toLabel: '~',
                },
            };
            $('#product_purchase_date_filter').daterangepicker(purchasedateRangeSettings, function(start, end) {
                $('#product_purchase_date_filter').val(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                );
                sub_category_table.ajax.reload();
            });
            $('#product_purchase_date_filter').on('cancel.daterangepicker', function(ev, picker) {
                $('#product_purchase_date_filter').val('');
                sub_category_table.ajax.reload();
            });
            $('#product_purchase_date_filter').data('daterangepicker').setStartDate(moment().subtract(10, 'years'));
            $('#product_purchase_date_filter').data('daterangepicker').setEndDate(moment());
        }
    $(
        '#sub_category_filter_form #location_id,#product_list_to_date,#category_id'
    ).change(function() {
     sub_category_table.ajax.reload();
    });
</script>
@endsection