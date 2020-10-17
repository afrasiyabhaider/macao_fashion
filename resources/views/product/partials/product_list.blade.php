@inject('request', 'Illuminate\Http\Request')
<div class="row"  style="margin-bottom: 20px;">
    <div class="col-md-3">
        <div class="@if((url()->current() == url('products')) ||(url()->current() == url('website/product/list')) ) hidden @endif"">
            {!! Form::open(['url' => action('ProductController@massTransfer'), 'method' => 'post', 'id' => 'bulkTransfer_form','class' => 'ml-5' ]) !!}
            {!! Form::hidden('selected_products_bulkTransfer', null, ['id' => 'selected_products_bulkTransfer']); !!}
            {!! Form::hidden('selected_products_qty_bulkTransfer', null, ['id' => 'selected_products_qty_bulkTransfer']); !!}
            {!! Form::hidden('bussiness_bulkTransfer', null, ['id' => 'bussiness_bulkTransfer']); !!}
            {{-- {!! Form::submit(' Transfer Selected', array('class' => 'btn btn-warning', 'id' => 'bulkTransfer-selected')) !!} --}}
            <button type="submit" class="btn btn-warning" id="bulkTransfer-selected">
                <i class="fa fa-random"></i>
                Transfer Selected
            </button>
            {!! Form::close() !!}
        </div>
    </div>
    <div class="col-md-6"></div>
    <div class="col-md-3 align-right">
        @can('product.create')
            <a class="btn btn-primary pull-left" href="{{url('products/bulk_add')}}">
                <i class="fa fa-plus"></i> 
                @lang('messages.add')
            </a>
        @endcan
        {!! Form::open(['url' => action('ProductController@massBulkPrint'), 'method' => 'post', 'id' => 'bulkPrint_form' ]) !!}
                {!! Form::hidden('selected_products_bulkPrint', null, ['id' => 'selected_products_bulkPrint']); !!}
                {!! Form::hidden('selected_products_bulkPrint_qty', null, ['id' => 'selected_products_bulkPrint_qty']); !!}
                {!! Form::hidden('printing_location_id', 1, ['id' => 'printing_location_id']); !!}
                <button type="submit" class="btn btn-success pull-left" id="bulkPrint-selected" style="margin-left: 20px">
                    <i class="fa fa-print"></i> 
                    Print Selected
                </button>
                {{-- {!! Form::submit('Print Selected', array('class' => 'btn btn-md btn-warning', 'id' => 'bulkPrint-selected')) !!} --}}
        {!! Form::close() !!}
        {{-- <th>
            <input type="checkbox" id="select-all-row">
            Select All
        </th> --}}
    </div>
</div>
<div class="table-responsive">
    {{-- @dd($request->segment(2)) --}}
    <table class="table table-bordered table-striped ajax_view table-text-center" id="product_table">
        <thead>
            <tr>
                <th>#</th>
                <th>
                    <input type="checkbox" id="select-all-row">
                    Select All
                </th>
                @if ($request->segment(1) != 'products' || $request->segment(2) != null)
                    
                    <th>
                        Printing Qty
                    </th>
                @endif
                <th>Image</th>
                <th>@lang('sale.product')</th>
                <th>@lang('messages.action')</th>
                <th>Refference</th>
                <th>@lang('product.sku')</th>
                <th>Purchase Price</th>
                <th>@lang('lang_v1.selling_price')</th>
                <th>@lang('product.color')</th>
                <th>@lang('product.size')</th>
                <th>@lang('report.current_stock')</th>
                <th>@lang('product.product_type')</th>
                <th>Suppliers</th>
                <th>@lang('product.category')</th>
                <th>@lang('product.sub_category')</th>
                <th>Date</th>
                <th>BulkCode</th>
                <th>Description</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td colspan="12">
                <div style="display: flex; width: 100%;">
                    @can('product.delete')
                        {!! Form::open(['url' => action('ProductController@massDestroy'), 'method' => 'post', 'id' => 'mass_delete_form' ]) !!}
                        {!! Form::hidden('selected_rows', null, ['id' => 'selected_rows']); !!}
                        {!! Form::submit(__('lang_v1.delete_selected'), array('class' => 'btn btn-xs btn-danger', 'id' => 'delete-selected')) !!}
                        {!! Form::close() !!}
                    @endcan
                    &nbsp;
                    {!! Form::open(['url' => action('ProductController@massDeactivate'), 'method' => 'post', 'id' => 'mass_deactivate_form' ]) !!}
                    {!! Form::hidden('selected_products', null, ['id' => 'selected_products']); !!}
                    {!! Form::submit(__('lang_v1.deactivate_selected'), array('class' => 'btn btn-xs btn-warning', 'id' => 'deactivate-selected')) !!}
                    {!! Form::close() !!} @show_tooltip(__('lang_v1.deactive_product_tooltip'))
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
</div>