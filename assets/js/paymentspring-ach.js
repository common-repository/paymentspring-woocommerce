window.paymentspring_ach = (function () {

    var jsonpTimeout;
    var jsonpURL = "https://api.paymentspring.com/api/v1/tokens/jsonp";

    /**
     * @constructor
     */
    function PaymentSpringACH() {
        this.script = null;
        this.callback = null;
        this.key = null;
    }

    PaymentSpringACH.prototype.generateTokenACH = function (public_key, account_number, routing_number, first_name, last_name, callback) {
        if (this.script) return;
        this.key = public_key;
        this.callback = callback;
        this.script = document.createElement("script");
        this.script.type = "text/javascript";
        this.script.id = "paymentspring_ach_request_script";
        this.script.src = jsonpURL
        + "?public_api_key=" + this.key
        + "&token_type=bank_account"
        + "&bank_account_number=" + account_number 
        + "&bank_routing_number=" + routing_number 
        + "&bank_account_holder_first_name=" + first_name 
        + "&bank_account_holder_last_name=" + last_name 
        + "&bank_account_type=checking"
        + "&callback=paymentspring_ach.onComplete";

        document.body.appendChild(this.script);
        var closure_this = this;
        jsonpTimeout = setTimeout(function() { document.body.removeChild(closure_this.script); closure_this.script = null; callback(null); }, 5000);
    };

    PaymentSpringACH.prototype.onComplete = function(data) {
        clearTimeout(jsonpTimeout);
        document.body.removeChild(this.script);
        this.script = null;
        this.callback(data);
    };

    return new PaymentSpringACH();
}());

