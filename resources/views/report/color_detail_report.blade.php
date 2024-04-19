@extends('layouts.app')
@section('title', 'Color Detail Report')
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

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>{{ 'Color Detail Report' }}</h1>
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
                                @foreach ($business_locations as $key => $value)
                                    {{-- @if ($key != 1 && $value != 'Main Shop') --}}
                                    @php
                                        $newBusiness_locations[$key] = $value;
                                    @endphp
                                    {{-- @endif --}}
                                @endforeach
                                {{-- {{dd(collect($newBusiness_locations))}} --}}
                                {{-- <select name="category_id" id="transferBusiness" class="form-control select2"
                                style="width:100%">
                                <optgroup>
                                    <option value="all">{{__('lang_v1.all')}}</option>
                                    @foreach ($business_locations as $key => $item)
                                    @if ($key != 1 && $item != 'Main Shop')
                                    <option value="{{$key}}">
                                        {{$item}}
                                    </option>
                                    @endif
                                    @endforeach
                                </optgroup>
                            </select> --}}
                                {!! Form::select('category_id', collect($newBusiness_locations), null, [
                                    'class' => 'form-control select2',
                                    'style' => 'width:100%',
                                    'id' => 'transferBussiness',
                                    'placeholder' => __('lang_v1.all'),
                                ]) !!}
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
                    {!! Form::open([
                        'url' => action('ReportController@getcolorDetailReport'),
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
                            {!! Form::label('product_sr_date_filter', 'Purchase Date:') !!}
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
        @component('components.widget', ['class' => 'box-primary'])
            <div class="form-row">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#psrc_grouped_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-cart-plus"
                                    aria-hidden="true"></i>
                                Grouped Products</a>
                        </li>

                        <li>
                            <a href="#psrc_detailed_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-list"
                                    aria-hidden="true"></i> @lang('lang_v1.detailed')</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="psrc_grouped_tab">
                            <div class="row">
                                <div class="col-md-12">
                                    @include('report.partials.grouped_color_detail_report_table')

                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="psrc_detailed_tab">
                            <div class="row" style="margin-bottom: 20px">
                                <div class="col-12">
                                    <form action="{{ action('ProductController@showPos') }}" method="post" class="ml-5"
                                        style="margin-left: 20px" id="show_pos">
                                        @csrf
                                        <input type="hidden" name="product_id" id="product_id">
                                        <button type="submit" class="btn btn-danger pull-left" id="show_pos_button">
                                            <i class="fa fa-desktop"></i>
                                            Show Top in POS
                                        </button>
                                    </form>
                                    <form action="{{ action('ProductController@showBottomPos') }}" method="post" class="ml-5"
                                        style="margin-left: 20px" id="show_bottom_pos">
                                        @csrf
                                        <input type="hidden" name="product_id" id="product_id">
                                        <button type="submit" class="btn btn-warning pull-left" id="show_bottom_pos_button">
                                            <i class="fa fa-desktop"></i>
                                            Show Normal in POS
                                        </button>
                                    </form>
                                    {!! Form::open([
                                        'url' => action('ProductController@massBulkPrint'),
                                        'method' => 'post',
                                        'id' => 'bulkPrint_form',
                                        'target' => '_blank',
                                    ]) !!}
                                    {{-- {!! Form::submit('Print Selected', array('class' => 'btn btn-md btn-warning', 'id' =>
                                    'bulkPrint-selected')) !!} --}}
                                    {!! Form::hidden('selected_products_bulkPrint', null, ['id' => 'selected_products_bulkPrint']) !!}
                                    {!! Form::hidden('selected_products_bulkPrint_qty', null, ['id' => 'selected_products_bulkPrint_qty']) !!}
                                    {!! Form::hidden('printing_location_id', 1, ['id' => 'printing_location_id']) !!}

                                    <button type="submit" class="btn btn-success pull-left" id="bulkPrint-selected"
                                        style="margin-left: 20px">
                                        <i class="fa fa-print"></i>
                                        Print Selected
                                    </button>
                                    {!! Form::close() !!}
                                    <form action="{{ action('WebsiteController@addToWebsite') }}" method="post"
                                        class="ml-5" style="margin-left: 20px" id="add_to_website">
                                        @csrf
                                        <input type="hidden" name="product_id" id="product_id">
                                        <button type="submit" class="btn btn-info pull-left" id="add_to_website_button">
                                            <i class="fa fa-copy"></i>
                                            Add to Website
                                        </button>
                                    </form>
                                    {!! Form::open([
                                        'url' => action('ProductController@massTransfer'),
                                        'method' => 'post',
                                        'id' => 'bulkTransfer_form',
                                        'class' => 'ml-5',
                                    ]) !!}
                                    {!! Form::hidden('selected_products_bulkTransfer', null, ['id' => 'selected_products_bulkTransfer']) !!}
                                    {!! Form::hidden('selected_products_qty_bulkTransfer', null, ['id' => 'selected_products_qty_bulkTransfer']) !!}
                                    {!! Form::hidden('bussiness_bulkTransfer', null, ['id' => 'bussiness_bulkTransfer']) !!}
                                    {!! Form::hidden('current_location', null, ['id' => 'current_location']) !!}
                                    {{-- {!! Form::submit(' Transfer Selected', array('class' => 'btn btn-warning', 'id' =>
                            'bulkTransfer-selected')) !!} --}}
                                    <button type="submit" class="btn btn-warning" id="bulkTransfer-selected">
                                        <i class="fa fa-random"></i>
                                        Transfer Selected
                                    </button>
                                    {!! Form::close() !!}

                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    @include('report.partials.color_detail_report_table')
                                </div>
                            </div>
                        </div>
                       
                    </div>
                </div>
            </div>
        @endcomponent
        <div class="modal fade" id="view_product_modal" tabindex="-1" role="dialog"
            aria-labelledby="gridSystemModalLabel">
        </div>
        <div class="modal fade view_register" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>
        <div class="modal fade" id="view_product_color_detail" tabindex="-1" role="dialog"
            aria-labelledby="gridSystemModalLabel"></div>
    </section>
    <!-- /.content -->

@endsection

@section('javascript')
    {{-- <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script> --}}
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script>
        $(document).on('shown.bs.modal', 'div.view_product_modal, div.view_modal', function() {
            __currency_convert_recursively($(this));
        });

        /**
         * Desired Qty of Barcodes
         *
         **/

        $(document).on('click', '#bulkPrint-selected', function(e) {
            e.preventDefault();
            var selected_rows = [];
            var print_qty = [];
            var i = 0;
            var j = 0;
            $('.row-select:checked').each(function() {
                selected_rows[i++] = $(this).val();
                print_qty[j++] = $("#printing_qty_" + $(this).val()).val();
                // console.log(selected_rows);
                // console.log(print_qty);
                // return 0;
            });
            if (selected_rows.length > 0) {
                $('input#selected_products_bulkPrint').val(selected_rows);
                $('input#selected_products_bulkPrint_qty').val(print_qty);
                $('input#printing_location_id').val($("#location_id").val());
                $("#location_id").val(1);

                $('form#bulkPrint_form').submit();
            } else {
                $('input#selected_products_bulkPrint').val('');
                swal('@lang('lang_v1.no_row_selected')');
            }
        })

        function TransferSelected() {
            var transferBussiness = $("#transferBussiness option:selected").val();

            if (transferBussiness == "" || transferBussiness == undefined) {
                alert("Please Choose Bussiness First to Transfer ");
                return (false);
            }
            $("#bussiness_bulkTransfer").val(transferBussiness);
            $("#current_location").val($("#location_id").val());
            $('form#bulkTransfer_form').submit();
        }

        $(document).on('click', '#bulkTransfer-selected', function(e) {
            e.preventDefault();
            var selected_rows = [];
            var selected_rows_qty = [];
            var i = 0;
            $('.row-select:checked').each(function() {
                var selectedQty = $("#stock_qty_" + $(this).val()).val();
                var selectedMaxQty = $("#stock_qty_" + $(this).val()).attr('max');
                var selectedLocation = $("#location_" + $(this).val()).text();
                var selectedLocationId = $("#location_" + $(this).val()).attr("max");
                if (parseInt(selectedQty) <= parseInt(selectedMaxQty)) {
                    selected_rows[i++] = $(this).val() + "@" + selectedQty + "@" + selectedMaxQty + "@" +
                        selectedLocationId;
                }
            });


            if (selected_rows.length > 0) {
                $('#unknownDiscountModal').modal('show');
                $('input#selected_products_bulkTransfer').val(selected_rows);
            } else {
                $('input#selected_products_bulkTransfer').val('');
                swal('@lang('lang_v1.no_row_selected')');
            }
        })
        $(document).on('click', '#add_to_website_button', function(e) {
            e.preventDefault();
            var selected_rows = [];
            var i = 0;
            $('.row-select:checked').each(function() {
                var selectedQty = $("#stock_qty_" + $(this).val()).val();
                var selectedMaxQty = $("#stock_qty_" + $(this).val()).attr('max');
                if (parseInt(selectedQty) <= parseInt(selectedMaxQty)) {
                    selected_rows[i++] = $(this).val();
                }
            });
            // console.log(selected_rows);
            // return 0;


            if (selected_rows.length > 0) {
                $('input#product_id').val(selected_rows);
                $("form#add_to_website").submit();
            } else {
                $('input#product_id').val('');
                swal('@lang('lang_v1.no_row_selected')');
            }
        }) 
        $(document).on('click', '#remove_to_website_button', function(e) {
            e.preventDefault();
            var selected_rows = [];
            var i = 0;
            $('.row-select:checked').each(function() {
                var selectedQty = $("#stock_qty_" + $(this).val()).val();
                var selectedMaxQty = $("#stock_qty_" + $(this).val()).attr('max');
                if (parseInt(selectedQty) <= parseInt(selectedMaxQty)) {
                    selected_rows[i++] = $(this).val();
                }
            });
            // console.log(selected_rows);
            // return 0;


            if (selected_rows.length > 0) {
                $('input#product_id').val(selected_rows);
                $("form#remove_to_website").submit();
            } else {
                $('input#product_id').val('');
                swal('@lang('lang_v1.no_row_selected')');
            }
        })
        $(document).on('click', '#show_pos_button', function(e) {
            e.preventDefault();
            var selected_rows = [];
            var i = 0;
            $('.row-select:checked').each(function() {
                var selectedQty = $("#stock_qty_" + $(this).val()).val();
                var selectedMaxQty = $("#stock_qty_" + $(this).val()).attr('max');
                if (parseInt(selectedQty) <= parseInt(selectedMaxQty)) {
                    selected_rows[i++] = $(this).val();
                }
            });

            if (selected_rows.length > 0) {
                $('input#product_id').val(selected_rows);
                $("form#show_pos").submit();
            } else {
                $('input#product_id').val('');
                swal('@lang('lang_v1.no_row_selected')');
            }
        })
        $(document).on('click', '#show_bottom_pos_button', function(e) {
            e.preventDefault();
            var selected_rows = [];
            var i = 0;
            $('.row-select:checked').each(function() {
                var selectedQty = $("#stock_qty_" + $(this).val()).val();
                var selectedMaxQty = $("#stock_qty_" + $(this).val()).attr('max');
                if (parseInt(selectedQty) <= parseInt(selectedMaxQty)) {
                    selected_rows[i++] = $(this).val();
                }
            });
            // console.log(selected_rows);
            // return 0;


            if (selected_rows.length > 0) {
                $('input#product_id').val(selected_rows);
                $("form#show_bottom_pos").submit();
            } else {
                $('input#product_id').val('');
                swal('@lang('lang_v1.no_row_selected')');
            }
        })
    </script>
@endsection
