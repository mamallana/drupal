<?php

namespace Drupal\openai_layout_converter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends FormBase {

  // Other methods and properties 

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Other form elements

    // API Key Field
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('Please enter your API Key. This will not display a default value for security reasons.'),
      '#default_value' => '', // Don't show default value
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $api_key = $form_state->getValue('api_key');

    // Only update the API key if a new value is provided
    if (!empty($api_key)) {
      // Save the new API Key value
      
    }
    // Handle other submissions
  }

  // Other methods
}