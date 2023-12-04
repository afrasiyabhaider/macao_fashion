@extends('layouts.app')
@section('title', __('stock_in_out'))
@section('content')
    <section class="content-header">
        <h1>Stock In</h1>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                {{-- @dd($business_locations) --}}
                @component('components.filters', ['title' => __('report.filters')])
                    {!! Form::open([
                        'url' => action('ReportController@getstockInOutReport'),
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
                            {!! Form::label('category_id', __('category.category') . ':') !!}
                            {!! Form::select('category', $categories, null, [
                                'placeholder' => __('messages.all'),
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'category_id',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
                            {!! Form::select('sub_category', [], null, [
                                'placeholder' => __('messages.all'),
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'sub_category_id',
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
                    {!! Form::close() !!}
                @endcomponent
            </div>
        </div>
        <div class="row" style="margin-top: 20px;">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="form-row">
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="active">
                                <a href="#psr_grouped_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-cart-plus"
                                        aria-hidden="true"></i>
                                    Grouped Products</a>
                            </li>
                            <li>
                                <a href="#stock_in" data-toggle="tab" aria-expanded="true"><i class="fa fa-cart-plus"
                                        aria-hidden="true"></i>
                                    Stock In</a>
                            </li>

                            <li>
                                <a href="#stock_out" data-toggle="tab" aria-expanded="true"><i class="fa fa-list"
                                        aria-hidden="true"></i> Stock Out</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="psr_grouped_tab">
                                <div class="row">
                                    <div class="col-md-12">
                                        <h4>Total stock in: <span class="total_stock" data-currency_symbol="false"></span></h4>
                                        <h4>Total stock out: <span class="total_stock_out" data-currency_symbol="false"></span></h4>
                                        <h4>Total amount buying: <span class="total_buying_amount" data-currency_symbol="false"></span></h4>
                                        <h4>Total amount sale price: <span class="total_sell_price" data-currency_symbol="false"></span></h4>
                                        <h4>Total reference added: <span class="total_refference"data-currency_symbol="false"></span></h4>
                                        @include('report.partials.stock_group_table')

                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="stock_in">
                                <div class="row">
                                    <div class="col-md-12">
                                        @include('report.partials.stock_in_table')

                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="stock_out">
                                <div class="row">
                                    <div class="col-md-12">
                                        @include('report.partials.stock_out_table')
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </section>
@endsection
@section('javascript')
    {{-- <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script> --}}
    {{-- <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script> --}}
    <script>
         stock_in_out_grouped_report_table = $('#stock_in_out_grouped_report_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/reports/stock-in-out-grouped-report',
                data: function(d) {
                    var start = '';
                    var end = '';
                    var start = $.datepicker.formatDate('yy-mm-dd', new Date());
                    var end = $.datepicker.formatDate('yy-mm-dd', new Date());
                    // var start1 = new Date();
                    // var end1 = new Date();
                    // start1.setDate(end1.getDate() - 6);
                    // var start = $.datepicker.formatDate('yy-mm-dd', start1);
                    // var end = $.datepicker.formatDate('yy-mm-dd', end1);
                    if ($('#product_purchase_date_filter').val()) {
                        start = $('input#product_purchase_date_filter')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        end = $('input#product_purchase_date_filter')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                        // if ($('#product_purchase_date_filter').val()) {
                        // start = $('input#product_purchase_date_filter')
                        //         .data('daterangepicker')
                        //         .moment().subtract(1, 'month').format('YYYY-MM-DD');
                        //     end = $('input#product_purchase_date_filter')
                        //         .data('daterangepicker')
                        //         .endDate.format('YYYY-MM-DD');
                    }
                    d.start_date = start;
                    d.end_date = end;

                    d.location_id = $('#location_id').val();
                    d.category_id = $('#category_id').val();
                    d.sub_category_id = $('#sub_category_id').val();
                    d.supplier_id = $('#suppliers').val();
                    // d.from_date = $('#product_list_from_date').val();
                    // d.to_date = $('#product_list_to_date').val();
                    d.unit_id = $('#unit').val();
                },
            },
            pageLength: 30,
            lengthMenu: [
                [30, 40, 60, 80, 90, 100, 150, 300, 500, 1000, -1],
                [30, 40, 60, 80, 90, 100, 150, 300, 500, 1000, 'All'],
            ],
            aaSorting: [2, 'asc'],
            columns: [{
                    data: 'DT_Row_Index',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'image',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'product',
                    name: 'p.name'
                },
                // {
                //     data: 'detail',
                //     orderable: false,
                //     searchable: false
                // },
                {
                    data: 'refference',
                    name: 'p.refference'
                },
                {
                    data: 'location_name',
                    name: 'bl.name'
                },
                {
                    data: 'unit_price',
                    name: 'v.sell_price_inc_tax'
                },
                {
                    data: 'total_transfered',
                    name: 'total_transfered',
                    searchable: false
                },  
                // 
                {
                    data: 'total_sold',
                    name: 'total_sold',
                    searchable: false
                },
                {
                data: 'stock',
                name: 'stock',
                searchable: false
            },
               
            {
                data: 'total_sale_price',
                name: 'total_sale_price',
                searchable: false
            },
            {
                data: 'purchase_price',
                name: 'purchase_price',
                searchable: false
            },
            {
                data: 'total_buying_amount',
                name: 'total_buying_amount',
                searchable: false
            },
            ],
            fnDrawCallback: function(oSettings) {
                let api = this.api();
                $('.total_stock').html(sum_table_col($('#stock_in_out_grouped_report_table'),
                    'total_transfered'));
                $('.total_stock_out').html(sum_table_col($('#stock_in_out_grouped_report_table'),
                    'total_sold'));
                $('.total').html(__sum_stock($('#stock_in_out_grouped_report_table'),
                    'current_stock'));
                $('.total_sell_price').text(sum_table_col($('#stock_in_out_grouped_report_table'),
                    'total_sale_price'));
                $('.total_buying_amount').text(sum_table_col($('#stock_in_out_grouped_report_table'),
                    'row_subtotal'));
                    // $('#footer_total_sold').html(__sum_stock($('#stock_report_table'), 'total_sold'));
                    // $('.current_stock').html(__sum_stock($('#stock_report_table'), 'current_stock'));
                // $('.total_qty').html(sum_table_col($('#stock_in_out_grouped_report_table'),
                //     'total_qty'));
               
                let total_refference = 0;
                // let total_buying_amount = 0;
                $.each([4], function(index, value) {
                    api.column(value) .data()
                            .reduce(function(a, b) {
                                total_refference += 1;
                            }, 0)
                });
                $('.total_refference').html(total_refference);
                __currency_convert_recursively($('#stock_in_out_grouped_report_table'));

                // $.each([7], function(index, value) {
                //     api
                //     .column(value) 
                //     .data()
                //     .reduce(function(a, b) {
                //         console.log(a, b);
                //         // return a+b;
                //         total_qty += 1;
                //     }, 0)
                // });
                // $('.total_qty').html(total_qty);
               
            },
        });


        stock_in_table = $('#stock_in_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/reports/stock-in-out',
                data: function(d) {
                    var start = '';
                    var end = '';
                    var start = $.datepicker.formatDate('yy-mm-dd', new Date());
                    var end = $.datepicker.formatDate('yy-mm-dd', new Date());
                    // var start1 = new Date();
                    // var end1 = new Date();
                    // start1.setDate(end1.getDate() - 6);
                    // var start = $.datepicker.formatDate('yy-mm-dd', start1);
                    // var end = $.datepicker.formatDate('yy-mm-dd', end1);
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
                // {
                //     data: 'mass_delete',
                //     name: 'mass_delete',
                //     orderable: false,
                //     searchable: false
                // },
                // {
                //     data: 'printing_qty',
                //     name: 'printing_qty',
                //     orderable: false,
                //     searchable: false
                // },
                {
                    data: 'image',
                    name: 'image',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'sku',
                    name: 'variations.sub_sku'
                },
                // {
                //     data: 'show_pos',
                //     name: 'show_pos',
                //     orderable: false,
                //     searchable: false
                // },
                {
                    data: 'product',
                    name: 'p.name'
                },
                {
                    data: 'refference',
                    name: 'p.refference'
                },
                {
                    data: 'location_name',
                    name: 'bl.name'
                },
                // {
                //     data: 'actions',
                //     name: 'actions',
                //     searchable: false,
                //     orderable: false
                // },
                {
                    data: 'unit_price',
                    name: 'variations.sell_price_inc_tax'
                },
                {
                    data: 'color_name',
                    name: 'colors.name'
                },
                {
                    data: 'category_name',
                    name: 'categories.name'
                },
                {
                    data: 'sub_category_name',
                    name: 'sub_cat.name'
                },
                {
                    data: 'size_name',
                    name: 'sizes.name'
                },
                // {
                //     data: 'description',
                //     name: 'p.description'
                // },
                // {
                //     data: 'sale_percent',
                //     name: 'sale_percent'
                // },
                // {
                //     data: 'stock',
                //     name: 'stock',
                //     searchable: false
                // },
                // {
                //     data: 'total_sold',
                //     name: 'total_sold',
                //     searchable: false
                // },
                {
                    data: 'total_transfered',
                    name: 'total_transfered',
                    searchable: false
                }, 
                {
                    data: 'total_qty',
                    name: 'total_qty',
                    searchable: false
                },
                // {
                //     data: 'supplier_name',
                //     name: 'suppliers.name'
                // },
                // {
                //     data: 'product_date',
                //     name: 'vld.product_updated_at'
                // },
                {
                    data: 'product_date',
                    name: 'vld.updated_at'
                },

                // { data: 'updated_at', name: 'updated_at' },
                // { data: 'total_adjusted', name: 'total_adjusted', searchable: false },
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

        stock_out_table = $('#stock_out_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/reports/stock-out',
                data: function(d) {
                    var start = '';
                    var end = '';
                    var start = $.datepicker.formatDate('yy-mm-dd', new Date());
                    var end = $.datepicker.formatDate('yy-mm-dd', new Date());
                    // Get the current date
                    // var start1 = new Date();
                    // var end1 = new Date();
                    // start1.setDate(end1.getDate() - 6);
                    // var start = $.datepicker.formatDate('yy-mm-dd', start1);
                    // var end = $.datepicker.formatDate('yy-mm-dd', end1);
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
                    console.log(start, end);
                    d.start_date = start;
                    d.end_date = end;

                    d.location_id = $('#location_id').val();
                    d.category_id = $('#category_id').val();
                    d.sub_category_id = $('#sub_category_id').val();
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
                // {
                //     data: 'mass_delete',
                //     name: 'mass_delete',
                //     orderable: false,
                //     searchable: false
                // },
                // {
                //     data: 'printing_qty',
                //     name: 'printing_qty',
                //     orderable: false,
                //     searchable: false
                // },
                {
                    data: 'image',
                    name: 'image',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'sku',
                    name: 'variations.sub_sku'
                },
                // {
                //     data: 'show_pos',
                //     name: 'show_pos',
                //     orderable: false,
                //     searchable: false
                // },
                {
                    data: 'product',
                    name: 'p.name'
                },
                {
                    data: 'refference',
                    name: 'p.refference'
                },
                {
                    data: 'location_name',
                    name: 'bl.name'
                },
                // {
                //     data: 'actions',
                //     name: 'actions',
                //     searchable: false,
                //     orderable: false
                // },
                {
                    data: 'unit_price',
                    name: 'variations.sell_price_inc_tax'
                },
                {
                    data: 'color_name',
                    name: 'colors.name'
                },
                {
                    data: 'category_name',
                    name: 'categories.name'
                },
                {
                    data: 'sub_category_name',
                    name: 'sub_cat.name'
                },
                {
                    data: 'size_name',
                    name: 'sizes.name'
                },
                // {
                //     data: 'description',
                //     name: 'p.description'
                // },
                // {
                //     data: 'sale_percent',
                //     name: 'sale_percent'
                // },
                // {
                //     data: 'stock',
                //     name: 'stock',
                //     searchable: false
                // },
                {
                    data: 'total_sold',
                    name: 'total_sold',
                    searchable: false
                },
                {
                    data: 'total_transfered',
                    name: 'total_transfered',
                    searchable: false
                },
                // {
                //     data: 'supplier_name',
                //     name: 'suppliers.name'
                // },
                // {
                //     data: 'product_date',
                //     name: 'vld.product_updated_at'
                // },
                {
                    data: 'product_date',
                    name: 'vld.updated_at'
                },

                // { data: 'updated_at', name: 'updated_at' },
                // { data: 'total_adjusted', name: 'total_adjusted', searchable: false },
            ],
            fnDrawCallback: function(oSettings) {
                $('#footer_total_stock').html(__sum_stock($('#stock_out_table'), 'current_stock'));
                $('#footer_total_sold').html(__sum_stock($('#stock_out_table'), 'total_sold'));
                $('#footer_total_transfered').html(
                    __sum_stock($('#stock_out_table'), 'total_transfered')
                );
                $('#footer_total_adjusted').html(
                    __sum_stock($('#stock_out_table'), 'total_adjusted')
                );
                __currency_convert_recursively($('#stock_out_table'));
            },
        });


        if ($('#product_purchase_date_filter').length == 1) {
            var purchasedateRangeSettings = {
                ranges: ranges,
                startDate: moment().subtract(365, 'days'),
                endDate: moment(),
                // startDate: moment().subtract(6, 'd').format('MM/DD/YYYY'),
                // endDate: moment().format('MM/DD/YYYY'),
                // endDate = moment().format('MM/DD/YYYY'),
                // startDate = moment().subtract(6, 'd').format('MM/DD/YYYY'),
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
                stock_out_table.ajax.reload();
                stock_in_out_grouped_report_table.ajax.reload();

            });
            $('#product_purchase_date_filter').on('cancel.daterangepicker', function(ev, picker) {
                $('#product_purchase_date_filter').val('');
                stock_in_table.ajax.reload();
                stock_out_table.ajax.reload();
                stock_in_out_grouped_report_table.ajax.reload();

            });
            $('#product_purchase_date_filter').data('daterangepicker').setStartDate(moment());
            // $('#product_purchase_date_filter').data('daterangepicker').setStartDate(moment().subtract(6, 'd'));
            $('#product_purchase_date_filter').data('daterangepicker').setEndDate(moment());
        }
        $(
            '#stock_report_filter_form #location_id, #stock_report_filter_form #category_id, #stock_report_filter_form #sub_category_id, #stock_report_filter_form #brand,#stock_report_filter_form #suppliers, #stock_report_filter_form #unit,#stock_report_filter_form #view_stock_filter,#product_list_to_date, #product_list_from_date#product_sr_date_filter'
        ).change(function() {
            stock_in_table.ajax.reload();
            stock_out_table.ajax.reload();
            stock_in_out_grouped_report_table.ajax.reload();
        });
    </script>
@endsection
