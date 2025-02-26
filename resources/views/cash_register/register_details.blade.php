<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">

    <div class="modal-header">
      <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h3 class="modal-title">@lang( 'cash_register.register_details' ) ( {{ \Carbon::createFromFormat('Y-m-d H:i:s', $register_details->open_time)->format('jS M, Y h:i A') }} - {{ \Carbon::now()->format('jS M, Y h:i A') }})</h3>
    </div>
{{-- @dd($register_details) --}}
    <div class="modal-body">
      <div class="row">
        <div class="col-sm-12">
          <table class="table">
            <tr>
              <td>
                @lang('cash_register.cash_in_hand'):
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{ $cash_in_hand }}</span>
                {{-- <span class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand }}</span> --}}
              </td>
            </tr>
            <tr>
              @php
                if($transactions->sum('final_total') ){
                  $just_cash_sale = $transactions->sum('final_total') - $card - $gift_card - $coupon;
                }else{
                  $just_cash_sale = 0;
                }
              @endphp
              <td>
                @lang('cash_register.cash_payment'):
              </th>
              <td>
                <span class="display_currency" data-currency_symbol="true">
                  {{-- {{$just_cash_sale}} --}}
                  {{$cash}}
                </span>
                {{-- <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash }}</span> --}}
              </td>
            </tr>
            {{-- <tr>
              <td>
                @lang('cash_register.checque_payment'):
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cheque }}</span>
              </td>
            </tr> --}}
            <tr>
              <td>
                @lang('cash_register.card_payment'):
              </td>
              <td>
                {{-- <span class="display_currency" data-currency_symbol="true">{{ $card }}</span> --}}
                <span class="display_currency" data-currency_symbol="true">{{ $card }}</span>
                {{-- <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_card }}</span> --}}
              </td>
            </tr>
            <tr>
              <td>
                Discount Given:
              </td>
              <td>
                {{-- <span class="display_currency" data-currency_symbol="true">{{ $register_details->discount_given}}</span> --}}
                <span class="display_currency" data-currency_symbol="true">{{ $discount }}</span>
              </td>
            </tr>
            <tr>
              <td>
                Unknown Discount Given:
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{$details['transaction_details']->total_discount}}</span>
              </td>
            </tr>
            <tr>
              <td>
                Coupons:
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{$coupon ?? 0}}</span>
              </td>
            </tr>
            <tr>
              <td>
                Gift Card:
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{$gift_card ?? 0}}</span>
              </td>
            </tr>
            {{-- <tr>
              <td>
                @lang('cash_register.bank_transfer'):
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_bank_transfer }}</span>
              </td>
            </tr> --}}
            {{-- @if(config('constants.enable_custom_payment_1'))
              <tr>
                <td>
                  @lang('lang_v1.custom_payment_1'):
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_1 }}</span>
                </td>
              </tr>
            @endif
            @if(config('constants.enable_custom_payment_2'))
              <tr>
                <td>
                  @lang('lang_v1.custom_payment_2'):
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_2 }}</span>
                </td>
              </tr>
            @endif
            @if(config('constants.enable_custom_payment_3'))
              <tr>
                <td>
                  @lang('lang_v1.custom_payment_3'):
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_3 }}</span>
                </td>
              </tr>
            @endif --}}
            {{-- <tr>
              <td>
                @lang('cash_register.other_payments'):
              </td>
              <td>
                <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_other }}</span>
              </td>
            </tr> --}}
            <tr>
              <td>
                @lang('cash_register.total_cash')
              </td>
              <td>
                {{-- orignal one --}}
                {{-- <span class="display_currency" data-currency_symbol="true">{{$just_cash_sale + $cash_in_hand}}</span> --}}
                <span class="display_currency" data-currency_symbol="true">{{$cash + $cash_in_hand}}</span>
                {{-- <b><span class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand + $register_details->total_cash - $register_details->total_cash_refund }}</span></b> --}}
              </td>
              
            </tr>
            <tr>
              <td>
                Total Forced Prices
              </td>
              <td>
                <span>{{$forced_prices}}</span>
              </td>
            </tr>
            <tr class="success">
              <th>
                @lang('cash_register.total_refund')
              </th>
              <td>
                <b><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_refund }}</span></b><br>
                <small>
                @if($register_details->total_cash_refund != 0)
                  Cash: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_refund }}</span><br>
                @endif
                @if($register_details->total_cheque_refund != 0) 
                  Cheque: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cheque_refund }}</span><br>
                @endif
                @if($register_details->total_card_refund != 0) 
                  Card: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_card_refund }}</span><br> 
                @endif
                @if($register_details->total_bank_transfer_refund != 0)
                  Bank Transfer: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_bank_transfer_refund }}</span><br>
                @endif
                @if(config('constants.enable_custom_payment_1') && $register_details->total_custom_pay_1_refund != 0)
                    @lang('lang_v1.custom_payment_1'): <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_1_refund }}</span>
                @endif
                @if(config('constants.enable_custom_payment_2') && $register_details->total_custom_pay_2_refund != 0)
                    @lang('lang_v1.custom_payment_2'): <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_2_refund }}</span>
                @endif
                @if(config('constants.enable_custom_payment_3') && $register_details->total_custom_pay_3_refund != 0)
                    @lang('lang_v1.custom_payment_3'): <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_3_refund }}</span>
                @endif
                @if($register_details->total_other_refund != 0)
                  Other: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_other_refund }}</span>
                @endif
                </small>
              </td>
            </tr>
            <tr class="success">
              <th>
                @lang('cash_register.total_sales'):
              </th>
              <td>
                {{-- <span class="display_currency" data-currency_symbol="true">{{ ($card + $register_details->total_cash) }}</span> --}}
                {{-- <b class="display_currency" data-currency_symbol="true">{{ $transactions->sum('final_total') }}</b> --}}
                <b class="display_currency" data-currency_symbol="true">{{ $cash + $card + $coupon }}</b>
              </td>
            </tr>
          </table>
        </div>
      </div>
      @if (!isset($show_detail))
        @include('cash_register.register_product_details')
      @endif
      @include('cash_register.register_recent_transaction')
      
      <div class="row">
        <div class="col-xs-6">
          <b>@lang('report.user'):</b> {{ Auth::user()->username}}<br>
          <b>Email:</b> {{ Auth::user()->email}}
          {{-- <b>@lang('report.user'):</b> {{ $register_details->user_name}}<br>
          <b>Email:</b> {{ $register_details->email}} --}}
        </div>
        @if(!empty($register_details->closing_note))
          <div class="col-xs-6">
            <strong>@lang('cash_register.closing_note'):</strong><br>
            {{$register_details->closing_note}}
          </div>
        @endif
      </div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn btn-primary no-print" 
        aria-label="Print" 
          onclick="$(this).closest('div.modal').printThis();">
        <i class="fa fa-print"></i> @lang( 'messages.print' )
      </button>

      <button type="button" class="btn btn-default no-print" 
        data-dismiss="modal">@lang( 'messages.cancel' )
      </button>
    </div>

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->