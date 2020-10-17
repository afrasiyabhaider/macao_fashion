@extends('layouts.app')
@section('title', __('lang_v1.sell_return'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
     <h1>@lang('lang_v1.sell_return') </h1>
</section>

<!-- Main content -->
<section class="content no-print">
     <div class="box box-solid">
          <div class="box-header">
               <h3 class="box-title">Reciept</h3>
          </div>
          <div class="box-body">
               <div class="col-sm-4">
                    <div class="form-group">
                         {!! Form::label('invoice_no', __('sale.invoice_no').':') !!}
                         {!! Form::text('invoice_no',null, ['class' =>
                         'form-control','autofocus'=>'true','maxlength'=>'9','placeholder'=>'Enter '. $invoice_scheme->total_digits.' Digit Invoice Number']); !!}
                    </div>
               </div>
               {{-- <div class="col-sm-3">
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
               </div> --}}
          </div>
     </div>
     
     <div id="reciept-data">
          {{-- Data will Append Here through Ajax --}}
     </div>
          

</section>
@stop
@section('javascript')
<script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/sell_return.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
     $(document).ready( function(){
		$('form#sell_return_form').validate();
          update_sell_return_total();
          $("#invoice_no").val('');
		//Date picker
	    // $('#transaction_date').datepicker({
	    //     autoclose: true,
	    //     format: datepicker_date_format
	    // });
	});
	$(document).on('change', 'input.return_qty, #discount_amount, #discount_type', function(){
		update_sell_return_total()
	});

	function update_sell_return_total(){
		var net_return = 0;
		$('table#sell_return_table tbody tr').each( function(){
			var quantity = __read_number($(this).find('input.return_qty'));
			var unit_price = __read_number($(this).find('input.unit_price'));
			var subtotal = quantity * unit_price;
			$(this).find('.return_subtotal').text(__currency_trans_from_en(subtotal, true));
			net_return += subtotal;
		});
		var discount = 0;
		if($('#discount_type').val() == 'fixed'){
			discount = __read_number($("#discount_amount"));
		} else if($('#discount_type').val() == 'percentage'){
			var discount_percent = __read_number($("#discount_amount"));
			discount = __calculate_amount('percentage', discount_percent, net_return);
		}
		discounted_net_return = net_return - discount;

		var tax_percent = $('input#tax_percent').val();
		var total_tax = __calculate_amount('percentage', tax_percent, discounted_net_return);
		var net_return_inc_tax = total_tax + discounted_net_return;

		$('input#tax_amount').val(total_tax);
		$('span#total_return_discount').text(__currency_trans_from_en(discount, true));
		$('span#total_return_tax').text(__currency_trans_from_en(total_tax, true));
		$('span#net_return').text(__currency_trans_from_en(net_return_inc_tax, true));
     }
     var number_of_digits = 0;
     $('#invoice_no').on('change',function () {
           if($("#invoice_no").val().length == '{!!$invoice_scheme->total_digits!!}'){
               var url = '{{url("/")}}';
               $.ajax({
                    method: 'GET',
                    url: url+'/sell-return/invoice/'+$("#invoice_no").val(),
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                             $("#reciept-data").html(res.html);
                             update_sell_return_total()
                         } else {
                              toastr.error('No Record Found');
                         }
                    },
               });
          }
     });
     function removeRow(row) {
          $(row).parent().parent().remove();
          update_sell_return_total()
     }
</script>
@endsection