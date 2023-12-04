<div class="table-responsive">
    <table class="table table-bordered ajax_view table-striped dataTable" id="stock_in_out_grouped_report_table">
         <thead>
              <tr>
                   <th>
                        #
                   </th>
                   <th>Image</th>
                   <th>@lang('business.product')</th>
                   {{-- <th>Color Detail</th> --}}
                   <th>Refference</th>
                   <th>Location Name</th>
                   {{-- <th>Actions</th> --}}
                   <th>@lang('sale.unit_price')</th>
                   {{-- <th>Category</th>
                   <th>Sub-Category</th> --}}
                   {{-- <th>Qty</th> --}}
                   <th>Stock In</th>
                   <th>Total Sale</th>
                    <th>Total Stock</th>
                    <th>Total Sale Price</th>
                    <th>Purchase Price</th>
                    <th>Total Amount Price</th>
                   {{--<th>Transfered Added</th> --}}
                   {{-- <th>Description</th> --}}
                   {{-- <th>@lang('lang_v1.total_unit_adjusted')</th> --}}
              </tr>
         </thead>
         <tfoot>
              <tr class="bg-gray font-17 text-center footer-total">
                   <td colspan="3"><strong>@lang('sale.total'):</strong></td> 
                    <td class="total_refference"></td>
                    <td></td>
                    <td></td>
                    {{-- <td class="total_qty"></td> --}}
                    <td class="total_stock"></td>
                    <td class="total_stock_out"></td>
                    <td class="current_stock"></td>
                    <td class="total_sell_price"></td>
                    <td class=""></td>
                    <td class="total_buying_amount"></td>
                   
              </tr>
         </tfoot>
    </table>
</div>