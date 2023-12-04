<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        @php
            $form_id = 'contact_add_form';
            if (isset($quick_add)) {
                $form_id = 'quick_add_contact';
            }
        @endphp
        {!! Form::open(['url' => action('ContactController@store'), 'method' => 'post', 'id' => $form_id]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('contact.add_contact')</h4>
        </div>

        <div class="modal-body">
            <div class="row">


                <div class="col-md-6 contact_type_div">
                    <div class="form-group">
                        {!! Form::label('type', __('contact.contact_type') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            {!! Form::select('type', $types, null, [
                                'class' => 'form-control',
                                'id' => 'contact_type',
                                'placeholder' => __('messages.please_select'),
                                'required',
                            ]) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">

                    <div class="form-group">
                        {!! Form::label('name', 'First Name' . ':*') !!}
                        {{-- {!! Form::label('name', __('contact.name') . ':*') !!} --}}
                        <div class="input-group">
                          <span class="input-group-addon">
                            <i class="fa fa-user"></i>
                        </span>
                            {!! Form::text('first_name', null, ['class' => 'form-control', 'placeholder' => 'First Name', 'required']) !!}

                        </div>
                    </div>
                </div>
                <div class="col-md-6">

                    <div class="form-group">
                        {!! Form::label('name', 'Last Name' . ':*') !!}
                        <div class="input-group">
                          <span class="input-group-addon">
                            <i class="fa fa-user"></i>
                        </span>

                            {!! Form::text('last_name', null, ['class' => 'form-control', 'placeholder' => 'Last Name', 'required']) !!}
                        </div>
                    </div>
                </div>
                {{-- <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('name', 'Discount' . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            {!! Form::number(
                                'discount',
                                isset($objBusiness['default_user_profit']) ? $objBusiness['default_user_profit'] : 0,
                                ['class' => 'form-control', 'placeholder' => 'Discount %', 'required'],
                            ) !!}
                        </div>
                    </div>
                </div> --}}
                <div class="col-md-6">
                    <div class="form-group">
                        {{-- {!! Form::label('name', 'Bonus Points' . ':*') !!} --}}
                        <div class="input-group">
                            {{-- <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span> --}}
                            {!! Form::hidden('bonus_points', 0, ['class' => 'form-control', 'value'=> 0 , 'placeholder' => 'Bonus Points ', 'required']) !!}
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-md-4 supplier_fields">
                    <div class="form-group">
                        {!! Form::label('supplier_business_name', __('business.business_name') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-briefcase"></i>
                            </span>
                            {!! Form::text('supplier_business_name', null, [
                                'class' => 'form-control',
                                'required',
                                'placeholder' => __('business.business_name'),
                            ]) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('mobile', 'Phone Number' . ':*') !!}
                        {{-- {!! Form::label('contact_id', 'Loyality Barcode' . ':') !!} --}}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-id-badge"></i>
                            </span>
                            {!! Form::text('mobile', null, ['class' => 'form-control', 'required', 'placeholder' => 'Phone Number']) !!}
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('email', __('business.email') . ':') !!}
                        <small>
                            (Password: 12345678)
                        </small>
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-envelope"></i>
                            </span>
                            {!! Form::email('email', null, ['class' => 'form-control', 'placeholder' => __('business.email')]) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('city', __('business.city') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-map-marker"></i>
                            </span>
                            {!! Form::text('city', null, ['class' => 'form-control', 'placeholder' => __('business.city')]) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('state','Post Code' . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-map-marker"></i>
                            </span>
                            {!! Form::text('post_code', null, ['class' => 'form-control', 'placeholder' => 'Post Code']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('landmark', 'Address' . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-map-marker"></i>
                            </span>
                            {!! Form::text('landmark', null, ['class' => 'form-control', 'placeholder' => 'Address']) !!}
                        </div>
                    </div>
                </div>
                
                <div class="clearfix"></div>

            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
