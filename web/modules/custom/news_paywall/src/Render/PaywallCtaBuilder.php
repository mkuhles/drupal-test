<?php

namespace Drupal\news_paywall\Render;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\news_paywall\Service\EntitlementChecker;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Builds call-to-action render arrays for paywalled content.
 *
 * This class encapsulates the logic for constructing the CTA box that is
 * displayed to users who attempt to access premium articles without the
 * necessary entitlements.
 */
final class PaywallCtaBuilder {

  public function __construct(
    private readonly EntitlementChecker $entitlementChecker,
    private readonly RequestStack $requestStack,
  ) {}

  /**
   * Determines if the CTA should be displayed for the given node and view mode.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node being viewed.
   * @param string $view_mode
   *   The view mode being used.
   *
   * @return bool
   *   TRUE if the CTA should be displayed, FALSE otherwise.
   */
  public function applies(NodeInterface $node, string $view_mode): bool {
    if ($node->bundle() !== 'article' || $view_mode !== 'full') {
      return FALSE;
    }
    if (!$node->hasField('field_premium') || $node->get('field_premium')->isEmpty() || !(bool) $node->get('field_premium')->value) {
      return FALSE;
    }
    return !$this->entitlementChecker->currentUserHasPremiumAccess();
  }

  /**
   * Builds the CTA render array for the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node being viewed.
   *
   * @return array
   *   A render array representing the CTA box.
   */
  public function build(NodeInterface $node): array {
    $subscribe_url = Url::fromUserInput('/product/news-abo');
    $subscribe_link = Link::fromTextAndUrl(new TranslatableMarkup('Jetzt Abo abschließen'), $subscribe_url)->toRenderable();
    $subscribe_link['#attributes']['class'][] = 'button';
    $subscribe_link['#attributes']['class'][] = 'button--primary';

    $destination = $this->requestStack->getCurrentRequest()?->getRequestUri() ?? $node->toUrl()->toString();
    $login_url = Url::fromRoute('user.login', [], ['query' => ['destination' => $destination]]);
    $login_link = Link::fromTextAndUrl(new TranslatableMarkup('Schon Subscriber? Einloggen'), $login_url)->toRenderable();

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['news-paywall__box']],
      'message' => [
        '#markup' => '<p><strong>Dieser Artikel ist Premium.</strong> Bitte schließe ein Abo ab, um den vollständigen Inhalt zu lesen.</p>',
      ],
      'actions' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['news-paywall__actions']],
        'subscribe' => $subscribe_link,
        'login' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['news-paywall__login']],
          'link' => $login_link,
        ],
      ],
      '#attached' => [
        'library' => ['news_paywall/global'],
      ],
    ];

    (new CacheableMetadata())
      ->addCacheContexts(['user.permissions'])
      ->addCacheableDependency($node)
      ->applyTo($build);

    return $build;
  }

}
