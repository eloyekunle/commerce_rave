<?php

namespace Drupal\commerce_rave\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;

/**
 * Provides the interface for the Rave payment gateway.
 */
interface RaveInterface extends OffsitePaymentGatewayInterface {

  /**
   * Get the configured Rave API Secret key.
   *
   * @return string
   *   The Rave API Secret key.
   */
  public function getSecretKey();

  /**
   * Get the configured Rave API Public key.
   *
   * @return string
   *   The Rave API Public key.
   */
  public function getPublicKey();

  /**
   * Get the configured Rave Payment Flow mode.
   *
   * @return string
   *   The Rave Payment Flow mode.
   */
  public function getPaymentFlow();

  /**
   * Get the configured Rave Payment Button Text.
   *
   * @return string
   *   The Rave Payment button text.
   */
  public function getPayButtonText();

  /**
   * Get the configured Rave Transaction reference prefix.
   *
   * @return string
   *   The Rave Transaction reference prefix.
   */
  public function getTransactionReferencePrefix();

  /**
   * Get the Rave base url to use for API requests based on the mode.
   *
   * @return string
   *   The Rave Base URL.
   */
  public function getBaseUrl();

  /**
   * Verifies a previous transaction from the Rave payment gateway.
   *
   * @param string $referenceNumber
   *   This should be the reference number of the transaction
   *   you want to verify.
   *
   * @return array
   *   Array contains Boolean value if transaction succeeds or not,
   *   and an array of Rave data for the transaction.
   */
  public function verifyTransaction($referenceNumber): array;

}

// @todo implement RefundInterface for refunding payments.
// @todo implement Webhook to receive payment notifications from Rave
