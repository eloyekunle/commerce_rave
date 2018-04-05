(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.raveForm = {
    attach: function (context) {
      var options = drupalSettings.rave.transactionData;

      var $paymentForm = $('.payment-redirect-form', context);

      $paymentForm.on('submit', function () {
        getpaidSetup(JSON.parse(options));

        return false;
      });

      // Trigger form submission when user visits Payment page.
      $paymentForm.once('getPaid').trigger('submit');
    }
  };

})(jQuery, Drupal, drupalSettings);
