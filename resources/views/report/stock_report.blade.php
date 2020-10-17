@extends('layouts.app')
@section('title', __('report.stock_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('report.stock_report')}}</h1>
</section>
<div class="modal fade in" tabindex="-1" role="dialog" id="unknownDiscountModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" id="closeThis" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">Ã—</span></button>
                <h4 class="modal-title">SELECT BUSSINESS</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('category_id', 'Business :') !!}
                            @foreach ($business_locations as $key=>$value)
                            {{-- @if ($key != 1 && $value != "Main Shop") --}}
                            @php
                            $newBusiness_locations[$key] = $value;
                            @endphp
                            {{-- @endif --}}
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
                            {!! Form::select('category_id', collect($newBusiness_locations), null, ['class' =>
                            'form-control select2', 'style' => 'width:100%', 'id' => 'transferBussiness', 'placeholder'
                            => __('lang_v1.all')]); !!}
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
            {{-- @dd($business_locations) --}}
            @component('components.filters', ['title' => __('report.filters')])
            {!! Form::open(['url' => action('ReportController@getStockReport'), 'method' => 'get', 'id' =>
            'stock_report_filter_form' ]) !!}
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('category_id', __('category.category') . ':') !!}
                    {!! Form::select('category', $categories, null, ['placeholder' => __('messages.all'), 'class' =>
                    'form-control select2', 'style' => 'width:100%', 'id' => 'category_id']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
                    {!! Form::select('sub_category', array(), null, ['placeholder' => __('messages.all'), 'class' =>
                    'form-control select2', 'style' => 'width:100%', 'id' => 'sub_category_id']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('suppliers', 'Suppliers :') !!}
                    {!! Form::select('suppliers', $suppliers, null, ['placeholder' => __('messages.all'), 'class' =>
                    'form-control select2', 'style' => 'width:100%']); !!}
                </div>
            </div>
            {{-- <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('unit',__('product.unit') . ':') !!}
                        {!! Form::select('unit', $units, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div> --}}
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

    @component('components.widget', ['class' => 'box-primary'])
    <div class="form-row">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active">
                    <a href="#psr_grouped_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-cart-plus" aria-hidden="true"></i>
                        Grouped Products</a>
                </li>

                <li>
                <a href="#psr_detailed_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-list"
                    aria-hidden="true"></i> @lang('lang_v1.detailed')</a>
                </li>
                
                
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="psr_grouped_tab">
                    <div class="row">
                        <div class="col-md-12">
                            @include('report.partials.grouped_stock_report_table')
                        </div>
                    </div>
                </div>
                <div class="tab-pane" id="psr_detailed_tab">
                    <div class="row" style="margin-bottom: 20px">
                        <div class="col-12">
                            {!! Form::open(['url' => action('ProductController@massBulkPrint'), 'method' => 'post', 'id' =>
                            'bulkPrint_form' ]) !!}
                            {{-- {!! Form::submit('Print Selected', array('class' => 'btn btn-md btn-warning', 'id' => 'bulkPrint-selected')) !!} --}}
                            {!! Form::hidden('selected_products_bulkPrint', null, ['id' => 'selected_products_bulkPrint']); !!}
                            {!! Form::hidden('selected_products_bulkPrint_qty', null, ['id' => 'selected_products_bulkPrint_qty']); !!}
                            {!! Form::hidden('printing_location_id', 1, ['id' => 'printing_location_id']); !!}
                
                            <button type="submit" class="btn btn-success pull-left" id="bulkPrint-selected" style="margin-left: 20px">
                                <i class="fa fa-print"></i>
                                Print Selected
                            </button>
                            {!! Form::close() !!}
                            {!! Form::open(['url' => action('ProductController@massTransfer'), 'method' => 'post', 'id' =>
                            'bulkTransfer_form','class' => 'ml-5' ]) !!}
                            {!! Form::hidden('selected_products_bulkTransfer', null, ['id' => 'selected_products_bulkTransfer']); !!}
                            {!! Form::hidden('selected_products_qty_bulkTransfer', null, ['id' =>
                            'selected_products_qty_bulkTransfer']); !!}
                            {!! Form::hidden('bussiness_bulkTransfer', null, ['id' => 'bussiness_bulkTransfer']); !!}
                            {!! Form::hidden('current_location', null, ['id' => 'current_location']); !!}
                            {{-- {!! Form::submit(' Transfer Selected', array('class' => 'btn btn-warning', 'id' => 'bulkTransfer-selected')) !!} --}}
                            <button type="submit" class="btn btn-warning" id="bulkTransfer-selected">
                                <i class="fa fa-random"></i>
                                Transfer Selected
                            </button>
                            {!! Form::close() !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            @include('report.partials.stock_report_table')
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    @endcomponent
    <div class="modal fade" id="view_product_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
</section>
<!-- /.content -->

@endsection

@section('javascript')
{{-- <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script> --}}
<script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
<script>
    $(document).on('shown.bs.modal', 'div.view_product_modal, div.view_modal', function(){
            __currency_convert_recursively($(this));
        });
        // $(document).on('click', '#bulkPrint-selected', function(e){
        //         e.preventDefault();
        //         var selected_rows = [];
        //         var i = 0;
        //         $('.row-select:checked').each(function () {
        //             selected_rows[i++] = $(this).val();
        //         }); 
                
        //         if(selected_rows.length > 0){
        //             $('input#selected_products_bulkPrint').val(selected_rows);
        //             // swal({
        //             //     title: LANG.sure,
        //             //     icon: "warning",
        //             //     buttons: true,
        //             //     dangerMode: true,
        //             // }).then((willDelete) => {
        //             //     if (willDelete) {
        //                     $('form#bulkPrint_form').submit();
        //         //         }
        //         //     });
        //         // } else{
        //         //     $('input#selected_products_bulkPrint').val('');
        //         //     swal('@lang("lang_v1.no_row_selected")');
        //         }    
        //     });

            /**
            * Desired Qty of Barcodes
            *
            **/

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
                    $('input#printing_location_id').val($("#location_id").val());
                    $("#location_id").val(1);
                    // swal({
                    //     title: LANG.sure,
                    //     icon: "warning",
                    //     buttons: true,
                    //     dangerMode: true,
                    // }).then((willDelete) => {
                    //     if (willDelete) {
                            $('form#bulkPrint_form').submit();
                    //     }
                    // });
                } else{
                    $('input#selected_products_bulkPrint').val('');
                    swal('@lang("lang_v1.no_row_selected")');
                }    
            })

            // $(document).on('click', '#bulkPrint-selected', function(e){
            //     e.preventDefault();
            //     var selected_rows = [];
            //     var selected_rows_qty = [];
            //     var i = 0;
            //     $('.row-select:checked').each(function () {
            //         var selectedQty = $("#qty_"+$(this).val()).val();
            //         // var selectedMaxQty = $("#qty_"+$(this).val()).attr('max');
            //         // if(parseInt(selectedQty) <= parseInt(selectedMaxQty))
            //         // {
            //             selected_rows[i++] = $(this).val()+"@"+selectedQty;
            //             // }
            //         }); 
            //         // return 0;
            //     // console.log(selected_rows);
                
            //     if(selected_rows.length > 0){
            //         $('input#selected_products_bulkPrint').val(selected_rows);
            //         // swal({
            //         //     title: LANG.sure,
            //         //     icon: "warning",
            //         //     buttons: true,
            //         //     dangerMode: true,
            //         // }).then((willDelete) => {
            //         //     if (willDelete) {
            //                 $('#unknownDiscountModal').modal('show'); 
            //                 $('form#bulkPrint_form').submit();
            //         //     }
            //         // });
            //     } else{
            //         $('input#selected_products_bulkPrint').val('');
            //         swal('@lang("lang_v1.no_row_selected")');
            //     }    
            // })

            function TransferSelected()
            {
                var transferBussiness = $("#transferBussiness option:selected").val();

                if(transferBussiness == "" || transferBussiness == undefined)
                {
                    alert("Please Choose Bussiness First to Transfer ");return(false);
                } 
                $("#bussiness_bulkTransfer").val(transferBussiness);
                $("#current_location").val($("#location_id").val());
                $('form#bulkTransfer_form').submit();
            }

            $(document).on('click', '#bulkTransfer-selected', function(e){
                e.preventDefault();
                var selected_rows = [];
                var selected_rows_qty = [];
                var i = 0;
                $('.row-select:checked').each(function () {
                    var selectedQty = $("#stock_qty_"+$(this).val()).val();
                    var selectedMaxQty = $("#stock_qty_"+$(this).val()).attr('max');
                    var selectedLocation= $("#location_"+$(this).val()).text();
                    var selectedLocationId= $("#location_"+$(this).val()).attr("max");
                    if(parseInt(selectedQty) <= parseInt(selectedMaxQty))
                    {
                        selected_rows[i++] = $(this).val()+"@"+selectedQty+"@"+selectedMaxQty+"@"+selectedLocationId;
                    }
                    // console.log(selectedQty +'    '+$(this).val() +'   '+selectedLocation+'   '+selectedLocationId);
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
                    //     // If uncommented it will make issue in product transfer
                    //         $('#unknownDiscountModal').modal('show'); 
                    //         $('form#bulkTransfer_form').submit();
                    //     }
                    // });
                } else{
                    $('input#selected_products_bulkTransfer').val('');
                    swal('@lang("lang_v1.no_row_selected")');
                }    
            })
</script>
@endsection