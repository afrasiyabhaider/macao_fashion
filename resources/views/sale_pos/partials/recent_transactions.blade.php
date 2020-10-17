@if(!empty($transactions))

{{-- @dd($transactions->first()->cash_register_payments()->first()) --}}
	<table class="table table-slim no-border">
		<thead>
			<tr>
				<th>Total</th>
				<td class="display_currency">
					{{$transactions->sum('final_total')}}
				</td>
			</tr>
			<tr>
				<th>
					Receipt#
				</th>
				<th>
					Amount
				</th>
				<th>
					Date Time
				</th>
				<th>
					Mode
				</th>
				<th>
					Qty
				</th>
				{{-- <th>
					Operator
				</th>
				<th>
					Discount
				</th> --}}
				<th>
					Actions
				</th>
			</tr>
		</thead>
		@foreach ($transactions as $transaction)
			<tr class="cursor-pointer" 
	    		data-toggle="tooltip"
	    		data-html="true"
	    		title="Customer: {{optional($transaction->contact)->name}} 
		    		@if(!empty($transaction->contact->mobile) && $transaction->contact->is_default == 0)
		    			<br/>Mobile: {{$transaction->contact->mobile}}
		    		@endif
	    		" >
				{{-- <td>
					{{ $loop->iteration}}.
				</td> --}}
				<td>
					{{ $transaction->invoice_no }}
					 {{-- ({{optional($transaction->contact)->name}}) --}}
				</td>
				<td class="display_currency">
					{{ $transaction->final_total }}
				</td>
				<td>
					@if (Carbon\Carbon::parse($transaction->transaction_date)->format('d.m.Y') == Carbon\Carbon::create('now')->format('d.m.Y'))
						{{
							Carbon\Carbon::parse($transaction->transaction_date)->format('H:i s')
						}}
					@else
						{{
							Carbon\Carbon::parse($transaction->transaction_date)->format('d.m.Y H:i:s')
						}}
					@endif
					
				</td>
				<td>
					{{
						$transaction->cash_register_payments()->first()->pay_method
					}}
				</td>
				<td>
					@php
					    $qty = App\TransactionSellLine::where('transaction_id',$transaction->id)->sum('quantity')
					@endphp
					{{
						(int)$qty.' Pcs'

					}}
				</td>
				{{-- <td>
					{{
						$transaction->cash_register_payments()->first()->cash_register()->first()->user()->first()->username
					}}
				</td>
				<td class="display_currency">
					{{
						$transaction->discount_amount
					}}
				</td> --}}
				<td>
					<a href="{{action('SellPosController@edit', [$transaction->id])}}">
	    				<i class="fa fa-pencil text-muted" aria-hidden="true" title="{{__('lang_v1.click_to_edit')}}"></i>
	    			</a>
	    			
	    			<a href="{{url('/pos/transaction/'.$transaction->id.'/delete')}}" class="delete-sale" style="padding-left: 20px; padding-right: 20px"><i class="fa fa-trash text-danger" title="{{__('lang_v1.click_to_delete')}}"></i></a>
	    			{{-- <a href="{{action('SellPosController@delete_transaction', $transaction->id)}}" class="delete-sale" style="padding-left: 20px; padding-right: 20px"><i class="fa fa-trash text-danger" title="{{__('lang_v1.click_to_delete')}}"></i></a> --}}

	    			<a href="{{action('SellPosController@printInvoice', [$transaction->id])}}" class="print-invoice-link">
	    				<i class="fa fa-print text-muted" aria-hidden="true" title="{{__('lang_v1.click_to_print')}}"></i>
	    			</a>
				</td>
			</tr>
		@endforeach
	</table>
@else
	<p>@lang('sale.no_recent_transactions')</p>
@endif