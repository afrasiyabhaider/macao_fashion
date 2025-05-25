@extends('layouts.app')
@section('title', 'Color Report')

@section('content')
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('location_id', $business_locations, request()->location_id, [
                                'class' => 'form-control select2 location',
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
                            ]) !!}
                        </div>
                    </div>
                @endcomponent
            </div>
            <div id="filterAppend">
                <div class="col-md-12">
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs" id="myTabs">
                            <li class="active">
                                <a href="#color_current" data-toggle="tab" aria-expanded="true">
                                    <i class="fa fa-list" aria-hidden="true"></i>
                                    Color Current Report
                                </a>
                            </li>
                            <li>
                                <a href="#color_history" data-toggle="tab" aria-expanded="true">
                                    <i class="fa fa-calendar" aria-hidden="true"></i>
                                    Color History Report

                                </a>
                            </li>
                        </ul>
                        <input type="hidden" value="{{ $refference }}" class="refference">
                        <div class="tab-content">
                            <div class="tab-pane active" id="color_current">
                                <h4 class="modal-title">From: {{ $from_date }} - To: {{ $to_date }}</h4>
                                {{-- current group --}}
                                <div class="row">
                                    <div class="col-md-12">
                                        <h3>Groupped Color Report:</h3>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-condensed bg-gray">
                                                <tr class="bg-green">
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Color</th>
                                                    <th>Quantity Sold <small>(Date Filter)</small> </th>
                                                    <th>Today Sold</th>
                                                    <th>7-D Sold</th>
                                                    <th>15-D Sold</th>
                                                    <th>All Time Sold</th>
                                                    <th>Current Stock</th>
                                                    <th>All Time Purchase</th>
                                                    <th>Purchase Date</th>
                                                    <th>Last Update Date</th>
                                                </tr>
                                                {{-- @dd($current_group_color) --}}
                                                @foreach ($merged_summed_values as $key => $item)
                                                    {{-- @dd($key,$item) --}}
                                                    <tr>
                                                        <td>
                                                            {{ $loop->iteration }}
                                                        </td>
                                                        <td>
                                                            {{ $item['product_name'] }}
                                                            {{-- {{ $item->product_id }} --}}
                                                        </td>
                                                        <td>
                                                            {{ $item['color'] }}
                                                        </td>
                                                        
                                                         <td>
                                                            {{ (int) $item['total_qty_sold'] }}
                                                        </td>
                                                        <td>

                                                            {{ (int) $item['today_sold'] }}
                                                        </td>
                                                        <td>

                                                            {{ (int) $item['seven_day_sold'] }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item['fifteen_day_sold'] }}

                                                        </td>
                                                       <td>
                                                            {{ (int) $item['all_time_sold'] }}
                                                        </td>

                                                        <td>
                                                            {{ (int) $item['current_stock'] }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item['all_time_sold'] + (int) $item['current_stock'] }}
                                                        </td>
                                                        <td>
                                                            {{ $item['purchase_date'] }}
                                                        </td>
                                                        <td>
                                                            {{ $item['last_update_date'] }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                {{-- current group color --}}
                                <div class="row">
                                    <div class="col-md-12">
                                        <h3>Groupped Color Size Report <small>(With Stock)</small>:</h3>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-condensed bg-gray">
                                                <tr class="bg-green">
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Color</th>
                                                    <th>Size</th>
                                                    <th>Quantity Sold <small>(Date Filter)</small></th>
                                                    <th>Today Sold</th>
                                                    <th>7-D Sold</th>
                                                    <th>15-D Sold</th>
                                                    <th>All Time Sold</th>
                                                    <th>Current Stock</th>
                                                    <th>
                                                        All Time Purchase
                                                    </th>
                                                    <th>Purchase Date</th>
                                                    <th>Last Update Date</th>
                                                </tr>
                                                @foreach ($current_group as $item)
                                                    <tr>
                                                        <td>
                                                            {{ $loop->iteration }}
                                                        </td>
                                                        <td>
                                                            {{ $item->product_name }}
                                                            {{-- {{ $item->product_id }} --}}
                                                        </td>
                                                        <td>
                                                            {{ $item->color }}
                                                        </td>
                                                        <td>
                                                            {{ $item->size }}
                                                        </td>

                                                        <td>
                                                            {{ (int) $item->total_qty_sold }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item->today_sold }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item->seven_day_sold }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item->fifteen_day_sold }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item->all_time_sold }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item->current_stock }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item->all_time_sold + (int) $item->current_stock }}
                                                        </td>
                                                        <td>
                                                            {{ $item->purchase_date }}
                                                        </td>
                                                        <td>
                                                            {{ $item->last_update_date }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="row hidden">
                                    <div class="col-md-12">
                                        <h3>Detailed Color History Report:</h3>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-condensed bg-gray">
                                                <tr class="bg-info">
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Color</th>
                                                    <th>Size</th>
                                                    <th>Selling Date</th>
                                                    <th>Quantity Sold</th>
                                                    <th>Current Stock</th>
                                                </tr>
                                                @foreach ($history_detail as $item)
                                                    <tr>
                                                        <td>
                                                            {{ $loop->iteration }}
                                                        </td>
                                                        <td>
                                                            {{ $item->product_name }}
                                                        </td>
                                                        <td>
                                                            {{ $item->color }}
                                                        </td>
                                                        <td>
                                                            {{ $item->size }}
                                                        </td>
                                                        <td>
                                                            {{ $item->transaction_date }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item->sell_qty }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item->current_stock }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <h3>Detailed Color Report:</h3>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-condensed bg-gray">
                                                <tr class="bg-info">
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Color</th>
                                                    <th>Selling Date</th>
                                                    <th>Quantity Sold</th>
                                                    {{-- <th>Current Stock</th> --}}
                                                </tr>
                                                @foreach ($current_detail as $item)
                                                    <tr>
                                                        <td>
                                                            {{ $loop->iteration }}
                                                        </td>
                                                        <td>
                                                            {{ $item->product_name }}
                                                        </td>
                                                        <td>
                                                            {{ $item->color }}
                                                        </td>
                                                        <td>
                                                            {{ $item->transaction_date }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item->sell_qty }}
                                                        </td>
                                                        {{-- <td>
                                                              {{ (int)$item->current_stock }}
                                                         </td> --}}
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane " id="color_history">
                                <div class="row">
                                    <h4 class="modal-title">From: {{ $from_date }} - To: {{ $to_date }}</h4>
                                    <div class="col-md-12">
                                        <h3>Groupped History Color Report:</h3>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-condensed bg-gray">
                                                <tr class="bg-green">
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Color</th>
                                                    <th>Quantity Sold</th>
                                                    <th>Current Stock</th>
                                                </tr>
                                                @foreach ($history_group as $item)
                                                    <tr>
                                                        <td>
                                                            {{ $loop->iteration }}
                                                        </td>
                                                        <td>
                                                            {{ $item->product_name }}
                                                        </td>
                                                        <td>
                                                            {{ $item->color }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item->total_qty_sold }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item->current_stock }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <h3>Detailed Color History Report:</h3>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-condensed bg-gray">
                                                <tr class="bg-info">
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Color</th>
                                                    <th>Size</th>
                                                    <th>Selling Date</th>
                                                    <th>Quantity Sold</th>
                                                    <th>Current Stock</th>
                                                </tr>
                                                @foreach ($history_detail as $item)
                                                    <tr>
                                                        <td>
                                                            {{ $loop->iteration }}
                                                        </td>
                                                        <td>
                                                            {{ $item->product_name }}
                                                        </td>
                                                        <td>
                                                            {{ $item->color }}
                                                        </td>
                                                        <td>
                                                            {{ $item->size }}
                                                        </td>
                                                        <td>
                                                            {{ $item->transaction_date }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item->sell_qty }}
                                                        </td>
                                                        <td>
                                                            {{ (int) $item->current_stock }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@stop
@section('javascript')
    {{-- <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script> --}}
    {{-- <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script> --}}
    <script>
        if ($('#product_purchase_date_filter').length == 1) {
            let currentUrl = window.location.href;
            let urlSegments = currentUrl.split('/');
            let startDateFromUrl = urlSegments[6] || moment().subtract(365, 'days');
            let endDateFromUrl = urlSegments[7] || moment();
            console.log(startDateFromUrl, endDateFromUrl);

            var purchasedateRangeSettings = {
                ranges: ranges,
                startDate: moment(startDateFromUrl).format(moment_date_format), //moment().subtract(365, 'days'),
                endDate: moment(endDateFromUrl).format(moment_date_format), //moment(),
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
            });
            $('#product_purchase_date_filter').on('cancel.daterangepicker', function(
                ev, picker) {
                $('#product_purchase_date_filter').val('');
            });
            // $('#product_purchase_date_filter').data('daterangepicker').setStartDate(moment());
            // $('#product_purchase_date_filter').data('daterangepicker').setStartDate(moment().subtract(6, 'd'));
            // $('#product_purchase_date_filter').data('daterangepicker').setEndDate(moment());
            $('#product_purchase_date_filter').data('daterangepicker').setStartDate(moment(startDateFromUrl));
            $('#product_purchase_date_filter').data('daterangepicker').setEndDate(moment(endDateFromUrl));
        }
        $("input#product_purchase_date_filter,.location").on('change', function(e) {
            var start = '';
            var end = '';
            var location = $('.location').val() != 0 ? $('.location').val() : ''
            var refference = $('.refference').val()

            start = $('input#product_purchase_date_filter')
                .data('daterangepicker')
                .startDate.format('YYYY-MM-DD');
            end = $('input#product_purchase_date_filter')
                .data('daterangepicker')
                .endDate.format('YYYY-MM-DD');
            $.ajax({
                url: "{{ url('product/color-detail') }}/" + @json($name) + '/' +
                    start +
                    '/' + end + '/' + refference,
                data: {
                    "location_id": location
                },
                success: function(response) {
                    $('#filterAppend').html('');
                    $('#filterAppend').html(response);
                },
            });
        })
    </script>
@stop
