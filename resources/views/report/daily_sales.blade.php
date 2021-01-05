@extends('layouts.app')
@section('title', 'Daily Sale Report')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
     <h1>
          Daily Sale Report
     </h1>
</section>

<!-- Main content -->
<section class="content">
     <div class="row">
          <div class="col-md-12">
               @component('components.filters', ['title' => __('report.filters')])
               {!! Form::open(['url' => action('ReportController@dailySales'), 'method' => 'get', 'id' =>
               'daily_sale_report_filter_form' ]) !!}
               <div class="col-md-4">
                    <div class="form-group">
                         {!! Form::label('location_id', 'Location' . ':') !!}
                         {!! Form::select('location_id', $business, null, ['class' => 'form-control select2', 'style' =>
                         'width:100%',
                         'placeholder' => 'All Locations']); !!}
                    </div>
               </div>
               <div class="col-md-4">
                    <div class="form-group">
                         {!! Form::label('daily_sale_sr_date_filter', __('report.date_range') . ':') !!}
                         {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class'
                         =>
                         'form-control', 'id' => 'daily_sale_sr_date_filter', 'readonly']); !!}
                    </div>
               </div>
               {!! Form::close() !!}
               @endcomponent
          </div>
     </div>
     <div class="row">
          <div class="col-md-12">
               @component('components.widget', ['class' => 'box-primary'])
               <h4>Total Cash: <span class="display_currency total_cash" data-currency_symbol="false"></span> €
               </h4>
               <h4>Total Card: <span class="display_currency total_card" data-currency_symbol="false"></span> €
               </h4>
               <h4>Total Coupon: <span class="display_currency total_coupon" data-currency_symbol="false"></span> €
               </h4>
               <h4>Total Gift Card: <span class="display_currency total_gift_card" data-currency_symbol="false"></span>
                    €</h4>
               {{-- 
                                   <h4>Total Discount: <span class="display_currency" data-currency_symbol="false"
                                        id="total_discount"></span> € 
                                   </h4>
                                   <h4>Total Items: <span class="display_currency" data-currency_symbol="false" id="total_items"></span> 
                              --}}
               </h4>
               <h4>Total Invoices: <span class="display_currency total_invoices" data-currency_symbol="false"></span>
               </h4>
               <h4>Total Amount: <span class="display_currency " data-currency_symbol="false" id="total_amount_top"></span> €
               </h4>

               <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="daily_sale_report_table">
                         <thead>
                              <tr>
                                   <th>Location Name</th>
                                   <th>Date</th>
                                   {{-- <th>Items</th> --}}
                                   <th>Invoices</th>
                                   <th>Cash</th>
                                   <th>Card</th>
                                   <th>Gift Card</th>
                                   <th>Coupon</th>
                                   {{-- <th>Discounted Amount</th> --}}
                                   <th>
                                        Total
                                        <small>(TVA Inclu)</small>
                                   </th>
                              </tr>
                         </thead>
                         <tfoot>
                              <tr>
                                   <td></td>
                                   <td>
                                        Total
                                   </td>
                                   <td>
                                        <span class="display_currency total_invoices" data-currency_symbol="false">
                                   </td>
                                   <td>
                                        <span class="display_currency total_cash" data-currency_symbol="false"></span> €
                                   </td>
                                   <td>
                                        <span class="display_currency total_card" data-currency_symbol="false"></span> €
                                   </td>
                                   <td>
                                        <span class="display_currency total_gift_card" data-currency_symbol="false"></span>
                                        €
                                   </td>
                                   <td>
                                        <span class="display_currency total_coupon" data-currency_symbol="false"></span> €
                                   </td>
                                   <td>
                                        <span class="display_currency" data-currency_symbol="false" id="total_amount"></span> €
                                   </td>
                              </tr>
                         </tfoot>
                    </table>
               </div>
               @endcomponent
          </div>
     </div>
</section>
<!-- /.content -->
<div class="modal fade view_register" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>
@endsection

@section('javascript')
<script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
<script>
     if ($('#daily_sale_sr_date_filter').length == 1) {
          //date range setting
          $('input#daily_sale_sr_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
               $('input#daily_sale_sr_date_filter').val(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
               );
               daily_sale_report_table.ajax.reload();
          });
          $('input#daily_sale_sr_date_filter').on('apply.daterangepicker', function(ev, picker) {
               $(this).val(
                    picker.startDate.format(moment_date_format) +
                    ' ~ ' +
                    picker.endDate.format(moment_date_format)
               );
          });

          $('input#daily_sale_sr_date_filter').on('cancel.daterangepicker', function(ev, picker) {
               $(this).val('');
          });
     }
     var buttons = [{
          extend: 'copy',
          text: '<i class="fa fa-files-o" aria-hidden="true"></i> ' + LANG.copy,
          className: 'bg-info',
          exportOptions: {
          columns: ':visible',
          },
          footer: true,
          },
          {
          extend: 'csv',
          text: '<i class="fa fa-file-text-o" aria-hidden="true"></i> ' + LANG.export_to_csv,
          className: 'bg-info',
          exportOptions: {
          columns: ':visible',
          },
          footer: true,
          },
          {
          extend: 'excel',
          text: '<i class="fa fa-file-excel-o" aria-hidden="true"></i> ' + LANG.export_to_excel,
          className: 'bg-info',
          exportOptions: {
          columns: ':visible',
          },
          footer: true,
          },
          {
          extend: 'print',
          text: '<i class="fa fa-print" aria-hidden="true"></i> ' + LANG.print,
          className: 'bg-info',
          exportOptions: {
          columns: ':visible',
          stripHtml: false,
          },
          footer: true,
          },
          {
          extend: 'colvis',
          text: '<i class="fa fa-columns" aria-hidden="true"></i> ' + LANG.col_vis,
          className: 'bg-info',
          },
     ];
     //Register report
     daily_sale_report_table = $('#daily_sale_report_table').DataTable({
          processing: true,
          serverSide: true,
          ajax: {
               url: '{{url("/")}}/daily/sales',
               data: function(d) {
                    start = null;
                    end = null;
                    if ($('#daily_sale_sr_date_filter').val()) {
                    start = $('input#daily_sale_sr_date_filter')
                    .data('daterangepicker')
                    .startDate.format('YYYY-MM-DD');
                    end = $('input#daily_sale_sr_date_filter')
                    .data('daterangepicker')
                    .endDate.format('YYYY-MM-DD');
                    }
                    d.start_date = start;
                    d.end_date = end;
                    
                    
                    d.location_id = $('#location_id').val();
               },
          },
          // columnDefs: [{ targets: [6], orderable: false, searchable: false }],
          dom: '<"row margin-bottom-12"<"col-sm-12"<"pull-left"l><"pull-right margin-left-10"B><"pull-right"fr>>>tip',
          buttons: [{
                    extend: 'collection',
                    text: '<i class="fa fa-list" aria-hidden="true"></i> &nbsp;' + LANG.action,
                    className: 'btn-info',
                    init: function(api, node, config) {
                         $(node).removeClass('btn-default');
                    },
                    buttons: buttons,
               },
          ],
          pageLength: -1,
          lengthMenu: [
               [20, 50, 70, 100, 300, 500, 1000, -1],
               [20, 50, 70, 100, 300, 500, 1000, 'All'],
          ],
          columns: [
               {
               data: 'location_name',
               name: 'location_name'
               },
               {
               data: 'date',
               name: 'date'
               },
               // { data: 'status', name: 'status' },
               // {
               // data: 'items',
               // name: 'items'
               // },
               {
               data: 'invoices',
               name: 'invoices'
               },
               {
               data: 'cash',
               name: 'cash'
               },
               {
               data: 'card',
               name: 'card'
               },
               {
               data: 'gift_card',
               name: 'gift_card'
               },
               {
               data: 'coupon',
               name: 'coupon'
               },
               // {
               // data: 'discount',
               // name: 'discount'
               // },
               {
               data: 'total',
               name: 'total'
               }
          ],
          fnDrawCallback: function(oSettings) {
               __currency_convert_recursively($('#daily_sale_report_table'));
               $('#total_amount_top').text(
               sum_table_col($('#daily_sale_report_table'), 'total_amount').toFixed(2)
               );
               $('#total_amount').text(
               sum_table_col($('#daily_sale_report_table'), 'total_amount').toFixed(2)
               );
               $('.total_cash').text(
               sum_table_col($('#daily_sale_report_table'), 'cash_amount').toFixed(2)
               );
               $('.total_card').text(
               sum_table_col($('#daily_sale_report_table'), 'card_amount').toFixed(2)
               );
               $('.total_coupon').text(
               sum_table_col($('#daily_sale_report_table'), 'coupon_amount').toFixed(2)
               );
               $('.total_gift_card').text(
               sum_table_col($('#daily_sale_report_table'), 'giftcard_amount').toFixed(2)
               );
               // $('#total_discount').text(
               // sum_table_col($('#daily_sale_report_table'), 'discounted_amount')
               // );
               $('.total_invoices').text(
               sum_table_col($('#daily_sale_report_table'), 'invoices').toFixed(2)
               );
               // $('#total_items').text(
               // sum_table_col($('#daily_sale_report_table'), 'items')
               // );
               __currency_convert_recursively($('#daily_sale_report_table'));
          },
     });

     $('.view_register').on('shown.bs.modal', function() {
          __currency_convert_recursively($(this));
     });


     $(
     '#daily_sale_report_filter_form #location_id, #daily_sale_report_filter_form #daily_sale_sr_date_filter'
     ).on('change', function() {
          daily_sale_report_table.ajax.reload();
     });
</script>
@endsection