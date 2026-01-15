<?php

namespace Drupal\news_paywall\EventSubscriber;

use Drupal\commerce_payment\Event\PaymentEvent;
use Drupal\commerce_payment\Event\PaymentEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserStorageInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Subscribes to commerce payment events to grant entitlements.
 *
 * Whenever a payment transitions to a completed or captured state, this
 * subscriber grants the purchasing user the 'subscriber' role. This simple
 * implementation can be extended to record additional entitlement
 * information (e.g. license expiry dates or subscription IDs).
 */
class PaymentSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The current user proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new PaymentSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\user\UserStorageInterface $userStorage
   *   The user storage handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user proxy.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, UserStorageInterface $userStorage, AccountProxyInterface $currentUser) {
    $this->entityTypeManager = $entityTypeManager;
    $this->userStorage = $userStorage;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Listen to payment events to capture when a payment is completed.
    // Transition events are emitted when using the default and manual
    // workflows. We also subscribe to the insert event to handle custom
    // payments created programmatically in a completed state (as done by the
    // fake payment gateway order subscriber).
    return [
      PaymentEvents::PAYMENT_INSERT => 'onPaymentInserted',
      PaymentEvents::PAYMENT_UPDATE => 'onPaymentUpdated',
    ];
  }

  /**
   * Handles payment insertion events.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   */
  public function onPaymentInserted(PaymentEvent $event): void {
    $this->grantSubscriberRoleIfCompleted($event);
  }

  /**
   * Handles payment update events.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   */
  public function onPaymentUpdated(PaymentEvent $event): void {
    $this->grantSubscriberRoleIfCompleted($event);
  }

  /**
   * Grants 'subscriber' role to the purchasing user if payment is completed.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   */
  private function grantSubscriberRoleIfCompleted(PaymentEvent $event): void {
    $payment = $event->getPayment();
    if ($payment->getState()->value !== 'completed') {
      return;
    }

    $order = $payment->getOrder();
    if (!$order) {
      return;
    }

    $customer = $order->getCustomer();
    if (!$customer || $customer->isAnonymous()) {
      return;
    }

    if (!$customer->hasRole('subscriber')) {
      $customer->addRole('subscriber');
      $customer->save();
    }
  }

}
