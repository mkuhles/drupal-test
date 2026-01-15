<?php

namespace Drupal\news_paywall\Service;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides entitlement checks for premium content.
 *
 * This service encapsulates the logic used to determine if a given user
 * is entitled to view premium content. Entitlements can come from user
 * roles, permissions or custom entities such as licenses or subscriptions.
 */
class EntitlementChecker {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new EntitlementChecker object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * Determines if a user has access to premium content.
   *
   * This method centralises the business rules for paywall access. Right now
   * it checks if the user has a specific permission or a role. If you later
   * integrate with Commerce License or a custom subscription entity,
   * additional checks can be added here.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) The user account to check. Defaults to the current user.
   *
   * @return bool
   *   TRUE if the user has premium access, FALSE otherwise.
   */
  public function currentUserHasPremiumAccess(?AccountProxyInterface $account = NULL) {
    $account = $account ?: $this->currentUser;

    // Users with the 'access premium content' permission always have access.
    if ($account->hasPermission('access premium content')) {
      return TRUE;
    }

    // Check for commerce licenses or entitlements here in the future. For
    // example, you might load all active licenses for the user and verify
    // whether any of them entitle the user to this content.
    return FALSE;
  }

}
