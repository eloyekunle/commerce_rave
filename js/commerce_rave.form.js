(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.raveForm = {
    attach: function (context) {
      var options = drupalSettings.rave.transactionData;
      console.log(JSON.parse(options));

      $('.payment-redirect-form', context).on('submit', function () {
        console.log("Pay with Rave!");

        getpaidSetup(JSON.parse(options));

        return false;
      });

      $('.payment-redirect-form', context).trigger('submit');
    }
  };

})(jQuery, Drupal, drupalSettings);
