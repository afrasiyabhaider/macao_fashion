@inject('request', 'Illuminate\Http\Request')
<div class="col-md-12 no-print pos-header">
  <input type="hidden" id="pos_redirect_url" value="{{action('SellPosController@create')}}">
  <div class="row">
    
    
    <div class="col-md-5 pull-right">
      <span class=" btn btn-info  pull-right" style="padding: 10px">
        <i class="fa fa-user"></i>
        <span class="h4 font-weight-bold">
          {{Auth::user()->first_name}}
          {{Auth::user()->last_name}}
        </span> -
        {{
          Auth::user()->business_location()->first()->name
        }}
      </span>
      <a href="{{ action('Auth\LoginController@logout')}}" title="LogOff" data-toggle="tooltip" data-placement="bottom" class="btn btn-danger btn-flat m-6  m-5 pull-right" >
        <strong><i class="fa fa-sign-in fa-lg"></i> LogOff</strong>
      </a>
      

       <a href="{{ action('HomeController@index')}}" title="Dashboard" data-toggle="tooltip" data-placement="bottom" class="btn btn-success btn-flat m-6  m-5 pull-right">
        <strong><i class="fa fa-tachometer fa-lg"></i></strong>
      </a>
      
      <a href="#" onclick="openPopupWindow('/products');" title="Stocks" data-toggle="tooltip" data-placement="bottom" class="btn btn-info btn-flat m-6 m-5 pull-right">
        <strong><i class="fa fa-barcode fa-lg"></i></strong>
      </a>
      {{-- <a href="#" onclick="openPopupWindow('/products/transfer');" title="Transfer Products" data-toggle="tooltip" data-placement="bottom" class="btn btn-warning btn-flat m-6  m-5 pull-right">
        <strong><i class="fa fa-random fa-lg"></i></strong>
      </a> --}}
       {{-- <a href="#" onclick="openPopupWindow('/pos');" title="Return Products" data-toggle="tooltip" data-placement="bottom" class="btn btn-info btn-flat m-6  m-5 pull-right">
        <strong><i class="fa fa-undo fa-lg"></i></strong>
      </a> --}}
       <a href="#" onclick="openPopupWindow('/products/bulk_add');" title="Bulk Product Add" data-toggle="tooltip" data-placement="bottom" class="btn btn-info btn-flat m-6  m-5 pull-right">
        <strong><i class="fa fa-plus fa-lg"></i></strong>
      </a>

      {{-- Close Register Button --}}
      {{-- <button type="button" id="close_register" title="{{ __('cash_register.close_register') }}" data-toggle="tooltip" data-placement="bottom" class="btn btn-danger btn-flat m-6 p-5 m-5 btn-modal pull-right" data-container=".close_register_modal" 
          data-href="{{ action('CashRegisterController@getCloseRegister')}}">
            <strong><i class="fa fa-window-close fa-lg"></i></strong>
      </button> --}}
      
      <button type="button" id="register_details" title="{{ __('cash_register.register_details') }}" data-toggle="tooltip" data-placement="bottom" class="btn btn-success btn-flat m-6  m-5 btn-modal pull-right" data-container=".register_details_modal" 
          data-href="{{ action('CashRegisterController@getRegisterDetails')}}">
            <strong><i class="fa fa-briefcase fa-lg" aria-hidden="true"></i></strong>
      </button>

      <button title="@lang('lang_v1.calculator')" id="btnCalculator" type="button" class="btn btn-success btn-flat pull-right m-5  mt-10 popover-default" data-toggle="popover" data-trigger="click" data-content='@include("layouts.partials.calculator")' data-html="true" data-placement="bottom">
            <strong><i class="fa fa-calculator fa-lg" aria-hidden="true"></i></strong>
        </button>

      {{-- <button type="button" title="{{ __('lang_v1.full_screen') }}" data-toggle="tooltip" data-placement="bottom" class="btn btn-primary btn-flat m-6 hidden-xs  m-5 pull-right" id="full_screen">
            <strong><i class="fa fa-window-maximize fa-lg"></i></strong>
      </button> --}}

      {{-- <button type="button" id="view_suspended_sales" title="{{ __('lang_v1.view_suspended_sales') }}" data-toggle="tooltip" data-placement="bottom" class="btn bg-yellow btn-flat m-6  m-5 btn-modal pull-right" data-container=".view_modal" 
          data-href="{{ action('SellController@index')}}?suspended=1">
            <strong><i class="fa fa-pause-circle-o fa-lg"></i></strong>
      </button> --}}

      @if(Module::has('Repair'))
        @include('repair::layouts.partials.pos_header')
      @endif

    </div>

    <div class="col-md-2">
      <div class="m-6 pull-right mt-15 hidden-xs"><strong>{{ @format_date('now') }}</strong></div>
    </div>
    
  </div>
</div>
