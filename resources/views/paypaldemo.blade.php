
<!DOCTYPE html>

<head>
    <!-- Add meta tags for mobile and IE -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
</head>

<body>
    <!-- Set up a container element for the button -->
    <div id="paypal-button-container"></div>

    <!-- Include the PayPal JavaScript SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=sb&currency=USD"></script>

    <script>
        // Render the PayPal button into #paypal-button-container
        paypal.Buttons({
            style: {
                color:  'blue',
                shape:  'pill',
                label:  'pay',
                height: 40
            },
            // Call your server to set up the transaction
            createOrder: function(data, actions) {
                return fetch('http://paypaltest.ets/createorder/', {
                    method: 'get'
                }).then(function(res) {
                    return res.json();
                }).then(function(data) {
                   let token;
                    for (link of data.links) {
                        if (link.rel === 'approval_url') {
                            token = link.href.match(/EC-\w+/)[0];
                        }
                    }
                    return token;
                });
            },

            // Call your server to finalize the transaction
            onApprove: function(data, actions) {
                console.log( data );
                return fetch('http://paypaltest.ets/' + data.payerID + '/' + data.paymentID + '/capture/', {
                    method: 'get'
                }).then(function(res) {
                    return res.json();
                }).then(function(orderData) {
                    
                    // Three cases to handle:
                    //   (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
                    //   (2) Other non-recoverable errors -> Show a failure message
                    //   (3) Successful transaction -> Show a success / thank you message

                    // Your server defines the structure of 'orderData', which may differ
                    var errorDetail = Array.isArray(orderData.details) && orderData.details[0];

                    if (errorDetail && errorDetail.issue === 'INSTRUMENT_DECLINED') {
                        // Recoverable state, see: "Handle Funding Failures"
                        // https://developer.paypal.com/docs/checkout/integration-features/funding-failure/
                        return actions.restart();
                    }

                    if (errorDetail) {
                        var msg = 'Sorry, your transaction could not be processed.';
                        if (errorDetail.description) msg += '\n\n' + errorDetail.description;
                        if (orderData.debug_id) msg += ' (' + orderData.debug_id + ')';
                        // Show a failure message
                        return alert(msg);
                    }

                    // Show a success message to the buyer
                    alert('Transaction completed by ' + orderData.payer.name.given_name);
                });
            }


        }).render('#paypal-button-container');
    </script>
</body>
    