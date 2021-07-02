@extends('layouts.app')
@section('title', __('lang_v1.product_sell_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>{{ __('lang_v1.product_sell_report')}}</h1>
</section>

<!-- Main content -->
<section class="content no-print">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
              {!! Form::open(['url' => action('ReportController@getStockReport'), 'method' => 'get', 'id' => 'product_sell_report_form' ]) !!}
                <div class="col-md-3">
                    <div class="form-group">

                        <label>Puchasing Date</label>
                        {!! Form::text('product_purchase_date_filter', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'product_purchase_date_filter', 'readonly']); !!}
                    </div>
                    {{-- <div class="form-group">
                        {!! Form::label('search_product', __('lang_v1.search_product') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-search"></i>
                            </span>
                            <input type="hidden" value="" id="variation_id">
                            {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'autofocus']); !!}
                        </div>
                    </div> --}}
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('supplier_id', 'Supplier' . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            {!! Form::select('supplier_id', $suppliers, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
                        </div>
                    </div>
                </div>
                {{-- <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('customer_id', __('contact.customer') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
                        </div>
                    </div>
                </div> --}}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location').':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-map-marker"></i>
                            </span>
                            @if (auth()->user()->permitted_locations() != 'all')
                                {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'required']); !!}
                                
                            @else
                                {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">

                        {!! Form::label('product_sr_date_filter', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'product_sr_date_filter', 'readonly']); !!}
                    </div>
                </div>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#psr_grouped_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-cart-plus" aria-hidden="true"></i>
                            Grouped Sold Products</a>
                    </li>
                    <li >
                        <a href="#psr_detailed_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-list" aria-hidden="true"></i> @lang('lang_v1.detailed')</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="psr_grouped_tab">
                        <div class="table-responsive">
                            <table class="table table-bordered ajax_view table-striped dataTable" id="product_sell_grouped_report_table"
                                style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Action</th>
                                        <th>@lang('sale.product')</th>
                                        {{-- <th>Purchase Date</th> --}}
                                        <th>Reffernce</th>
                                        {{-- <th>Total Sold</th> --}}
                                        {{-- <th>Barcode</th>
                                                            <th>@lang('messages.date')</th> --}}
                                        <th>@lang('report.current_stock')</th>
                                        <th>@lang('report.total_unit_sold')</th>
                                        <th>Sale Percentage</th>
                                        <th>@lang('sale.total')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                                        <td id="footer_total_grouped_sold"></td>
                                        <td></td>
                                        <td><span class="display_currency" id="footer_grouped_subtotal" data-currency_symbol="true"></span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="psr_detailed_tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped ajax_view dataTable" 
                            id="product_sell_report_table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>@lang('sale.product')</th>
                                        <th>Refference</th>
                                        <th>Size</th>
                                        {{-- <th>Total Sold</th> --}}
                                        <th>Current Stock</th>
                                        <th>Sold Quantity</th>
                                        <th>Supplier</th>
                                        <th>Before Force Price</th>
                                        <th>@lang('sale.unit_price')</th>
                                        <th>After Discount</th>
                                        <th>@lang('sale.discount')</th>
                                        <th>@lang('sale.total')</th>
                                        <th>Barcode</th>
                                        <th>@lang('messages.date')</th>
                                        <th>Purchase Date</th>
                                        {{-- <th>@lang('sale.customer_name')</th> --}}
                                        <th>@lang('sale.invoice_no')</th>
                                        {{-- <th>@lang('sale.tax')</th> --}}
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td colspan="4">
                                            <strong>@lang('sale.total'):</strong>
                                        </td>
                                        {{-- <td></td>
                                        <td></td>
                                        <td id="footer_tax"></td>
                                        <td></td> --}}
                                        <td>
                                            <span id="footer_total_sold" >    
                                        </td>
                                        <td colspan="2"></td>
                                        <td>
                                            <span class="display_currency" id="footer_total_before_discount" data-currency_symbol="true">

                                        </td>
                                        <td></td>
                                        {{-- <td colspan=""></td> --}}
                                        <td><span class="display_currency" id="footer_subtotal" data-currency_symbol ="true"></span></td>
                                        <td colspan="6"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->
<div class="modal fade view_register" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade" id="view_product_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
<div class="modal fade" id="view_product_color_detail" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
@endsection

@section('javascript')
<script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
<script>
    $(document).on('shown.bs.modal', 'div.view_product_modal, div.view_modal', function(){
            __currency_convert_recursively($(this));
        });
    // $(function () {
    //     var column = $("#product_sell_report_table").DataTable().column(10);
    //     column.visible( !column.visible());
    // });
</script>
@endsection