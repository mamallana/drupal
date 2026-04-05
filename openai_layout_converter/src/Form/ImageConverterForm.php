// src/Form/ImageConverterForm.php
<?php

namespace Drupal\openai_layout_converter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\openai_layout_converter\Service\ImageConverterService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImageConverterForm extends FormBase {

  protected $imageConverterService;
  protected $messenger;

  public function __construct(ImageConverterService $converterService, MessengerInterface $messenger) {
    $this->imageConverterService = $converterService;
    $this->messenger = $messenger;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openai_layout_converter.converter'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openai_layout_converter_image_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['enctype'] = 'multipart/form-data';

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Image'),
      '#description' => $this->t('Upload an image to convert to a layout template. Supported formats: PNG, JPG, GIF, WebP'),
      '#upload_location' => 'public://openai-converter/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg gif webp'],
        'file_validate_size' => [5242880], // 5MB
      ],
      '#required' => TRUE,
    ];

    $form['analysis_depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Analysis Depth'),
      '#description' => $this->t('How detailed should the layout analysis be?'),
      '#options' => [
        'basic' => $this->t('Basic (colors, structure)'),
        'detailed' => $this->t('Detailed (includes typography, spacing)'),
        'comprehensive' => $this->t('Comprehensive (includes all details)'),
      ],
      '#default_value' => 'detailed',
    ];

    $form['generate_preview'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate HTML preview'),
      '#default_value' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Convert to Layout'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $file_id = $form_state->getValue('image')[0];
      $depth = $form_state->getValue('analysis_depth');
      $preview = $form_state->getValue('generate_preview');

      $result = $this->imageConverterService->convertImage($file_id, $depth, $preview);

      $this->messenger->addStatus($this->t('Image successfully converted to layout template!'));

      // Store result in session for display
      $_SESSION['openai_layout_converter_result'] = $result;

      $form_state->setRedirect('openai_layout_converter.result');

    } catch (\Exception $e) {
      $this->messenger->addError($this->t('Error: @message', ['@message' => $e->getMessage()]));
    }
  }
}
