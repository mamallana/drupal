<?php

namespace Drupal\openai_layout_converter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\TransientTempStore;

class ImageConverterForm extends FormBase {
    
    protected $tempStore;

    public function __construct() {
        $this->tempStore = 
            \\Drupal::service('tempstore.private')->get('openai_layout_converter');
    }

    public function getFormId() {
        return 'image_converter_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['file_upload'] = [
            '#type' => 'file',
            '#title' => $this->t('Upload your image'),
            '#description' => $this->t('Image must be a valid JPEG or PNG.'),
            '#required' => TRUE,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Convert Image'),
        ];

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        $file = $form_state->getValue('file_upload');
        if ($file['#filename']) {
            $extension = pathinfo($file['#filename'], PATHINFO_EXTENSION);
            if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $form_state->setErrorByName('file_upload', $this->t('Uploaded file must be a JPEG or PNG.'));
            }
        } else {
            $form_state->setErrorByName('file_upload', $this->t('Please upload a file.'));
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Process the uploaded file
        $this->tempStore->set('uploaded_file', $form_state->getValue('file_upload'));
        drupal_set_message($this->t('The image has been uploaded.'));
    }
}