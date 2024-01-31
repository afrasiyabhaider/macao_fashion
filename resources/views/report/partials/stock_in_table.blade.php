<div class="table-responsive">
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
                {{-- <td colspan="8"><strong>@lang('sale.total'):</strong></td> --}}
                {{-- <td id="footer_total_stock"></td>
                <td id="footer_total_sold"></td>
                <td id="footer_total_transfered"></td>
                <td id="footer_total_adjusted"></td>
                <td></td> --}}
            </tr>
        </tfoot>
    </table>
</div>