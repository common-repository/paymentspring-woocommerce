jQuery(function($){

  var psForm = {
    form: $('form.woocommerce-checkout'),
    
    hasToken: function(){
      return 0 < $( 'input.ps_token' ).length; 
    },

    payWithPaymentSpring: function(){
      return $('#payment_method_paymentspring').is(':checked') && (!$('input[name="wc-paymentspring-payment-token"]:checked').length || 'new' === $('input[name="wc-paymentspring-payment-token"]:checked').val());
    },

    onSubmit: function(e){
      if(!psForm.hasToken() && psForm.payWithPaymentSpring() && cc_settings_public_key !== "false"){
        e.preventDefault();
        var expiryDate = $('#paymentspring-card-expiry').payment("cardExpiryVal");
        paymentspring.generateToken(
          cc_settings_public_key,
          $('#paymentspring-card-number').val().replace(/ /g,''),
          $('#paymentspring-card-cvc').val(),
          $('#billing_first_name').val() + " " + $('#billing_last_name').val(), 
          expiryDate.month,
          expiryDate.year,
          psForm.onCompletion
        );
        return false;
      }
    },

    onCompletion: function(response){
      if(response["errors"]){
        psForm.onFailure(response);
      }else{
        psForm.onSuccess(response);
      }
    },

    onSuccess: function(response){
      var token = response.id;
      psForm.form.append("<input type='hidden' class='ps_token' name='ps_token' value='" + token + "'/>");
      psForm.form.submit();
    },

    errorMessage: function(errors){
      return $.map(errors, function(error){
        return error.message;
      }).join(', ');
    },
    
    onFailure: function(response){
      $('.wc-paymentspring-error, .ps_token').remove();
      $('#paymentspring-card-number').closest( 'p' ).before( 
        '<ul class="woocommerce_error woocommerce-error wc-paymentspring-error"><li>' + this.errorMessage(response["errors"]) + '</li></ul>' 
      );
      psForm.form.unblock();
    }
  }

  psForm.form.on('checkout_place_order_paymentspring', psForm.onSubmit);
});
