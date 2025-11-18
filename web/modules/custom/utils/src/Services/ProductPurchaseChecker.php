<?php

declare(strict_types=1);

namespace Drupal\utils\Services;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Component\Datetime\Time;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Service for checking purchased items.
 */
final class ProductPurchaseChecker {

  /**
   * Constructs a ProductPurchaseChecker object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountInterface $currentUser,
    private readonly RouteMatchInterface $routeMatch,
    private readonly Time $time,
  ) {}

  /**
   * Return purchased orders.
   */
  public function getOrders(
    bool $all_orders = FALSE,
  ): array {
    $user_id = $this->currentUser->id();
    $user = $this->entityTypeManager
      ->getStorage('user')
      ->load($user_id);

    if (!$user) {
      return [];
    }

    if ($all_orders) {
      return $this->entityTypeManager
        ->getStorage('commerce_order')
        ->loadByProperties(
          [
            'state' => 'completed',
          ]
        );
    }

    return $this->entityTypeManager
      ->getStorage('commerce_order')
      ->loadByProperties(
        [
          'uid' => $user->id(),
          'state' => 'completed',
        ]
      );
  }

  /**
   * Check if current product is purchased.
   */
  public function checkIfProductPurchased(
    array $orders,
  ): bool {
    $product = $this->routeMatch
      ->getParameter('commerce_product');
    $storage = $this->entityTypeManager
      ->getStorage('paragraph');

    if (!($product instanceof ProductInterface)) {
      return FALSE;
    }
    $activated_course = $storage->loadByProperties([
      'type' => 'activated_course',
      'field_course' => $product->id(),
      'field_user' => $this->currentUser->id(),
    ]);
    $activated_course = reset($activated_course);
    $duration = 0;
    if ($activated_course instanceof ProductInterface) {
      $duration = strtotime($activated_course->get('field_duration')->value);
    }
    $variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');
    $variations = $variation_storage->loadByProperties(['product_id' => $product->id()]);
    $variation = reset($variations);

    if (!($variation instanceof ProductVariationInterface)) {
      return FALSE;
    }

    foreach ($orders as $order) {
      if ($order instanceof OrderInterface) {
        $items = $order->getItems();
        foreach ($items as $order_item) {
          if (
            $order_item->getPurchasedEntityId() === $variation->id()
            && (!$activated_course || $duration > $this->time->getCurrentTime())
          ) {
            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * Checks if product is of specific product type.
   */
  public function checkProductType(
    OrderItemInterface $order_item,
    string $product_type,
  ): bool {
    $purchasable_entity = $order_item->getPurchasedEntity();
    if ($purchasable_entity) {
      return $purchasable_entity->getEntityTypeId() === 'commerce_product_variation'
        && $purchasable_entity->bundle() === $product_type;
    }
    return FALSE;
  }

}
