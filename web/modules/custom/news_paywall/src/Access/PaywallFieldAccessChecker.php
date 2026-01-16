<?php

namespace Drupal\news_paywall\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\news_paywall\Service\EntitlementChecker;
use Drupal\node\NodeInterface;

/**
 * Checks field-level access for paywalled content.
 *
 * This class encapsulates the logic for determining access to specific
 * fields on premium articles. It is used by the
 * news_paywall_entity_field_access() hook implementation to centralise
 * the access logic.
 */
final class PaywallFieldAccessChecker {

  public function __construct(
    private readonly EntitlementChecker $entitlementChecker,
  ) {}

  /**
   * Checks access to a field on a potentially premium article.
   *
   * @param string $operation
   *   The operation being performed (e.g. 'view', 'edit').
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account performing the operation.
   * @param \Drupal\Core\Field\FieldItemListInterface|null $items
   *   (optional) The field items being accessed.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function check(string $operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items): AccessResult {
    if ($operation !== 'view' || !$items) {
      return AccessResult::neutral();
    }

    $entity = $items->getEntity();
    if (!$entity instanceof NodeInterface || $entity->bundle() !== 'article') {
      return AccessResult::neutral();
    }

    if (!$entity->hasField('field_premium') || $entity->get('field_premium')->isEmpty() || !(bool) $entity->get('field_premium')->value) {
      return AccessResult::neutral();
    }

    // später konfigurierbar (Option B)
    $protected_fields = ['body'];
    if (!in_array($field_definition->getName(), $protected_fields, TRUE)) {
      return AccessResult::neutral();
    }

    if ($this->entitlementChecker->hasPremiumAccess($account)) {
      return AccessResult::allowed()
        ->cachePerPermissions()
        ->addCacheableDependency($entity);
    }

    return AccessResult::forbidden()
      ->cachePerPermissions()
      ->addCacheableDependency($entity);
  }

}
