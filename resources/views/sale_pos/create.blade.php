@extends('layouts.app')

@section('title', 'POS')

@section('css')
<style>
    .pos-express-btn {
        font-size: 13px !important;
        overflow: hidden !important;
        height: 41px !important;
        white-space: normal;
    }

    .grid-container {
        display: grid;
        grid-template-columns: auto auto auto auto;
        padding-right: 15px;
        gap: 6px;
    }
</style>

@endsection
@section('content')

<!-- Content Header (Page header) -->
<!-- <section class="content-header">
                                            <h1>Add Purchase</h1> -->
<!-- <ol class="breadcrumb">
                                                <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
                                                <li class="active">Here</li>
                                            </ol> -->
<!-- </section> -->

<!-- Main content -->
{{-- @dd($pos_settings) --}}

<section class="content no-print">
    <div class="row">

        <div
            class="@if (!empty($pos_settings['hide_product_suggestion']) && !empty($pos_settings['hide_recent_trans'])) col-md-10 col-md-offset-1 @else col-md-7 @endif col-sm-12">
            @component('components.widget', ['class' => 'box-success'])
            @slot('header')
            <div class="row" style="margin-bottom: 5px;">
                <div class="col-sm-12">


                    <div class="row" style="margin-bottom: 5px;">
                        <div class="col-sm-4" style="padding-right: 0 !important;">
                            <h3 class="box-title">POS Terminal <i class="fa fa-keyboard-o hover-q text-muted"
                                    aria-hidden="true" data-container="body" data-toggle="popover"
                                    data-placement="bottom"
                                    data-content="@include('sale_pos.partials.keyboard_shortcuts_details')"
                                    data-html="true" data-trigger="hover" data-original-title="" title=""></i>
                            </h3>
                            <br> <strong class="ml-2" id="total_b_point"> </strong>
                            <br>
                        </div>
                        <div class="col-sm-8" style="padding-right: 0 !important;">
                            {{-- onclick="openReturnWindow();" --}}

                            <div class="grid-container ">
                                <a title="Return Sale" data-toggle="tooltip" data-placement="bottom"
                                    class="btn btn-danger btn-md " href="{{ url('sell-return/add') }}" target="__blank">
                                    <strong><i class="fa fa-undo"></i></strong>
                                    Return
                                    {{-- <strong><i class="fa fa-asl-interpreting"></i></strong> --}}
                                    {{-- RETURN --}}
                                </a>
                                {{-- <button type="button" onclick="openPopupWindow('/products/transfer');"
                                    title="Transfer Products" data-toggle="tooltip" data-placement="bottom"
                                    class="btn btn-warning ">
                                    <strong><i class="fa fa-random fa-lg"></i></strong>
                                    Transfer
                                </button> --}}
                                {{-- <button type="button" onclick="TransferSelected();"
                                    title="Transfer Products" data-toggle="tooltip" data-placement="bottom"
                                    class="btn btn-warning ">
                                    <strong><i class="fa fa-random fa-lg"></i></strong>
                                    Transfer
                                </button> --}}

                                <button type="submit" class="btn btn-warning" id="bulkTransfer-selected">
                                    <i class="fa fa-random"></i>
                                    Transfer Selected
                                </button>
                                <button type="button" title="Gift Card" data-toggle="tooltip" data-placement="bottom"
                                    class="btn btn-success pos_add_quick_product  btn-3"
                                    data-href="{{ action('GiftCardController@quickAdd') }}"
                                    data-container=".quick_add_product_modal">
                                    <i class="fa fa-archive fa-lg"></i>
                                    Gift Card
                                </button>


                                <button type="button" title="Add Cupons" data-toggle="tooltip" data-placement="bottom"
                                    class=" btn btn-success pos_add_quick_product  btn-4"
                                    data-href="{{ action('CouponController@quickAdd') }}"
                                    data-container=".quick_add_product_modal">
                                    <i class="fa fa-calendar-check-o fa-lg"></i>
                                    Cupon
                                </button>

                                <a title="Return Sale" data-toggle="tooltip" data-placement="bottom"
                                    class="btn btn-info btn-md  btn-5" href="{{ url('products/') }}" target="__blank">
                                    <strong><i class="fa fa-list-ul"></i></strong>
                                    List Product
                                    {{-- <strong><i class="fa fa-asl-interpreting"></i></strong> --}}
                                    {{-- RETURN --}}
                                </a>
                                <a title="Return Sale" data-toggle="tooltip" data-placement="bottom"
                                    class="btn btn-info btn-md btn-6" href="{{ url('reports/product-sell-report') }}"
                                    target="__blank">
                                    <strong><i class="fa fa-dollar"></i></strong>
                                    Sale Report
                                </a>


                                <a title="Return Sale" data-toggle="tooltip" data-placement="bottom"
                                    class="btn btn-info btn-md  btn-7" href="{{ url('reports/stock-report') }}"
                                    target="__blank">
                                    <strong>
                                        <i class="fa fa-inbox"></i>
                                    </strong>
                                    Stock Report
                                </a>

                                <button type="button" class="  btn-8 btn btn-warning btn-md "
                                    onClick="transationHistory();">Client
                                    History</button>
                            </div>


                        </div>

                    </div>
                    <br>
                    <input type="hidden" id="item_addition_method"
                        value="{{ $business_details->item_addition_method }}">
                    @if (is_null($default_location))
                    <div class="float-left	">
                        <div class="col-12">
                            <div class="form-group" style="margin-bottom: 0px;">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-map-marker"></i>
                                    </span>
                                    {!! Form::select(
                                    'select_location_id',
                                    $business_locations,
                                    null,
                                    [
                                    'class' => 'form-control input-md mousetrap ',
                                    'placeholder' => __('lang_v1.select_location'),
                                    'id' => 'select_location_id',
                                    'onchange' => 'locationChange(event);',
                                    'required',
                                    'autofocus',
                                    ],
                                    $bl_attributes,
                                    ) !!}
                                    <span class="input-group-addon">
                                        @show_tooltip(__('tooltip.sale_location'))
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

            </div>
            @endslot
            {!! Form::open(['url' => action('SellPosController@store'), 'method' => 'post', 'id' =>
            'add_pos_sell_form']) !!}

            {!! Form::hidden('location_id', $default_location, [
            'id' => 'location_id',
            'data-receipt_printer_type' => isset($bl_attributes[$default_location]['data-receipt_printer_type'])
            ? $bl_attributes[$default_location]['data-receipt_printer_type']
            : 'browser',
            ]) !!}

            <!-- /.box-header -->
            <div class="box-body" style="    padding-top: 0;">
                <div class="row">
                    <input type="text" hidden id="direct_cash" name="direct_cash" value="0">

                    @if (config('constants.enable_sell_in_diff_currency') == true)
                    <div class="col-md-4 col-sm-6">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-exchange"></i>
                                </span>
                                {!! Form::text('exchange_rate', config('constants.currency_exchange_rate'), [
                                'class' => 'form-control input-sm input_number',
                                'placeholder' => __('lang_v1.currency_exchange_rate'),
                                'id' => 'exchange_rate',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    @endif
                    @if (!empty($price_groups))
                    @if (count($price_groups) > 1)
                    <div class="col-md-4 col-sm-6">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-money"></i>
                                </span>
                                @php
                                reset($price_groups);
                                @endphp
                                {!! Form::hidden('hidden_price_group', key($price_groups), ['id' =>
                                'hidden_price_group']) !!}
                                {!! Form::select('price_group', $price_groups, null, [
                                'class' => 'form-control
                                select2',
                                'id' => 'price_group',
                                'style' => 'width: 100%;',
                                ]) !!}
                                <span class="input-group-addon">
                                    @show_tooltip(__('lang_v1.price_group_help_text'))
                                </span>
                            </div>
                        </div>
                    </div>
                    @else
                    @php
                    reset($price_groups);
                    @endphp
                    {!! Form::hidden('price_group', key($price_groups), ['id' => 'price_group']) !!}
                    @endif
                    @endif

                    @if (in_array('subscription', $enabled_modules))
                    <div class="col-md-4 pull-right col-sm-6">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('is_recurring', 1, false, ['class' => 'input-icheck', 'id' =>
                                'is_recurring']) !!} @lang('lang_v1.subscribe')?
                            </label><button type="button" data-toggle="modal" data-target="#recurringInvoiceModal"
                                class="btn btn-link"><i
                                    class="fa fa-external-link"></i></button>@show_tooltip(__('lang_v1.recurring_invoice_help'))
                        </div>
                    </div>
                    @endif
                </div>
                <div class="row">
                    <div class="@if (!empty($commission_agent)) col-sm-4 @else col-sm-6 @endif "
                        style="padding-left: 5px ; padding-right: 5px ;">
                        <div class="form-group" style="width: 100% !important">
                            <div class="input-group" style="    margin-bottom: 25px;">
                                <span class="input-group-addon">
                                    <i class="fa fa-user"></i>
                                </span>
                                <input type="hidden" id="default_customer_id" value="{{ $walk_in_customer['id'] }}">
                                <input type="hidden" id="default_customer_name" value="{{ $walk_in_customer['name'] }}">
                                {!! Form::select('contact_id', [], null, [
                                'class' => 'form-control mousetrap',
                                'id' => 'customer_id',
                                'placeholder' => 'Enter Customer name / phone',
                                'onchange' => 'checkThiss(this);',
                                'required',
                                'style' => 'width: 100%;',
                                ]) !!}
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default bg-white btn-flat add_new_customer"
                                        data-name="" @if (!auth()->user()->can('customer.create')) disabled @endif><i
                                            class="fa fa-plus-circle text-primary fa-lg"></i></button>
                                </span>
                            </div>
                        </div>

                    </div>
                    <input type="hidden" name="pay_term_number" id="pay_term_number"
                        value="{{ $walk_in_customer['pay_term_number'] }}">
                    <input type="hidden" name="pay_term_type" id="pay_term_type"
                        value="{{ $walk_in_customer['pay_term_type'] }}">

                    @if (!empty($commission_agent))
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::select('commission_agent', $commission_agent, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('lang_v1.commission_agent'),
                            ]) !!}
                        </div>
                    </div>
                    @endif

                    <div class="@if (!empty($commission_agent)) col-sm-4 @else col-sm-6 @endif"
                        style="padding-left: 5px ; padding-right: 5px ;">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-btn">
                                    <button type="button"
                                        class="btn btn-default bg-white btn-flat pos_add_quick_product"
                                        data-href="{{ action('ProductController@quickAddOnly') }}"
                                        data-container=".quick_add_product_modal"><i class="fa fa-barcode"></i></button>

                                </span>
                                {!! Form::text('search_product', null, [
                                'class' => 'form-control mousetrap',
                                'id' => 'search_product',
                                'placeholder' => __('lang_v1.search_product_placeholder'),
                                'disabled' => is_null($default_location) ? true : false,
                                'autofocus' => is_null($default_location) ? false : true,
                                ]) !!}
                                {{-- <span class="input-group-btn">
                                    <button type="button"
                                        class="btn btn-default bg-white btn-flat pos_add_quick_product"
                                        data-href="{{action('ProductController@quickAdd')}}"
                                        data-container=".quick_add_product_modal"><i
                                            class="fa fa-plus-circle text-primary fa-lg"></i></button>
                                </span> --}}
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>

                    <!-- Call restaurant module if defined -->
                    @if (in_array('tables', $enabled_modules) || in_array('service_staff', $enabled_modules))
                    <span id="restaurant_module_span">
                        <div class="col-md-3"></div>
                    </span>
                    @endif
                </div>


                <div class="row">
                    <div class="form-group col-md-6" id="Bonus_Points" style="display: none; padding:0 5px;">
                        <div class="input-group " style="width:100%;">
                            {{-- <label>Bonus Points</label> --}}
                            <p id="custDet" hidden></p>
                            <input class="form-control" type="hidden" id="user_total_point" placeholder="Bonus Point">
                            <input class="form-control" type="hidden" id="total_points" placeholder="Bonus Point">
                            <input class="form-control" type="number" id="cust_points" name="cust_bonus_point"
                                placeholder="Bonus Point" onchange="bonusinputes()">
                            <span id="errorshow" class="text-danger"></span>
                        </div>
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        {{-- <label>Discount %</label> --}}
                        <input class="form-control" type="hidden" id="cust_discount" name="cust_discount"
                            onchange="updateDiscount(this.value);" placeholder="Discount" readonly>
                    </div>
                </div>
                {{-- <div class="form-group col-md-2">
                    <div class="input-group">
                        <label> Gift Card </label>
                        <button type="button" title="Quick Gift Card"
                            class="col-md-12 btn btn-default bg-white btn-flat pos_add_quick_product"
                            data-href="{{action('GiftCardController@quickAdd')}}"
                            data-container=".quick_add_product_modal"><i
                                class="fa fa-archive text-primary fa-lg"></i></button>
                    </div>
                </div>
                <div class="form-group col-md-2">
                    <div class="input-group">
                        <label> Coupons </label>
                        <button type="button" class="col-md-12 btn btn-default bg-white btn-flat pos_add_quick_product"
                            data-href="{{action('CouponController@quickAdd')}}"
                            data-container=".quick_add_product_modal"><i
                                class="fa fa-calendar-check-o text-primary fa-lg"></i></button>
                    </div>
                </div> --}}
                <div class="row">
                    <div class="col-sm-12 pos_product_div">
                        <input class="form-control" type="hidden" id="cust_expiry" value="0">
                        <input type="hidden" name="sell_price_tax" id="sell_price_tax"
                            value="{{ $business_details->sell_price_tax }}">

                        <!-- Keeps count of product rows -->
                        <input type="hidden" id="product_row_count" value="0">
                        @php
                        $hide_tax = '';
                        if (session()->get('business.enable_inline_tax') == 0) {
                        $hide_tax = 'hide';
                        }
                        @endphp
                        <table class="table table-condensed table-bordered table-striped table-responsive"
                            id="pos_table">
                            <thead>
                                <tr>
                                    <th
                                        class="tex-center @if (!empty($pos_settings['inline_service_staff'])) col-md-3 @else col-md-4 @endif">
                                        @lang('sale.product') @show_tooltip(__('lang_v1.tooltip_sell_product_column'))
                                    </th>
                                    <th class="text-center col-md-3">
                                        Discount
                                    </th>
                                    <th class="text-center col-md-3">
                                        @lang('sale.qty')
                                    </th>

                                    @if (!empty($pos_settings['inline_service_staff']))
                                    <th class="text-center col-md-2">
                                        @lang('restaurant.service_staff')
                                    </th>
                                    @endif
                                    <th class="text-center col-md-2 {{ $hide_tax }}">
                                        @lang('sale.price_inc_tax')
                                    </th>
                                    <th class="text-center col-md-3">
                                        Original Price
                                    </th>
                                    <th class="text-center col-md-3">
                                        UP
                                    </th>
                                    <th class="text-center col-md-2">
                                        @lang('sale.subtotal')
                                    </th>
                                    <th class="text-center"><i class="fa fa-close" aria-hidden="true"></i></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                @include('sale_pos.partials.pos_details')

                @include('sale_pos.partials.payment_modal')

                @if (empty($pos_settings['disable_suspend']))
                @include('sale_pos.partials.suspend_note_modal')
                @endif

                @if (empty($pos_settings['disable_recurring_invoice']))
                @include('sale_pos.partials.recurring_invoice_modal')
                @endif
            </div>
            <!-- /.box-body -->
            {!! Form::close() !!}
            @endcomponent
        </div>

        <div class="col-md-5 col-sm-12">
            @include('sale_pos.partials.right_div')
        </div>
    </div>
</section>

<!-- This will be printed -->
<section class="invoice print_section" id="receipt_section">
</section>
<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    @include('contact.create', ['quick_add' => true])
</div>
<!-- /.content -->
<div class="modal fade register_details_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade close_register_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"
    id="quick_add_product_modal"></div>

<div class="modal fade in" tabindex="-1" role="dialog" id="unknownDiscountModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" id="closeThis" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">×</span></button>
                <h4 class="modal-title">Unknown Discount</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="discount_type_modal">
                                Discount Amount:*
                            </label>
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-info"></i>
                                </span>
                                <input type="text" class="form-control" placeholder="0.00" id="unknownDiscountAmount">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="updateUnknown();">Update</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Ok</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
{{-- Transaction History Start --}}
<div class="modal fade in" tabindex="-1" role="dialog" id="tHistoryModal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" id="closeThis" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">×</span></button>
                <h4 class="modal-title">transaction History</h4>
            </div>
            <div class="modal-body">
                <div id="transaction_table"></div>


            </div>
            <div class="modal-footer">
                {{-- <button type="button" class="btn btn-primary" onclick="updateUnknown();">Update</button> --}}
                {{-- <button type="button" class="btn btn-default" data-dismiss="modal">Ok</button> --}}
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
{{-- Transaction History Stop --}}

{{-- Price Change Start --}}
{{-- <div class="modal fade in" tabindex="-1" role="dialog" id="PChangeModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" id="closeThis" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">×</span></button>
                <h4 class="modal-title">Price Change</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="New_Price">
                                New Price*
                            </label>
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-info"></i>
                                </span>
                                <input type="text" class="form-control" placeholder="0.00" id="unknownDiscountAmount">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="updateUnknown11();">Update</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Ok</button>
            </div>
        </div>
    </div>
</div> --}}

<div>
</section>
<!-- modal-dialog -->
<div class="modal fade in" tabindex="-1" role="dialog" id="transferModal" >
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
<!-- -->
</div>
{{-- Price Change Stop --}}
@stop

@section('javascript')
<script type="text/javascript">
    $(function() {
            window.onbeforeunload = function() {
                return "Do you really want to leave this page?";
                //if we return nothing here (just calling return;) then there will be no pop-up question at all
                //return;
            };
            $('#change_text').html(__currency_trans_from_en(0.00, true))
        });

        function clacuateChange(id) {
            var amount = $('#' + $(id).attr('id')).val();
            // if (__read_number($('#total_payable')) < 1) {
            if (__read_number($('#final_total_input')) < 1) {
                alert('Payable is too Low');
                return 0;
            }
            bonusinputes()
     
            // var calc = parseFloat(amount - __read_number($('#final_total_input')));
            var totalPayable = $('#total_payable').text();
            var newTotalPayable = totalPayable.replace(/,/g, '.');
            var calc = parseFloat(amount - newTotalPayable);
            console.log('amount',amount)
            console.log('newTotalPayable',newTotalPayable)
            $('#change_text').html(__currency_trans_from_en(calc, true));
        }

        function transationHistory() {
            $('#tHistoryModal').modal('show');
            // $('#customer_id');
            var customer_id = $('#customer_id').val();
            $.ajax({
                type: 'GET',
                url: '/sells/pos/transationhistory?customer_id=' + customer_id,
                //    url:'/sells/pos/transationhistory', 
                success: function(data) {
                    // console.log(data);

                    $('#transaction_table').html('');
                    $('#transaction_table').append(data)
                }
            });

        }
        // function PriceChange(){
        // 	$('#PChangeModal').modal('show'); 
        // 	// $('#customer_id');
        // 	var customer_id=$('#customer_id').val();
        // 	var location_id=$('#location_id').val();
        // 	var product_id=$('#product_id').attr("data-variation_id")
        // 	console.log(location_id,product_id);
        // 	$.ajax({
        //        type:'GET',
        //        url:'/sells/pos/transationhistory?customer_id='+customer_id, 
        //     //    url:'/sells/pos/transationhistory', 
        //        success:function(data){
        // 		// console.log(data);

        // 		// $('#transaction_table').html('');
        // 		// $('#transaction_table').append(data)
        // 	}
        //     });

        // } 
        function ResetFields(index) {
            $("#amount_" + index).removeAttr("max");
            $("#amount_" + index).removeAttr("min");
            $("#amount_" + index).removeAttr("readonly");
            $("#amount_" + index).val(0);
        }

        function applyGiftCard(input, rowIndex) {
            ResetFields(rowIndex);
            isOk = true;
            $('.gift_cardc').each(function() {
                var currentElement = $(this);
                if (currentElement.attr("id") != input.id) {
                    var value = currentElement.val();
                    if (input.value == value) {
                        alert("You already used this please use another one");
                        isOk = false;
                        return (false);
                    }
                }
            });
            if (!isOk) {
                return (false);
            }
            name = input.value;
            PATCH = "PATCH";
            $.ajax({
                type: 'GET',
                url: '/sells/pos/verifyGiftCard/' + name,
                success: function(data) {
                    if (data.success) {
                        var obj = data.msg;
                        if (obj['isActive'] == "active") {
                            if (obj['current_date'] <= obj['expiry_date']) {
                                $("#amount_" + rowIndex).attr("readonly", true);
                                $("#amount_" + rowIndex).val(obj['value']).change();
                                // $("#amount_"+rowIndex).attr("readonly",true);
                                $("#note_" + rowIndex).val('Availed Gift Card : ' + obj['name'] + ' Value: ' +
                                    obj['value']).change();
                            } else {
                                alert("Sorry This Gift Card is Expired or Consumed \n Expiry Date : " + obj[
                                    'expiry_date']);
                                $("#amount_" + rowIndex).val(0).change();
                                $("#note_" + rowIndex).val('');
                                ResetFields(rowIndex);
                            }
                        } else {
                            alert("Sorry This Gift Card Status is " + obj['isActive'])
                            $("#amount_" + rowIndex).val(0).change();
                            $("#note_" + rowIndex).val('');
                            ResetFields(rowIndex);
                        }
                    } else {
                        alert(" " + data.msg);
                        $("#amount_" + rowIndex).val(0).change();
                        $("#note_" + rowIndex).val('');
                        ResetFields(rowIndex);
                    }
                }
            });

        }

        function applyCoupon(input, rowIndex) {
            ResetFields(rowIndex);
            isOk = true;
            $('.couponc').each(function() {
                var currentElement = $(this);
                if (currentElement.attr("id") != input.id) {
                    var value = currentElement.val();
                    if (input.value == value) {
                        alert("you Already use This Please use another One");
                        isOk = false;
                        return (false);
                    }
                }
            });
            if (!isOk) {
                return (false);
            }
            name = input.value;
            PATCH = "PATCH";
            $.ajax({
                type: 'GET',
                url: '/sells/pos/verifyCoupon/' + name,
                success: function(data) {
                    if (data.success) {
                        var obj = data.msg;
                        if (obj['isActive'] == "active") {
                            $("#amount_" + rowIndex).val(obj['value']).change();
                            $("#note_" + rowIndex).val('Availed Coupon : ' + obj['name'] + ' Value: ' + obj[
                                'value']).change();
                        } else {
                            alert("Sorry This Gift Card Status is " + obj['isActive'])
                            $("#amount_" + rowIndex).val(0).change();
                            $("#note_" + rowIndex).val('');
                            ResetFields(rowIndex);
                        }
                    } else {
                        alert(" " + data.msg);
                        $("#amount_" + rowIndex).val(0).change();
                        $("#note_" + rowIndex).val('');
                        ResetFields(rowIndex);
                    }
                }
            });
        }

        function locationChange(e) {
            var category_id = $('select#product_category').val();
            var brand_id = $('select#product_brand').val();
            get_product_suggestion_list(category_id, brand_id, e.target.value);
        }

        function checkThiss(obj) {
            if (obj.value != '1') {
                $.ajax({
                    type: 'GET',
                    url: '/sells/pos/getCustDiscount/' + obj.value,
                    success: function(data) {
                        if (data.success) {
                            var objData = data.msg;
                            //Update values
                            // $('input#discount_amount_modal').val(objData['discount']);
                            $('#Bonus_Points').css('display', 'block');

                            //   $('input#cust_points').val(objData['bonus_points']);
                            const bonusePoint = @json(config('app.discount_amount'));
                            total_amountOfbonus = bonusePoint * objData['bonus_points'];

                            // console.log(objData);
                            // console.log('total bonus Points'+bonusePoint+total_amountOfbonus);
                            $('input#total_points').val(total_amountOfbonus.toFixed(2));
                            $('input#cust_points').val(0);
                            $('input#user_total_point').val(objData['bonus_points']);
                            $("#cust_points").attr("max", total_amountOfbonus.toFixed(2));

                            $('#custDet').text('');
                            $('#custDet').text(' Mobile: ' + objData['mobile'] + ' Address: ' + objData[
                                'landmark']);
                            $('#total_b_point').text('');
                            $('#total_b_point').text('Total BP: ' + objData['bonus_points'] + ' (' +
                                total_amountOfbonus.toFixed(2) + ')' + ' Mobile: ' + objData['mobile'] +
                                ' Address: ' + objData['landmark']);
                            $('input#cust_discount').val(objData['discount']);
                            $('input#cust_expiry').val(objData['bp_expiry']);

                            // $('input#discount_type').val($('select#discount_type_modal').val());
                            // __write_number($('input#discount_amount'), __read_number($('input#discount_amount_modal')));
                            // pos_total_row();
                        } else {
                            alert(" " + data.msg);
                        }
                    }
                });
            } else {
                $('input#cust_points').val(0);
                $('input#cust_discount').val(0);
                $('input#cust_expiry').val(0);
            }
        }

        function updateDiscount(Value) {
            // $('input#discount_type_modal').val('percentage');
            // $('input#discount_amount_modal').val(Value);
            // $('#posEditDiscountModalUpdate').click();
        }

        function bonusinputes() {
            const value = parseInt($('#cust_points').val());
            // console.log(value);
            var user_total_point = parseInt($('#user_total_point').val());
            var total_point = parseInt($('#total_points').val());
            const discount = @json(config('app.discount_amount'));
            total_amountOfbonus = discount * user_total_point;
            // console.log(total_point,value, value <= total_point);
            if (value <= total_point) {
                $('#errorshow').text('');
                var request_point = value * 20;
                var remaining_point = user_total_point - request_point;

                var detail = $('#custDet').text()
                $('#total_b_point').text('Total BP: ' + remaining_point + ' (' + (total_amountOfbonus - value).toFixed(2) +
                    ')' + detail)
                var subtract_price = value
                // var subtract_price = value*discount
                const spanText = $('input[name=final_total]').val().replace(',', '.');
                var total_price = parseFloat(spanText) != 0 ? parseFloat(spanText) - subtract_price : 0;
                console.log(spanText, total_price, parseFloat(spanText));
                $('#total_payable').text(total_price.toFixed(2).replace('.', ','));
            } else {
                $('input#cust_points').val(total_point);
                $('#errorshow').text('Please enter a value less than or equal to ' + total_point);
            }

        }

        function openGiftCard() {
            $('#modal_payment').modal('show');
            //method_0
            $('#method_0').val("gift_card").change();
            $('#gift_card_0').focus();

        }

        function openBonusPoint() {
            $('#modal_payment').modal('show');
            //method_0
            $('#method_0').val("bonus_points").change();
            $('#bonus_points_0').focus();
            // $('#pos-save').removeAttr('disabled');


        }

        function couponPayment() {
            // $('#coupon_payment_modal').modal('show');
            $('#modal_payment').modal('show');
            $('#method_0').val("coupon").change();
            $('#coupon_0').focus();

        }

        function changePayment(obj, rowIndex) {
            console.log(obj);
            var discount = $('input#cust_discount').val();
            var amount_bonus_points = obj.value * 0.5;
            $("#amount_" + rowIndex).val(amount_bonus_points).change();
            $("#amount_" + rowIndex).removeAttr("max");
        }

        function applyUnknown(obj, rowIndex) {
            $('input#discount_amount_modal').val(obj.value);
            $("#discount_type_modal").val("fixed");

            $("#posEditDiscountModalUpdate").click();

        }

        function applyBonusPoint(rowIndex) {
            var ed = $('input#cust_expiry').val();
            var today = new Date();
            var dd = String(today.getDate()).padStart(2, '0');
            var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
            var yyyy = today.getFullYear();

            var td = yyyy + '-' + mm + '-' + dd;
            // if(ed == '0' || ed == '' )
            // {
            // 	alert("your Bonus Points is Expired ");return(false);
            // } 
            // if(td >= ed )
            // {
            // 	alert("This user Bonus Points is Expired ");return(false);
            // }
            var points = $('input#cust_points').val();
            var discount = $('input#cust_discount').val();
            var amount_bonus_points = points * 0.5;
            $("#bonus_points_" + rowIndex).val(points);
            $("#amount_" + rowIndex).val(amount_bonus_points).change();

            $("#bonus_points_" + rowIndex).attr("max", points)
            // $("#amount_"+rowIndex).attr("max",points)

        }

        function giveDiscount(discount, rowIndex, isButton = true) {


            var currentDiscount = parseInt($("#row_discount_amount_" + rowIndex).val());
            var currentTotal = parseInt($("#pos_line_total_" + rowIndex).val());
            if (discount == 0) {

            }

            currentDiscount += parseInt(discount);
            if (discount == 0) {
                currentDiscount = 0;
            }
            if (currentDiscount > 100) {
                alert("You cannot Give More Discount");
                return (false);
            }
            $("#row_discount_type_" + rowIndex).val('percentage');
            $("#val_un_discount_" + rowIndex).html(currentDiscount);
            $("#row_discount_amount_" + rowIndex).val(currentDiscount);
            $("#row_discount_amount_" + rowIndex).trigger("change");
        }

        function unKnownDiscountTotal(rowIndex) {
            var currentDiscount = parseInt($("#un_discount_" + rowIndex).val());
            var currentTotal = parseInt($("#pos_line_total_" + rowIndex).val());
            fTotal = currentTotal - currentDiscount;
            $("#pos_line_total_" + rowIndex).val(fTotal);
            $("#pos_line_total_text_" + rowIndex).text(__currency_trans_from_en(fTotal, true));
        }
        var isUnknownUse = false;

        function openDiscount() {
            if (!isUnknownUse) {
                $("#discount_type_modal").val('percentage');
            } else {
                $("#discount_type_modal").val('fixed');
            }
            // $("#discount_type_modal option").filter(function() {
            //     return this.value == "percentage"; 
            // }).attr('selected', true);
            $('#posEditDiscountModal').modal('show');
        }

        function openUnkown() {
            $('#unknownDiscountModal').modal('show');
        }

        function updateUnknown() {
            isUnknownUse = true;
            var discount = $("#unknownDiscountAmount").val();
            $("#discount_type_modal option").filter(function() {
                return this.text == "Unknown";
            }).attr('selected', true);
            $('input#discount_amount_modal').val(discount);
            $('#posEditDiscountModalUpdate').click();
            $('#closeThis').click();
        }

        function openReturnWindow() {
            link = "<?= url('pos') ?>";
            window.open(link, "_blank", "toolbar=yes,scrollbars=yes,resizable=yes,top=500,left=200,width=1200,height=800");
        }

        $("#quick_add_product_modal").on('shown.bs.modal', function(e) {
            $("#CustomPrice").focus();
            $("#value").focus();
        });

        onafterprint = function () {
            $('#total_b_point').text('');
            // $('#cust_points').hide();
                // window.location.href = "{{ url('pos/create') }}";
            }

        let productData = [];

        function TransferSelected() {
            const data = JSON.stringify( productData);
            const select_location_id = $('#select_location_id').val();
            var category_id = $('select#transferBussiness').val();
            $.ajax({
                method: 'POST',
                url: "{{url('/products/pos/mass-transfer')}}",
                data: {
                    products:data,
                    current_location:select_location_id,
                    bussiness_bulkTransfer:category_id,
                },
                success: function (result) {
                    if (result.success == 1) {
                        toastr.success(result.msg);
                        $('#transferModal').modal('hide'); 
                        reset_pos_form()
                    } else {
                        toastr.error(result.msg);
                        $('#transferModal').modal('hide'); 
                        reset_pos_form()
                    }
                },
                error: function (xhr, status, error) {
                    // Handle errors here
                    console.error('AJAX Error:', status, error);
                }
            });
                     
        }


        $(document).on('click', '#bulkTransfer-selected', function(e){
            e.preventDefault();
            productData = [];
            // Select all rows in the table
            const rows = document.querySelectorAll('#pos_table tbody tr');
            // Loop through each row to extract product ID and quantity
            rows.forEach(row => {
                const productId = row.querySelector('.product_id')?.value;
                const availableQty = row.querySelector('.available_qty')?.value;
                const quantity = row.querySelector('.input_quantity')?.value;

                if (productId && quantity) {
                    productData.push({
                        product_id: productId,
                        available_qty: availableQty,
                        quantity: quantity
                    });
                }
            });

            if(productData.length > 0){
                $('#transferModal').modal('show'); 
            } else{
                productData = [];
                swal('@lang("lang_v1.no_row_selected")');
            }    
        });


</script>

<script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
@include('sale_pos.partials.keyboard_shortcuts')

<!-- Call restaurant module if defined -->
@if (in_array('tables', $enabled_modules) ||
in_array('modifiers', $enabled_modules) ||
in_array('service_staff', $enabled_modules))
<script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
@endif
@endsection