@extends('layouts.app')
@section('title','Website Products')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        Website Product List
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
<div class="row">
    <div class="col-md-12">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-4">
            {{-- <div class="form-group">
                {!! Form::label('type', __('product.product_type') . ':') !!}
                {!! Form::select('type', ['product' => 'Products', 'gift_card' => 'Gift Cards', 'coupon' => 'Coupons'], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_type', 'placeholder' => __('lang_v1.all')]); !!}
            </div> --}}
            <div class="form-group">
                {!! Form::label('supplier_id', __('product.supplier') . ':') !!}
                {!! Form::select('supplier_id', $suppliers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_type', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
            {{-- <div class="form-group">
                <label>
                    Supplier
                </label>
                <select name="supplier_id" id="product_list_filter_type" class="form-control select2">
                    <option value="">All</option>
                    @foreach ($suppliers as $key=>$item)
                        <option value="{{$key}}">
                            {{$item}}
                        </option>
                    @endforeach
                </select>
            </div> --}}
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('category_id', __('product.category') . ':') !!}
                {!! Form::select('category_id', $categories, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'category_id', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('sub_category_id', __('product.subcategory') . ':') !!}
                {!! Form::select('sub_category_id', [], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_category_id', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>

        <!--<div class="col-md-3">-->
        <!--    <div class="form-group">-->
        <!--        {!! Form::label('unit_id', __('product.unit') . ':') !!}-->
        <!--        {!! Form::select('unit_id', $units, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_unit_id', 'placeholder' => __('lang_v1.all')]); !!}-->
        <!--    </div>-->
        <!--</div>-->
        <!--<div class="col-md-3">-->
        <!--    <div class="form-group">-->
        <!--        {!! Form::label('tax_id', __('product.tax') . ':') !!}-->
        <!--        {!! Form::select('tax_id', $taxes, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_tax_id', 'placeholder' => __('lang_v1.all')]); !!}-->
        <!--    </div>-->
        <!--</div>-->
        <!--<div class="col-md-3">-->
        <!--    <div class="form-group">-->
        <!--        {!! Form::label('brand_id', __('product.brand') . ':') !!}-->
        <!--        {!! Form::select('brand_id', $brands, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_brand_id', 'placeholder' => __('lang_v1.all')]); !!}-->
        <!--    </div>-->
        <!--</div>-->
        <!--<div class="col-md-3 hide" id="location_filter">-->
        <!--    <div class="form-group">-->
        <!--        {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}-->
        <!--        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}-->
        <!--    </div>-->
        <!--</div>-->
        <!--<div class="col-md-3  " id="location_filter">-->
        <!--    <div class="form-group">-->
        <!--        {!! Form::label('p_type',   ' Product By:') !!}-->
        <!--        {!! Form::select('p_type', ['product' => 'Products', 'gift_card' => 'Gift Cards'], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_brand_id', 'placeholder' => __('lang_v1.all')]); !!}-->
        <!--    </div>-->
        <!--</div>-->
        <div class="form-row">
            <div id="location_filter">
                <div class="form-group col-md-6">
                    {!! Form::label('from_date',   ' From Date:') !!}
                    <input type="date" name="product_list_from_date" value="{{date('Y-m-d')}}" id="product_list_from_date" class="form-control">
                </div>
                <div class="form-group col-md-6">
                    {!! Form::label('to_date',   ' To Date:') !!}
                    <input type="date" name="product_list_to_date" id="product_list_to_date" value="" class="form-control">
                </div> 
            </div>
        </div>
    @endcomponent
    </div>
</div>
@can('product.view')
    <div class="row">
        <div class="col-md-12">
           <!-- Custom Tabs -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#product_list_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-desktop" aria-hidden="true"></i>
                            Website Products
                        </a>
                    </li>

                    {{-- <li>
                        <a href="#product_stock_report" data-toggle="tab" aria-expanded="true"><i class="fa fa-hourglass-half" aria-hidden="true"></i> @lang('report.stock_report')</a>
                    </li> --}}
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="product_list_tab">
                       
                       @inject('request', 'Illuminate\Http\Request')
                        <div class="row" style="margin-bottom: 20px;">
                            <div class="col-md-3">
                                <div class="@if((url()->current() == url('products')) ||(url()->current() == url('website/product/list')) ) hidden @endif"">
                                    {!! Form::open(['url' => action('ProductController@massTransfer'), 'method' => 'post', 'id' => 'bulkTransfer_form','class' => 'ml-5' ]) !!}
                                    {!! Form::hidden('selected_products_bulkTransfer', null, ['id' => 'selected_products_bulkTransfer']); !!}
                                    {!! Form::hidden('selected_products_qty_bulkTransfer', null, ['id' => 'selected_products_qty_bulkTransfer']); !!}
                                    {!! Form::hidden('bussiness_bulkTransfer', null, ['id' => 'bussiness_bulkTransfer']); !!}
                                    {{-- {!! Form::submit(' Transfer Selected', array('class' => 'btn btn-warning', 'id' => 'bulkTransfer-selected')) !!} --}}
                                    <button type=" submit" class="btn btn-warning" id="bulkTransfer-selected">
                                    <i class="fa fa-random"></i>
                                    Transfer Selected
                                    </button>
                                    {!! Form::close() !!}
                                </div>
                            </div>
                            <div class="col-md-6"></div>
                            <div class="col-md-3 align-right">
                                @can('product.create')
                                <a class="btn btn-primary pull-left" href="{{url('products/bulk_add')}}">
                                    <i class="fa fa-plus"></i>
                                    @lang('messages.add')
                                </a>
                                @endcan
                                {!! Form::open(['url' => action('ProductController@massBulkPrint'), 'method' => 'post', 'id' => 'bulkPrint_form'
                                ]) !!}
                                {!! Form::hidden('selected_products_bulkPrint', null, ['id' => 'selected_products_bulkPrint']); !!}
                                {!! Form::hidden('selected_products_bulkPrint_qty', null, ['id' => 'selected_products_bulkPrint_qty']); !!}
                                {!! Form::hidden('printing_location_id', 1, ['id' => 'printing_location_id']); !!}
                                <button type="submit" class="btn btn-success pull-left" id="bulkPrint-selected" style="margin-left: 20px">
                                    <i class="fa fa-print"></i>
                                    Print Selected
                                </button>
                                {{-- {!! Form::submit('Print Selected', array('class' => 'btn btn-md btn-warning', 'id' => 'bulkPrint-selected')) !!} --}}
                                {!! Form::close() !!}
                                {{-- <th>
                                    <input type="checkbox" id="select-all-row">
                                    Select All
                                </th> --}}
                            </div>
                        </div>
                        <div class="table-responsive">
                            {{-- @dd($request->segment(2)) --}}
                            <table class="table table-bordered table-striped ajax_view table-text-center" id="product_table">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="select-all-row">
                                            Select All
                                        </th>
                                        <th>Image</th>
                                        <th>Website Images</th>
                                        <th>@lang('sale.product')</th>
                                        <th>@lang('messages.action')</th>
                                        <th>Refference</th>
                                        <th>Purchase Price</th>
                                        <th>@lang('lang_v1.selling_price')</th>
                                        <th>@lang('product.color')</th>
                                        <th>@lang('product.size')</th>
                                        <th>@lang('report.current_stock')</th>
                                        <th>@lang('product.product_type')</th>
                                        <th>Suppliers</th>
                                        <th>@lang('product.category')</th>
                                        <th>@lang('product.sub_category')</th>
                                        <th>Date</th>
                                        <th>BulkCode</th>
                                        <th>Barcode</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <td colspan="12">
                                            <div style="display: flex; width: 100%;">
                                                @can('product.delete')
                                                {!! Form::open(['url' => action('ProductController@massDestroy'), 'method' => 'post', 'id' =>
                                                'mass_delete_form' ]) !!}
                                                {!! Form::hidden('selected_rows', null, ['id' => 'selected_rows']); !!}
                                                {!! Form::submit(__('lang_v1.delete_selected'), array('class' => 'btn btn-xs btn-danger', 'id'
                                                => 'delete-selected')) !!}
                                                {!! Form::close() !!}
                                                @endcan
                                                &nbsp;
                                                {!! Form::open(['url' => action('ProductController@massDeactivate'), 'method' => 'post', 'id' =>
                                                'mass_deactivate_form' ]) !!}
                                                {!! Form::hidden('selected_products', null, ['id' => 'selected_products']); !!}
                                                {!! Form::submit(__('lang_v1.deactivate_selected'), array('class' => 'btn btn-xs btn-warning',
                                                'id' => 'deactivate-selected')) !!}
                                                {!! Form::close() !!} @show_tooltip(__('lang_v1.deactive_product_tooltip'))
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- <div class="tab-pane" id="product_stock_report">
                        @include('report.partials.stock_report_table')
                    </div> --}}
                </div>
            </div>
        </div>
    </div>
@endcan
<input type="hidden" id="is_rack_enabled" value="{{$rack_enabled}}">

<div class="modal fade product_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade" id="view_product_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade" id="opening_stock_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

</section>
<!-- /.content -->

@endsection

@section('javascript')
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        function TransferSelected()
        {
            var transferBussiness = $("#transferBussiness option:selected").val();
            if(transferBussiness == "" || transferBussiness == undefined)
            {
                alert("Please Choose Bussiness First to Transfer ");return(false);
            }
            $("#bussiness_bulkTransfer").val(transferBussiness);
            $('form#bulkTransfer_form').submit();
        }
        $(document).ready( function(){
            product_table = $('#product_table').DataTable({
                processing: true,
                serverSide: true,
                "ajax": {
                    "url": "/website/product/list",
                    "data": function ( d ) {
                        console.log(d);
                        d.supplier_id = $('#product_list_filter_type').val();
                        d.category_id = $('#product_list_filter_category_id').val();
                        d.brand_id = $('#product_list_filter_brand_id').val();
                        d.unit_id = $('#product_list_filter_unit_id').val();
                        d.tax_id = $('#product_list_filter_tax_id').val();
                        d.from_date = $('#product_list_from_date').val();
                        d.to_date = $('#product_list_to_date').val();
                    }
                },
                columnDefs: [ {
                    "targets": [0, 1, 11],
                    "orderable": false,
                    "searchable": false
                } ],
                aaSorting: [2, 'asc'],
                columns: [
                        { data: 'mass_delete'  },
                        { data: 'image', name: 'products.image'  },
                        { data: 'website_images', name: 'website_images'  },
                        { data: 'product', name: 'products.name'  },
                        { data: 'action', name: 'action'},
                        { data: 'refference', name: 'products.refference'  },
                        { data: 'purchase_price', name: 'purchase_price', searchable: false},
                        { data: 'selling_price', name: 'selling_price', searchable: false},
                        { data: 'color', name: 'color_id'},
                        { data: 'size', name: 'sub_size_id'},
                        { data: 'current_stock', searchable: false},
                        { data: 'type', name: 'products.type'},
                        { data: 'supplier_name', name: 'products.supplier_id'},
                        { data: 'category', name: 'c1.name'},
                        { data: 'sub_category', name: 'c2.name'},
                        { data: 'date', name: 'products.created_at'},
                        { data: 'bulk_add', name: 'products.bulk_add', searchable: true},
                        { data: 'sku', name: 'products.sku'},
                        { data: 'description', name: 'products.description'},
                    ],
                    createdRow: function( row, data, dataIndex ) {
                        if($('input#is_rack_enabled').val() == 1){
                            var target_col = 0;
                            @can('product.delete')
                                target_col = 1;
                            @endcan
                            $( row ).find('td:eq('+target_col+') div').prepend('<i style="margin:auto;" class="fa fa-plus-circle text-success cursor-pointer no-print rack-details" title="' + LANG.details + '"></i>&nbsp;&nbsp;');
                        }
                        $( row ).find('td:eq(0)').attr('class', 'selectable_td');
                    },
                    fnDrawCallback: function(oSettings) {
                        __currency_convert_recursively($('#product_table'));
                    },
            });
            // Array to track the ids of the details displayed rows
            var detailRows = [];

            $('#product_table tbody').on( 'click', 'tr i.rack-details', function () {
                var i = $(this);
                var tr = $(this).closest('tr');
                var row = product_table.row( tr );
                var idx = $.inArray( tr.attr('id'), detailRows );

                if ( row.child.isShown() ) {
                    i.addClass( 'fa-plus-circle text-success' );
                    i.removeClass( 'fa-minus-circle text-danger' );

                    row.child.hide();
         
                    // Remove from the 'open' array
                    detailRows.splice( idx, 1 );
                } else {
                    i.removeClass( 'fa-plus-circle text-success' );
                    i.addClass( 'fa-minus-circle text-danger' );

                    row.child( get_product_details( row.data() ) ).show();
         
                    // Add to the 'open' array
                    if ( idx === -1 ) {
                        detailRows.push( tr.attr('id') );
                    }
                }
            });

            $('table#product_table tbody').on('click', 'a.delete-product', function(e){
                e.preventDefault();
                swal({
                  title: LANG.sure,
                  icon: "warning",
                  buttons: true,
                  dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: "DELETE",
                            url: href,
                            dataType: "json",
                            success: function(result){
                                if(result.success == true){
                                    toastr.success(result.msg);
                                    product_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $(document).on('click', '#delete-selected', function(e){
                e.preventDefault();
                var selected_rows = [];
                var i = 0;
                $('.row-select:checked').each(function () {
                    selected_rows[i++] = $(this).val();
                }); 
                
                if(selected_rows.length > 0){
                    $('input#selected_rows').val(selected_rows);
                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            $('form#mass_delete_form').submit();
                        }
                    });
                } else{
                    $('input#selected_rows').val('');
                    swal('@lang("lang_v1.no_row_selected")');
                }    
            });

            $(document).on('click', '#deactivate-selected', function(e){
                e.preventDefault();
                var selected_rows = [];
                var i = 0;
                $('.row-select:checked').each(function () {
                    selected_rows[i++] = $(this).val();
                }); 
                
                if(selected_rows.length > 0){
                    $('input#selected_products').val(selected_rows);
                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            $('form#mass_deactivate_form').submit();
                        }
                    });
                } else{
                    $('input#selected_products').val('');
                    swal('@lang("lang_v1.no_row_selected")');
                }    
            })

            $(document).on('click', '#bulkPrint-selected', function(e){
                e.preventDefault();
                var selected_rows = [];
                var i = 0;
                $('.row-select:checked').each(function () {
                    selected_rows[i++] = $(this).val();
                }); 
                
                if(selected_rows.length > 0){
                    $('input#selected_products_bulkPrint').val(selected_rows);
                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            $('form#bulkPrint_form').submit();
                        }
                    });
                } else{
                    $('input#selected_products_bulkPrint').val('');
                    swal('@lang("lang_v1.no_row_selected")');
                }    
            })


            $(document).on('click', '#bulkTransfer-selected', function(e){
                e.preventDefault();
                var selected_rows = [];
                var i = 0;
                $('.row-select:checked').each(function () {
                    selected_rows[i++] = $(this).val();
                }); 
                
                if(selected_rows.length > 0){
                    $('input#selected_products_bulkTransfer').val(selected_rows);
                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            $('#unknownDiscountModal').modal('show'); 
                            // $('form#bulkTransfer_form').submit();
                        }
                    });
                } else{
                    $('input#selected_products_bulkTransfer').val('');
                    swal('@lang("lang_v1.no_row_selected")');
                }    
            })

            $('table#product_table tbody').on('click', 'a.activate-product', function(e){
                e.preventDefault();
                var href = $(this).attr('href');
                $.ajax({
                    method: "get",
                    url: href,
                    dataType: "json",
                    success: function(result){
                        if(result.success == true){
                            toastr.success(result.msg);
                            product_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

            $(document).on('change', '#product_list_filter_type, #product_list_filter_category_id, #product_list_filter_brand_id, #product_list_filter_unit_id, #product_list_filter_tax_id, #location_id', 
            function() {
                if ($("#product_list_tab").hasClass('active')) {
                    product_table.ajax.reload();
                }

                if ($("#product_stock_report").hasClass('active')) {
                    stock_report_table.ajax.reload();
                }
            });
            $(document).on('change', '#product_list_from_date', 
                function() {
                    $("#product_list_to_date").val(null);
            });
            $(document).on('change', '#product_list_to_date', 
                function() {
                    if ($("#product_list_tab").hasClass('active')) {
                        product_table.ajax.reload();
                    }

                    if ($("#product_stock_report").hasClass('active')) {
                        stock_report_table.ajax.reload();
                    }
            });
        });

        $(document).on('shown.bs.modal', 'div.view_product_modal, div.view_modal', function(){
            __currency_convert_recursively($(this));
        });
        var data_table_initailized = false;
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if ($(e.target).attr('href') == '#product_stock_report') {
                $('#location_filter').removeClass('hide');
                if (!data_table_initailized) {
                    //Stock report table
                    stock_report_table = $('#stock_report_table').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '/reports/stock-report',
                            data: function(d) {
                                d.location_id = $('#location_id').val();
                                d.category_id = $('#product_list_filter_category_id').val();
                                d.brand_id = $('#product_list_filter_brand_id').val();
                                d.unit_id = $('#product_list_filter_unit_id').val();
                                d.type = $('#product_list_filter_type').val();
                            }
                        },
                        columns: [
                            { data: 'sku', name: 'variations.sub_sku' },
                            { data: 'product', name: 'p.name' },
                            { data: 'unit_price', name: 'variations.sell_price_inc_tax' },
                            { data: 'stock', name: 'stock', searchable: false },
                            { data: 'total_sold', name: 'total_sold', searchable: false },
                            { data: 'total_transfered', name: 'total_transfered', searchable: false },
                            { data: 'total_adjusted', name: 'total_adjusted', searchable: false },
                        ],
                        fnDrawCallback: function(oSettings) {
                            $('#footer_total_stock').html(__sum_stock($('#stock_report_table'), 'current_stock'));
                            $('#footer_total_sold').html(__sum_stock($('#stock_report_table'), 'total_sold'));
                            $('#footer_total_transfered').html(
                                __sum_stock($('#stock_report_table'), 'total_transfered')
                            );
                            $('#footer_total_adjusted').html(
                                __sum_stock($('#stock_report_table'), 'total_adjusted')
                            );
                            __currency_convert_recursively($('#stock_report_table'));
                        },
                    });
                    data_table_initailized = true;
                } else {
                    stock_report_table.ajax.reload();
                }
            } else {
                $('#location_filter').addClass('hide');
                product_table.ajax.reload();
            }
        });
    </script>
    <script type="text/javascript">
  
    $(document).ready(function(){
      $("#searchDate").change(function(){
         alert($(this).val());
      });
    });
 </script> 
@endsection