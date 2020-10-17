<!-- Edit discount Modal -->  
<div class="modal fade" tabindex="-1" role="dialog" id="posEditDiscountModal">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">@lang('sale.edit_discount')</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-6">
				        <div class="form-group">
				            {!! Form::label('discount_type_modal', __('sale.discount_type') . ':*' ) !!}
				            <div class="input-group">
				                <span class="input-group-addon">
				                    <i class="fa fa-info"></i>
				                </span>
				                <select class="form-control valid" required id="discount_type_modal" name="discount_type_modal" aria-required="true" aria-invalid="false">
								 <optgroup>
									 <!-- <option value="">Please Select</option> -->
									 <!-- <option value="fixed" >Fixed</option> -->
									 <option value="fixed" selected>Unknown</option>
									 <option value="percentage" >Percentage</option>
								 </optgroup>
				                </select>
				            </div>
				        </div>
				    </div>

				    <div class="col-md-6">
				        <div class="form-group">
				            {!! Form::label('discount_amount_modal', __('sale.discount_amount') . ':*' ) !!}
				            <div class="input-group">
				                <span class="input-group-addon">
				                    <i class="fa fa-info"></i>
				                </span>
							 {{-- {!! Form::text('discount_amount_modal', '{{$sales_discount}}', ['class' => 'form-control input_number']); !!} --}}
							<input type="text" id="discount_amount_modal" class="form-control input_number" value="{{$sales_discount,0}}">
				            </div>
				        </div>
				    </div>

				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" id="posEditDiscountModalUpdate">@lang('messages.update')</button>
			    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal