<div class="modal-dialog modal-xl" role="document">
     <div class="modal-content">
          <div class="modal-header">
               <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                         aria-hidden="true">&times;</span></button>
               <h4 class="modal-title" id="modalTitle">{{$current_detail[0]->product_name}}</h4>
          </div>
          <div class="modal-body">
               <div class="row">
                    <div class="col-md-12">
                         <div class="nav-tabs-custom">
                              <ul class="nav nav-tabs" id="myTabs">
                                   <li class="active">
                                        <a href="#color_current" data-toggle="tab" aria-expanded="true">
                                             <i class="fa fa-list" aria-hidden="true"></i>
                                             Color Current Report
                                        </a>
                                   </li>
                                   <li>
                                        <a href="#color_history" data-toggle="tab" aria-expanded="true">
                                             <i class="fa fa-calendar" aria-hidden="true"></i>
                                             Color History Report

                                        </a>
                                   </li>
                              </ul>
                              <div class="tab-content">
                                   <div class="tab-pane active" id="color_current">
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
                                                                 <th>Size</th>
                                                                 <th>Quantity Sold</th>
                                                                 <th>Current Stock</th>
                                                            </tr>
                                                            @foreach($current_group as $item)
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
                                                                      {{ $item->size }}
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
                                                  <h3>Detailed Color History Report:</h3>
                                             </div>
                                             <div class="col-md-12">
                                                  <div class="table-responsive">
                                                       <table class="table table-condensed bg-gray">
                                                            <tr class="bg-info">
                                                                 <th>#</th>
                                                                 <th>Name</th>
                                                                 <th>Color</th>
                                                                 <th>Size</th>
                                                                 <th>Selling Date</th>
                                                                 <th>Quantity Sold</th>
                                                                 <th>Current Stock</th>
                                                            </tr>
                                                            @foreach($history_detail as $item)
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
                                                                      {{ $item->size }}
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
                                        <div class="row hidden">
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
                                                                 {{-- <th>Selling Date</th> --}}
                                                                 <th>Quantity Sold</th>
                                                                 <th>Current Stock</th>
                                                            </tr>
                                                            @foreach($current_detail as $item)
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
                                                                 {{-- <td>
                                                                      {{ $item->product_date }}
                                                                 </td> --}}
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
                                   <div class="tab-pane " id="color_history">
                                        <div class="row">
                                             <div class="col-md-12">
                                                  <h3>Groupped History Color Report:</h3>
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
                                                            @foreach($history_group as $item)
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
                                                  <h3>Detailed Color History Report:</h3>
                                             </div>
                                             <div class="col-md-12">
                                                  <div class="table-responsive">
                                                       <table class="table table-condensed bg-gray">
                                                            <tr class="bg-info">
                                                                 <th>#</th>
                                                                 <th>Name</th>
                                                                 <th>Color</th>
                                                                 <th>Size</th>
                                                                 <th>Selling Date</th>
                                                                 <th>Quantity Sold</th>
                                                                 <th>Current Stock</th>
                                                            </tr>
                                                            @foreach($history_detail as $item)
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
                                                                      {{ $item->size }}
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
                              </div>
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