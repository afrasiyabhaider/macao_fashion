<div class="table-responsive">
    <table class="table table-bordered ajax_view table-striped dataTable" id="stock_report_table">
        <thead>
            <tr>
                <th>
                    #
                </th>
                <th>
                    <input type="checkbox" id="select-all-row">
                    All
                </th>
                <th>
                    Printing
                </th>
                <th>Image</th>
                <th>SKU</th>
                <th>@lang('business.product')</th>
                <th>Refference</th>
                <th>Location Name</th>
                <th>Actions</th>
                <th>@lang('sale.unit_price')</th>
                <th>Color</th>
                <th>Category</th>
                <th>Sub-Category</th>
                <th>Size</th>
                <th>Description</th>
                <th>Sale Percentage</th>
                <th>@lang('report.current_stock')</th>
                <th>Total Sold</th>
                <th>Total Transfered</th>
                <th>Supplier</th>
                <th>Transfered On</th>
                {{-- <th>@lang('lang_v1.total_unit_adjusted')</th> --}}
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
                <td colspan="16"><strong>@lang('sale.total'):</strong></td>
                <td id="footer_total_stock"></td>
                <td id="footer_total_sold"></td>
                <td id="footer_total_transfered"></td>
                <td id="footer_total_adjusted"></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>