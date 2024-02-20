{{-- old table --}}
{{-- <div class="table-responsive">
    <table class="table table-bordered ajax_view table-striped dataTable" id="stock_out_table">
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
                <th>Total Sold</th>
                <th>Total Transfered</th>
                <th>Updated At</th>
            </tr>
        </thead>
        <tfoot>
        </tfoot>
    </table>
</div> --}}

<div class="table-responsive">
    <table class="table table-bordered ajax_view table-striped dataTable" id="stock_out_table" style="width: 100%;">
        <thead>
            <tr>
                <th>Image</th>
                <th>@lang('sale.product')</th>
                <th>Ref</th>
                <th>
                    U Sold <small style="color: grey;font-weight:400">Filter</small>
                </th>
                {{-- <th>@lang('report.current_stock')</th> --}}
                <th>Unit Price</th>
                {{-- <th>Color</th>
                <th>Category</th>
                <th>Sub-Category</th>
                <th>Size</th> --}}
                <th>Total</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-10 footer-total text-center">
                <td colspan="3"><strong>@lang('sale.total'):</strong></td>
                <td id="footer_total_grouped_sold"></td>
                {{-- <td></td>
                <td></td>
                <td></td>
                <td></td> --}}
                <td></td>
                {{-- <td></td>
                    
                    <td>
                    </td> --}}
                <td>
                    <span class="display_currency" id="footer_grouped_subtotal" data-currency_symbol="true"></span>
                </td>
            </tr>
        </tfoot>
    </table>
</div>
