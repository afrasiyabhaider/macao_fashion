{{-- @dd($transactions) --}}
@if(!empty($transactions))
{{-- class="cursor-pointer" 
data-toggle="tooltip"
data-html="true"
title="Customer: {{optional($transaction->contact)->name}} 
     @if(!empty($transaction->contact->mobile) && $transaction->contact->is_default == 0)
          <br/>Mobile: {{$transaction->contact->mobile}}
     @endif
"  --}}
	<table class="table no-print">
          <thead>
               <tr class="no-print">
                    <th class="no-print">
                         Sr#
                    </th>
                    <th class="no-print">
                         Transaction
                    </th>
                    <th class="no-print">
                         Amount
                    </th>
               </tr>
          </thead>
		@foreach ($transactions as $transaction)
			<tr class="no-print">
				<td class="no-print">
					{{ $loop->iteration}}.
				</td>
				<td class="no-print" >
                         <a data-href="{{action('SellController@show', [$transaction->id])}}" href="#" data-container=".view_modal" class="btn-modal">
                              {{ $transaction->invoice_no }} ({{optional($transaction->contact)->name}})
                         </a>
				</td>
				<td class="display_currency no-print">
                         {{ $transaction->final_total }}
                         <i class="fa fa-euro-sign"></i>
				</td>
			</tr>
		@endforeach
	</table>
@else
	<p  class="no-print">@lang('sale.no_recent_transactions')</p>
@endif