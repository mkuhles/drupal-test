<?php

namespace Drupal\news_paywall\EventSubscriber;

use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Auto-creates a completed payment for Manual Gateway orders.
 *
 * This allows ORDER_PAID to fire and entitlements to be granted.
 */
class OrderPlacedSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // order_default: transition "place" (draft -> completed)
      'commerce_order.place.post_transition' => 'onPlacePostTransition',
    ];
  }

  /**
   * Handles order placed events to create completed payment for Manual Gateway.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function onPlacePostTransition(WorkflowTransitionEvent $event): void {
    $order = $event->getEntity();

    // Defensive: only act on commerce orders.
    if (!$order instanceof OrderInterface) {
      return;
    }

    $balance = $order->getBalance();
    if ($balance->isZero()) {
      return;
    }

    $gateway_id = $this->configFactory->get('news_paywall.settings')->get('gateway_id') ?: 'news_paywall_manual';

    $gateway = $this->entityTypeManager->getStorage('commerce_payment_gateway')->load($gateway_id);
    if (!$gateway || !$gateway instanceof PaymentGatewayInterface) {
      $this->logger->error('Failed to load payment gateway.');
      return;
    }
    if ($gateway->getPluginId() !== 'manual') {
      return;
    }

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $existing = $payment_storage->loadByProperties(['order_id' => $order->id()]);
    if ($existing) {
      return;
    }

    $payment = $payment_storage->create([
      'type' => 'payment_default',
      'payment_gateway' => $gateway->id(),
      'order_id' => $order->id(),
      'remote_id' => 'manual_' . $order->id() . '_' . time(),
      'remote_state' => 'completed',
      'amount' => $balance,
      'state' => 'completed',
    ]);
    $payment->save();
  }

}
