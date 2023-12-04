<div class="table-responsive">
     <table class="table table-bordered ajax_view table-striped dataTable" id="grouped_color_detail_report_table">
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
                    <th>Description</th>
                    {{-- <th>@lang('lang_v1.total_unit_adjusted')</th> --}}
               </tr>
          </thead>
          <tfoot>
               <tr class="bg-gray font-17 text-center footer-total">
                    <td colspan="7"><strong>@lang('sale.total'):</strong></td>
                    <td id="footer_group_total_stock"></td>
                    <td></td>
               </tr>
          </tfoot>
     </table>
</div>