<?php

namespace Drupal\news_payment_fake\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;

/**
 * Provides a fake on-site payment gateway for development and testing.
 *
 * This gateway performs no real API requests. When used at checkout it relies
 * on an accompanying event subscriber to create and complete the payment
 * automatically when the order is placed. Developers can use this gateway to
 * integrate and test paywall logic without depending on a live payment
 * provider.
 *
 * @CommercePaymentGateway(
 *   id = "news_fake",
 *   label = @Translation("Fake Payment Gateway"),
 *   display_label = @Translation("Fake Gateway"),
 *   modes = {
 *     "test" = @Translation("Test mode")
 *   },
 *   payment_type = "payment_default",
 *   credit_card_types = {},
 *   requires_billing_information = FALSE,
 * )
 */
class FakeGateway extends PaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

}
