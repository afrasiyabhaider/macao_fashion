@extends('layouts.app')
@section('title', __('report.trending_products'))

@section('css')
    {!! Charts::styles(['highcharts']) !!}
@endsection

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('report.trending_products')}}</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row no-print">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
              {!! Form::open(['url' => action('ReportController@getTrendingProducts'), 'method' => 'get' ]) !!}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('category_id', __('product.category') . ':') !!}
                        {!! Form::select('category', $categories, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'category_id']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
                        {!! Form::select('sub_category', array(), null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'sub_category_id']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('supplier', __('product.supplier') . ':') !!}
                        {!! Form::select('supplier', $suppliers, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                {{-- <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('brand', __('product.brand') . ':') !!}
                        {!! Form::select('brand', $brands, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div> --}}
                {{-- <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('unit', __('product.unit') . ':') !!}
                        {!! Form::select('unit', $units, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div> --}}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('purchase_product_date_range',__('Purchase Date') .  ':') !!}
                        {!! Form::text('purchase_date', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'purchase_product_date_range', 'readonly']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('trending_product_date_range',__('report.date_range') .  ':') !!}
                        {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'trending_product_date_range', 'readonly']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('limit', __('lang_v1.no_of_products') . ':') !!} @show_tooltip(__('tooltip.no_of_products_for_trending_products'))
                        {!! Form::number('limit', 15, ['placeholder' => __('lang_v1.no_of_products'), 'class' => 'form-control', 'min' => 1]); !!}
                    </div>
                </div>
                <div class="col-sm-12">
                  <button type="submit" class="btn btn-primary pull-right">@lang('report.apply_filters')</button>
                </div> 
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            @component('components.widget', ['class' => 'box-primary'])
                @slot('title')
                    @lang('report.top_trending_products') @show_tooltip(__('tooltip.top_trending_products'))
                @endslot
                {!! $chart->html() !!}
            @endcomponent
        </div>
    </div>
    <div class="row no-print">
        <div class="col-sm-12">
            <button type="button" class="btn btn-primary pull-right" 
            aria-label="Print" onclick="window.print();"
            ><i class="fa fa-print"></i> @lang( 'messages.print' )</button>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            @component('components.widget', ['class' => 'box-primary'])
                @slot('title')
                    Trending Product Detail @show_tooltip('Trending Products Detail')
                @endslot
                <h4>
                    Total Amount: <span class="display_currency" id="top_subtotal" data-currency_symbol="true"></span>
                </h4>
                <h4>
                    Total Quantity: <span class="display_currency" id="top_totalQty" ></span> Pcs
                </h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ajax_view dataTable" 
                    id="trendig_product_sell_report_table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>@lang('sale.product')</th>
                                <th>Size</th>
                                <th>Refference</th>
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
                        <tbody>
                            @php
                                $price_total = 0;
                                $qty_total = 0;
                            @endphp
                            @foreach ($details as $key=>$detail)
                                <tr>
                                    <td>
                                        <div style="display: flex;"><img src="{{$detail->image_url}}" alt="Product image" class="product-thumbnail-small"></div>
                                    </td>
                                    <td>
                                        @php
                                            $product_name = $detail->product_name;
                                            if ($detail->product_type == 'variable') {
                                                $product_name .= ' - ' . $detail->product_variation . ' - ' . $detail->variation_name;
                                            }
                                        @endphp
                                        {{$product_name}}
                                    </td>
                                    <td>
                                        @if($detail->product()->first()->sub_size()->first()['name'])
                                             {{$detail->product()->first()->sub_size()->first()['name']}}
                                        @else
                                        <b class="text-center">-</b>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            if ($detail->refference) {
                                                echo $detail->refference;
                                            } else {
                                                echo '<b class="text-center">-</b>';
                                            }
                                        @endphp
                                    </td>
                                    <td>
                                        <span data-is_quantity="true" class="display_currency current_stock" data-currency_symbol=false data-orig-value=" {{(int) $detail->current_stock}}" data-unit="{{$detail->unit }}"> 
                                            {{(int) $detail->current_stock}}
                                        </span>  
                                        {{$detail->unit}}
                                    </td>
                                    <td>
                                        <span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value=" {{(int) $detail->sell_qty}}" data-unit="{{$detail->unit }}"> 
                                            {{(int) $detail->sell_qty}}
                                        </span>  
                                        {{$detail->unit}}
                                        @php
                                            $qty_total += $detail->sell_qty
                                        @endphp
                                    </td>
                                    <td>
                                        @php
                                            if ($detail->product()->first()->supplier()->first()) {
                                                echo $detail->product()->first()->supplier()->first()['name'];
                                            } else {
                                                echo '-';
                                            }
                                        @endphp
                                    </td>
                                    <td>
                                        @php
                                            if ($detail->original_amount) {
                                                echo '<span class="display_currency" data-currency_symbol = true>' . $detail->original_amount . '</span>';
                                            } else {
                                                echo '-';
                                            }
                                        @endphp
                                    </td>
                                    <td>
                                        <span class="display_currency" data-currency_symbol = true>{{$detail->unit_price}}</span>
                                    </td>
                                    <td>
                                        <span class="display_currency" data-currency_symbol = true>{{$detail->unit_sale_price}}</span>
                                    </td>
                                    <td>
                                        @if($detail->discount_type == "percentage")
                                        {{@number_format($detail->discount_amount)}} %
                                        @elseif($detail->discount_type == "fixed")
                                        {{@number_format($detail->discount_amount)}}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="display_currency row_subtotal" data-currency_symbol="true" data-orig-value=" {{$detail->subtotal}}">{{$detail->subtotal}}</span>
                                        @php
                                            $price_total += $detail->subtotal;
                                        @endphp
                                    </td>
                                    <td>
                                        {{$detail->barcode}}
                                    </td>
                                    <td>
                                        {{
                                            Carbon::parse($detail->transaction_date)->format('d-M-Y H:i')
                                        }}
                                    </td>
                                    <td>
                                        {{
                                            Carbon::parse($detail->product_updated_at)->format('d-M-Y H:i')
                                        }}
                                    </td>
                                    <td>
                                        <a data-href="{{action('SellController@show', [$detail->transaction_id])}}" href="#" data-container=".view_modal" class="btn-modal">{{$detail->invoice_no}}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="5">
                                    <strong>@lang('sale.total'):</strong>
                                </td>
                                {{-- <td></td>
                                <td></td>
                                <td id="footer_tax"></td>
                                <td></td> --}}
                                <td id="footer_total_sold">
                                </td>
                                <td colspan="5"></td>
                                <td><span class="display_currency" id="footer_subtotal" data-currency_symbol ="true"></span></td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    {!! Charts::assets(['highcharts']) !!}
    {!! $chart->script() !!}
    <script>
        $("#trendig_product_sell_report_table").DataTable({
            pageLength: -1,
            lengthMenu: [
                [20, 50, 70, 100, 300, 500, 1000, -1],
                [20, 50, 70, 100, 300, 500, 1000, 'All'],
            ],
            fnDrawCallback: function(oSettings) {
                $('#footer_subtotal').text(
                    sum_table_col($('#trendig_product_sell_report_table'), 'row_subtotal')
                );

                $('#top_subtotal').text(
                    sum_table_col($('#trendig_product_sell_report_table'), 'row_subtotal')
                );

                $('#footer_total_sold').html(
                    __sum_stock($('#trendig_product_sell_report_table'), 'sell_qty')
                );
                
                $('#top_totalQty').html(
                    sum_table_col($('#trendig_product_sell_report_table'), 'sell_qty')
                );

                // $('#footer_tax').html(__sum_stock($('#product_sell_report_table'), 'tax', 'left'));

                // __currency_convert_recursively($('#trendig_product_sell_report_table'));
                // __currency_convert_recursively($('#top_subtotal'));
            },
        });
    </script>
@endsection