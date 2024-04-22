@extends('layouts.app')
@section('title', __('report.register_report'))
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
    <h1>{{ __('report.register_report')}}</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
              {!! Form::open(['url' => action('ReportController@getRegisterReport'), 'method' => 'get', 'id' => 'register_report_filter_form' ]) !!}
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('location_id', 'Location' . ':') !!}
                        {!! Form::select('location_id', $business, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => 'All Locations']); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('register_sr_date_filter', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'register_sr_date_filter', 'readonly']); !!}
                    </div>
                </div>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <h4>Total Cash: <span class="display_currency" data-currency_symbol="false" id="total_cash"></span> €</h4>
                <h4>Total Card: <span class="display_currency" data-currency_symbol="false" id="total_card"></span> €</h4>
                <h4>Total Coupon: <span class="display_currency" data-currency_symbol="false" id="total_coupon"></span> €</h4>
                <h4>Total Gift Card: <span class="display_currency" data-currency_symbol="false" id="total_gift_card"></span> €</h4>
                <h4>Total Discount: <span class="display_currency" data-currency_symbol="false" id="total_discount"></span> €</h4>
                <h4>Total Items: <span class="display_currency" data-currency_symbol="false" id="total_items"></span></h4>
                <h4>Total Invoices: <span class="display_currency" data-currency_symbol="false" id="total_invoices"></span></h4>
                <h4>Total Amount: <span class="display_currency" data-currency_symbol="false" id="total_amount"></span> €</h4>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="register_report_table">
                        <thead>
                            <tr>
                                <th>@lang('messages.action')</th>
                                <th>Location Name</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Invoices</th>
                                <th>Cash</th>
                                <th>Card</th>
                                <th>Gift Card</th>
                                <th>Coupon</th>
                                <th>Discounted Amount</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->
<div class="modal fade view_register" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
@endsection