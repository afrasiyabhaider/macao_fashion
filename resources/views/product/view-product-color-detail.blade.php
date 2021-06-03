<div class="modal-dialog modal-xl" role="document">
     <div class="modal-content">
          <div class="modal-header">
               <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                         aria-hidden="true">&times;</span></button>
               <h4 class="modal-title" id="modalTitle">{{$query[0]->product_name}}</h4>
          </div>
          <div class="modal-body">
               <div class="row">
                    <div class="col-md-12">
                         <h3>Groupped Color Report:</h3>
                    </div>
                    <div class="col-md-12">
                         <div class="table-responsive">
                              <table class="table table-condensed bg-gray">
                                   <tr class="bg-green">
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Color</th>
                                        <th>Quantity Sold</th>
                                        <th>Current Stock</th>
                                   </tr>
                                   @foreach($group_query as $item)
                                        <tr>
                                             <td>
                                                  {{ $loop->iteration }}
                                             </td>
                                             <td>
                                                  {{ $item->product_name }}
                                             </td>
                                             <td>
                                                  {{ $item->color }}
                                             </td>
                                             <td>
                                                  {{ (int)$item->total_qty_sold }}
                                             </td>
                                             <td>
                                                  {{ (int)$item->current_stock }}
                                             </td>
                                        </tr>
                                   @endforeach
                              </table>
                         </div>
                    </div>
               </div>
               <div class="row">
                    <div class="col-md-12">
                         <h3>Detailed Color Report:</h3>
                    </div>
                    <div class="col-md-12">
                         <div class="table-responsive">
                              <table class="table table-condensed bg-gray">
                                   <tr class="bg-info">
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Color</th>
                                        <th>Selling Date</th>
                                        <th>Quantity Sold</th>
                                        <th>Current Stock</th>
                                   </tr>
                                   @foreach($query as $item)
                                        <tr>
                                             <td>
                                                  {{ $loop->iteration }}
                                             </td>
                                             <td>
                                                  {{ $item->product_name }}
                                             </td>
                                             <td>
                                                  {{ $item->color }}
                                             </td>
                                             <td>
                                                  {{ $item->transaction_date }}
                                             </td>
                                             <td>
                                                  {{ (int)$item->sell_qty }}
                                             </td>
                                             <td>
                                                  {{ (int)$item->current_stock }}
                                             </td>
                                        </tr>
                                   @endforeach
                              </table>
                         </div>
                    </div>
               </div>
          </div>
          <div class="modal-footer">
               <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close'
                    )</button>
          </div>
     </div>
</div>