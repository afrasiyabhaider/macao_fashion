$(document).ready(function () {
    //Prevent enter key function except texarea
    $('form').on('keyup keypress', function (e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13 && e.target.tagName != 'TEXTAREA') {
            e.preventDefault();
            return false;
        }
    });

    //For edit pos form
    if ($('form#edit_pos_sell_form').length > 0) {
        pos_total_row();
        pos_form_obj = $('form#edit_pos_sell_form');
    } else {
        pos_form_obj = $('form#add_pos_sell_form');
    }
    if ($('form#edit_pos_sell_form').length > 0 || $('form#add_pos_sell_form').length > 0) {
        initialize_printer();
    }

    $('select#select_location_id').change(function () {
        // get_recent_transactions('final', $('div#tab_final'),$(this).val());
        // console.log($(this).val())
        reset_pos_form();
    });

    //get customer
    $('#customer_id').select2({
        ajax: {
            url: '/contacts/customers',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                };
            },
            processResults: function (data) {
                return {
                    results: data,
                };
            },
        },
        templateResult: function (data) {
            // return data.text + '<br>' + LANG.mobile + ': ' + data.mobile;

            console.log(data);
            return data.text + ' BP: ' + data.bonus_points + '<br>' + LANG.mobile + ': ' + data.mobile + '<br>' + 'Address' + ': ' + data.landmark + '<br>' + 'Last Vist: ' + data.updated_at;
        },
        minimumInputLength: 1,
        language: {
            noResults: function () {
                var name = $('#customer_id').data('select2').dropdown.$search.val();
                return (
                    '<button type="button" data-name="' +
                    name +
                    '" class="btn btn-link add_new_customer"><i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i>&nbsp; ' +
                    __translate('add_name_as_new_customer', { name: name }) +
                    '</button>'
                );
            },
        },
        escapeMarkup: function (markup) {
            return markup;
        },
    });
    $('#customer_id').on('select2:select', function (e) {
        var data = e.params.data;
        if (data.pay_term_number) {
            $('input#pay_term_number').val(data.pay_term_number);
        } else {
            $('input#pay_term_number').val('');
        }

        if (data.pay_term_type) {
            $('#pay_term_type').val(data.pay_term_type);
        } else {
            $('#pay_term_type').val('');
        }
    });

    set_default_customer();

    //Add Product
    $('#search_product')
        .autocomplete({
            source: function (request, response) {
                var price_group = '';
                if ($('#price_group').length > 0) {
                    price_group = $('#price_group').val();
                }
                $.getJSON(
                    '/products/list', {
                    price_group: price_group,
                    location_id: $('input#location_id').val(),
                    term: request.term,
                },
                    response
                );
            },
            minLength: 2,
            response: function (event, ui) {
                if (ui.content.length == 1) {
                    ui.item = ui.content[0];
                    // if (ui.item.qty_available > 0) {
                    if (ui.item) {
                        $(this)
                            .data('ui-autocomplete')
                            ._trigger('select', 'autocompleteselect', ui);
                        $(this).autocomplete('close');
                    }
                } else if (ui.content.length == 0) {
                    toastr.error(LANG.no_products_found);
                    $('input#search_product').select();
                }
            },
            focus: function (event, ui) {
                // if (ui.item.qty_available <= 0) {
                //     return false;
                // }
            },
            select: function (event, ui) {
                // if (ui.item.enable_stock != 1 || ui.item.qty_available > 0) {
                $(this).val(null);
                pos_product_row(ui.item.variation_id);
                // } else {
                //     alert(LANG.out_of_stock);
                // }
            },
        })
        .autocomplete('instance')._renderItem = function (ul, item) {
            // if (item.enable_stock == 1 && item.qty_available <= 0) {
            //     var string = '<li class="ui-state-disabled">' + item.name;
            //     if (item.type == 'variable') {
            //         string += '-' + item.variation;
            //     }
            //     var selling_price = item.selling_price;
            //     if (item.variation_group_price) {
            //         selling_price = item.variation_group_price;
            //     }
            //     string +=
            //         ' (' +
            //         item.sub_sku +
            //         ')' +
            //         '<br> Price: ' +
            //         selling_price +
            //         ' (Out of stock) </li>';
            //     return $(string).appendTo(ul);
            // } else {
            var string = '<div>' + item.name;
            if (item.type == 'variable') {
                string += '-' + item.variation;
            }

            var selling_price = item.selling_price;
            if (item.variation_group_price) {
                selling_price = item.variation_group_price;
            }

            string += ' (' + item.sub_sku + ')' + '<br> Price: ' + selling_price;
            if (item.enable_stock == 1) {
                var qty_available = __currency_trans_from_en(
                    item.qty_available,
                    false,
                    false,
                    __currency_precision,
                    true
                );
                string += ' - ' + qty_available + item.unit;
            }
            string += '</div>';

            return $('<li>').append(string).appendTo(ul);
            // }
        };

    //Update line total and check for quantity not greater than max quantity
    $('table#pos_table tbody').on('change', 'input.pos_quantity', function () {
        if (sell_form_validator) {
            sell_form_validator.element($(this));
        }
        if (pos_form_validator) {
            pos_form_validator.element($(this));
        }
        // var max_qty = parseFloat($(this).data('rule-max'));
        var entered_qty = __read_number($(this));

        var tr = $(this).parents('tr');

        var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));
        var line_total = entered_qty * unit_price_inc_tax;

        __write_number(tr.find('input.pos_line_total'), line_total, false, 2);
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));

        pos_total_row();
    });

    //If change in unit price update price including tax and line total
    $('table#pos_table tbody').on('change', 'input.pos_unit_price', function () {
        var unit_price = $(this).val();
        // var unit_price = __read_number($(this));

        var tr = $(this).parents('tr');
        var rowTc = tr.find('input.rowTc').val();
        $('#row_unit_price' + rowTc).html(__number_f(unit_price) + ' €');
        // $('#row_unit_price' + rowTc).html(__number_f(unit_price) + ' €');
        // $('#row_unit_price' + rowTc).html(unit_price);
        //calculate discounted unit price

        var discounted_unit_price = calculate_discounted_unit_price(tr);

        var tax_rate = tr.find('select.tax_id').find(':selected').data('rate');
        // var quantity = tr.find('input.pos_quantity').val();
        var quantity = __read_number(tr.find('input.pos_quantity'));

        var unit_price_inc_tax = __add_percent(discounted_unit_price, tax_rate);
        var line_total = quantity * unit_price_inc_tax;

        // alert(__number_f(line_total));
        __write_number(tr.find('input.pos_unit_price_inc_tax'), unit_price_inc_tax);
        __write_number(tr.find('input.pos_line_total'), line_total);
        // __write_number(tr.find('input.pos_line_total'), __currency_trans_from_en(line_total, true));

        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));
        pos_each_row(tr);
        // setInterval(pos_total_row, 1000);
        pos_total_row();
        round_row_to_iraqi_dinnar(tr);
    });

    //If change in tax rate then update unit price according to it.
    $('table#pos_table tbody').on('change', 'select.tax_id', function () {
        var tr = $(this).parents('tr');

        var tax_rate = tr.find('select.tax_id').find(':selected').data('rate');
        var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));

        var discounted_unit_price = __get_principle(unit_price_inc_tax, tax_rate);
        var unit_price = get_unit_price_from_discounted_unit_price(tr, discounted_unit_price);
        __write_number(tr.find('input.pos_unit_price'), unit_price);
        pos_each_row(tr);
    });

    //If change in unit price including tax, update unit price
    $('table#pos_table tbody').on('change', 'input.pos_unit_price_inc_tax', function () {
        var unit_price_inc_tax = __read_number($(this));

        if (iraqi_selling_price_adjustment) {
            unit_price_inc_tax = round_to_iraqi_dinnar(unit_price_inc_tax);
            __write_number($(this), unit_price_inc_tax);
        }

        var tr = $(this).parents('tr');

        var tax_rate = tr.find('select.tax_id').find(':selected').data('rate');
        var quantity = __read_number(tr.find('input.pos_quantity'));

        var line_total = quantity * unit_price_inc_tax;
        var discounted_unit_price = __get_principle(unit_price_inc_tax, tax_rate);
        var unit_price = get_unit_price_from_discounted_unit_price(tr, discounted_unit_price);

        __write_number(tr.find('input.pos_unit_price'), unit_price);
        __write_number(tr.find('input.pos_line_total'), line_total, false, 2);
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));

        pos_each_row(tr);
        pos_total_row();
    });

    //Change max quantity rule if lot number changes
    $('table#pos_table tbody').on('change', 'select.lot_number', function () {
        var qty_element = $(this).closest('tr').find('input.pos_quantity');

        var tr = $(this).closest('tr');
        var multiplier = 1;
        var unit_name = '';
        var sub_unit_length = tr.find('select.sub_unit').length;
        if (sub_unit_length > 0) {
            var select = tr.find('select.sub_unit');
            multiplier = parseFloat(select.find(':selected').data('multiplier'));
            unit_name = select.find(':selected').data('unit_name');
        }
        var allow_overselling = qty_element.data('allow-overselling');
        if ($(this).val() && !allow_overselling) {
            var lot_qty = $('option:selected', $(this)).data('qty_available');
            var max_err_msg = $('option:selected', $(this)).data('msg-max');

            if (sub_unit_length > 0) {
                lot_qty = lot_qty / multiplier;
                var lot_qty_formated = __number_f(lot_qty, false);
                max_err_msg = __translate('lot_max_qty_error', {
                    max_val: lot_qty_formated,
                    unit_name: unit_name,
                });
            }

            qty_element.attr('data-rule-max-value', lot_qty);
            qty_element.attr('data-msg-max-value', max_err_msg);

            qty_element.rules('add', {
                'max-value': lot_qty,
                messages: {
                    'max-value': max_err_msg,
                },
            });
        } else {
            var default_qty = qty_element.data('qty_available');
            var default_err_msg = qty_element.data('msg_max_default');
            if (sub_unit_length > 0) {
                default_qty = default_qty / multiplier;
                var lot_qty_formated = __number_f(default_qty, false);
                default_err_msg = __translate('pos_max_qty_error', {
                    max_val: lot_qty_formated,
                    unit_name: unit_name,
                });
            }

            qty_element.attr('data-rule-max-value', default_qty);
            qty_element.attr('data-msg-max-value', default_err_msg);

            qty_element.rules('add', {
                'max-value': default_qty,
                messages: {
                    'max-value': default_err_msg,
                },
            });
        }
        qty_element.trigger('change');
    });

    //Change in row discount type or discount amount
    $('table#pos_table tbody').on(
        'change',
        'select.row_discount_type, input.row_discount_amount',
        function () {
            var tr = $(this).parents('tr');

            //calculate discounted unit price
            var discounted_unit_price = calculate_discounted_unit_price(tr);

            var tax_rate = tr.find('select.tax_id').find(':selected').data('rate');
            var quantity = __read_number(tr.find('input.pos_quantity'));

            var unit_price_inc_tax = __add_percent(discounted_unit_price, tax_rate);
            var line_total = quantity * unit_price_inc_tax;

            __write_number(tr.find('input.pos_unit_price_inc_tax'), unit_price_inc_tax);
            __write_number(tr.find('input.pos_line_total'), line_total, false, 2);
            tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));
            pos_each_row(tr);
            pos_total_row();
            round_row_to_iraqi_dinnar(tr);
        }
    );

    //Remove row on click on remove row
    $('table#pos_table tbody').on('click', 'i.pos_remove_row', function () {
        $(this).parents('tr').remove();
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            const value = parseInt($('#cust_points').val());
            var user_total_point = parseInt($('#user_total_point').val());
            var total_amountOfbonus = 0.05 * user_total_point;
            var request_point = value * 20;
            var remaining_point = user_total_point - request_point;
            var detail = $('#custDet').text();
            console.log(total_amountOfbonus, detail);
            $('#total_b_point').text('Total BP: ' + remaining_point + ' (' + (total_amountOfbonus).toFixed(2) + ')' + detail);
            $('#cust_points').val(0)
            // $('#cust_points').trigger('change');
        }
        pos_total_row();
        $('#cust_points').trigger('change');
    });

    //Cancel the invoice
    $('button#pos-cancel').click(function () {
        reset_pos_form();
    });

    //Save invoice as draft
    $('button#pos-draft').click(function () {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var is_valid = isValidPosForm();
        if (is_valid != true) {
            return;
        }

        var data = pos_form_obj.serialize();
        data = data + '&status=draft';
        var url = pos_form_obj.attr('action');

        $.ajax({
            method: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function (result) {
                if (result.success == 1) {
                    reset_pos_form();
                    toastr.success(result.msg);
                    get_recent_transactions('draft', $('div#tab_draft'));
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    //Save invoice as Quotation
    $('button#pos-quotation').click(function () {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var is_valid = isValidPosForm();
        if (is_valid != true) {
            return;
        }

        var data = pos_form_obj.serialize();
        data = data + '&status=quotation';
        var url = pos_form_obj.attr('action');

        $.ajax({
            method: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function (result) {
                if (result.success == 1) {
                    reset_pos_form();
                    toastr.success(result.msg);

                    //Check if enabled or not
                    if (result.receipt.is_enabled) {
                        pos_print(result.receipt);
                    }

                    get_recent_transactions('quotation', $('div#tab_quotation'));
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    //Finalize invoice, open payment modal
    $('button#pos-finalize').click(function () {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        $('#modal_payment').modal('show');
        $('#change_text').html(__currency_trans_from_en(0.00, true))
        var total_payable1 = $("#total_payable").text().replace(',', '.');
        $('span.total_payable_span').text(__currency_trans_from_en(total_payable1, true));
        $('span.total_paying').text(__currency_trans_from_en(total_payable1, true));
        $('.payment-amount').val(total_payable1);
        $('#total_paying_input').val(total_payable1);
        // total_paying_input
    });

    $('#modal_payment').on('shown.bs.modal', function () {
        $('#modal_payment').find('input').filter(':visible:first').focus().select();
    });

    //Finalize without showing payment options
    $('button.pos-express-finalize').click(function () {
        //Check if product is present or not.
        $("#direct_cash").val('')
        $("#direct_cash").val('1')
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var pay_method = $(this).data('pay_method');

        //Check for remaining balance & add it in 1st payment row
        var total_payable = __read_number($('input#final_total_input'));
        var total_paying = __read_number($('input#total_paying_input'));

        // var total_payable = $("#total_payable").text().replace(',', '.');
        // var total_paying = $("#total_payable").text().replace(',', '.');
        if (total_payable > total_paying) {
            var bal_due = total_payable - total_paying;

            var first_row = $('#payment_rows_div').find('.payment-amount').first();
            var first_row_val = __read_number(first_row);
            first_row_val = first_row_val + bal_due;
            __write_number(first_row, first_row_val);
            first_row.trigger('change');
        }

        //Change payment method.
        $('#payment_rows_div').find('.payment_types_dropdown').first().val(pay_method);
        if (pay_method == 'card') {
            $('div#card_details_modal').modal('show');
        } else if (pay_method == 'suspend') {
            $('div#confirmSuspendModal').modal('show');
        } else {
            pos_form_obj.submit(); //Submitting form
        }
        $('#change_text').html(__currency_trans_from_en(0.00, true))
    });

    $('div#card_details_modal').on('shown.bs.modal', function (e) {
        $('input#card_number').focus();
    });

    $('div#confirmSuspendModal').on('shown.bs.modal', function (e) {
        $(this).find('textarea').focus();
    });

    //on save card details
    $('button#pos-save-card-external').click(function () {
        $('select#method_0').val('card');
        $('input#card_number_0').val('112233445566');
        $('input#card_holder_name_0').val('Dummy User');
        $('input#card_transaction_number_0').val('11223344');
        $('select#card_type_0').val('credit');
        $('input#card_month_0').val('01');
        $('input#card_year_0').val('20000');
        $('input#card_security_0').val('1122');
        // Here Form submitting of POS_Create
        // $('div#card_details_modal').modal('hide');
        pos_form_obj.submit();
    });
    /**
     * Old Script
     **/
    $('button#pos-save-card').click(function () {
        $('input#card_number_0').val($('#card_number').val());
        $('input#card_holder_name_0').val($('#card_holder_name').val());
        $('input#card_transaction_number_0').val($('#card_transaction_number').val());
        $('select#card_type_0').val($('#card_type').val());
        $('input#card_month_0').val($('#card_month').val());
        $('input#card_year_0').val($('#card_year').val());
        $('input#card_security_0').val($('#card_security').val());

        $('div#card_details_modal').modal('hide');
        pos_form_obj.submit();
    });

    $('button#pos-suspend').click(function () {
        $('input#is_suspend').val(1);
        $('div#confirmSuspendModal').modal('hide');
        pos_form_obj.submit();
        $('input#is_suspend').val(0);
    });

    //fix select2 input issue on modal
    $('#modal_payment')
        .find('.select2')
        .each(function () {
            $(this).select2({
                dropdownParent: $('#modal_payment'),
            });
        });

    $('button#add-payment-row').click(function () {
        var row_index = $('#payment_row_index').val();
        $.ajax({
            method: 'POST',
            url: '/sells/pos/get_payment_row',
            data: { row_index: row_index },
            dataType: 'html',
            success: function (result) {
                if (result) {
                    var appended = $('#payment_rows_div').append(result);

                    var total_payable = __read_number($('input#final_total_input'));
                    var total_paying = __read_number($('input#total_paying_input'));
                    //  total paument logic 
                    var total_payable1 = $("#total_payable").text().replace(',', '.');
                    var cust_points = $("#cust_points").val();
                    // console.log(cust_points);
                    total_payable = total_payable1 + cust_points;
                    // total_payable = total_payable1;
                    console.log(total_payable + ' - ' + total_paying);

                    var b_due = total_payable - total_paying;
                    $(appended).find('input.payment-amount').focus();
                    // .val(__currency_trans_from_en(b_due, false))
                    $(appended)
                        .find('input.payment-amount')
                        .last()
                        .val(b_due)
                        .change()
                        .select();
                    __select2($(appended).find('.select2'));
                    $('#payment_row_index').val(parseInt(row_index) + 1);
                }
            },
        });
    });

    $(document).on('click', '.remove_payment_row', function () {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                $(this).closest('.payment_row').remove();
                calculate_balance_due();
            }
        });
    });

    pos_form_validator = pos_form_obj.validate({

        submitHandler: function (form, e) {
            // var total_payble = __read_number($('input#final_total_input'));
            // var total_paying = __read_number($('input#total_paying_input'));
            var cnf = true;

            //Ignore if the difference is less than 0.5
            if ($('input#in_balance_due').val() >= 0.5) {
                cnf = confirm(LANG.paid_amount_is_less_than_payable);
                // if( total_payble > total_paying ){
                // 	cnf = confirm( LANG.paid_amount_is_less_than_payable );
                // } else if(total_payble < total_paying) {
                // 	alert( LANG.paid_amount_is_more_than_payable );
                // 	cnf = false;
                // }
            }

            if (cnf) {

                $('div.pos-processing').show();
                $('#pos-save').attr('disabled', 'true');
                // console.log('sfds',direct_cash);
                var data = $(form).serialize();
                data = data + '&status=final';
                var url = $(form).attr('action');
                console.log(url);
                $.ajax({
                    method: 'POST',
                    url: url,
                    data: data,
                    dataType: 'json',
                    success: function (result) {
                        if (result.success == 1) {
                            $('#modal_payment').modal('hide');
                            toastr.success(result.msg);

                            reset_pos_form();

                            //Check if enabled or not
                            if (result.receipt.is_enabled) {
                                pos_print(result.receipt);
                            }

                            get_recent_transactions('final', $('div#tab_final'));
                        } else {
                            toastr.error(result.msg);
                        }

                        $('div.pos-processing').hide();
                        $('#pos-save').removeAttr('disabled');
                    },
                });
            }
            return false;
        },
    });

    $(document).on('change', '.payment-amount', function () {
        calculate_balance_due();
    });
    $(document).on('keyup', '.payment-amount', function () {
        calculate_balance_due();
    });
    var c = null;
    //Update discount
    $('button#posEditDiscountModalUpdate').click(function () {
        //Close modal

        $('div#posEditDiscountModal').modal('hide');

        //Update values
        $('input#discount_type').val($('select#discount_type_modal').val());

        $('.val_un_discount').each(function () {
            c = $(this);
            if ($('select#discount_type_modal').val() == 'percentage') {
                c.html(__read_number($('input#discount_amount_modal')) + '%');
            } else {
                // c.html("0");
            }
        });
        $('.row_discount_amount').each(function () {
            c = $(this);
            if ($('select#discount_type_modal').val() == 'percentage') {
                c.val($('input#discount_amount_modal').val());
            } else {
                // c.val(0);
            }
            c.change();
        });
        if ($('select#discount_type_modal').val() == 'percentage') {
            // console.log(c);
            c.html($('input#discount_amount_modal').val() + '%');
            c.html(__read_number($('input#discount_amount_modal')) + '%');
        } else {
            __write_number(
                $('input#discount_amount'),
                $('input#discount_amount_modal').val()
                // __read_number($('input#discount_amount_modal'))
            );
            pos_total_row();
        }
    });

    //Shipping
    $('button#posShippingModalUpdate').click(function () {
        //Close modal
        $('div#posShippingModal').modal('hide');

        //update shipping details
        $('input#shipping_details').val($('#shipping_details_modal').val());

        //Update shipping charges
        __write_number(
            $('input#shipping_charges'),
            __read_number($('input#shipping_charges_modal'))
        );

        //$('input#shipping_charges').val(__read_number($('input#shipping_charges_modal')));

        pos_total_row();
    });

    $('#posShippingModal').on('shown.bs.modal', function () {
        $('#posShippingModal')
            .find('#shipping_details_modal')
            .filter(':visible:first')
            .focus()
            .select();
    });

    $(document).on('shown.bs.modal', '.row_edit_product_price_model', function () {
        $('.row_edit_product_price_model').find('input').filter(':visible:first').focus().select();
    });

    //Update Order tax
    $('button#posEditOrderTaxModalUpdate').click(function () {
        //Close modal
        $('div#posEditOrderTaxModal').modal('hide');

        var tax_obj = $('select#order_tax_modal');
        var tax_id = tax_obj.val();
        var tax_rate = tax_obj.find(':selected').data('rate');

        $('input#tax_rate_id').val(tax_id);

        __write_number($('input#tax_calculation_amount'), tax_rate);
        pos_total_row();
    });

    //Displays list of recent transactions
    get_recent_transactions('final', $('div#tab_final'));
    get_recent_transactions('quotation', $('div#tab_quotation'));
    get_recent_transactions('draft', $('div#tab_draft'));

    $(document).on('click', '.add_new_customer', function () {
        $('#customer_id').select2('close');
        var name = $(this).data('name');
        $('.contact_modal').find('input#name').val(name);
        $('.contact_modal')
            .find('select#contact_type')
            .val('customer')
            .closest('div.contact_type_div')
            .addClass('hide');
        $('.contact_modal').modal('show');
    });
    $('form#quick_add_contact')
        .submit(function (e) {
            e.preventDefault();

        })
        .validate({
            rules: {
                contact_id: {
                    remote: {
                        url: '/contacts/check-contact-id',
                        type: 'post',
                        data: {
                            contact_id: function () {
                                return $('#contact_id').val();
                            },
                            hidden_id: function () {
                                if ($('#hidden_id').length) {
                                    return $('#hidden_id').val();
                                } else {
                                    return '';
                                }
                            },
                        },
                    },
                },
            },
            messages: {
                contact_id: {
                    remote: LANG.contact_id_already_exists,
                },
            },
            submitHandler: function (form) {
                $(form).find('button[type="submit"]').attr('disabled', true);
                var data = $(form).serialize();
                $.ajax({
                    method: 'POST',
                    url: $(form).attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function (result) {
                        if (result.success == true) {
                            $('select#customer_id').append(
                                $('<option>', { value: result.data.id, text: result.data.name })
                            );
                            $('select#customer_id').val(result.data.id).trigger('change');
                            $('div.contact_modal').modal('hide');
                            $('#total_b_point').text('');
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            },
        });
    $('.contact_modal').on('hidden.bs.modal', function () {
        $('form#quick_add_contact').find('button[type="submit"]').removeAttr('disabled');
        $('form#quick_add_contact')[0].reset();
    });
    $('.register_details_modal, .close_register_modal').on('shown.bs.modal', function () {
        __currency_convert_recursively($(this));
    });

    //Updates for add sell
    $('select#discount_type, input#discount_amount, input#shipping_charges').change(function () {
        pos_total_row();
    });
    $('select#tax_rate_id').change(function () {
        var tax_rate = $(this).find(':selected').data('rate');
        __write_number($('input#tax_calculation_amount'), tax_rate);
        pos_total_row();
    });
    //Datetime picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    //Direct sell submit
    sell_form = $('form#add_sell_form');
    if ($('form#edit_sell_form').length) {
        sell_form = $('form#edit_sell_form');
        pos_total_row();
    }
    sell_form_validator = sell_form.validate();

    $('button#submit-sell').click(function () {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }
        if (sell_form.valid()) {
            sell_form.submit();
        }
    });

    //Show product list.
    get_product_suggestion_list(
        $('select#product_category').val(),
        $('select#product_brand').val(),
        $('input#location_id').val(),
        null,
        null
    );
    $('select#product_category, select#product_brand').on('change', function (e) {
        $('input#suggestion_page').val(1);
        var location_id = $('input#location_id').val();
        console.log(location_id);
        if (location_id != '' || location_id != undefined) {
            get_product_suggestion_list(
                $('select#product_category').val(),
                $('select#product_brand').val(),
                $('input#location_id').val(),
                null
            );
        }
    });

    /**
     * Search box
     *
     **/
    $('#search_box').keyup(function () {
        var search_txt = $('input#search_box').val();
        if (search_txt != '' || search_txt != undefined) {
            // console.log(search_txt);
            get_product_suggestion_list(
                $('select#product_category').val(),
                $('select#product_brand').val(),
                $('input#location_id').val(),
                search_txt,
                null
            );
        }
    });

    $(document).on('click', 'div.product_box', function () {
        //Check if location is not set then show error message.
        if ($('input#location_id').val() == '') {
            toastr.warning(LANG.select_location);
        } else {
            pos_product_row($(this).data('variation_id'));
        }
    });

    $(document).on('shown.bs.modal', '.row_description_modal', function () {
        $(this).find('textarea').first().focus();
    });

    //Press enter on search product to jump into last quantty and vice-versa
    $('#search_product').keydown(function (e) {
        var key = e.which;
        if (key == 9) {
            // the tab key code
            e.preventDefault();
            if ($('#pos_table tbody tr').length > 0) {
                $('#pos_table tbody tr:last').find('input.pos_quantity').focus().select();
            }
        }
    });
    $('#pos_table').on('keypress', 'input.pos_quantity', function (e) {
        var key = e.which;
        if (key == 13) {
            // the enter key code
            $('#search_product').focus();
        }
    });

    $('#exchange_rate').change(function () {
        var curr_exchange_rate = 1;
        if ($(this).val()) {
            curr_exchange_rate = __read_number($(this));
        }
        var total_payable = __read_number($('input#final_total_input'));
        var shown_total = total_payable * curr_exchange_rate;
        $('span#total_payable').text(__currency_trans_from_en(shown_total, false));
    });

    $('select#price_group').change(function () {
        var curr_val = $(this).val();
        var prev_value = $('input#hidden_price_group').val();
        $('input#hidden_price_group').val(curr_val);
        if (curr_val != prev_value && $('table#pos_table tbody tr').length > 0) {
            swal({
                title: LANG.sure,
                text: LANG.form_will_get_reset,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    if ($('form#edit_pos_sell_form').length > 0) {
                        $('table#pos_table tbody').html('');
                        pos_total_row();
                    } else {
                        reset_pos_form();
                    }

                    $('input#hidden_price_group').val(curr_val);
                    $('select#price_group').val(curr_val).change();
                } else {
                    $('input#hidden_price_group').val(prev_value);
                    $('select#price_group').val(prev_value).change();
                }
            });
        }
    });

    //Quick add product
    $(document).on('click', 'button.pos_add_quick_product', function () {
        var url = $(this).data('href');
        var container = $(this).data('container');
        var location_id = $('#location_id').val();
        console.log(location_id);
        $.ajax({
            url: url + '?product_for=pos&location_id=' + location_id,
            dataType: 'html',
            success: function (result) {
                $(container).html(result).modal('show');
                $('.os_exp_date').datepicker({
                    autoclose: true,
                    format: 'dd-mm-yyyy',
                    clearBtn: true,
                });
            },
        });
    });

    $(document).on('change', 'form#quick_add_product_form input#single_dpp', function () {
        var unit_price = __read_number($(this));
        $('table#quick_product_opening_stock_table tbody tr').each(function () {
            var input = $(this).find('input.unit_price');
            __write_number(input, unit_price);
            input.change();
        });
    });

    $(document).on('quickProductAdded', function (e) {
        //Check if location is not set then show error message.
        if ($('input#location_id').val() == '') {
            toastr.warning(LANG.select_location);
        } else {
            pos_product_row(e.variation.id);
        }
    });

    $('div.view_modal').on('show.bs.modal', function () {
        __currency_convert_recursively($(this));
    });

    $('table#pos_table').on('change', 'select.sub_unit', function () {
        var tr = $(this).closest('tr');
        var base_unit_selling_price = tr.find('input.hidden_base_unit_sell_price').val();

        var selected_option = $(this).find(':selected');

        var multiplier = parseFloat(selected_option.data('multiplier'));

        var allow_decimal = parseInt(selected_option.data('allow_decimal'));

        tr.find('input.base_unit_multiplier').val(multiplier);

        var unit_sp = base_unit_selling_price * multiplier;

        var sp_element = tr.find('input.pos_unit_price');
        __write_number(sp_element, unit_sp);

        sp_element.change();

        var qty_element = tr.find('input.pos_quantity');
        var base_max_avlbl = qty_element.data('qty_available');
        var error_msg_line = 'pos_max_qty_error';

        if (tr.find('select.lot_number').length > 0) {
            var lot_select = tr.find('select.lot_number');
            if (lot_select.val()) {
                base_max_avlbl = lot_select.find(':selected').data('qty_available');
                error_msg_line = 'lot_max_qty_error';
            }
        }

        qty_element.attr('data-decimal', allow_decimal);
        var abs_digit = true;
        if (allow_decimal) {
            abs_digit = false;
        }
        qty_element.rules('add', {
            abs_digit: abs_digit,
        });

        if (base_max_avlbl) {
            var max_avlbl = parseFloat(base_max_avlbl) / multiplier;
            var formated_max_avlbl = __number_f(max_avlbl);
            var unit_name = selected_option.data('unit_name');
            var max_err_msg = __translate(error_msg_line, {
                max_val: formated_max_avlbl,
                unit_name: unit_name,
            });
            qty_element.attr('data-rule-max-value', max_avlbl);
            qty_element.attr('data-msg-max-value', max_err_msg);
            qty_element.rules('add', {
                'max-value': max_avlbl,
                messages: {
                    'max-value': max_err_msg,
                },
            });
            qty_element.trigger('change');
        }
    });
});

function get_product_suggestion_list(
    category_id,
    brand_id,
    location_id,
    search_txt = null,
    url = null
) {
    if ($('div#product_list_body').length == 0) {
        return false;
    }

    if (url == null) {
        url = '/sells/pos/get-product-suggestion';
    }
    $('#suggestion_page_loader').fadeIn(700);
    var page = $('input#suggestion_page').val();
    if (page == 1) {
        $('div#product_list_body').html('');
    }
    if ($('div#product_list_body').find('input#no_products_found').length > 0) {
        $('#suggestion_page_loader').fadeOut(700);
        return false;
    }
    $.ajax({
        method: 'GET',
        url: url,
        data: {
            category_id: category_id,
            brand_id: brand_id,
            location_id: location_id,
            search: search_txt,
            page: page,
        },
        dataType: 'html',
        success: function (result) {
            $('div#product_list_body').append(result);
            $('#suggestion_page_loader').fadeOut(700);

        },
    });
}

//Get recent transactions
function get_recent_transactions(status, element_obj) {
    if (element_obj.length == 0) {
        return false;
    }
    // var location_id=$('#select_location_id').val();
    // if (location_id == '') {
    //     location_id = 1;
    // }
    $.ajax({
        method: 'GET',
        // url: '/sells/pos/get-recent-transactions?location_id='+location_id,
        url: '/sells/pos/get-recent-transactions',
        data: { status: status },
        dataType: 'html',
        success: function (result) {
            element_obj.html(result);
            __currency_convert_recursively(element_obj);
        },
    });
}

function pos_product_row(variation_id) {
    //Get item addition method
    var item_addtn_method = 0;
    var add_via_ajax = true;

    if ($('#item_addition_method').length) {
        item_addtn_method = $('#item_addition_method').val();
    }

    if (item_addtn_method == 0) {
        add_via_ajax = true;
    } else {
        var is_added = false;

        //Search for variation id in each row of pos table
        $('#pos_table tbody')
            .find('tr')
            .each(function () {
                var row_v_id = $(this).find('.row_variation_id').val();
                var enable_sr_no = $(this).find('.enable_sr_no').val();
                var modifiers_exist = false;
                if ($(this).find('input.modifiers_exist').length > 0) {
                    modifiers_exist = true;
                }

                if (
                    row_v_id == variation_id &&
                    enable_sr_no !== '1' &&
                    !modifiers_exist &&
                    !is_added
                ) {
                    add_via_ajax = false;
                    is_added = true;

                    //Increment product quantity
                    qty_element = $(this).find('.pos_quantity');
                    var qty = __read_number(qty_element);
                    __write_number(qty_element, qty + 1);
                    qty_element.change();

                    round_row_to_iraqi_dinnar($(this));

                    $('input#search_product').focus().select();
                }
            });
    }

    if (add_via_ajax) {
        var product_row = $('input#product_row_count').val();
        var location_id = $('input#location_id').val();
        var customer_id = $('select#customer_id').val();
        var is_direct_sell = false;
        if (
            $('input[name="is_direct_sale"]').length > 0 &&
            $('input[name="is_direct_sale"]').val() == 1
        ) {
            is_direct_sell = true;
        }

        var price_group = '';
        if ($('#price_group').length > 0) {
            price_group = $('#price_group').val();
        }

        $.ajax({
            method: 'GET',
            url: '/sells/pos/get_product_row/' + variation_id + '/' + location_id,
            async: false,
            data: {
                product_row: product_row,
                customer_id: customer_id,
                is_direct_sell: is_direct_sell,
                price_group: price_group,
            },
            dataType: 'json',
            success: function (result) {
                if (result.success) {
                    $('table#pos_table tbody')
                        .prepend(result.html_content)
                        .find('input.pos_quantity');
                    //increment row count
                    $('input#product_row_count').val(parseInt(product_row) + 1);
                    var this_row = $('table#pos_table tbody').find('tr').last();
                    pos_each_row(this_row);

                    //For initial discount if present
                    var line_total = __read_number(this_row.find('input.pos_line_total'));
                    this_row.find('span.pos_line_total_text').text(line_total);

                    pos_total_row();
                    if (result.enable_sr_no == '1') {
                        var new_row = $('table#pos_table tbody').find('tr').last();
                        new_row.find('.add-pos-row-description').trigger('click');
                    }

                    round_row_to_iraqi_dinnar(this_row);
                    __currency_convert_recursively(this_row);

                    $('input#search_product').focus().select();
                    // $('#cust_points').on('change', function(){
                    bonusinputes();
                    // });

                    //Used in restaurant module
                    if (result.html_modifier) {
                        $('table#pos_table tbody')
                            .find('tr')
                            .last()
                            .find('td:first')
                            .append(result.html_modifier);
                    }
                } else {
                    toastr.error(result.msg);
                    $('input#search_product').focus().select();
                }
            },
        });
    }
}

//Update values for each row
function pos_each_row(row_obj) {
    var unit_price = __read_number(row_obj.find('input.pos_unit_price'));

    var discounted_unit_price = calculate_discounted_unit_price(row_obj);
    var tax_rate = row_obj.find('select.tax_id').find(':selected').data('rate');

    var unit_price_inc_tax =
        discounted_unit_price + __calculate_amount('percentage', tax_rate, discounted_unit_price);
    __write_number(row_obj.find('input.pos_unit_price_inc_tax'), unit_price_inc_tax);

    var discount = __read_number(row_obj.find('input.row_discount_amount'));

    if (discount > 0) {
        var qty = __read_number(row_obj.find('input.pos_quantity'));
        var line_total = qty * unit_price_inc_tax;
        __write_number(row_obj.find('input.pos_line_total'), line_total);
    }
    //var unit_price_inc_tax = __read_number(row_obj.find('input.pos_unit_price_inc_tax'));

    __write_number(row_obj.find('input.item_tax'), unit_price_inc_tax - discounted_unit_price);
}

function pos_total_row() {
    var total_quantity = 0;
    var price_total = 0;
    var custDiscount = parseInt($('#cust_points').val());

    $('table#pos_table tbody tr').each(function () {
        total_quantity = total_quantity + __read_number($(this).find('input.pos_quantity'));
        price_total = price_total + __read_number($(this).find('input.pos_line_total'));

    });

    // $('table#pos_table tbody tr .product_row').each(function() {
    //     total_quantity = total_quantity + __read_number($(this).find('input.pos_quantity'));
    //     price_total = price_total + __read_number($(this).find('input.pos_line_total'))-custDiscount;
    // });
    // alert(__currency_trans_from_en(price_total, true));
    //Go through the modifier prices.
    $('input.modifiers_price').each(function () {
        price_total = price_total + __read_number($(this));
    });

    //updating shipping charges
    $('span#shipping_charges_amount').text(
        __currency_trans_from_en(__read_number($('input#shipping_charges_modal')), false)
    );

    $('span.total_quantity').each(function () {
        $(this).html(__number_f(total_quantity));
    });

    //$('span.unit_price_total').html(unit_price_total);
    $('span.price_total').html(__currency_trans_from_en(price_total, false));


    let chanage_amount = $('#change_amount').val();
    if (chanage_amount > 0) {
        $('#change_text').html(__currency_trans_from_en(price_total - chanage_amount, true));
    }

    // const spanText =$('input[name=final_total]').val().replace(',', '.');
    // var total_price =parseFloat(spanText)!=0 ? parseFloat(spanText)-custDiscount:0;
    // $('#total_payable').text(total_price.toFixed(2).replace('.', ','));

    calculate_billing_details(price_total);
}

function oldCalculate_billing_details(price_total) {
    var discount = pos_discount(price_total);
    var order_tax = pos_order_tax(price_total, discount);

    //Add shipping charges.
    var shipping_charges = __read_number($('input#shipping_charges'));

    var total_payable = price_total + order_tax - discount + shipping_charges;

    // var total_payable = $("#total_payable").text().replace(',', '.');
    // $('span.total_payable_span').text(__currency_trans_from_en(total_payable1, true));
    // $('.payment-amount').val(total_payable1);
    // console.log(total_payable);
    // alert(price_total + '  + ' + order_tax + ' - ' + discount + '  +  ' + shipping_charges);
    __write_number($('input#final_total_input'), total_payable);
    var curr_exchange_rate = 1;
    if ($('#exchange_rate').length > 0 && $('#exchange_rate').val()) {
        curr_exchange_rate = __read_number($('#exchange_rate'));
    }
    var shown_total = total_payable * curr_exchange_rate;
    // alert(total_payable + '     ' + curr_exchange_rate);
    $('span#total_payable').text(__currency_trans_from_en(shown_total, false));

    // $('span.total_payable_span').text(total_payable);
    $('span.total_payable_span').text(__currency_trans_from_en(total_payable, true));

    //Check if edit form then don't update price.
    if ($('form#edit_pos_sell_form').length == 0) {
        $('.payment-amount').val(total_payable);
        // __write_number($('.payment-amount').first(), total_payable);
    }

    $(document).trigger('invoice_total_calculated');

    calculate_balance_due();
}

function calculate_billing_details(price_total) {
    var discount = pos_discount(price_total);
    var order_tax = pos_order_tax(price_total, discount);

    //Add shipping charges.
    var shipping_charges = __read_number($('input#shipping_charges'));
    // var cust_points = $('#cust_points').val();
    // console.log(cust_points);
    var total_payable = price_total + order_tax - discount + shipping_charges;
    //  var total_payable = $("#total_payable").text().replace(',', '.');
    // $('span.total_payable_span').text(__currency_trans_from_en(total_payable, true));
    // $('span.total_paying').text(__currency_trans_from_en(total_payable, true));
    // $('.payment-amount').val(total_payable);
    // alert(price_total + '  + ' + order_tax + ' - ' + discount + '  +  ' + shipping_charges);
    __write_number($('input#final_total_input'), total_payable);
    var curr_exchange_rate = 1;
    if ($('#exchange_rate').length > 0 && $('#exchange_rate').val()) {
        curr_exchange_rate = __read_number($('#exchange_rate'));
    }
    var shown_total = total_payable * curr_exchange_rate;
    // alert(total_payable + '     ' + curr_exchange_rate);
    $('span#total_payable').text(__currency_trans_from_en(shown_total, false));

    // $('span.total_payable_span').text(total_payable);
    $('span.total_payable_span').text(__currency_trans_from_en(total_payable, true));

    //Check if edit form then don't update price.
    if ($('form#edit_pos_sell_form').length == 0) {
        $('.payment-amount').val(total_payable);
        // __write_number($('.payment-amount').first(), total_payable);
    }

    $(document).trigger('invoice_total_calculated');

    calculate_balance_due();
}

function pos_discount(total_amount) {
    var calculation_type = $('#discount_type').val();
    var calculation_amount = __read_number($('#discount_amount'));

    var discount = __calculate_amount(calculation_type, calculation_amount, total_amount);

    $('span#total_discount').text(__currency_trans_from_en(discount, false));

    return discount;
}

function pos_order_tax(price_total, discount) {
    var tax_rate_id = $('#tax_rate_id').val();
    var calculation_type = 'percentage';
    var calculation_amount = __read_number($('#tax_calculation_amount'));
    var total_amount = price_total - discount;

    if (tax_rate_id) {
        var order_tax = __calculate_amount(calculation_type, calculation_amount, total_amount);
    } else {
        var order_tax = 0;
    }

    $('span#order_tax').text(__currency_trans_from_en(order_tax, false));

    return order_tax;
}

function calculate_balance_due() {
    var total_payable = __read_number($('#final_total_input'));
    var total_paying = 0;
    $('#payment_rows_div')
        .find('.payment-amount')
        .each(function () {
            if (parseFloat($(this).val())) {
                total_paying += parseFloat($(this).val());
                // total_paying = $("#total_payable").text().replace(',', '.');
                // total_paying += __read_number($(this));
            }
        });
    // var bal_due = total_payable - total_paying;
    //  total paument logic 
    var total_payable1 = $("#total_payable").text().replace(',', '.');
    var cust_points = $("#cust_points").val();
    // console.log(cust_points);
    total_payable = total_payable1 + cust_points;
    // total_payable = total_payable1;
    var bal_due = total_payable - total_paying;
    var change_return = 0;

    //change_return
    if (bal_due < 0 || Math.abs(bal_due) < 0.05) {
        __write_number($('input#change_return'), bal_due * -1);
        $('span.change_return_span').text(__currency_trans_from_en(bal_due * -1, true));
        change_return = bal_due * -1;
        bal_due = 0;
    } else {
        __write_number($('input#change_return'), 0);
        $('span.change_return_span').text(__currency_trans_from_en(0, true));
        change_return = 0;
    }
    console.log('Total Paying: ' + total_paying);
    __write_number($('input#total_paying_input'), total_paying);
    $('span.total_paying').text(__currency_trans_from_en(total_paying, true));

    __write_number($('input#in_balance_due'), bal_due);
    $('span.balance_due').text(__currency_trans_from_en(bal_due, true));

    __highlight(bal_due * -1, $('span.balance_due'));
    __highlight(change_return * -1, $('span.change_return_span'));
}

function isValidPosForm() {
    flag = true;
    $('span.error').remove();

    if ($('select#customer_id').val() == null) {
        flag = false;
        error = '<span class="error">' + LANG.required + '</span>';
        $(error).insertAfter($('select#customer_id').parent('div'));
    }

    if ($('tr.product_row').length == 0) {
        flag = false;
        error = '<span class="error">' + LANG.no_products + '</span>';
        $(error).insertAfter($('input#search_product').parent('div'));
    }

    return flag;
}

function reset_pos_form() {
    //If on edit page then redirect to Add POS page
    if ($('form#edit_pos_sell_form').length > 0) {
        setTimeout(function () {
            window.location = $('input#pos_redirect_url').val();
        }, 4000);
        return true;
    }

    if (pos_form_obj[0]) {
        pos_form_obj[0].reset();
    }
    if (sell_form[0]) {
        sell_form[0].reset();
    }
    set_default_customer();
    set_location();

    $('tr.product_row').remove();
    $(
        'span.total_quantity, span.price_total, span#total_discount, span#order_tax, span#total_payable, span#shipping_charges_amount'
    ).text(0);
    $('span.total_payable_span', 'span.total_paying', 'span.balance_due').text(0);

    $('#modal_payment')
        .find('.remove_payment_row')
        .each(function () {
            $(this).closest('.payment_row').remove();
        });

    //Reset discount
    __write_number($('input#discount_amount'), $('input#discount_amount').data('default'));
    $('input#discount_type').val($('input#discount_type').data('default'));

    //Reset tax rate
    $('input#tax_rate_id').val($('input#tax_rate_id').data('default'));
    __write_number(
        $('input#tax_calculation_amount'),
        $('input#tax_calculation_amount').data('default')
    );

    $('select.payment_types_dropdown').val('cash').trigger('change');
    $('#price_group').trigger('change');

    //Reset shipping
    __write_number($('input#shipping_charges'), $('input#shipping_charges').data('default'));
    $('input#shipping_details').val($('input#shipping_details').data('default'));

    if ($('input#is_recurring').length > 0) {
        $('input#is_recurring').iCheck('update');
    }

    $(document).trigger('sell_form_reset');
}

function set_default_customer() {
    var selector = $('select#customer_id');
    var default_customer_id = $('#default_customer_id').val();
    var default_customer_name = $('#default_customer_name').val();
    var exists = $(selector.val()).length;
    // var exists = $('[value=' + default_customer_id + ']').length;
    if (exists == 0) {
        $('select#customer_id').append(
            $('<option>', { value: default_customer_id, text: default_customer_name })
        );
    }

    $('select#customer_id').val(default_customer_id).trigger('change');
}

//Set the location and initialize printer
function set_location() {
    if ($('select#select_location_id').length == 1) {
        $('input#location_id').val($('select#select_location_id').val());
        $('input#location_id').data(
            'receipt_printer_type',
            $('select#select_location_id').find(':selected').data('receipt_printer_type')
        );
    }

    if ($('input#location_id').val()) {
        $('input#search_product').prop('disabled', false).focus();
    } else {
        $('input#search_product').prop('disabled', true);
    }

    initialize_printer();
}

function initialize_printer() {
    if ($('input#location_id').data('receipt_printer_type') == 'printer') {
        initializeSocket();
    }
}

$('body').on('click', 'label', function (e) {
    var field_id = $(this).attr('for');
    if (field_id) {
        if ($('#' + field_id).hasClass('select2')) {
            $('#' + field_id).select2('open');
            return false;
        }
    }
});

$('body').on('focus', 'select', function (e) {
    var field_id = $(this).attr('id');
    if (field_id) {
        if ($('#' + field_id).hasClass('select2')) {
            $('#' + field_id).select2('open');
            return false;
        }
    }
});

function round_row_to_iraqi_dinnar(row) {
    if (iraqi_selling_price_adjustment) {
        var element = row.find('input.pos_unit_price_inc_tax');
        var unit_price = round_to_iraqi_dinnar(__read_number(element));
        __write_number(element, unit_price);
        element.change();
    }
}

function pos_print(receipt) {
    //If printer type then connect with websocket
    if (receipt.print_type == 'printer') {
        var content = receipt;
        content.type = 'print-receipt';

        //Check if ready or not, then print.
        if (socket != null && socket.readyState == 1) {
            socket.send(JSON.stringify(content));
        } else {
            initializeSocket();
            setTimeout(function () {
                socket.send(JSON.stringify(content));
            }, 700);
        }
    } else if (receipt.html_content != '') {
        //If printer type browser then print content
        $('#receipt_section').html(receipt.html_content);
        __currency_convert_recursively($('#receipt_section'));
        setTimeout(function () {
            window.print();
        }, 1000);
    }
}
var i = 1;

function calculate_discounted_unit_price(row) {
    var this_unit_price = row.find('input.pos_unit_price').val();
    // var this_unit_price = __read_number(row.find('input.pos_unit_price'));
    var row_discounted_unit_price = this_unit_price;
    var row_discount_type = row.find('select.row_discount_type').val();
    var rowTc = row.find('input.rowTc').val();
    var row_discount_amount = row.find('input.row_discount_amount').val();
    // var row_discount_amount = __read_number(row.find('input.row_discount_amount'));
    // SHow orignal Value if Forced price is entered
    // if (i != 1) {
    if ($('#input_actual_price' + rowTc).val() != row.find('input.pos_unit_price').val()) {
        $('#input_original_price' + rowTc).val($('#input_actual_price' + rowTc).val());
    }
    i++;
    // $('#input_original_price' + rowTc).val($('#row_discount_amount_' + rowTc).val());
    if (row_discount_amount) {
        if (row_discount_type == 'fixed') {
            row_discounted_unit_price = this_unit_price - row_discount_amount;
            $('#val_un_discount_' + rowTc).html(
                $('#row_discount_amount_' + rowTc).val() + '-Fixed'
            );
        } else {
            if (row_discount_amount > 100) {
                alert('You cann add More Discount then 100');
                return false;
            }
            row_discounted_unit_price = __substract_percent(this_unit_price, row_discount_amount);
            $('#val_un_discount_' + rowTc).html($('#row_discount_amount_' + rowTc).val() + '%');
        }
    }
    return row_discounted_unit_price;
}

function get_unit_price_from_discounted_unit_price(row, discounted_unit_price) {
    var this_unit_price = discounted_unit_price;
    var row_discount_type = row.find('select.row_discount_type').val();
    var row_discount_amount = __read_number(row.find('input.row_discount_amount'));
    if (row_discount_amount) {
        if (row_discount_type == 'fixed') {
            this_unit_price = discounted_unit_price + row_discount_amount;
        } else {
            this_unit_price = __get_principle(discounted_unit_price, row_discount_amount, true);
        }
    }
    return this_unit_price;
}

//Update quantity if line subtotal changes
$('table#pos_table tbody').on('change', 'input.pos_line_total', function () {
    var subtotal = __read_number($(this));
    var tr = $(this).parents('tr');
    var quantity_element = tr.find('input.pos_quantity');
    var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));
    var quantity = subtotal / unit_price_inc_tax;
    __write_number(quantity_element, quantity);

    if (sell_form_validator) {
        sell_form_validator.element(quantity_element);
    }
    if (pos_form_validator) {
        pos_form_validator.element(quantity_element);
    }
    tr.find('span.pos_line_total_text').text(__number_f(subtotal));

    pos_total_row();
});

$('div#product_list_body').on('scroll', function () {
    if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
        var page = parseInt($('#suggestion_page').val());
        page += 1;
        $('#suggestion_page').val(page);
        var location_id = $('input#location_id').val();
        console.log(location_id);
        var category_id = $('select#product_category').val();
        var brand_id = $('select#product_brand').val();

        get_product_suggestion_list(category_id, brand_id, location_id);
    }
});

$(document).on('ifChecked', '#is_recurring', function () {
    $('#recurringInvoiceModal').modal('show');
});

$(document).on('shown.bs.modal', '#recurringInvoiceModal', function () {
    $('input#recur_interval').focus();
});

$(document).on('click', '#select_all_service_staff', function () {
    var val = $('#res_waiter_id').val();
    $('#pos_table tbody')
        .find('select.order_line_service_staff')
        .each(function () {
            $(this).val(val).change();
        });
});

$(document).on('click', '.print-invoice-link', function (e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('href') + '?check_location=true',
        dataType: 'json',
        success: function (result) {
            if (result.success == 1) {
                //Check if enabled or not
                if (result.receipt.is_enabled) {
                    pos_print(result.receipt);
                }
            } else {
                toastr.error(result.msg);
            }
        },
    });
});