{{-- <div class="table-responsive">
    <table class="table table-bordered ajax_view table-striped dataTable" id="stock_in_table">
        <thead>
            <tr>
                <th>
                    #
                </th>
                <th>Image</th>
                <th>SKU</th>
                <th>@lang('business.product')</th>
                <th>Refference</th>
                <th>Location Name</th>
                <th>@lang('sale.unit_price')</th>
                <th>Color</th>
                <th>Category</th>
                <th>Sub-Category</th>
                <th>Size</th>
                <th>Transfered Added</th>
                <th>Update Qty</th>
                <th>Updated At</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
            
            </tr>
        </tfoot>
    </table>
</div> --}}


{{-- data with size and color --}}
{{-- <div class="table-responsive">
    <table class="table table-bordered ajax_view table-striped dataTable" id="stock_in_table">
        <thead>
            <tr>
                <th>
                    #
                </th>
                <th>Image</th>
                <th>SKU</th>
                <th>@lang('business.product')</th>
                <th>Refference</th>
                <th>Location Name</th>
                <th>@lang('sale.unit_price')</th>
                <th>Color</th>
                <th>Category</th>
                <th>Sub-Category</th>
                <th>Size</th>
                <th>@lang('report.current_stock')</th>
                <th>Supplier</th>
                <th>Updated At</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
                <td colspan="11"><strong>@lang('sale.total'):</strong></td>
                <td id="footer_total_stock"></td>
                <td id="footer_total_transfered"></td>
                <td id="footer_total_adjusted"></td>
            </tr>
        </tfoot>
    </table>
</div> --}}


<div class="table-responsive">
    <table class="table table-bordered ajax_view table-striped dataTable" id="stock_in_table" style="width: 100%;">
        <thead>
            <tr>
                <th>
                    #
                </th>
                <th>Image</th>
                <th>@lang('business.product')</th>
                <th>Color Detail</th>
                <th>Refference</th>
                <th>Location Name</th>
                {{-- <th>Actions</th> --}}
                <th>@lang('sale.unit_price')</th>
                {{-- <th>Category</th>
                   <th>Sub-Category</th> --}}
                <th>@lang('report.current_stock')</th>
                {{-- <th>Description</th> --}}
                {{-- <th>@lang('lang_v1.total_unit_adjusted')</th> --}}
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
                <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                <td></td>
                <td id="footer_group_total_stock"></td>
            </tr>
        </tfoot>
    </table>
</div>
