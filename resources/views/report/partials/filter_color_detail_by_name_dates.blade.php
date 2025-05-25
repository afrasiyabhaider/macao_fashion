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
        <input type="hidden" value="{{ $refference }}" class="refference">
        <div class="tab-content">
            <div class="tab-pane active" id="color_current">
                <h4 class="modal-title">From: {{ $from_date }} - To: {{ $to_date }}</h4>
                {{-- current group --}}
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
                                    <th>Quantity Sold <small>(Date Filter)</small> </th>
                                    <th>Today Sold</th>
                                    <th>7-D Sold</th>
                                    <th>15-D Sold</th>
                                    <th>All Time Sold</th>
                                    <th>Current Stock</th>
                                    <th>All Time Purchase</th>
                                    <th>Purchase Date</th>
                                    <th>Last Update Date</th>
                                </tr>
                                @foreach ($merged_summed_values as $key => $item)
                                    {{-- @foreach ($current_group_color as $item) --}}
                                    <tr>
                                        <td>
                                            {{ $loop->iteration }}
                                        </td>
                                        <td>
                                            {{ $item['product_name'] }}
                                            {{-- {{ $item->product_id }} --}}
                                        </td>
                                        <td>
                                            {{ $item['color'] }}
                                        </td>
                                        <td>
                                            {{ (int) $item['total_qty_sold'] }}
                                        </td>
                                        <td>

                                            {{ (int) $item['today_sold'] }}
                                        </td>
                                        <td>

                                            {{ (int) $item['seven_day_sold'] }}
                                        </td>
                                        <td>
                                            {{ (int) $item['fifteen_day_sold'] }}

                                        </td>
                                        <td>
                                            {{ (int) $item['all_time_sold'] }}
                                        </td>
                                        <td>
                                            {{ (int) $item['current_stock'] }}
                                        </td>
                                        <td>
                                            {{ (int) $item['all_time_sold'] + (int) $item['current_stock'] }}
                                        </td>


                                        <td>
                                            {{ $item['purchase_date'] }}
                                        </td>
                                        <td>
                                            {{ $item['last_update_date'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
                {{-- current group color --}}
                <div class="row">
                    <div class="col-md-12">
                        <h3>Groupped Color Size Report <small>(With Stock)</small>:</h3>
                    </div>
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-condensed bg-gray">
                                <tr class="bg-green">
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Color</th>
                                    <th>Size</th>
                                    <th>Quantity Sold <small>(Date Filter)</small></th>
                                    <th>Today Sold</th>
                                    <th>7-D Sold</th>
                                    <th>15-D Sold</th>
                                    <th>All Time Sold</th>
                                    <th>Current Stock</th>
                                    <th>
                                        All Time Purchase
                                    </th>
                                    <th>Purchase Date</th>
                                    <th>Last Update Date</th>
                                </tr>
                                @foreach ($current_group as $item)
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
                                            {{ (int) $item->total_qty_sold }}
                                        </td>
                                        <td>
                                            {{ (int) $item->today_sold }}
                                        </td>
                                        <td>
                                            {{ (int) $item->seven_day_sold }}
                                        </td>
                                        <td>
                                            {{ (int) $item->fifteen_day_sold }}
                                        </td>

                                        <td>
                                            {{ (int) $item->all_time_sold }}
                                        </td>
                                        <td>
                                            {{ (int) $item->current_stock }}
                                        </td>
                                        <td>
                                            {{ (int) $item->all_time_sold + (int) $item->current_stock }}
                                        </td>
                                        <td>
                                            {{ $item->purchase_date }}
                                        </td>
                                        <td>
                                            {{ $item->last_update_date }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
                <div class="row hidden">
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
                                @foreach ($history_detail as $item)
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
                                            {{ (int) $item->sell_qty }}
                                        </td>
                                        <td>
                                            {{ (int) $item->current_stock }}
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
                                    {{-- <th>Current Stock</th> --}}
                                </tr>
                                @foreach ($current_detail as $item)
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
                                            {{ (int) $item->sell_qty }}
                                        </td>
                                        {{-- <td>
                                              {{ (int)$item->current_stock }}
                                         </td> --}}
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane " id="color_history">
                <div class="row">
                    <h4 class="modal-title">From: {{ $from_date }} - To: {{ $to_date }}</h4>
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
                                @foreach ($history_group as $item)
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
                                            {{ (int) $item->total_qty_sold }}
                                        </td>
                                        <td>
                                            {{ (int) $item->current_stock }}
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
                                @foreach ($history_detail as $item)
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
                                            {{ (int) $item->sell_qty }}
                                        </td>
                                        <td>
                                            {{ (int) $item->current_stock }}
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
