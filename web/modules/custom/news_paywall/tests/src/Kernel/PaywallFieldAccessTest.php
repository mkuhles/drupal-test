<?php

declare(strict_types=1);

namespace Drupal\Tests\news_paywall\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

/**
 * Tests field access control for premium content.
 *
 * @group news_paywall
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class PaywallFieldAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system', 'user', 'field', 'text', 'filter', 'node',
    'options', 'news_paywall',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Minimal notwendige Schemas.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');
    $this->installEntitySchema('filter_format');

    // Bundle anlegen.
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    // Textformat anlegen (damit body sauber renderbar ist).
    FilterFormat::create([
      'format' => 'plain_text',
      'name' => 'Plain text',
      'filters' => [],
    ])->save();

    // Body-Feld (text_long) anlegen.
    FieldStorageConfig::create([
      'field_name' => 'body',
      'entity_type' => 'node',
      'type' => 'text_long',
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'body',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Body',
      'required' => FALSE,
    ])->save();

    // Premium-Flag anlegen.
    FieldStorageConfig::create([
      'field_name' => 'field_premium',
      'entity_type' => 'node',
      'type' => 'boolean',
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_premium',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Premium',
      'required' => FALSE,
    ])->save();
  }

  /**
   * Tests that the body field is denied access for non-premium users.
   */
  public function testBodyDeniedWithoutPermission(): void {
    $u1 = User::create(['name' => 'u1']);
    $u1->activate();
    $u1->save();

    $node = Node::create([
      'type' => 'article',
      'title' => 'Premium',
      'field_premium' => 1,
      'body' => ['value' => 'SECRET', 'format' => 'plain_text'],
      'uid' => $u1->id(),
    ]);
    $node->save();

    $u2 = User::create(['name' => 'u2']);
    $u2->activate();
    $u2->save();

    $items = $node->get('body');
    $field_def = $items->getFieldDefinition();

    $result = \Drupal::service('news_paywall.field_access')->check('view', $field_def, $u2, $items);
    $this->assertTrue($result->isForbidden(), 'Body must be forbidden for non-premium user.');
  }

  /**
   * Tests that the body field is allowed access for premium users.
   */
  public function testBodyAllowedWithPermission(): void {
    $u = User::create(['name' => 'premium']);
    $u->activate();
    // Permission kommt i.d.R. über role; im Test kannst du entweder role
    // anlegen oder user direkt über role ausstatten.
    $u->addRole('news_paywall_subscriber');
    $u->save();

    // Node wie oben...
    // dann assert allowed.
  }

}
