jQuery(function($){

  var psACHForm = {
    form: $('form.woocommerce-checkout'),
    
    hasToken: function(){
      return 0 < $( 'input.ps_token' ).length; 
    },

    payWithPaymentSpring: function(){
      return $('#payment_method_paymentspring_ach').is(':checked') && (!$('input[name="wc-paymentspring-payment-token"]:checked').length || 'new' === $('input[name="wc-paymentspring-payment-token"]:checked').val());
    },

    onSubmit: function(e){
      if(!psACHForm.hasToken() && psACHForm.payWithPaymentSpring() && ach_settings_public_key !== "false"){
        e.preventDefault();
        paymentspring_ach.generateTokenACH(
          ach_settings_public_key,
          $('#paymentspring_ach-account-number').val().replace(/ /g,''),
          $('#paymentspring_ach-routing-number').val().replace(/ /g,''),
          $('#billing_first_name').val(),
          $('#billing_last_name').val(), 
          psACHForm.onCompletion
        );
        return false;
      }
    },

    onCompletion: function(response){
      if(response["errors"]){
        psACHForm.onFailure(response);
      }else{
        psACHForm.onSuccess(response);
      }
    },

    onSuccess: function(response){
      var token = response.id;
      psACHForm.form.append("<input type='hidden' class='ps_token' name='ps_token' value='" + token + "'/>");
      psACHForm.form.submit();
    },

    errorMessage: function(errors){
      return $.map(errors, function(error){
        return error.message;
      }).join(', ');
    },
    
    onFailure: function(response){
      $('.wc-paymentspring_ach-error, .ps_token').remove();
      $('#paymentspring_ach-routing-number').closest( 'p' ).before( 
        '<ul class="woocommerce_error woocommerce-error wc-paymentspring_ach-error"><li>' + this.errorMessage(response["errors"]) + '</li></ul>' 
      );
      psACHForm.form.unblock();
    }
  }

  psACHForm.form.on('checkout_place_order_paymentspring_ach', psACHForm.onSubmit);
});
