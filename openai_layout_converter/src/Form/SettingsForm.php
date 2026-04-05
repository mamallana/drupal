// src/Form/SettingsForm.php
<?php

namespace Drupal\openai_layout_converter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openai_layout_converter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openai_layout_converter_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openai_layout_converter.settings');

    $form['openai_api_key'] = [
      '#type' => 'password',
      '#title' => $this->t('OpenAI API Key'),
      '#description' => $this->t('Your OpenAI API key. Get it from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>'),
      '#default_value' => $config->get('openai_api_key') ?: '',
      '#required' => TRUE,
    ];

    $form['openai_model'] = [
      '#type' => 'select',
      '#title' => $this->t('OpenAI Model'),
      '#description' => $this->t('Select the OpenAI model for image analysis'),
      '#options' => [
        'gpt-4o' => 'GPT-4 Omni (Recommended)',
        'gpt-4-vision' => 'GPT-4 Vision',
        'gpt-4-turbo-vision' => 'GPT-4 Turbo Vision',
      ],
      '#default_value' => $config->get('openai_model') ?: 'gpt-4o',
      '#required' => TRUE,
    ];

    $form['max_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Tokens'),
      '#description' => $this->t('Maximum tokens for OpenAI response (higher = more detailed)'),
      '#default_value' => $config->get('max_tokens') ?: 2000,
      '#min' => 100,
      '#max' => 4000,
      '#required' => TRUE,
    ];

    $form['css_generation'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CSS Generation Settings'),
      '#tree' => TRUE,
    ];

    $form['css_generation']['generate_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-generate CSS'),
      '#default_value' => $config->get('css_generation.generate_css') ?? TRUE,
    ];

    $form['css_generation']['include_responsive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include responsive design'),
      '#default_value' => $config->get('css_generation.include_responsive') ?? TRUE,
      '#states' => [
        'visible' => [
          ':input[name="css_generation[generate_css]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['css_generation']['css_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS Class Prefix'),
      '#description' => $this->t('Prefix for generated CSS classes'),
      '#default_value' => $config->get('css_generation.css_prefix') ?: 'layout-conv',
      '#states' => [
        'visible' => [
          ':input[name="css_generation[generate_css]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['layout_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Layout Settings'),
      '#tree' => TRUE,
    ];

    $form['layout_settings']['default_layout_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Layout Type'),
      '#options' => [
        'layout_onecol' => 'One Column',
        'layout_twocol' => 'Two Columns',
        'layout_threecol' => 'Three Columns',
      ],
      '#default_value' => $config->get('layout_settings.default_layout_type') ?: 'layout_onecol',
    ];

    $form['layout_settings']['auto_save_templates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-save generated templates'),
      '#default_value' => $config->get('layout_settings.auto_save_templates') ?? FALSE,
    ];

    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced Settings'),
      '#tree' => TRUE,
    ];

    $form['advanced']['enable_logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable detailed logging'),
      '#default_value' => $config->get('advanced.enable_logging') ?? TRUE,
    ];

    $form['advanced']['cache_responses'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cache API responses'),
      '#default_value' => $config->get('advanced.cache_responses') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('openai_layout_converter.settings');

    $config->set('openai_api_key', $form_state->getValue('openai_api_key'))
      ->set('openai_model', $form_state->getValue('openai_model'))
      ->set('max_tokens', $form_state->getValue('max_tokens'))
      ->set('css_generation', $form_state->getValue('css_generation'))
      ->set('layout_settings', $form_state->getValue('layout_settings'))
      ->set('advanced', $form_state->getValue('advanced'))
      ->save();

    parent::submitForm($form, $form_state);
    $this->messenger()->addStatus($this->t('OpenAI Layout Converter settings saved.'));
  }
}
