<?php

namespace Drupal\Tests\utils\Unit;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\utils\Services\ProductPurchaseChecker;

/**
 * @coversDefaultClass \Drupal\utils\Services\ProductPurchaseChecker
 * @group utils
 */
class ProductPurchaseCheckerTest extends UnitTestCase {

  /**
   * The product purchase checker service.
   *
   * @var \Drupal\utils\Services\ProductPurchaseChecker
   */
  protected ProductPurchaseChecker $checker;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $user = $this->createMock(AccountInterface::class);
    $time = $this->createMock(TimeInterface::class);

    $this->checker = new ProductPurchaseChecker(
      $entityTypeManager,
      $user,
      $routeMatch,
      $time,
    );
  }

  /**
   * @covers ::checkProductType
   */
  public function testCheckProductType() {
    // Case 1: No purchased entity.
    $order_item1 = $this->createMock(OrderItemInterface::class);
    $order_item1->method('getPurchasedEntity')->willReturn(NULL);
    $this->assertFalse($this->checker->checkProductType($order_item1, 'membership'));

    // Case 2: Wrong entity type.
    $entity2 = $this->createMock(EntityInterface::class);
    $entity2->method('getEntityTypeId')->willReturn('node');
    $entity2->method('bundle')->willReturn('article');

    $order_item2 = $this->createMock(OrderItemInterface::class);
    $order_item2->method('getPurchasedEntity')->willReturn($entity2);
    $this->assertFalse($this->checker->checkProductType($order_item2, 'article'));

    // Case 3: Wrong bundle.
    $entity3 = $this->createMock(ProductVariationInterface::class);
    $entity3->method('getEntityTypeId')->willReturn('commerce_product_variation');
    $entity3->method('bundle')->willReturn('membership');

    $order_item3 = $this->createMock(OrderItemInterface::class);
    $order_item3->method('getPurchasedEntity')->willReturn($entity3);
    $this->assertFalse($this->checker->checkProductType($order_item3, 'book'));

    // Case 4: Correct entity.
    $entity4 = $this->createMock(ProductVariationInterface::class);
    $entity4->method('getEntityTypeId')->willReturn('commerce_product_variation');
    $entity4->method('bundle')->willReturn('membership');

    $order_item4 = $this->createMock(OrderItemInterface::class);
    $order_item4->method('getPurchasedEntity')->willReturn($entity4);
    $this->assertTrue($this->checker->checkProductType($order_item4, 'membership'));
  }

}
