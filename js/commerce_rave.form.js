(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.raveForm = {
    attach: function (context) {
      var options = drupalSettings.rave.transactionData;

      $('.payment-redirect-form', context).on('submit', function () {
        getpaidSetup(JSON.parse(options));

        return false;
      });

      // Trigger form submission when user visits Payment page.
      $('.payment-redirect-form', context).once('getPaid').trigger('submit');
    }
  };

})(jQuery, Drupal, drupalSettings);
