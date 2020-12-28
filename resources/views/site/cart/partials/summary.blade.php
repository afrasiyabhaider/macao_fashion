<div class="col-lg-4">
     <div class="cart-summary">
          <h3>Summary</h3>

          <h4>
               <a data-toggle="collapse" href="#total-estimate-section" class="collapsed" role="button"
                    aria-expanded="false" aria-controls="total-estimate-section">Estimate Shipping and Tax</a>
          </h4>

          <div class="collapse" id="total-estimate-section">
               <form action="#">
                    <div class="form-group form-group-sm">
                         <label>Country</label>
                         <div class="select-custom">
                              <select class="form-control form-control-sm">
                                   <option value="USA">United States</option>
                                   <option value="Turkey">Turkey</option>
                                   <option value="China">China</option>
                                   <option value="Germany">Germany</option>
                              </select>
                         </div><!-- End .select-custom -->
                    </div><!-- End .form-group -->

                    <div class="form-group form-group-sm">
                         <label>State/Province</label>
                         <div class="select-custom">
                              <select class="form-control form-control-sm">
                                   <option value="CA">California</option>
                                   <option value="TX">Texas</option>
                              </select>
                         </div><!-- End .select-custom -->
                    </div><!-- End .form-group -->

                    <div class="form-group form-group-sm">
                         <label>Zip/Postal Code</label>
                         <input type="text" class="form-control form-control-sm">
                    </div><!-- End .form-group -->

                    <div class="form-group form-group-custom-control">
                         <label>Flat Way</label>
                         <div class="custom-control custom-checkbox">
                              <input type="checkbox" class="custom-control-input" id="flat-rate">
                              <label class="custom-control-label" for="flat-rate">Fixed $5.00</label>
                         </div><!-- End .custom-checkbox -->
                    </div><!-- End .form-group -->

                    <div class="form-group form-group-custom-control">
                         <label>Best Rate</label>
                         <div class="custom-control custom-checkbox">
                              <input type="checkbox" class="custom-control-input" id="best-rate">
                              <label class="custom-control-label" for="best-rate">Table Rate $15.00</label>
                         </div><!-- End .custom-checkbox -->
                    </div><!-- End .form-group -->
               </form>
          </div><!-- End #total-estimate-section -->

          <table class="table table-totals">
               <tbody>
                    <tr>
                         <td>Subtotal</td>
                         <td>
                              <i class="fa fa-euro-sign"></i>
                              {{Cart::total()}}
                         </td>
                    </tr>
                    <tr>
                         <td>Tax</td>
                         <td>
                              <i class="fa fa-euro-sign"></i>
                              {{-- {{floatval(Cart::total())}} --}}
                              {{Cart::tax()}}
                         </td>
                    </tr>
               </tbody>
               <tfoot>
                    <tr>
                         <td>Order Total</td>
                         <td>
                              <i class="fa fa-euro-sign"></i>
                              {{-- {{round(Cart::total())}} --}}
                              {{Cart::total()}}
                         </td>
                    </tr>
               </tfoot>
          </table>

          <div class="checkout-methods">
               {{-- <a href="checkout-shipping.html" class="btn btn-block btn-sm btn-primary">Go to Checkout</a> --}}
               {{-- <a href="#" class="btn btn-link btn-block">Check Out with Multiple Addresses</a> --}}
               <form action="{{ url('checkout') }}" method="POST" id="payment-form">
                    <div class="form-row">
                         <label>
                              <span>Cardholder Name</span>
                              <input type="text" data-vp="cardholder" size="20" name="txtCardHolder" autocomplete="off" />
                         </label>
                    </div>
               
                    <div class="form-row">
                         <label>
                              <span>Card Number</span>
                              <input type="text" data-vp="cardnumber" size="20" name="txtCardNumber" autocomplete="off" />
                         </label>
                    </div>
               
                    <div class="form-row">
                         <label>
                              <span>CVV</span>
                              <input type="text" data-vp="cvv" name="txtCVV" size="4" autocomplete="off" />
                         </label>
                    </div>
               
                    <div class="form-row">
                         <label>
                              <span>Expiration (MM/YYYY)</span>
                              <input type="text" data-vp="month" size="2" name="txtMonth" autocomplete="off" />
                         </label>
                         <span> / </span>
                         <input type="text" data-vp="year" size="4" name="txtYear" autocomplete="off" />
                    </div>
               
                    <div class="form-row">
                         <label>
                              <span>Installments</span>
                              <select id="js-installments" name="installments" style="display:none"></select>
                         </label>
                    </div>
               
                    <input type="hidden" id="charge-token" name="chargeToken" />
                    <input type="button" id="submit" value="Submit Payment" />
               </form>
               
               <div id="threed-pane" style="height: 450px; width:500px"></div>
          </div><!-- End .checkout-methods -->
     </div><!-- End .cart-summary -->
</div><!-- End .col-lg-4 -->