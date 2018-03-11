<?php

namespace Drupal\commerce_rave\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;

/**
 * Provides the interface for the Stripe payment gateway.
 */
interface RaveInterface extends OffsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {

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
