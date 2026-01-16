<?php

namespace Drupal\news_paywall\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to order paid events to grant entitlements.
 *
 * Whenever an order is marked as paid, this subscriber grants the purchasing
 * user the 'subscriber' role. This simple implementation can be extended to
 * record additional entitlement information (e.g. license expiry dates or
 * subscription IDs).
 */
final class OrderPaidSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      OrderEvents::ORDER_PAID => 'onOrderPaid',
    ];
  }

  /**
   * Handles order paid events to grant the 'subscriber' role.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The order event.
   */
  public function onOrderPaid(OrderEvent $event): void {

    $order = $event->getOrder();
    $customer = $order->getCustomer();

    if (!$customer instanceof UserInterface || $customer->isAnonymous()) {
      return;
    }

    if (!$customer->hasRole('subscriber')) {
      $customer->addRole('subscriber');
      $customer->save();
    }
  }

}
