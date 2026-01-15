<?php

namespace Drupal\news_paywall\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for subscription redirection.
 */
final class SubscribeController extends ControllerBase {

  /**
   * Redirects users to the subscription product page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the subscription product page.
   */
  public function redirect($route_name, array $route_parameters = [], array $options = [], $status = 302): RedirectResponse {
    // MVP: später konfigurierbar. Für jetzt: URL zu deinem "News Abo" Produkt.
    // Beispiel:
    return new RedirectResponse('/product/news-abo');
  }

}
