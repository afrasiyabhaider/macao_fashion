<table class="table  ">
    <thead>
        <tr>
            {{-- <th>Total</th>
            <td class="display_currency">
                {{ $transactions->sum('final_total') }}
            </td> --}}
        </tr>
        <tr>
            <th>Customer Name#</th>
            <th>Article Name#</th>
            <th>barcode </th>
            <th>receipt number </th>
            <th>Date Time</th>
            <th>shop name</th>
            <th>size</th>
            <th>price </th>
            <th>discount price </th>
            <th>bonus point </th>
            <th>Mode</th>
            <th>Qty</th>
        </tr>
    </thead>
    @foreach ($transactions as $transaction)
        @foreach ($transaction->sell_lines as $sell_line)
            <tr class="cursor-pointer" data-toggle="tooltip" data-html="true">

                <td>
                    {{ $transaction->contact->name }}
                </td>
                <td>
                    {{-- @foreach ($transaction->sell_lines as $sell_line) --}}
                    {{ $sell_line->product->name }}
                    {{-- @endforeach --}}
                </td>
                <td>
                    {{-- @foreach ($transaction->sell_lines as $sell_line) --}}
                    {{ $sell_line->product->sku }}
                    {{-- @endforeach --}}
                </td>
                <td class="display_currency">
                    {{ $transaction->invoice_no }}
                </td>
                <td>
                    @if (Carbon\Carbon::parse($transaction->transaction_date)->format('d.m.Y') ==
                            Carbon\Carbon::create('now')->format('d.m.Y'))
                        {{ Carbon\Carbon::parse($transaction->transaction_date)->format('H:i s') }}
                    @else
                        {{ Carbon\Carbon::parse($transaction->transaction_date)->format('d.m.Y H:i:s') }}
                    @endif

                </td>
                <td>
                    {{ $transaction->location->name }}
                </td>
                <td class="display_currency">
                    {{-- @foreach ($transaction->sell_lines as $sell_line) --}}
                    @if ($sell_line->product->sub_size != null)
                        {{ $sell_line->product->sub_size->name }}
                    @endif
                    {{-- {{ $sell_line->product->sub_size }} --}}
                    {{-- @endforeach --}}
                </td>
                <td class="display_currency">
                    {{-- @foreach ($transaction->sell_lines as $sell_line) --}}
                    {{ $sell_line->unit_price_before_discount }}
                    {{-- @endforeach --}}
                </td>
                <td class="display_currency">
                    {{-- {{ $transaction->discount_amount }} --}}
                    {{-- @foreach ($transaction->sell_lines as $sell_line) --}}
                    {{ $sell_line->discounted_amount }}
                    {{-- @endforeach --}}
                </td>
                <td class="display_currency">
                    <?php
                    $dicount = $sell_line->unit_price_before_discount * 0.05;
                    $total_price = $dicount * 20;
                    ?>
                    {{-- @foreach ($transaction->sell_lines as $sell_line) --}}
                    {{ $sell_line->discounted_amount != 0.00 ? 0:$total_price.'('.$dicount.')'  }}
                    {{-- @endforeach --}}
                    {{-- @foreach ($transaction->payment_lines as $payment_line)
                    @if ($loop->first)
                        {{ $payment_line->bonus_points }}
                    @endif
                @endforeach --}}
                </td>

                <td>
                    {{ $transaction->cash_register_payments()->first()->pay_method }}
                </td>
                <td>
                    {{-- @php
                    $qty = App\TransactionSellLine::where('transaction_id', $transaction->id)->sum('quantity');
                @endphp
                {{ (int) $qty . ' Pcs' }} --}}
                    {{ $sell_line->quantity }}
                </td>

                {{-- <td>
                <a href="{{action('SellPosController@edit', [$transaction->id])}}">
                    <i class="fa fa-pencil text-muted" aria-hidden="true" title="{{__('lang_v1.click_to_edit')}}"></i>
                </a>
                
                <a href="{{url('/pos/transaction/'.$transaction->id.'/delete')}}" class="delete-sale" style="padding-left: 20px; padding-right: 20px"><i class="fa fa-trash text-danger" title="{{__('lang_v1.click_to_delete')}}"></i></a>

                <a href="{{action('SellPosController@printInvoice', [$transaction->id])}}" class="print-invoice-link">
                    <i class="fa fa-print text-muted" aria-hidden="true" title="{{__('lang_v1.click_to_print')}}"></i>
                </a>
            </td> --}}
            </tr>
        @endforeach
    @endforeach
</table>
