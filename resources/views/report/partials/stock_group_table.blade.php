<div class="table-responsive">
     <table class="table table-bordered ajax_view table-striped dataTable" id="stock_in_out_grouped_report_table">
          <thead>
               <tr>
                    <th>
                         #
                    </th>
                    <th>Image</th>
                    <th>@lang('business.product')</th>
                    <th>Refference</th>
                    <th>Location Name</th>
                    <th>@lang('sale.unit_price')</th>
                    <th>Main Transfer</th>
                    <th>Sub Shop Transfer</th>
                    <th>Total Transfer</th>
                    <th>Update Qty</th>
                    <th>Stock In</th>
                    <th>Total Sale</th>
                     {{-- <th>Total Stock</th> --}}
                     <th>Total Sale Price</th>
                     <th>Discount Amount</th>
                     <th>Purchase Price</th>
                     <th>Total Amount Price</th>
               </tr>
          </thead>
          <tfoot>
               <tr class="bg-gray font-17 text-center footer-total">
                    <td colspan="3"><strong>@lang('sale.total'):</strong></td> 
                     <td class="total_refference"></td>
                     <td></td>
                     <td></td>
                     <td></td>
                     <td></td>
                     <td></td>
                     <td></td>
                     <td class="total_stock"></td>
                     <td class="total_stock_out"></td>
                     {{-- <td class="current_stock"></td> --}}
                     <td class="total_sell_price"></td>
                     <td class="discount_amount1"></td>
                     <td class=""></td>
                     <td class="total_buying_amount"></td>
                    
               </tr>
          </tfoot>
     </table>
 </div>