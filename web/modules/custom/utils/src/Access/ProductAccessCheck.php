<?php

declare(strict_types=1);

namespace Drupal\utils\Access;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks if product is online course before config form access.
 */
final class ProductAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(
    ProductInterface $commerce_product,
    AccountInterface $account,
  ): AccessResult {
    if ($commerce_product->bundle() === 'online_course' &&
        $account->hasPermission('administer commerce_product')
    ) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
