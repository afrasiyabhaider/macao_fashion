@extends('layouts.app')
@section('title', __('coupon_report'))
@section('css')
    <style>
        .product-thumbnail-small {
            height: 100px !important;
            width: 100px !important;
        }

        table.table-bordered.dataTable td {
            padding-top: 0px !important;
            padding-bottom: 0px !important;
            vertical-align: middle;
        }
    </style>

@endsection
@section('content')
    <section class="content-header">
        <h1>Coupon Report</h1>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    {!! Form::open([
                        'url' => action('ReportController@getCouponReport'),
                        'method' => 'get',
                        'id' => 'stock_report_filter_form',
                    ]) !!}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('location_id', $business_locations, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                            ]) !!}
                        </div>
                    </div>


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
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('isActive', 'Status' . ':') !!}
                            {!! Form::select('isActive', ['active' => 'Active', 'inactive' => 'Inactive', 'expired'=> 'Expired', 'consumed'=> 'Consumeded', 'cancell'=> 'Cancelled'], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_type', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                    {!! Form::close() !!}
                @endcomponent
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary'])
                        <div class="table-responsive">
                            <table class="table table-bordered ajax_view table-striped dataTable" id="stock_in_table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Barcode</th>
                                        <th>Value</th>
                                        <th>Used Value</th>
                                        <th>Location Name</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Used Date</th>
                                        <th>Detail</th>
                                        <th>Status</th>
                                        {{-- <th>Sub-Category</th> --}}
                                        {{-- <th>Size</th> --}}
                                        {{-- <th>Transfered Added</th> --}}
                                        {{-- <th>Updated At</th> --}}
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 text-center footer-total">
                                        {{-- <td colspan="8"><strong>@lang('sale.total'):</strong></td> --}}
                                        {{-- <td id="footer_total_stock"></td>
                                                        <td id="footer_total_sold"></td>
                                                        <td id="footer_total_transfered"></td>
                                                        <td id="footer_total_adjusted"></td>
                                                        <td></td> --}}
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                @endcomponent
            </div>
        </div>
    </section>
@endsection
@section('javascript')
    <script>
        stock_in_table = $('#stock_in_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/reports/coupon_reports',
                data: function(d) {
                    var start = $.datepicker.formatDate('yy-mm-dd', new Date());
                    var end = $.datepicker.formatDate('yy-mm-dd', new Date());
                    if ($('#product_purchase_date_filter').val()) {
                        // start = $('input#product_purchase_date_filter')
                        //     .data('daterangepicker')
                        //     .moment().subtract(10, 'years').format('YYYY-MM-DD');
                        // end = $('input#product_purchase_date_filter')
                        //     .data('daterangepicker')
                        //     .endDate.format('YYYY-MM-DD');
                        start = $('input#product_purchase_date_filter')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        end = $('input#product_purchase_date_filter')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                    }
                    d.start_date = start;
                    d.end_date = end;

                    d.location_id = $('#location_id').val();
                    d.category_id = $('#category_id').val();
                    d.sub_category_id = $('#sub_category_id').val();
                    d.type = $('#product_list_filter_type').val();
                    // d.supplier_id = $('#suppliers').val();
                    // d.from_date = $('#product_list_from_date').val();
                    // d.to_date = $('#product_list_to_date').val();
                    // d.unit_id = $('#unit').val();
                },
            },
            pageLength: 300,
            lengthMenu: [
                [30, 40, 60, 80, 90, 100, 150, 300, 500, 1000, -1],
                [30, 40, 60, 80, 90, 100, 150, 300, 500, 1000, 'All'],
            ],
            // aaSorting: [21, 'desc'],
            columns: [{
                    data: 'DT_Row_Index',
                    name: 'DT_Row_Index',
                    searchable: false,
                    orderable: false
                },
                
                {
                    data: 'barcode',
                    name: 'barcode'
                },
                {
                    data: 'orig_value',
                    name: 'orig_value',
                    orderable: false,
                    searchable: false
                }, 
                {
                    data: 'used_value',
                    name: 'used_value',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'location_name',
                    name: 'bl.name'
                },
                {
                    data: 'start_date',
                    name: 'start_date'
                },
                {
                    data: 'CouponExpiryDate',
                    name: 'CouponExpiryDate'
                }, 
                {
                    data: 'usedDate',
                    name: 'usedDate'
                },  
                {
                    data: 'details',
                    name: 'details'
                }, 
                {
                    data: 'isActive',
                    name: 'isActive'
                },

            ],
            fnDrawCallback: function(oSettings) {
                //     $('#footer_total_stock').html(__sum_stock($('#stock_in_table'), 'current_stock'));
                //     $('#footer_total_sold').html(__sum_stock($('#stock_in_table'), 'total_sold'));
                //     $('#footer_total_transfered').html(
                //         __sum_stock($('#stock_in_table'), 'total_transfered')
                //     );
                //     $('#footer_total_adjusted').html(
                //         __sum_stock($('#stock_in_table'), 'total_adjusted')
                //     );
                //     __currency_convert_recursively($('#stock_in_table'));
            },
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
                stock_in_table.ajax.reload();


            });
            $('#product_purchase_date_filter').on('cancel.daterangepicker', function(ev, picker) {
                $('#product_purchase_date_filter').val('');
                stock_in_table.ajax.reload();


            });
            // $('#product_purchase_date_filter').data('daterangepicker').setStartDate(moment());
            $('#product_purchase_date_filter').data('daterangepicker').setStartDate(moment());
            $('#product_purchase_date_filter').data('daterangepicker').setEndDate(moment());
        }
        $(
            '#stock_report_filter_form #location_id, #product_list_filter_type, #stock_report_filter_form #category_id, #stock_report_filter_form #sub_category_id, #stock_report_filter_form #brand,#stock_report_filter_form #suppliers, #stock_report_filter_form #unit,#stock_report_filter_form #view_stock_filter,#product_list_to_date, #product_list_from_date#product_sr_date_filter'
        ).change(function() {
            stock_in_table.ajax.reload();

        });
    </script>
@endsection
