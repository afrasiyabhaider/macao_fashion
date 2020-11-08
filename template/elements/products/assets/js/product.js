/* ================================================
---------------------- Product.js ----------------- */
(function ($) {
  'use strict';
  var PortoProduct = {
    initialised: false,
    mobile: false,
    init: function () {

      if (!this.initialised) {
        this.initialised = true;
      } else {
        return;
      }

      this.modalView();
      this.productManage();
      this.ratingTooltip();

      /* Call function if Light Gallery plugin is included */
      if ($.fn.magnificPopup) {
        this.lightBox();
      }
    },
    ajaxLoading: function () {
      $('body').append("<div class='ajax-overlay'><i class='porto-loading-icon'></i></div>");
    },
    lightBox: function () {
      //QuickView Popup
      $('a.btn-quickview').on('click', function (e) {
        e.preventDefault();
        PortoProduct.ajaxLoading();
        var ajaxUrl = $(this).attr('href');
        setTimeout(function () {
          $.magnificPopup.open({
            type: 'ajax',
            mainClass: "mfp-ajax-product",
            tLoading: '',
            preloader: false,
            removalDelay: 350,
            items: {
              src: ajaxUrl
            },
            callbacks: {
              open: function() {
                $('.sticky-header').addClass('scroll');
                $('#scroll-top').addClass('scroll');
              },
              ajaxContentAdded: function () {
                Porto.owlCarousels();
                Porto.quantityInputs();
                if (typeof addthis !== 'undefined') {
                  addthis.layers.refresh();
                }
                else {
                  $.getScript("https://s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5b927288a03dbde6");
                }
              },
              beforeClose: function () {
                $('.ajax-overlay').remove();
              },
              afterClose: function() {
                $('.sticky-header').removeClass('scroll');
                $('#scroll-top').removeClass('scroll');
              }
            },
            ajax: {
              tError: '',
            }
          });
        }, 500);
      });
    },
    modalView: function() {
      $('.btn-add-cart').click(function(e){
        $('.add-cart-box #productImage').attr('src', $(this).parents('.product-default').find('figure img').attr('src'));
        $('.add-cart-box #productTitle').text($(this).parents('.product-default').find('.product-name a').text());
        $('.sticky-header').addClass('scroll');
        $('#scroll-top').addClass('scroll');
      });
      $('.modal#addCartModal').on('hidden.bs.modal', function(e){
        $('.sticky-header').removeClass('scroll');
        $('#scroll-top').removeClass('scroll');
      })
    },
    productManage: function () {
      $('.product-select').click(function(e) {
        $(this).parents('.product-default').find('figure img').attr('src', $(this).data('src'));
        $(this).addClass('checked').siblings().removeClass('checked');
      });
    },
    ratingTooltip: function () {
      $('.product-ratings').hover(function(e) {
        var ratingsRes = $(this).find('.ratings').width() / $(this).width() * 5;
        $(this).find('.tooltiptext').text(ratingsRes?ratingsRes.toFixed(2):ratingsRes);
      });
    }
  };

  // Ready Event
  jQuery(document).ready(function () {
    // Init our app
    PortoProduct.init();
  });
})(jQuery);