@extends('layouts.app')
@section('title', __('contact.view_contact'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
</section>

<!-- Main content -->
<section class="content no-print">
    <!-- list purchases -->
    <div class="box">
        <div class="box-header">
            <h3 class="box-title">
                <i class="fa fa-money margin-r-5"></i>
                @lang( 'contact.all_bonus_linked_to_this_contact')
            </h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-sm-12">
                    <div style="display: flex;">
                        <div class="form-group " style="align-self:center;">
                            <div class="input-group">
                                <button type="button" class="btn btn-primary" id="daterange-btn">
                                <span>
                                    <i class="fa fa-calendar"></i> {{ __("messages.filter_by_date") }}
                                </span>
                                <i class="fa fa-caret-down"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group" style="    margin: 0 30px;">
                            <div class="input-group">
                                    <div class="form-group">
                                        {!! Form::label('contact_id', __( 'lang_v1.customer_group_name' ) . ':') !!}
                                        {!! Form::select('contact_id', $contacts, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'contact_id']); !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <table class="table table-bordered table-striped ajax_view" id="bonus_point_table">
                        <thead>
                            <tr>
                                <th>Contact</th>
                                <th>Point</th>
                                <th>Type</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

</section>
@stop
@section('javascript')
<script type="text/javascript">
$(document).ready( function(){
    //Purchase table
    let contact_id = ''
    bonus_point_table = $('#bonus_point_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[0, 'desc']],
        ajax: '/bonus-points?contact_id='+contact_id,
        columnDefs: [ {
            "targets": 2,
            "orderable": false,
            "searchable": false
        } ],
        columns: [
            { data: 'contact.name', name: 'contact'},
            { data: 'points', name: 'point'},
            { data: 'transaction_type', name: 'type'},
            { data: 'created_at', name: 'created_at'},
            // { data: 'action', name: 'action'}
        ],
        // "fnDrawCallback": function (oSettings) {
        //     __currency_convert_recursively($('#bonus_point_table'));
        // },
        // createdRow: function( row, data, dataIndex ) {
        //     $( row ).find('td:eq(4)').attr('class', 'clickable_td');
        // }
    });
    //Date range as a button
    $('#daterange-btn').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#daterange-btn span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
            bonus_point_table.ajax.url( '/bonus-points?contact_id='+contact_id+'&start_date=' + start.format('YYYY-MM-DD') +
                '&end_date=' + end.format('YYYY-MM-DD') ).load();
        }
    );
    $('#daterange-btn').on('cancel.daterangepicker', function(ev, picker) {
        bonus_point_table.ajax.url( '/bonus-points?contact_id='+ contact_id).load();
        $('#daterange-btn span').html('<i class="fa fa-calendar"></i> {{ __("messages.filter_by_date") }}');
    });


    $('#contact_id').on('change',function(){
        contact_id = $(this).val();
        bonus_point_table.ajax.url( '/bonus-points?contact_id='+ contact_id).load();
    });
});

</script>

@endsection
