<?php

namespace Drupal\Tests\utils\Functional;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * @coversDefaultClass \Drupal\utils\Services\ProductPurchaseChecker
 * @group utils
 */
class ProductAccessCheckTest extends BrowserTestBase {

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $user;

  /**
   * The default store for test.
   */
  protected StoreInterface $store;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'utils',
    'commerce_product',
    'system',
    'user',
    'commerce_store',
    'commerce_price',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer commerce_product',
      'access content',
    ]);

    $this->store = Store::create([
      'type' => 'online',
      'name' => 'My Test Store',
      'mail' => 'test@example.com',
      'currency' => 'USD',
    ]);
    $this->drupalLogin($this->user);
    $this->store->save();
  }

  /**
   * Test if config form is accessible for 'online_course'.
   */
  public function testCourseAccess() {
    $variation = ProductVariation::create([
      'type' => 'online_course',
      'sku' => $this->randomMachineName(),
      'price' => new Price('50', 'USD'),
    ]);
    $variation->save();

    $product = Product::create([
      'type' => 'online_course',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation],
    ]);
    $product->save();

    $this->drupalGet('product/' . $product->id() . '/config');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test if config form is not accessible for 'book'.
   */
  public function testBookAccess() {
    $variation = ProductVariation::create([
      'type' => 'ebook',
      'sku' => $this->randomMachineName(),
      'price' => new Price('10', 'USD'),
    ]);
    $variation->save();

    $product = Product::create([
      'type' => 'book',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation],
    ]);
    $product->save();

    $this->drupalGet('product/' . $product->id() . '/config');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test if config form is not accessible for 'membership'.
   */
  public function testMembershipAccess() {
    $variation = ProductVariation::create([
      'type' => 'membership',
      'sku' => $this->randomMachineName(),
      'price' => new Price('10', 'USD'),
    ]);
    $variation->save();

    $product = Product::create([
      'type' => 'membership',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation],
    ]);
    $product->save();

    $this->drupalGet('product/' . $product->id() . '/config');
    $this->assertSession()->statusCodeEquals(403);
  }

}
