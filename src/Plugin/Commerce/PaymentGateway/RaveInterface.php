<?php

namespace Drupal\commerce_rave\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;

/**
 * Provides the interface for the Stripe payment gateway.
 */
interface RaveInterface extends OffsitePaymentGatewayInterface {

  /**
   * Get the Stripe API Publisable key set for the payment gateway.
   *
   * @return string
   *   The Stripe API publishable key.
   */
  public function getSecretKey();

  public function getPublicKey();

  public function getPaymentFlow();

  public function getPayButtonText();

  public function getTransactionReferencePrefix();

  public function getBaseUrl();

  public function verifyTransaction($referenceNumber): array;

}

// @todo implement RefundInterface for refunding payments.
// @todo implement Webhook to receive payment notifications from Rave
