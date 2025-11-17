<?php

declare(strict_types=1);

namespace Drupal\utils\Form;

use Drupal\commerce_product\Entity\Product;
use Drupal\Component\Datetime\Time;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure utils settings for this site.
 */
final class CourseSettingsForm extends ConfigFormBase {

  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Time $time,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
  ): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'utils_course_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['utils.course_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ?Product $commerce_product = NULL,
  ): array {
    $config = $this->config('utils.course_settings');
    if ($commerce_product) {
      $variation = $commerce_product->getDefaultVariation();
      $product_id = $variation->getProductId();
      $schedule = $config->get("product_$product_id.schedule");

      $form['days'] = [
        '#type' => 'number',
        '#title' => $this->t('Days:'),
        '#min' => '0',
        '#default_value' => $schedule['days'] ?? 30,
      ];
      $form['hours'] = [
        '#type' => 'number',
        '#title' => $this->t('Hours:'),
        '#min' => '0',
        '#max' => '24',
        '#default_value' => $schedule['hours'] ?? '0',
      ];
      $form['minutes'] = [
        '#type' => 'number',
        '#title' => $this->t('Minutes:'),
        '#min' => '0',
        '#max' => '60',
        '#default_value' => $schedule['minutes'] ?? '0',
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $commerce_product = $form_state->getBuildInfo()['args'][0];
    $variation = $commerce_product->getDefaultVariation();
    $product_id = $variation->getProductId();
    $now = $this->time->getCurrentTime();
    $timestamp =
      ($form_state->getValue('days') * 86400) +
      ($form_state->getValue('hours') * 3600) +
      ($form_state->getValue('minutes') * 60);

    if ($product_id) {
      // Update all activated courses duration when time is changed.
      $this->config('utils.course_settings')
        ->set("product_$product_id.schedule", [
          'days' => $form_state->getValue('days'),
          'hours' => $form_state->getValue('hours'),
          'minutes' => $form_state->getValue('minutes'),
          'time' => $timestamp,
        ])
        ->save();

      $storage = $this->entityTypeManager->getStorage('paragraph');
      $results = $storage->loadByProperties([
        'type' => 'activated_course',
        'field_course.target_id' => $product_id,
      ]);
      $datetime = new \DateTime();
      $datetime->setTimestamp($now + $timestamp);

      foreach ($results as $result) {
        if ($result instanceof Paragraph) {
          $result->set('field_duration', [
            'value' => $datetime->format('Y-m-d\TH:i:s'),
          ]);
          $result->save();
        }
      }
    }
    parent::submitForm($form, $form_state);
  }

}
