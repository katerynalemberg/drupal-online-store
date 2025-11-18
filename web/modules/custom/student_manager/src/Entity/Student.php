<?php

declare(strict_types=1);

namespace Drupal\student_manager\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\student_manager\Form\StudentForm;
use Drupal\student_manager\StudentInterface;
use Drupal\student_manager\StudentListBuilder;
use Drupal\views\EntityViewsData;

/**
 * Defines the student entity class.
 */
#[ContentEntityType(
  id: 'student',
  label: new TranslatableMarkup('Student'),
  label_collection: new TranslatableMarkup('Students'),
  label_singular: new TranslatableMarkup('student'),
  label_plural: new TranslatableMarkup('students'),
  entity_keys: [
    'id' => 'id',
    'label' => 'id',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => StudentListBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'add' => StudentForm::class,
      'edit' => StudentForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/student',
    'add-form' => '/student/add',
    'canonical' => '/student/{student}',
    'edit-form' => '/student/{student}/edit',
    'delete-form' => '/student/{student}/delete',
    'delete-multiple-form' => '/admin/content/student/delete-multiple',
  ],
  admin_permission: 'administer student',
  base_table: 'student',
  label_count: [
    'singular' => '@count students',
    'plural' => '@count students',
  ],
  field_ui_base_route: 'entity.student.settings',
)]
class Student extends ContentEntityBase implements StudentInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setRequired(TRUE)
      ->setLabel(new TranslatableMarkup('User'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_courses'] = BaseFieldDefinition::create('entity_reference_revisions')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'paragraph')
      ->setSetting('handler_settings', [
        'target_bundles' => ['activated_course' => 'activated_course'],
      ])
      ->setLabel(new TranslatableMarkup('User courses'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ]);

    return $fields;
  }

}
