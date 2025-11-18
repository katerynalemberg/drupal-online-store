<?php

declare(strict_types=1);

namespace Drupal\Tests\utils\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\utils\Services\ProductPurchaseChecker;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\utils\Services\ProductPurchaseChecker
 * @group utils
 */
class ProductPurchaseCheckerTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'utils',
    'commerce_product',
    'commerce_order',
    'user',
    'profile',
    'state_machine',
    'entity_reference_revisions',
    'commerce_store',
    'commerce_price',
    'commerce_number_pattern',
  ];

  /**
   * The product purchase checker service.
   *
   * @var \Drupal\utils\Services\ProductPurchaseChecker
   */
  protected ProductPurchaseChecker $checker;

  /**
   * A user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $user1;

  /**
   * A user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $user2;

  /**
   * A test product.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected ProductVariationInterface $variation;

  /**
   * A test product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected ProductInterface $product;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('user');
    $this->installEntitySchema('profile');
    $this->installConfig(['commerce_product', 'commerce_store', 'commerce_order', 'commerce_number_pattern']);

    // Create product type to make order.
    ProductType::create([
      'id' => 'membership',
      'label' => 'Membership',
      'variationType' => 'default',
    ])->save();

    // Create a users.
    $this->user1 = $this->createUser();
    $this->user2 = $this->createUser();

    $this->variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'member001',
      'price' => new Price('50.00', 'USD'),
    ]);

    $this->product = Product::create([
      'type' => 'membership',
      'title' => 'Membership',
      'variations' => [$this->variation],
    ]);

    $this->variation->save();
    $this->product->save();

    $entityTypeManager = $this->container->get('entity_type.manager');
    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $time = $this->createMock(TimeInterface::class);

    $this->checker = new ProductPurchaseChecker(
      $entityTypeManager,
      $this->user1,
      $routeMatch,
      $time,
    );
  }

  /**
   * Test count when function returns orders for current user.
   */
  public function testGetOrdersPerUser() {
    $membership_item = OrderItem::create([
      'type' => 'default',
      'purchased_entity' => $this->variation,
      'quantity' => 1,
    ]);
    $membership_order = Order::create([
      'type' => 'default',
      'uid' => $this->user1->id(),
      'state' => 'completed',
      'order_items' => [$membership_item],
      'store_id' => $this->store->id(),
    ]);

    $membership_item->save();
    $membership_order->save();

    $orders = $this->checker->getOrders();

    $this->assertCount(1, $orders, 'All completed orders are returned.');
  }

  /**
   * Test count when function returns orders for all users.
   */
  public function testGetAllOrders() {
    $membership_item = OrderItem::create([
      'type' => 'default',
      'purchased_entity' => $this->variation,
      'quantity' => 1,
    ]);

    $membership_order_1 = Order::create([
      'type' => 'default',
      'uid' => $this->user1->id(),
      'state' => 'completed',
      'order_items' => [$membership_item],
      'store_id' => $this->store->id(),
    ]);

    $membership_order_2 = Order::create([
      'type' => 'default',
      'uid' => $this->user1->id(),
      'state' => 'completed',
      'order_items' => [$membership_item],
      'store_id' => $this->store->id(),
    ]);

    $membership_item->save();
    $membership_order_1->save();
    $membership_order_2->save();

    $orders = $this->checker->getOrders(TRUE);

    $this->assertCount(2, $orders, 'All completed orders are returned.');
  }

}
