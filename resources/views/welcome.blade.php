<style type="text/css">
  .test{
    margin: 100px 100px 100px 100px; 
  }
</style>
<div id="paypal-button" class="test"></div>
<script src="https://www.paypalobjects.com/api/checkout.js"></script>
<script>
  paypal.Button.render({
    // Configure environment
    env: 'sandbox',
    client: {
      sandbox: 'ASz2aeWWeLH4wAmWd1sM1JRuvtq3JM5fSMbJwk4jERuv2jQpL93aSM6OszArElh46x-xeRegtcuinqkX'
    },
    // Customize button (optional)
    locale: 'en_US',
    style: {
      size: 'small',
      color: 'blue',
      shape: 'pill',
    },

    // Enable Pay Now checkout flow (optional)
    commit: true,

    // Set up a payment
    payment: function(data, actions) {
      return actions.payment.create({
        
        redirect_urls:{
          return_url:'http://paypaltest.ets/execute',
        },

        transactions: [{
          amount: {
            total: '2',
            currency: 'USD'
          }
        }]
      });
    },

    // Set up a payment
  /*payment: function(data, actions) {
    return actions.payment.create({
       redirect_urls:{
            return_url:'http://paypaltest.ets/execute',
          },
      transactions: [{
        amount: {
          total: '100.00',
          currency: 'USD',
          details: {
            subtotal: '100.00'
          }
        },
        description: 'The payment transaction description.',
        custom: '90048630024435',
        //invoice_number: '12345', Insert a unique invoice number
        payment_options: {
          allowed_payment_method: 'INSTANT_FUNDING_SOURCE'
        },
        soft_descriptor: 'ECHI5786786',
        item_list: {
          items: [
          {
            name: 'Trademark application',
            description: 'Trademark application',
            quantity: '1',
            price: '100',
            currency: 'USD'
          }],
        }
      }],
      note_to_payer: 'Contact us for any questions on your order.'
    });
  },*/
    // Execute the payment
    onAuthorize: function(data, actions) {
      return actions.redirect();
      /*return actions.payment.execute().then(function() {
        // Show a confirmation message to the buyer
        window.alert('Thank you for your purchase!');
      });*/
    }
  }, '#paypal-button');

</script>