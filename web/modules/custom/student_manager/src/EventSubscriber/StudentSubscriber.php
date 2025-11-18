<?php

declare(strict_types=1);

namespace Drupal\student_manager\EventSubscriber;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Component\Datetime\Time;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\student_manager\Entity\Student;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to Course activation events.
 */
final class StudentSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $currentUser,
    private readonly Time $time,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Creates Student content when course is activated.
   */
  public function onFlag(FlaggingEvent $event): void {
    $flagging = $event->getFlagging();
    $entity_nid = $flagging->getFlaggable()->id();
    $course = $this->getCourse($entity_nid);

    if ($course) {
      $this->getStudent($course);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[FlagEvents::ENTITY_FLAGGED][] = ['onFlag', -200];

    return $events;
  }

  /**
   * Creates reference to a purchased course.
   */
  private function getCourse(
    string $entity_nid,
  ): ?EntityInterface {
    $product = $this->entityTypeManager
      ->getStorage('commerce_product')
      ->loadByProperties(
        [
          'product_id' => $entity_nid,
        ],
      );
    $product = reset($product);

    if (!($product instanceof ProductInterface)) {
      return NULL;
    }
    $storage = $this->entityTypeManager
      ->getStorage('paragraph');

    $course = $storage
      ->loadByProperties([
        'type' => 'activated_course',
        'field_course' => $product->id(),
        'field_user' => $this->currentUser->id(),
      ]);
    $course = reset($course);

    if (!($course instanceof EntityInterface)) {

      $config = $this->configFactory->getEditable('utils.course_settings');
      $course_id = $product->getDefaultVariation()?->getProductId();
      $schedule = $config->get("product_$course_id.schedule") ?? 0;
      if (!$schedule) {
        $config->set("product_$course_id.schedule", [
          'days' => '30',
          'hours' => '0',
          'minutes' => '0',
          'time' => 30 * 86400,
        ])
          ->save();
        $schedule = $config->get("product_$course_id.schedule");
      }
      $duration = $this->time->getCurrentTime() + $schedule['time'];

      $course = $this->entityTypeManager
        ->getStorage('paragraph')
        ->create([
          'type' => 'activated_course',
          'field_course' => [
            ['target_id' => $product->id()],
          ],
          'field_duration' => date('Y-m-d H:i', $duration),
          'field_user' => $this->currentUser->id(),
        ]);
      $course->save();
    }

    return $course;
  }

  /**
   * Creates reference to a student.
   */
  private function getStudent(
    EntityInterface $course,
  ): void {
    $student_storage = $this->entityTypeManager
      ->getStorage('student');
    $student = $student_storage->loadByProperties([
      'user' => $this->currentUser->id(),
    ]);
    $student = reset($student);

    // If student has already purchased course.
    if ($student instanceof Student &&
        $course instanceof Paragraph
    ) {
      $existing_courses = $student->get('user_courses')
        ->referencedEntities();
      $exists = FALSE;
      foreach ($existing_courses as $existing_course) {
        if ($existing_course->id() === $course->id()) {
          $exists = TRUE;
          break;
        }
      }

      if (!$exists) {
        $values = $student->get('user_courses')->getValue();
        $values[] = [
          'target_id' => $course->id(),
          'target_revision_id' => $course->getRevisionId(),
        ];
        $student->set('user_courses', $values);
        $student->save();
      }
      return;
    }

    $student_storage->create(
      [
        'user' => $this->currentUser->id(),
        'user_courses' => [$course],
        'status' => TRUE,
      ]
    )->save();
  }

}
