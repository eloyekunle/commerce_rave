<?php

namespace Drupal\commerce_rave\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "rave",
 *   label = @Translation("Rave"),
 *   display_label = @Translation("Rave"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_rave\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 *   modes= {
 *     "staging" = "Staging",
 *     "live" = "Live"
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   }
 * )
 */
class Rave extends OffsitePaymentGatewayBase implements RaveInterface {

  protected $verifyCount = 0;

  const RAVE_LIVE_URL = 'https://api.ravepay.co';

  const RAVE_STAGING_URL = 'http://flw-pms-dev.eu-west-1.elasticbeanstalk.com';

  /**
   * {@inheritdoc}
   */
  public function getSecretKey() {
    return $this->configuration['secret_key'];
  }

  public function getPublicKey() {
    return $this->configuration['public_key'];
  }

  public function getPaymentFlow() {
    return $this->configuration['payment_flow'];
  }

  public function getPayButtonText() {
    return $this->configuration['pay_button_text'];
  }

  public function getTransactionReferencePrefix() {
    return $this->configuration['txref_prefix'];
  }

  public function getBaseUrl() {
    if ($this->getMode() == 'live') {
      return self::RAVE_LIVE_URL;
    } else {
      return self::RAVE_STAGING_URL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'public_key' => '',
        'secret_key' => '',
        'payment_flow' => 'iframe',
        'pay_button_text' => '',
        'txref_prefix' => 'rave'
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public key'),
      '#description' => $this->t('Enter your Rave Public Key.'),
      '#default_value' => $this->getPublicKey(),
      '#required' => TRUE
    ];

    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key'),
      '#description' => $this->t('Enter your Rave Secret Key.'),
      '#default_value' => $this->getSecretKey(),
      '#required' => TRUE
    ];

    $form['payment_flow'] = [
      '#type' => 'radios',
      '#title' => t('Payment flow'),
      '#options' => ['iframe' => t('iFrame'), 'hosted_payment_page' => t('Hosted Payment Page')],
      '#default_value' => $this->getPaymentFlow(),
      '#required' => FALSE
    ];

    $form['pay_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pay button text'),
      '#description' => $this->t('(Optional) Enter a custom pay button text.'),
      '#default_value' => $this->getPayButtonText(),
      '#required' => FALSE
    ];

    $form['txref_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Transaction Reference Prefix'),
      '#description' => $this->t('(Optional) Enter a custom transaction reference prefix.'),
      '#default_value' => $this->getTransactionReferencePrefix(),
      '#required' => FALSE
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['public_key'] = $values['public_key'];
      $this->configuration['secret_key'] = $values['secret_key'];
      $this->configuration['payment_flow'] = $values['payment_flow'];
      $this->configuration['pay_button_text'] = $values['pay_button_text'];
      $this->configuration['txref_prefix'] = $values['txref_prefix'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $logger = \Drupal::logger('commerce_rave');

    $response = urldecode($request->query->get('resp'));

    $logger->info('Rave returned: ' . $response);

    $response = json_decode($response, TRUE);

    $merchantTransactionReference = $response['tx']['txRef'];
    $raveTransactionReference = $response['tx']['flwRef'];

    if ($raveTransactionReference) {
      $verifyTransaction = $this->verifyTransaction($raveTransactionReference);
      $transactionStatus = $verifyTransaction['status'];

      if ($transactionStatus) {
        $transactionData = $verifyTransaction['data'];
        $chargedAmount = $transactionData['charged_amount'];
        $orderAmount = $order->getTotalPrice()->getNumber();

        // Verify charged amount == Order Amount
        if ($orderAmount == $chargedAmount) {
          $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
          $flwRef = $transactionData['flw_ref'];
          $status = $transactionData['status'];

          $payment = $payment_storage->create([
            'state' => 'authorization',
            'amount' => $order->getTotalPrice(),
            'payment_gateway' => $this->entityId,
            'order_id' => $order->id(),
            'remote_id' => $flwRef,
            'remote_state' => $status,
          ]);

          $logger->info('Saving Payment information. Transaction reference: ' . $merchantTransactionReference);

          $payment->save();
          drupal_set_message('Payment was processed');

          $logger->info('Payment information saved successfully. Transaction reference: ' . $merchantTransactionReference);
        } else {
          $logger->warning('Charged Amount is: ' . $chargedAmount . ' while Order Amount: ' . $orderAmount);
          throw new PaymentGatewayException('Charged amount not equal to order amount.');
        }
      } else {
        throw new PaymentGatewayException('Payment was not successful.');
      }
    } else {
      throw new PaymentGatewayException('Cannot find Transaction Reference in request.');
    }
  }

  /**
   * Verifies a previous transaction from the Rave payment gateway
   * @param string $referenceNumber This should be the reference number of the transaction you want to verify.
   * @return object
   * */
  public function verifyTransaction($referenceNumber): array {
    $this->verifyCount++;
    $logger = \Drupal::logger('commerce_rave');

    $logger->notice('Verifying Transaction: ' . $referenceNumber);

    $data = [
      'flw_ref' => $referenceNumber,
      'SECKEY' => $this->getSecretKey(),
      'normalize' => '1'
    ];
    $url = $this->getBaseUrl() . '/flwv3-pug/getpaidx/api/verify';

    $client = new Client();
    try {
      $apiRequest = $client->request('POST', $url, ['json' => $data]);
    } catch (TransferException $e) {
      $logger->error('An error occurred while verifying transaction: ' . $referenceNumber . '. Error: ' . $e->getMessage());
      return [
        'status' => FALSE,
        'data' => $e->getMessage()
      ];
    }

    $response = json_decode((string)$apiRequest->getBody(), TRUE);

    // check the status is successful
    if ($response && $response['status'] === "success") {
      if ($response && $response['data'] && $response['data']['status'] === "successful") {
        $logger->notice('Verified a successful transaction. Transaction Reference: ' . $referenceNumber);
        return [
          'status' => TRUE,
          'data' => $response['data']
        ];
      } elseif ($response && $response['data'] && $response['data']['status'] === "failed") {
        $logger->warning('Verified a failed transaction. Transaction Reference: ' . $referenceNumber . '. Response: ' . json_encode($response));
        return [
          'status' => FALSE,
          'data' => $response['data']
        ];
      } else {
        // Handled an undecisive transaction. Probably timed out.
        $logger->warning('Verified an undecisive transaction: ' . json_encode($response) . 'Transaction Reference: ' . $referenceNumber);
        if ($this->verifyCount > 4) {
          // We couldn't get a status in 5 requeries.
          $logger->warning('Transaction verification timed out. Transaction Reference: ' . $referenceNumber);
          return [
            'status' => FALSE,
            'data' => $response
          ];
        } else {
          $logger->notice('Delaying next verification for 3 seconds. Transaction Reference: ' . $referenceNumber);
          sleep(3);
          $logger->notice('Now retrying verification. Transaction Reference: ' . $referenceNumber);
          $this->verifyTransaction($referenceNumber);
        }
      }
    } else {
      $logger->error('Verify call returned error: ' . json_encode($response) . 'Transaction Reference: ' . $referenceNumber);
      return [
        'status' => FALSE,
        'data' => $response
      ];
    }

    return [
      'status' => FALSE,
      'data' => $response
    ];
  }
}
