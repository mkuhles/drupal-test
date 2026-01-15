<?php

namespace Drupal\news_payment_fake\EventSubscriber;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to order events to create fake payments.
 */
final class OrderSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // Workflow transition event (State Machine).
      'commerce_order.place.post_transition' => 'onOrderPlaced',
    ];
  }

  /**
   * Handles order placement to create a fake payment.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function onOrderPlaced(WorkflowTransitionEvent $event): void {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    // OPTIONAL: Nur reagieren, wenn unser Fake-Gateway gewählt wurde.
    // Achtung: payment_gateway ist ein Base Field am Order, kann leer sein.
    if ($order->get('payment_gateway')->isEmpty()) {
      return;
    }
    $gateway = $order->get('payment_gateway')->entity;
    if (!$gateway || $gateway->getPluginId() !== 'news_fake') {
      return;
    }

    // Payment erstellen (completed).
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entityTypeManager
      ->getStorage('commerce_payment')
      ->create([
        'state' => 'completed',
        'amount' => new Price((string) $order->getTotalPrice()->getNumber(), $order->getTotalPrice()->getCurrencyCode()),
        'payment_gateway' => $gateway->id(),
        'order_id' => $order->id(),
        'remote_id' => 'fake-' . $order->id() . '-' . time(),
      ]);

    $payment->save();
  }

}
