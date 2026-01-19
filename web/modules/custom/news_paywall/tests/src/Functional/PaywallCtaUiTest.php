<?php

namespace Drupal\Tests\news_paywall\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

/**
 * Tests the paywall CTA visibility based on user roles.
 *
 * @group news_paywall
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class PaywallCtaUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc} */
  protected $profile = 'standard';

  /**
   * {@inheritdoc} */
  protected static $modules = [
    'node', 'user', 'field', 'text', 'filter',
    'news_paywall',
  ];

  /**
   * {@inheritdoc} */
  protected $defaultTheme = 'olivero';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Content type sicherstellen.
    if (!NodeType::load('article')) {
      NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    }

    // field_premium anlegen, falls nicht vorhanden.
    if (!FieldStorageConfig::loadByName('node', 'field_premium')) {
      FieldStorageConfig::create([
        'field_name' => 'field_premium',
        'entity_type' => 'node',
        'type' => 'boolean',
        'cardinality' => 1,
        'settings' => [],
      ])->save();
    }

    if (!FieldConfig::loadByName('node', 'article', 'field_premium')) {
      FieldConfig::create([
        'field_name' => 'field_premium',
        'entity_type' => 'node',
        'bundle' => 'article',
        'label' => 'Premium',
        'required' => FALSE,
      ])->save();
    }
  }

  /**
   * Tests the paywall CTA is visible.
   *
   * Tests that the paywall CTA is visible to anonymous users on premium content
   * and hidden for users with the 'news_paywall_subscriber' role.
   */
  public function testCtaVisibleForAnonymous(): void {
    // Node anlegen, premium.
    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Premium',
      'field_premium' => 1,
      'body' => ['value' => 'SECRET', 'format' => 'plain_text'],
    ]);

    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementExists('css', '.news-paywall__box');
    $this->assertSession()->pageTextNotContains('SECRET');
  }

  /**
   * Tests the paywall CTA is hidden.
   *
   * Tests that the paywall CTA is hidden for users with the
   * 'news_paywall_subscriber' role.
   */
  public function testCtaHiddenForSubscriber(): void {
    $user = $this->drupalCreateUser();
    $user->addRole('news_paywall_subscriber');
    $user->save();

    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Premium 2',
      'field_premium' => 1,
      'body' => [
        'value' => 'SECRET2',
        'format' => 'plain_text',
      ],
      'status' => 1,
    ]);

    $this->drupalLogin($user);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementNotExists('css', '.news-paywall__box');
    $this->assertSession()->pageTextContains('SECRET');
  }

}
