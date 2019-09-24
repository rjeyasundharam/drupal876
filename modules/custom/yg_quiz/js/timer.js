
(function($) {
    Drupal.behaviors.paypal = {
        attach: function(context, settings) {
            'use strict';
            var pro_paypal = 0;
            if (pro_paypal) {
                var PayPal_CLIENT_ID = 'Aam4SCk9QhqlwSKksE-_6TOonv383wrk7vjQkhjNSzjzhyr1bR_WsieZzIfO6GhCli40kAybzOSSl69o';
                var PayPal_SECRET = 'EKpxjpFJtu9OZcrSkPO1A4H3QC-VQ8wxQbHKnCa3V98TevBI5ggXWYPgowStl8N8_BH2MzfZRErofop_';
                var PayPal_BASE_URL = 'https://api.sandbox.paypal.com/v1/';
            } else {
                var PayPal_CLIENT_ID = 'AVVgEwRbUaIDo8GBMKkdyBg5-jZ-pNtCYtwR7q04krgy7j4JuyEOT4Bc1n2SnO0tCSnQ5GgLBVW5s2c4';
                var PayPal_SECRET = 'EDZaodlDDn88675czYr5SevaKgS3L4_ktmVF3eWVWkibZgpwjvs6fJvqBT6xVclDuxiuY4iiuArPsE9H';
                var PayPal_BASE_URL = 'https://api.paypal.com/v1/';
            }
            var name = $("input[name='name']").val();
            var email = $("input[name='email']").val();
            var country = $("input[name='country']").val();
            var sid = $("input[name='sid']").val();
            var node_id = $("input[name='node_id']").val();
            var field_price_usd = $("input[name='field_price']").val();
            var currency = 'USD';
            var price = 0;
            var inr = 70;
            var field_price_inr = field_price_usd * inr;
            if (country == 'India') {
                currency = 'INR';
                price = field_price_inr;
            } else {
                currency = 'USD';
                price = field_price_usd;
            }
            paypal.Button.render({
                env: 'production',
                client: {
                    sandbox: PayPal_CLIENT_ID,
                    production: PayPal_CLIENT_ID
                },
                commit: true,
                payment: function(data, actions) {
                    return actions.payment.create({
                        payment: {
                            transactions: [{
                                amount: {
                                    total: price,
                                    currency: currency
                                }
                            }]
                        }
                    });
                },
                onAuthorize: function(data, actions) {
                    return actions.payment.execute().then(function() {
                        // $("#pp-loader").show();
                        $.ajax({
                            url: "https://www.drupaldevelopersstudio.com/paypal_webform/process",
                            type: "get",
                            data: {
                                paymentID: data.paymentID,
                                payerID: data.payerID,
                                token: data.paymentToken,
                                pid: 1,
                                sid: sid,
                                email: email,
                                name: name,
                                node_id: node_id
                            },
                            success: function(response) {
                                if (response.data == 'approved') {
                                    var res = encodeURIComponent(response.url);
                                    window.location.replace("https://www.drupaldevelopersstudio.com/thankyou?status=approved&dwn=" + res + "");
                                } else {
                                    // $("#pp-loader").hide();
                                    // alert("Payment Failed Please Try Again...");
                                }
                            }
                        });
                    });
                }
            }, '#paypal-button-container');
        }
    };
})(jQuery, Drupal);

var quizVar = setInterval(function() {
  quizTimer()
}, 1000);
var d = 0;

function quizTimer() {
  document.getElementById("timer").innerHTML = d++;
}
