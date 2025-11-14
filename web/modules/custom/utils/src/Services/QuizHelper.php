<?php

declare(strict_types=1);

namespace Drupal\utils\Services;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\quiz\Entity\Quiz;

/**
 * Service to managing quiz results for current user.
 */
final class QuizHelper {

  /**
   * Constructs a QuizHelper object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountInterface $currentUser,
  ) {}

  /**
   * Check if quiz is passed.
   */
  public function isQuizPassed(
    ProductInterface $product,
  ): array {
    $quiz_id = $product->get('field_quiz')->target_id;

    $quiz = $this->entityTypeManager
      ->getStorage('quiz')
      ->loadByProperties([
        'qid' => $quiz_id,
      ]);
    $quiz = $quiz ? reset($quiz) : NULL;

    if ($quiz instanceof Quiz) {
      return [
        'quiz' => $quiz,
        'passed' => $quiz->isPassed($this->currentUser),
      ];
    }

    return [];
  }

}
