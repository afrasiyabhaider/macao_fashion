@extends('layouts.app')
@section('title', __('sale.products'))
@section('css')
    <style>
        .product-thumbnail-small {
            height: 80px !important;
            width: 80px !important;
        }

        table.table-bordered.dataTable td {
            padding-top: 0px !important;
            padding-bottom: 0px !important;
            vertical-align: middle;
        }
    </style>

@endsection
@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        Transfer Products
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>
<div class="modal fade in" tabindex="-1" role="dialog" id="unknownDiscountModal" >
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button"  id="closeThis" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title">SELECT BUSSINESS</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('category_id', 'Business :') !!}
                            @foreach ($business_locations as $key=>$value)
                                @if ($key != 1 && $value != "Main Shop")
                                    @php
                                        $newBusiness_locations[$key] = $value;
                                    @endphp
                                @endif
                            @endforeach
                            {{-- {{dd(collect($newBusiness_locations))}} --}}
                            {{-- <select name="category_id" id="transferBusiness" class="form-control select2" style="width:100%">
                                <optgroup>
                                    <option value="all">{{__('lang_v1.all')}}</option>
                                    @foreach ($business_locations as $key=>$item)
                                        @if ($key != 1 && $item != "Main Shop")
                                            <option value="{{$key}}">
                                                {{$item}}
                                            </option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            </select> --}}
                            {!! Form::select('category_id', collect($newBusiness_locations), null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'transferBussiness', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="TransferSelected();">Finalize Transfer</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Ok</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
<!-- Main content -->
<section class="content">
<div class="row">
    <div class="col-md-12">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="row">
            <div class="col-md-4">
                {{-- <div class="form-group">
                    {!! Form::label('type', __('product.product_type') . ':') !!}
                    {!! Form::select('type', ['product' => 'Products', 'gift_card' => 'Gift Cards', 'coupon' => 'Coupons'], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_type', 'placeholder' => __('lang_v1.all')]); !!}
                </div> --}}
                <div class="form-group">
                    {!! Form::label('supplier_id', __('product.supplier') . ':') !!}
                    {!! Form::select('supplier_id', $suppliers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_type', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
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
        </div>

        <!-- <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('unit_id', __('product.unit') . ':') !!}
                {!! Form::select('unit_id', $units, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_unit_id', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('tax_id', __('product.tax') . ':') !!}
                {!! Form::select('tax_id', $taxes, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_tax_id', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('brand_id', __('product.brand') . ':') !!}
                {!! Form::select('brand_id', $brands, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_brand_id', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3 hide" id="location_filter">
            <div class="form-group">
                {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
            </div>
        </div>
        <div class="col-md-3  " id="location_filter">
            <div class="form-group">
                {!! Form::label('p_type',   ' Product By:') !!}
                {!! Form::select('p_type', ['product' => 'Products', 'gift_card' => 'Gift Cards'], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'product_list_filter_brand_id', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div> -->
        <div class="row" id="location_filter">
            <div class="form-group col-md-6">
                {!! Form::label('from_date',   ' From Date:') !!}
                <input type="date" name="product_list_from_date" value="{{date('Y-m-d')}}" id="product_list_from_date" class="form-control">
            </div>
            <div class="form-group col-md-6">
                {!! Form::label('to_date',   ' To Date:') !!}
                <input type="date" name="product_list_to_date" id="product_list_to_date" value="" class="form-control">
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
                        <a href="#product_list_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-random" aria-hidden="true"></i> Transfer Products</a>
                    </li>

                    <li>
                        <a href="#product_stock_report" data-toggle="tab" aria-expanded="true"><i class="fa fa-hourglass-half" aria-hidden="true"></i> @lang('report.stock_report')</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="product_list_tab">
                        @include('product.partials.product_list')
                    </div>
                    <div class="tab-pane" id="product_stock_report">
                        @include('report.partials.stock_report_table')
                    </div>
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
                    "url": "/products/transfer",
                    "data": function ( d ) {
                        // d.type = $('#product_list_filter_type').val();
                        d.supplier_id = $('#product_list_filter_type').val();
                        d.category_id = $('#category_id').val();
                        d.sub_category_id = $('#product_list_filter_category_id').val();
                        d.brand_id = $('#product_list_filter_brand_id').val();
                        d.unit_id = $('#product_list_filter_unit_id').val();
                        d.tax_id = $('#product_list_filter_tax_id').val();
                        d.from_date = $('#product_list_from_date').val();
                        d.to_date = $('#product_list_to_date').val();
                    }
                },
                columnDefs: [ {
                    "targets": [0,1,2, 12],
                    "orderable": false,
                    "searchable": false
                } ],
                // aaSorting: [3, 'asc'],
               pageLength: 100,
                lengthMenu: [
                    [30, 40, 60, 80, 90, 100, 300, 500, 1000, -1],
                    [30, 40, 60, 80, 90, 100, 300, 500, 1000, 'All'],
                ],
                columns: [
                        {
                        data: 'DT_Row_Index',
                        searchable: false,
                        orderable: false
                        },
                        { data: 'mass_delete'},
                        { data: 'printing_qty'},
                        { data: 'image', name: 'products.image'  },
                        { data: 'product', name: 'products.name'  },
                        { data: 'action', name: 'action'},
                        { data: 'refference', name: 'products.refference'  },
                        { data: 'sku', name: 'products.sku'},
                        { data: 'purchase_price', name: 'purchase_price', searchable: false},
                        { data: 'selling_price', name: 'selling_price', searchable: false},
                        { data: 'color', name: 'colors.name'},
                        { data: 'size', name: 'sizes.name'},
                        { data: 'current_stock', searchable: false},
                        { data: 'type', name: 'products.type'},
                         { data: 'supplier_name', name: 'suppliers.name'},
                        { data: 'category', name: 'c1.name'},
                        { data: 'sub_category', name: 'c2.name'},
                        { data: 'date', name: 'products.created_at'},
                        { data: 'bulk_add', name: 'products.bulk_add', searchable: true},
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
                var print_qty = [];
                var i = 0;
                var j = 0;
                $('.row-select:checked').each(function () {
                    selected_rows[i++] = $(this).val();
                    print_qty[j++] = $("#printing_qty_"+$(this).val()).val();
                    // console.log(selected_rows);
                    // console.log(print_qty);
                    // return 0;
                }); 
                if(selected_rows.length > 0){
                    $('input#selected_products_bulkPrint').val(selected_rows);
                    $('input#selected_products_bulkPrint_qty').val(print_qty);
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
                var selected_rows_qty = [];
                var i = 0;
                $('.row-select:checked').each(function () {
                    var selectedQty = $("#qty_"+$(this).val()).val();
                    var selectedMaxQty = $("#qty_"+$(this).val()).attr('max');
                    if(parseInt(selectedQty) <= parseInt(selectedMaxQty))
                    {
                        selected_rows[i++] = $(this).val()+"@"+selectedQty+"@"+selectedMaxQty;
                    }
                }); 
                
                if(selected_rows.length > 0){
                    $('#unknownDiscountModal').modal('show'); 
                    $('input#selected_products_bulkTransfer').val(selected_rows);
                    // swal({
                    //     title: LANG.sure,
                    //     icon: "warning",
                    //     buttons: true,
                    //     dangerMode: true,
                    // }).then((willDelete) => {
                    //     if (willDelete) {
                        // If uncommented it will make issue in product transfer
                            // $('#unknownDiscountModal').modal('show'); 
                            // $('form#bulkTransfer_form').submit();
                    //     }
                    // });
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

            $(document).on('change', '#product_list_filter_type, #product_list_filter_category_id,#category_id, #product_list_filter_brand_id, #product_list_filter_unit_id, #product_list_filter_tax_id, #location_id', 
                function() {
                    // console.log($(this).val());
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
    function checkRow()
    {
        alert("Row Check");
        e.preventDefault();
        
    }
 </script> 
@endsection