<?php

namespace Drupal\commerce_rave\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  protected $integrityHash;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    // We're adding 0 to remove trailing 0s.
    // Check https://flutterwavedevelopers.readme.io/docs/checksum#section-rule-of-thumb
    // for details.
    $payment_amount = $payment->getAmount()->getNumber() + 0;

    /** @var \Drupal\commerce_rave\Plugin\Commerce\PaymentGateway\RaveInterface $plugin */
    $plugin = $payment->getPaymentGateway()->getPlugin();
    $gateway_mode = $plugin->getMode();
    $payment_flow = $plugin->getPaymentFlow();
    $order = $payment->getOrder();
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billingAddress */
    $billingAddress = $order->getBillingProfile()->get('address')->first();

    if ($gateway_mode == 'live') {
      $form['#attached']['library'][] = 'commerce_rave/rave_live';
    }
    else {
      $form['#attached']['library'][] = 'commerce_rave/rave_staging';
    }

    $form['#attached']['library'][] = 'commerce_rave/rave';

    $options = [
      "PBFPubKey" => $plugin->getPublicKey(),
      "amount" => $payment_amount,
      "customer_email" => $order->getEmail(),
      "customer_firstname" => $billingAddress->getGivenName(),
      "customer_lastname" => $billingAddress->getFamilyName(),
      "custom_logo" => Url::fromUri('internal:' . theme_get_setting('logo.url'), ['absolute' => TRUE])
        ->toString(),
      "txref" => $plugin->getTransactionReferencePrefix() . '-' . $payment->getOrderId(),
      "payment_method" => 'both',
      "country" => $billingAddress->getCountryCode(),
      "currency" => $payment->getAmount()->getCurrencyCode(),
      "custom_title" => \Drupal::config('system.site')->get('name'),
      "custom_description" => \Drupal::config('system.site')->get('slogan'),
      "pay_button_text" => $plugin->getPayButtonText(),
      "redirect_url" => $form['#return_url'],
    ];

    if ($payment_flow == 'hosted_payment_page') {
      $options = array_merge($options, ['hosted_payment' => 1]);
    }

    $form = $this->buildRedirectForm($form, $form_state, '', $options, '');

    $this->calculateChecksum($options);

    $options = array_merge($options, ['integrity_hash' => $this->integrityHash]);

    $form['#attached']['drupalSettings']['rave']['transactionData'] = json_encode($options);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRedirectForm(array $form, FormStateInterface $form_state, $redirect_url, array $data, $redirect_method = BasePaymentOffsiteForm::REDIRECT_GET) {
    if (array_key_exists('hosted_payment', $data) && $data['hosted_payment'] === 1) {
      $helpMessage = t('Please wait while you are redirected to the payment server. If nothing happens within 10 seconds, please click on the button below.');
    }
    else {
      $helpMessage = t('Please wait while the payment server loads. If nothing happens within 10 seconds, please click on the button below.');
    }

    $form['commerce_message'] = [
      '#markup' => '<div class="checkout-help">' . $helpMessage . '</div>',
      '#weight' => -10,
      // Plugin forms are embedded using #process, so it's too late to attach
      // another #process to $form itself, it must be on a sub-element.
      '#process' => [
        [get_class($this), 'processRedirectForm'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function processRedirectForm(array $element, FormStateInterface $form_state, array &$complete_form) {
    $complete_form['#attributes']['class'][] = 'payment-redirect-form';
    unset($element['#action']);
    // The form actions are hidden by default, but needed in this case.
    $complete_form['actions']['#access'] = TRUE;
    foreach (Element::children($complete_form['actions']) as $element_name) {
      $complete_form['actions'][$element_name]['#access'] = TRUE;
    }

    return $element;
  }

  /**
   * Calculate Checksum of Rave Payload.
   *
   * For more: https://flutterwavedevelopers.readme.io/docs/checksum.
   */
  protected function calculateChecksum(array $options) {
    ksort($options);

    $hashedPayload = '';

    foreach ($options as $key => $value) {
      $hashedPayload .= $value;
    }

    /** @var \Drupal\commerce_rave\Plugin\Commerce\PaymentGateway\RaveInterface $plugin */
    $plugin = $this->plugin;

    $completeHash = $hashedPayload . $plugin->getSecretKey();
    $hash = hash('sha256', $completeHash);

    $this->integrityHash = $hash;
  }

}
