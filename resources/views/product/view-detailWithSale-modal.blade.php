<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="modalTitle">{{ $product->name }}</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-sm-8">
                    <div class="col-sm-4 invoice-col">
                        <b>@lang('product.sku'):</b>
                        {{ $product->sku }}<br>
                        {{-- <b>@lang('product.brand'): </b>
						{{$product->brand->name or '--' }}<br> --}}
                        <b>Refference:</b>
                        {{ $product->refference }}<br>
                        <b>Supplier:</b>
                        {{ $product->supplier->name }}<br>
                        <b>Description:</b>
                        @if ($product->description == null)
                            {{ '-' }}
                        @else
                            {{ $product->description }}
                        @endif
                        <br>
                        {{-- <b>@lang('product.unit'): </b>
                              {{$product->unit->short_name or '--' }}<br>
                              <b>@lang('product.barcode_type'): </b>
                              {{$product->barcode_type or '--' }} --}}

                        {{-- @if (!empty($product->product_custom_field1))
                              <br />
                              <b>@lang('lang_v1.product_custom_field1'): </b>
                              {{$product->product_custom_field1 }}
                              @endif

                              @if (!empty($product->product_custom_field2))
                              <br />
                              <b>@lang('lang_v1.product_custom_field2'): </b>
                              {{$product->product_custom_field2 }}
                              @endif

                              @if (!empty($product->product_custom_field3))
                              <br />
                              <b>@lang('lang_v1.product_custom_field3'): </b>
                              {{$product->product_custom_field3 }}
                              @endif

                              @if (!empty($product->product_custom_field4))
                              <br />
                              <b>@lang('lang_v1.product_custom_field4'): </b>
                              {{$product->product_custom_field4 }}
                              @endif --}}
                        {{-- <hr /> --}}
                        {{-- <b>BULK CODE : </b>
                              {{$product->bulk_add or '---' }}<br>
                              <b>Supplier:</b>
                              {{$product->supplier->name or '---'  }}<br>
                              <b>Color: </b>
                              {{$product->color->name or '---' }}<br>
                              @if ($product->size)
                              <b>Size : </b>
                              {{$product->size->name or '---' }}<br>

                              @endif
                              <b>Sub Size : </b>
                              {{$product->sub_size()->first()->name or '---' }}<br> --}}

                    </div>

                    <div class="col-sm-4 invoice-col">
                        <b>@lang('product.category'): </b>
                        {{ $product->category->name }}<br>
                        <b>@lang('product.sub_category'): </b>
                        {{ $product->sub_category->name }}<br>
                        <b>First Date of register:</b>
                        {{ $product->created_at->format('Y/m/d') }}<br>
                        <b>Last Update:</b>
                        @foreach ($product->variations as $variation)
                            {{ $variation->updated_at->format('Y/m/d') }}
                        @endforeach
                        {{-- <b>@lang('product.manage_stock'): </b>
                              @if ($product->enable_stock)
                              @lang('messages.yes')
                              @else
                              @lang('messages.no')
                              @endif
                              <br>
                              @if ($product->enable_stock)
                              <b>@lang('product.alert_quantity'): </b>
                              {{$product->alert_quantity or '--' }}
                              @endif --}}
                    </div>

                    <div class="col-sm-4 invoice-col">
                        {{-- @php
                              $user = auth()->user();
                              $roles = $user->getRoleNames();
                              @endphp --}}
                        {{-- @if ($roles[0] == 'Admin#1') --}}
                        @can('view_purchase_price')
                            <b>Purchase Price:</b>
                            @foreach ($product->variations as $variation)
                                {{ $variation->default_purchase_price }}
                            @endforeach
                            <br>
                        @endcan
                        {{-- @endif --}}
                        @can('access_default_selling_price')
                            <b>Sell Price:</b>
                            @foreach ($product->variations as $variation)
                                {{ $variation->sell_price_inc_tax }}
                            @endforeach
                            <br>
                        @endcan
                        {{-- <b>@lang('product.expires_in'): </b>
                              @php
                              $expiry_array = ['months'=>__('product.months'), 'days'=>__('product.days'), ''
                              =>__('product.not_applicable') ];
                              @endphp
                              @if (!empty($product->expiry_period) && !empty($product->expiry_period_type))
                              {{$product->expiry_period}} {{$expiry_array[$product->expiry_period_type]}}
                              @else
                              {{$expiry_array['']}}
                              @endif
                              <br>
                              @if ($product->weight)
                              <b>@lang('lang_v1.weight'): </b>
                              {{$product->weight }}<br>
                              @endif --}}
                        {{-- <b>@lang('product.applicable_tax'): </b>
						{{$product->product_tax->name or __('lang_v1.none') }}<br> --}}
                        {{-- @php
                              $tax_type = ['inclusive' => __('product.inclusive'), 'exclusive' =>
                              __('product.exclusive')];
                              @endphp
                              <b>@lang('product.selling_price_tax_type'): </b>
                              {{$tax_type[$product->tax_type]  }}<br>
                              <b>@lang('product.product_type'): </b>
                              @lang('lang_v1.' . $product->type) --}}

                    </div>
                    <div class="clearfix"></div>
                    <br>
                    <div class="col-sm-12">
                        {!! $product->product_description !!}
                    </div>
                    {{-- new additoin  --}}

                    @if ($query)
                        <div class="row">
                            <div class="col-md-12">
                                <h4>Selling Details:</h4>
                            </div>
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-condensed bg-gray">
                                        <tr class="bg-info">
                                            <th>Image</th>
                                            <th>Refference</th>
                                            <th>Name</th>
                                            <th>Color</th>
                                            <th>Size</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Selling Date</th>
                                        </tr>
                                        @foreach ($query as $row)
                                            <tr>
                                                <td>
                                                    <img src=" {{ $row->image_url }}" alt="Product image"
                                                        class="product-thumbnail-small">
                                                </td>
                                                <td>
                                                    @if ($row->refference)
                                                        {{ $row->refference }}
                                                    @else
                                                        <b class="text-center">-</b>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $row->product_name }}
                                                </td>
                                                <td>
                                                    {{ $row->color }}
                                                </td>
                                                <td>
                                                    {{ $row->size }}
                                                </td>
                                                <td>
                                                    <span class="sell_qty" data-currency_symbol=false
                                                        data-orig-value=" {{ (int) $row->sell_qty }}"
                                                        data-unit="{{ $row->unit }} ">
                                                        {{ (int) $row->sell_qty }}
                                                    </span>
                                                    {{ $row->unit }}
                                                </td>
                                                <td>
                                                    <span class="display_currency row_subtotal"
                                                        data-currency_symbol=true
                                                        data-orig-value="{{ $row->subtotal }} ">
                                                        {{ $row->subtotal }}
                                                    </span>
                                                </td>
                                                <td>
                                                    {{ Carbon\Carbon::parse($row->transaction_date)->format('d-M-Y H:i') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($product->type == 'single')
                        @include('product.partials.single_product_details')
                    @else
                        @include('product.partials.variable_product_details')
                    @endif
                    {{-- end  --}}
                </div>
                <div class="col-sm-4 col-md-4 invoice-col">
                    <div class="thumbnail">
                        <img src="{{ $product->image_url }}" alt="Product image" height="430px" width="400px">
                    </div>
                </div>

            </div>
            @if ($rack_details->count())
                @if (session('business.enable_racks') || session('business.enable_row') || session('business.enable_position'))
                    <div class="row">
                        <div class="col-md-12">
                            <h4>@lang('lang_v1.rack_details'):</h4>
                        </div>
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-condensed bg-gray">
                                    <tr class="bg-green">
                                        <th>@lang('business.location')</th>
                                        @if (session('business.enable_racks'))
                                            <th>@lang('lang_v1.rack')</th>
                                        @endif
                                        @if (session('business.enable_row'))
                                            <th>@lang('lang_v1.row')</th>
                                        @endif
                                        @if (session('business.enable_position'))
                                            <th>@lang('lang_v1.position')</th>
                                        @endif
                                    </tr>
                                    @foreach ($rack_details as $rd)
                                        <tr>
                                            <td>{{ $rd->name }}</td>
                                            @if (session('business.enable_racks'))
                                                <td>{{ $rd->rack }}</td>
                                            @endif
                                            @if (session('business.enable_row'))
                                                <td>{{ $rd->row }}</td>
                                            @endif
                                            @if (session('business.enable_position'))
                                                <td>{{ $rd->position }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary no-print" aria-label="Print"
                onclick="$(this).closest('div.modal').printThis();">
                <i class="fa fa-print"></i> @lang('messages.print')
            </button>
            <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
