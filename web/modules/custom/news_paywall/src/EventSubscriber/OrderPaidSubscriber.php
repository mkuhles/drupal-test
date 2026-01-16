<?php

namespace Drupal\news_paywall\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Subscribes to order paid events to grant entitlements.
 *
 * Whenever an order is marked as paid, this subscriber grants the purchasing
 * user the 'news_paywall_subscriber' role. This simple implementation can be
 * extended to record additional entitlement information (e.g. license expiry
 * dates or subscription IDs).
 */
final class OrderPaidSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      OrderEvents::ORDER_PAID => 'onOrderPaid',
    ];
  }

  /**
   * Handles order paid events to grant the 'news_paywall_subscriber' role.
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

    $role_id = $this->configFactory
      ->get('news_paywall.settings')
      ->get('grant_role_id') ?: 'news_paywall_subscriber';
    if (!$role_id) {
      $this->logger->error('No role ID configured to grant upon order payment.');
      return;
    }
    if (!$customer->hasRole($role_id)) {
      $customer->addRole($role_id);
      $customer->save();
    }
  }

}
