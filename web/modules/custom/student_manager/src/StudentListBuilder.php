<?php

declare(strict_types=1);

namespace Drupal\student_manager;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\student_manager\Entity\Student;
use Drupal\user\UserInterface;

/**
 * Provides a list controller for the student entity type.
 */
final class StudentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['counter'] = $this->t('#');
    $header['user'] = $this->t('User');
    $header['course'] = $this->t('Course');
    $header['duration'] = $this->t('Duration');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $rows = [];
    $counter = 0;

    foreach ($this->getEntityIds() as $id) {
      $entity = $this->getStorage()->load($id);
      if (!($entity instanceof Student)) {
        break;
      }
      $user = $entity->get('user')->entity;
      $courses = $entity->get('user_courses')->referencedEntities();

      if (!($user instanceof UserInterface) ||
          empty($courses)
      ) {
        continue;
      }

      $user = Link::fromTextAndUrl($user->getDisplayName(),
        Url::fromRoute('entity.user.canonical',
          [
            'user' => $user->id(),
          ]
        )
      );

      foreach ($courses as $course) {
        $duration = $course->get('field_duration')->value;
        $product = $course->get('field_course')->entity;
        $rows[] = [
          'counter' => ++$counter,
          'user' => $user,
          'course' => Link::fromTextAndUrl(
            $product->label(),
            Url::fromRoute('entity.commerce_product.canonical',
              [
                'commerce_product' => $product->id(),
              ]
            )
          ),
          'duration' => $duration,
          'operations' => [
            'data' => $this->buildOperations($entity),
          ],
        ];
      }
    }

    return [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $rows,
      '#empty' => $this->t('No students found.'),
    ];
  }

}
