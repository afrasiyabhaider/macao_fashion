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
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('Product Type', __('Product Type') . ':') !!}
                            {!! Form::select('P_type', ['1' => 'Known Product', '2' => 'Unknown Product'], null, [
                                // 'placeholder' => __('messages.all'),
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'product_type',
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
                                        <h4>Total stock in: <span class="totalPurchaseSum" data-currency_symbol="false"></span>
                                        </h4>
                                        <h4>Total stock out: <span class="stockOUT" data-currency_symbol="false"></span></h4>
                                        <h4 hidden>Total stock out: <span class="total_stock_out1"
                                                data-currency_symbol="false"></span></h4>
                                        <h4>Total Purchase Amount: <span class="total_buying_amount"
                                                data-currency_symbol="false"></span></h4>
                                        <h4>Total Sell Amount: <span class="sold_price" data-currency_symbol="false"></span></h4>
                                        <h4 hidden>Total sale price: <span class="total_sell_price1"
                                                data-currency_symbol="false"></span></h4>
                                        {{-- <h4>Total reference: <span class="total_refference1"data-currency_symbol="false"></span>
                                        </h4> --}}
                                        <h4>Total Discount Amount : <span
                                                class="discount_amount11"data-currency_symbol="false"></span></h4>
                                        <h4>Total unknown product sold : <span
                                                class="unknown_soldUnknown"data-currency_symbol="false"></span></h4>
                                        <h4 hidden>Total unknown price : <span
                                                class="unknown_sold_price"data-currency_symbol="false"></span></h4>
                                        {{-- @include('report.partials.stock_group_table') --}}
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
    <script>
        $(document).ready(function() {
            getCount()
            getCountUnKnown()
            getselltotal()
            getpurchasetotal()
            // calculations() 
        });

        function getCount() {
            var start = null;
            var end = null;
            if ($('#product_purchase_date_filter').val()) {
                start = $('input#product_purchase_date_filter')
                    .data('daterangepicker')
                    .startDate.format('YYYY-MM-DD');
                end = $('input#product_purchase_date_filter')
                    .data('daterangepicker')
                    .endDate.format('YYYY-MM-DD');
            }
            $.ajax({
                url: '/reports/stock-in-out-grouped-report-total',
                type: 'POST',
                data: {
                    location_id: ($('#location_id').val() === '0') ? '' : $('#location_id').val(),
                    // product_type: $('#product_type').val(),
                    start_date: start,
                    end_date: end,
                    // start_date: '2024-02-12',
                    // end_date: '2024-02-12',
                },
                success: function(response) {
                    $('.total_stock1').text(response.totalStock);
                    $('.total_stock_out1').text(response.totalSoldSum);
                    $('.total_buying_amount1').text(Number(response.totalBuyingAmountSum).toFixed(
                        2));
                    $('.total_sell_price1').text(Number(response.totalSellPriceSum).toFixed(2));
                    $('.total_refference1').text(response.totalReffernce);
                    $('.discount_amount11').text(Number(response.totalDiscountSum).toFixed(2));
                    // calculations();
                },
                error: function(xhr, status, error) {
                    console.error('Ajax call failed. Status: ' + status + ', Error: ' + error);
                }
            });
        }

        function getselltotal() {
            var start = null;
            var end = null;
            if ($('#product_purchase_date_filter').val()) {
                start = $('input#product_purchase_date_filter')
                    .data('daterangepicker')
                    .startDate.format('YYYY-MM-DD');
                end = $('input#product_purchase_date_filter')
                    .data('daterangepicker')
                    .endDate.format('YYYY-MM-DD');
            }
            $.ajax({
                url: '/reports/stock-in-out-grouped-report-total-sell',
                type: 'POST',
                data: {
                    location_id: $('#location_id').val(),
                    start_date: start,
                    end_date: end,
                },
                success: function(response) {
                    $('.stockOUT').text(response.totalQtySold);
                    $('.sold_price').text(Number(response.totalSoldSum).toFixed(2));
                    // calculations();

                },
                error: function(xhr, status, error) {
                    console.error('Ajax call failed. Status: ' + status + ', Error: ' + error);
                }
            });
        }

        function getpurchasetotal() {
            var start = null;
            var end = null;
            if ($('#product_purchase_date_filter').val()) {
                start = $('input#product_purchase_date_filter')
                    .data('daterangepicker')
                    .startDate.format('YYYY-MM-DD');
                end = $('input#product_purchase_date_filter')
                    .data('daterangepicker')
                    .endDate.format('YYYY-MM-DD');
            }
            $.ajax({
                url: '/reports/stock-in-out-grouped-report-total-purchase',
                type: 'POST',
                data: {
                    location_id: ($('#location_id').val() === '0') ? '' : $('#location_id').val(),
                    start_date: start,
                    end_date: end,
                    // start_date: '2024-02-12',
                    // end_date: '2024-02-12',
                },
                success: function(response) {
                    $('.totalPurchaseSum').text(response.totalPurchaseSum);
                    $('.total_buying_amount').text(Number(response.totalPurchasePrice).toFixed(2));
                    // calculations();

                },
                error: function(xhr, status, error) {
                    console.error('Ajax call failed. Status: ' + status + ', Error: ' + error);
                }
            });
        }

        function getCountUnKnown() {
            var start = null;
            var end = null;
            if ($('#product_purchase_date_filter').val()) {
                start = $('input#product_purchase_date_filter')
                    .data('daterangepicker')
                    .startDate.format('YYYY-MM-DD');
                end = $('input#product_purchase_date_filter')
                    .data('daterangepicker')
                    .endDate.format('YYYY-MM-DD');
            }
            $.ajax({
                url: '/reports/stock-in-out-grouped-report-total-unknown',
                type: 'POST',
                data: {
                    location_id: $('#location_id').val(),
                    // product_type: $('#product_type').val(),
                    start_date: start,
                    end_date: end,
                },
                success: function(response) {
                    $('.unknown_soldUnknown').text(response.totalSoldSum);
                    // $('.unknown_sold_price').text(Number(response.totalSellPriceSum).toFixed(2));
                    // $('.total_refferenceUnknown').text(response.totalReffernce);
                    $('.discount_amountUnknown').text(Number(response.totalDiscountSum).toFixed(2));
                    // calculations();

                },
                error: function(xhr, status, error) {
                    console.error('Ajax call failed. Status: ' + status + ', Error: ' + error);
                }
            });
        }

        // function calculations() {
        //     var unknown_sold_price = parseFloat(document.querySelector('.unknown_sold_price').innerText);
        //     var total_sell_price1 = parseFloat(document.querySelector('.total_sell_price1').innerText);
        //     var unknown_soldUnknown = parseFloat(document.querySelector('.unknown_soldUnknown').innerText);
        //     // var total_stock_out1 = parseFloat(document.querySelector('.total_stock_out1').innerText);
        //     // Sum the values
        //     // var sold_price = unknown_sold_price + total_sell_price1;
        //     // var sold_price =  total_sell_price1;
        //     // var stockOUT = unknown_soldUnknown + total_stock_out1;
        //     $('.sold_price').text(Number(sold_price).toFixed(2));
        //     // $('.stockOUT').text(Number(stockOUT));
        //     // console.log(sold_price);
        // }


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
                    if ($('#product_purchase_date_filter').val()) {
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
                    d.supplier_id = $('#suppliers').val();
                    d.product_type = $('#product_type').val();
                    d.unit_id = $('#unit').val();
                },
            },
            pageLength: -1,
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
                {
                    data: 'refference',
                    name: 'refference'
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
                    data: 'main_transfered',
                    name: 'main_transfered',
                    searchable: false
                },
                {
                    data: 'subshop_transfered',
                    name: 'subshop_transfered',
                    searchable: false
                },
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
                {
                    data: 'stock_in',
                    name: 'stock_in',
                    searchable: false
                },
                // 
                {
                    data: 'total_sold',
                    name: 'total_sold',
                    searchable: false
                },
                //     {
                //     data: 'stock',
                //     name: 'stock',
                //     searchable: false
                // },

                {
                    data: 'total_sale_price',
                    name: 'total_sale_price',
                    searchable: false
                },
                {
                    data: 'discount_amount',
                    name: 'discount_amount',
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
                    'stock_in'));
                $('.total_stock_out').html(sum_table_col($('#stock_in_out_grouped_report_table'),
                    'total_sold'));
                $('.total').html(__sum_stock($('#stock_in_out_grouped_report_table'),
                    'current_stock'));
                $('.total_sell_price').text(sum_table_col($('#stock_in_out_grouped_report_table'),
                    'total_sale_price').toFixed(2));
                $('.total_buying_amount').text(sum_table_col($('#stock_in_out_grouped_report_table'),
                    'row_subtotal').toFixed(2));
                $('.discount_amount1').text(sum_table_col($('#stock_in_out_grouped_report_table'),
                    'discount_amount').toFixed(2));

                let total_refference = 0;
                $.each([4], function(index, value) {
                    api.column(value).data()
                        .reduce(function(a, b) {
                            total_refference += 1;
                        }, 0)
                });
                $('.total_refference').html(total_refference);
                __currency_convert_recursively($('#stock_in_out_grouped_report_table'));
            },
        });

        // old stockIn report
        // stock_in_table = $('#stock_in_table').DataTable({
        //     processing: true,
        //     serverSide: true,
        //     ajax: {
        //         url: '/reports/stock-in-out',
        //         data: function(d) {
        //             var start = '';
        //             var end = '';
        //             var start = $.datepicker.formatDate('yy-mm-dd', new Date());
        //             var end = $.datepicker.formatDate('yy-mm-dd', new Date());
        //             if ($('#product_purchase_date_filter').val()) {
        //                 start = $('input#product_purchase_date_filter')
        //                     .data('daterangepicker')
        //                     .startDate.format('YYYY-MM-DD');
        //                 end = $('input#product_purchase_date_filter')
        //                     .data('daterangepicker')
        //                     .endDate.format('YYYY-MM-DD');
        //             }
        //             d.start_date = start;
        //             d.end_date = end;

        //             d.location_id = $('#location_id').val();
        //             d.category_id = $('#category_id').val();
        //             d.sub_category_id = $('#sub_category_id').val();
        //         },
        //     },
        //     pageLength: 300,
        //     lengthMenu: [
        //         [30, 40, 60, 80, 90, 100, 150, 300, 500, 1000, -1],
        //         [30, 40, 60, 80, 90, 100, 150, 300, 500, 1000, 'All'],
        //     ],
        //     columns: [{
        //             data: 'DT_Row_Index',
        //             name: 'DT_Row_Index',
        //             searchable: false,
        //             orderable: false
        //         },
        //         {
        //             data: 'image',
        //             name: 'image',
        //             orderable: false,
        //             searchable: false
        //         },
        //         {
        //             data: 'sku',
        //             name: 'variations.sub_sku'
        //         },
        //         {
        //             data: 'product',
        //             name: 'p.name'
        //         },
        //         {
        //             data: 'refference',
        //             name: 'p.refference'
        //         },
        //         {
        //             data: 'location_name',
        //             name: 'bl.name'
        //         },
        //         {
        //             data: 'unit_price',
        //             name: 'variations.sell_price_inc_tax'
        //         },
        //         {
        //             data: 'color_name',
        //             name: 'colors.name'
        //         },
        //         {
        //             data: 'category_name',
        //             name: 'categories.name'
        //         },
        //         {
        //             data: 'sub_category_name',
        //             name: 'sub_cat.name'
        //         },
        //         {
        //             data: 'size_name',
        //             name: 'sizes.name'
        //         },
        //         {
        //             data: 'total_transfered',
        //             name: 'total_transfered',
        //             searchable: false
        //         },
        //         {
        //             data: 'total_qty',
        //             name: 'total_qty',
        //             searchable: false
        //         },
        //         {
        //             data: 'product_date',
        //             name: 'vld.updated_at'
        //         },
        //     ],
        //     fnDrawCallback: function(oSettings) {
        //         //     $('#footer_total_stock').html(__sum_stock($('#stock_in_table'), 'current_stock'));
        //         //     $('#footer_total_sold').html(__sum_stock($('#stock_in_table'), 'total_sold'));
        //         //     $('#footer_total_transfered').html(
        //         //         __sum_stock($('#stock_in_table'), 'total_transfered')
        //         //     );
        //         //     $('#footer_total_adjusted').html(
        //         //         __sum_stock($('#stock_in_table'), 'total_adjusted')
        //         //     );
        //         //     __currency_convert_recursively($('#stock_in_table'));
        //     },
        // });

        // new stockIn report
        stock_in_table = $('#stock_in_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/reports/stock-in-out',
                // ini_set:('max_execution_time', 180),
                data: function(d) {

                    var start = '';
                    var end = '';
                    var start = $.datepicker.formatDate('yy-mm-dd', new Date());
                    var end = $.datepicker.formatDate('yy-mm-dd', new Date());
                    if ($('#product_purchase_date_filter').val()) {
                        start = $('input#product_purchase_date_filter')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        end = $('input#product_purchase_date_filter')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                    }
                    d.start_date = start;
                    d.end_date = end;
                    // d.start_date = '2024-02-12';
                    // d.end_date = '2024-02-12';

                    d.location_id = $('#location_id').val();
                    d.category_id = $('#category_id').val();
                    d.sub_category_id = $('#sub_category_id').val();
                    d.supplier_id = $('#suppliers').val();
                    // d.from_date = $('#product_list_from_date').val();
                    // d.to_date = $('#product_list_to_date').val();
                    d.unit_id = $('#unit').val();

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
                    data: 'image',
                    name: 'image',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'sku',
                    name: 'variations.sub_sku'
                },
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
                {
                    data: 'stock',
                    name: 'stock',
                    searchable: false
                },
                {
                    data: 'supplier_name',
                    name: 'suppliers.name'
                },
                {
                    data: 'product_date',
                    name: 'vld.product_updated_at'
                },
            ],
            fnDrawCallback: function(oSettings) {
                $('#footer_total_stock').html(__sum_stock($('#stock_in_table'), 'current_stock'));
                $('#footer_total_sold').html(__sum_stock($('#stock_in_table'), 'total_sold'));
                $('#footer_total_transfered').html(
                    __sum_stock($('#stock_in_table'), 'total_transfered')
                );
                $('#footer_total_adjusted').html(
                    __sum_stock($('#stock_in_table'), 'total_adjusted')
                );
                __currency_convert_recursively($('#stock_in_table'));
            },
        });

        // old stockout 
        // stock_out_table = $('#stock_out_table').DataTable({
        //     processing: true,
        //     serverSide: true,
        //     ajax: {
        //         url: '/reports/stock-out',
        //         data: function(d) {
        //             var start = '';
        //             var end = '';
        //             var start = $.datepicker.formatDate('yy-mm-dd', new Date());
        //             var end = $.datepicker.formatDate('yy-mm-dd', new Date());
        //             if ($('#product_purchase_date_filter').val()) {
        //                 start = $('input#product_purchase_date_filter')
        //                     .data('daterangepicker')
        //                     .startDate.format('YYYY-MM-DD');
        //                 end = $('input#product_purchase_date_filter')
        //                     .data('daterangepicker')
        //                     .endDate.format('YYYY-MM-DD');
        //             }
        //             console.log(start, end);
        //             d.start_date = start;
        //             d.end_date = end;

        //             d.location_id = $('#location_id').val();
        //             d.category_id = $('#category_id').val();
        //             d.sub_category_id = $('#sub_category_id').val();
        //         },
        //     },
        //     pageLength: 300,
        //     lengthMenu: [
        //         [30, 40, 60, 80, 90, 100, 150, 300, 500, 1000, -1],
        //         [30, 40, 60, 80, 90, 100, 150, 300, 500, 1000, 'All'],
        //     ],
        //     columns: [{
        //             data: 'DT_Row_Index',
        //             name: 'DT_Row_Index',
        //             searchable: false,
        //             orderable: false
        //         },
        //         {
        //             data: 'image',
        //             name: 'image',
        //             orderable: false,
        //             searchable: false
        //         },
        //         {
        //             data: 'sku',
        //             name: 'variations.sub_sku'
        //         },
        //         {
        //             data: 'product',
        //             name: 'p.name'
        //         },
        //         {
        //             data: 'refference',
        //             name: 'p.refference'
        //         },
        //         {
        //             data: 'location_name',
        //             name: 'bl.name'
        //         },
        //         {
        //             data: 'unit_price',
        //             name: 'variations.sell_price_inc_tax'
        //         },
        //         {
        //             data: 'color_name',
        //             name: 'colors.name'
        //         },
        //         {
        //             data: 'category_name',
        //             name: 'categories.name'
        //         },
        //         {
        //             data: 'sub_category_name',
        //             name: 'sub_cat.name'
        //         },
        //         {
        //             data: 'size_name',
        //             name: 'sizes.name'
        //         },
        //         {
        //             data: 'total_sold',
        //             name: 'total_sold',
        //             searchable: false
        //         },
        //         {
        //             data: 'total_transfered',
        //             name: 'total_transfered',
        //             searchable: false
        //         },
        //         {
        //             data: 'product_date',
        //             name: 'vld.updated_at'
        //         },
        //     ],
        //     fnDrawCallback: function(oSettings) {
        //         $('#footer_total_stock').html(__sum_stock($('#stock_out_table'), 'current_stock'));
        //         $('#footer_total_sold').html(__sum_stock($('#stock_out_table'), 'total_sold'));
        //         $('#footer_total_transfered').html(
        //             __sum_stock($('#stock_out_table'), 'total_transfered')
        //         );
        //         $('#footer_total_adjusted').html(
        //             __sum_stock($('#stock_out_table'), 'total_adjusted')
        //         );
        //         __currency_convert_recursively($('#stock_out_table'));
        //     },
        // });

        // new stockout
        stock_out_table = $('table#stock_out_table').DataTable({
            processing: true,
            serverSide: true,
            pageLength: -1,
            lengthMenu: [
                [20, 50, 70, 100, 150, 300, 500, 1000, -1],
                [20, 50, 70, 100, 150, 300, 500, 1000, 'All'],
            ],
            aaSorting: [
                [1, 'desc']
            ],
            ajax: {
                url: '/reports/stock-out',
                data: function(d) {
                    var start = '';
                    var end = '';
                    var start = $.datepicker.formatDate('yy-mm-dd', new Date());
                    var end = $.datepicker.formatDate('yy-mm-dd', new Date());
                    if ($('#product_purchase_date_filter').val()) {
                        start = $('input#product_purchase_date_filter')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        end = $('input#product_purchase_date_filter')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                    }
                    d.start_date = start;
                    d.end_date = end;
                    // d.start_date = '2024-02-12';
                    // d.end_date = '2024-02-12';

                    d.location_id = $('#location_id').val();
                    d.category_id = $('#category_id').val();
                    d.sub_category_id = $('#sub_category_id').val();
                },
            },
            columns: [{
                    data: 'image',
                    name: 'products.image',
                    searchable: false,
                    orderable: false
                },


                {
                    data: 'product_name',
                    name: 'p.name'
                },
                // { data: 'product_updated_at', name: 'p.product_updated_at' },
                {
                    data: 'refference',
                    name: 'p.refference'
                },
                {
                    data: 'total_qty_sold',
                    name: 'total_qty_sold',
                    searchable: false
                },
                {
                    data: 'unit_price',
                    name: 'unit_price'
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
                // { data: 'total_sold', searchable: false, orderable: false },
                // { data: 'barcode', name: 'p.sku' },
                // { data: 'transaction_date', name: 't.transaction_date' },

                // {
                //     data: 'sale_percentage',
                //     name: 'sale_percentage',
                //     searchable: false
                // },
                {
                    data: 'subtotal',
                    name: 'subtotal',
                    searchable: false
                },
            ],
            fnDrawCallback: function() {
                $('#footer_grouped_subtotal').text(
                    sum_table_col($('#stock_out_table'), 'row_subtotal')
                );
                $('#footer_total_grouped_sold').html(
                    __sum_stock($('#stock_out_table'), 'sell_qty')
                );
                __currency_convert_recursively($('#stock_out_table'));
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
                stock_out_table.ajax.reload();
                stock_in_out_grouped_report_table.ajax.reload();
                getCount();
                getCountUnKnown();
                getselltotal();
                getpurchasetotal();


            });
            $('#product_purchase_date_filter').on('cancel.daterangepicker', function(ev, picker) {
                $('#product_purchase_date_filter').val('');
                stock_in_table.ajax.reload();
                stock_out_table.ajax.reload();
                stock_in_out_grouped_report_table.ajax.reload();
                getCount();
                getCountUnKnown();
                getselltotal();
                getpurchasetotal();


            });
            $('#product_purchase_date_filter').data('daterangepicker').setStartDate(moment());
            $('#product_purchase_date_filter').data('daterangepicker').setEndDate(moment());
        }
        $(
            '#stock_report_filter_form #location_id, #product_type, #stock_report_filter_form #category_id, #stock_report_filter_form #sub_category_id, #stock_report_filter_form #brand,#stock_report_filter_form #suppliers, #stock_report_filter_form #unit,#stock_report_filter_form #view_stock_filter,#product_list_to_date, #product_list_from_date#product_sr_date_filter'
        ).change(function() {
            stock_in_table.ajax.reload();
            stock_out_table.ajax.reload();
            stock_in_out_grouped_report_table.ajax.reload();
            getCount();
            getCountUnKnown();
            getselltotal();
            getpurchasetotal();

        });
    </script>
@endsection
