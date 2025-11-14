<?php

namespace Drupal\utils\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;

/**
 * Resolves the price fetched from the product variation fields.
 */
class PriceResolver implements PriceResolverInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    $user_id = $this->currentUser->id();
    $user = $this->entityTypeManager->getStorage('user')->load($user_id);

    if ($user) {
      $orders = $this->entityTypeManager
        ->getStorage('commerce_order')
        ->loadByProperties(['uid' => $user->id(), 'state' => 'completed']);

      foreach ($orders as $order) {
        foreach ($order->toArray() as $order_item) {
          if ($this->isMembershipProduct($order_item)) {
            return $entity->getPrice()->multiply('0.90');
          }
        }
      }
    }

    return $entity->getPrice();
  }

  /**
   * Checks if the order item is a membership product.
   */
  protected function isMembershipProduct(OrderItemInterface $order_item) {
    $purchasable_entity = $order_item->getPurchasedEntity();
    if ($purchasable_entity) {
      return $purchasable_entity->getEntityTypeId() === 'commerce_product_variation'
        && $purchasable_entity->bundle() === 'membership';
    }
    return FALSE;
  }

}
