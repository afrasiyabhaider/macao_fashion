<div class="box box-solid">
     <div class="box-header">
          <h3 class="box-title">@lang('lang_v1.parent_sale')</h3>
     </div>
     <div class="box-body">
          {!! Form::hidden('location_id', $sell->location->id, ['id' => 'location_id', 'data-receipt_printer_type' =>
          $sell->location->receipt_printer_type ]); !!}

          {!! Form::open(['url' => action('SellReturnController@store'), 'method' => 'post', 'id' => 'sell_return_form'
          ])
          !!}
          {!! Form::hidden('transaction_id', $sell->id); !!}
          <div class="row">
               <div class="col-sm-4">
                    <strong>@lang('sale.invoice_no'):</strong> {{ $sell->invoice_no }} <br>
                    <strong>@lang('messages.date'):</strong> {{@format_date($sell->transaction_date)}}
               </div>
               <div class="col-sm-4">
                    <strong>@lang('contact.customer'):</strong> {{ $sell->contact->name }} <br>
                    <strong>@lang('purchase.business_location'):</strong> {{ $sell->location->name }}
               </div>
          </div>
          <div class="row">
               <div class="col-sm-4 hidden">
                    <div class="form-group">
                         {!! Form::label('invoice_no', __('sale.invoice_no').':') !!}
                         {!! Form::text('invoice_no', !empty($sell->return_parent->invoice_no) ?
                         $sell->return_parent->invoice_no : null, ['class' => 'form-control','autofocus'=>'true']); !!}
                    </div>
               </div>
               <div class="col-sm-3 hidden">
                    <div class="form-group">
                         {!! Form::label('transaction_date', __('messages.date') . ':*') !!}
                         <div class="input-group">
                              <span class="input-group-addon">
                                   <i class="fa fa-calendar"></i>
                              </span>
                              @php
                              $transaction_date = !empty($sell->return_parent->transaction_date) ?
                              $sell->return_parent->transaction_date : 'now';
                              @endphp
                              {!! Form::text('transaction_date', @format_date($transaction_date), ['class' =>
                              'form-control', 'readonly', 'required']); !!}
                         </div>
                    </div>
               </div>
               <div class="col-sm-12">
                    <table class="table bg-gray" id="sell_return_table">
                         <thead>
                              <tr class="bg-green">
                                   <th>#</th>
                                   <th>@lang('product.product_name')</th>
                                   <th>@lang('sale.unit_price')</th>
                                   <th>@lang('lang_v1.sell_quantity')</th>
                                   <th>@lang('lang_v1.return_quantity')</th>
                                   <th>@lang('lang_v1.return_subtotal')</th>
                                   <th>Remove</th>
                              </tr>
                         </thead>
                         <tbody>

                              @foreach($sell->sell_lines as $sell_line)
                              @php
                              $check_decimal = 'false';
                              if($sell_line->product->unit->allow_decimal == 0){
                              $check_decimal = 'true';
                              }

                              $unit_name = $sell_line->product->unit->short_name;

                              if(!empty($sell_line->sub_unit)) {
                              $unit_name = $sell_line->sub_unit->short_name;

                              if($sell_line->sub_unit->allow_decimal == 0){
                              $check_decimal = 'true';
                              } else {
                              $check_decimal = 'false';
                              }
                              }

                              // $total_price += ($sell_line->unit_price_inc_tax*$sell_line->quantity);

                              @endphp
                              <tr>
                                   <td>
                                        {{ $loop->iteration }}
                                   </td>
                                   <td>
                                        {{ $sell_line->product->name }}
                                        @if( $sell_line->product->type == 'variable') -
                                        {{ $sell_line->variations->product_variation->name}} -
                                        {{ $sell_line->variations->name}}
                                        @endif
                                   </td>
                                   <td>
                                        <span class="display_currency"
                                             data-currency_symbol="true">{{ $sell_line->unit_price_inc_tax }}</span>
                                   </td>
                                   <td>
                                        {{ $sell_line->formatted_qty }}
                                        {{$unit_name}}
                                   </td>

                                   <td>
                                        <input type="text" name="products[{{$loop->index}}][quantity]"
                                             value="{{@format_quantity($sell_line->quantity)}}"
                                             class="form-control input-sm input_number return_qty input_quantity"
                                             data-rule-abs_digit="{{$check_decimal}}"
                                             data-msg-abs_digit="@lang('lang_v1.decimal_value_not_allowed')"
                                             data-rule-max-value="{{$sell_line->quantity}}"
                                             data-msg-max-value="@lang('validation.custom-messages.quantity_not_available', ['qty' => $sell_line->formatted_qty, 'unit' => $unit_name ])">

                                        <input name="products[{{$loop->index}}][unit_price_inc_tax]" type="hidden"
                                             class="unit_price" value="{{@num_format($sell_line->unit_price_inc_tax)}}">
                                        <input name="products[{{$loop->index}}][sell_line_id]" type="hidden"
                                             value="{{$sell_line->id}}">
                                   </td>
                                   <td>
                                        <div class="return_subtotal"></div>
                                   </td>
                                   <td>
                                        <a class="btn btn-danger" onclick="removeRow(this)">
                                             <i class="fa fa-trash"></i>
                                        </a>
                                   </td>
                              </tr>
                              @endforeach


                         </tbody>
                    </table>
               </div>
          </div>
          <div class="row">
               @php
               $discount_type = !empty($sell->return_parent->discount_type) ? $sell->return_parent->discount_type :
               $sell->discount_type;
               $discount_amount = !empty($sell->return_parent->discount_amount) ?
               $sell->return_parent->discount_amount : $sell->discount_amount;
               @endphp
               <div class="col-sm-4">
                    <div class="form-group">
                         {!! Form::label('discount_type', __( 'purchase.discount_type' ) . ':') !!}
                         {!! Form::select('discount_type', [ '' => __('lang_v1.none'), 'fixed' => __(
                         'lang_v1.fixed' ), 'percentage' => __( 'lang_v1.percentage' )], $discount_type, ['class'
                         => 'form-control']); !!}
                    </div>
               </div>
               <div class="col-sm-4">
                    <div class="form-group">
                         {!! Form::label('discount_amount', __( 'purchase.discount_amount' ) . ':') !!}
                         {!! Form::text('discount_amount', @num_format($discount_amount), ['class' => 'form-control
                         input_number']); !!}
                    </div>
               </div>
          </div>
          @php
          $tax_percent = 0;
          if(!empty($sell->tax)){
          $tax_percent = $sell->tax->amount;
          }
          @endphp
          {!! Form::hidden('tax_id', $sell->tax_id); !!}
          {!! Form::hidden('tax_amount', 0, ['id' => 'tax_amount']); !!}
          {!! Form::hidden('tax_percent', $tax_percent, ['id' => 'tax_percent']); !!}
          <div class="row">
               <div class="col-sm-12 text-right">
                    <strong>@lang('lang_v1.total_return_discount'):</strong>
                    &nbsp;(-) <span id="total_return_discount"></span>
               </div>
               <div class="col-sm-12 text-right">
                    <strong>@lang('lang_v1.total_return_tax') - @if(!empty($sell->tax))({{$sell->tax->name}} -
                         {{$sell->tax->amount}}%)@endif : </strong>
                    &nbsp;(+) <span id="total_return_tax"></span>
               </div>
               <div class="col-sm-12 text-right">
                    <strong>@lang('lang_v1.return_total'): </strong>&nbsp;
                    <span id="net_return">0</span>
               </div>
          </div>
          <br>
          <div class="row">
               <div class="col-sm-6 pull-left">

                    @if($passValues['isAdjusted'])
                    <a href='{{url("pos/return/".$sell->id)}}' class="btn btn-danger pull-left">Re-Adjust Again
                         This</a>
                    @endif
               </div>
               <div class="col-sm-6 text-right">

                    <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>

               </div>
          </div>
          {!! Form::close() !!}
     </div>
</div>