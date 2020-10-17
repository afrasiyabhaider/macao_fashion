<div class="table-responsive">
    <table class="table table-bordered table-striped ajax_view table-text-center" id="product_table">
        <thead>
            <tr>
                <th><input type="checkbox" id="select-all-row"></th>
                <th>@lang('gift.name')</th>
                <th>@lang('gift.value')</th>
                <th>@lang('gift.barcode')</th>
                <th>@lang('gift.start_date')</th>
                <th>@lang('gift.expiry_date')</th>
                <th>@lang('gift.type')</th>
                <th>@lang('gift.details')</th>
                <th> </th>
                <th> </th>
                <th>@lang('gift.is_active')</th>
                <th>@lang('messages.action')</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td colspan="7">
                <div style="display: flex; width: 100%;">
                    @can('product.delete')
                        {!! Form::open(['url' => action('GiftCardController@massDestroy'), 'method' => 'post', 'id' => 'mass_delete_form' ]) !!}
                        {!! Form::hidden('selected_rows', null, ['id' => 'selected_rows']); !!}
                        {!! Form::submit(__('lang_v1.delete_selected'), array('class' => 'btn btn-xs btn-danger', 'id' => 'delete-selected')) !!}
                        {!! Form::close() !!}
                    @endcan
                    &nbsp;
                    {!! Form::open(['url' => action('GiftCardController@massDeactivate'), 'method' => 'post', 'id' => 'mass_deactivate_form' ]) !!}
                    {!! Form::hidden('selected_products', null, ['id' => 'selected_products']); !!}
                    {!! Form::submit(__('lang_v1.deactivate_selected'), array('class' => 'btn btn-xs btn-warning', 'id' => 'deactivate-selected')) !!}
                    {!! Form::close() !!} @show_tooltip(__('lang_v1.deactive_product_tooltip'))
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
</div>