<?php

namespace Drupal\webform_email_validation\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform submission handler for email validation.
 *
 * @WebformHandler(
 *   id = "email_validation_handler",
 *   label = @Translation("Email Validation Handler"),
 *   description = @Translation("Validates unique email IDs for remind_me webform based on node ID and campaign ID"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class EmailValidationHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // Get webform ID
    $webform_id = $webform_submission->getWebform()->id();

    // Only apply validation to remind_me webform
    if ($webform_id !== 'remind_me') {
      return;
    }

    // Get data from submission
    $data = $webform_submission->getData();
    $email = isset($data['email']) ? trim($data['email']) : '';
    $nid = isset($data['nid']) ? $data['nid'] : '';
    $campaign_id = isset($data['campaign_id']) ? $data['campaign_id'] : '';

    // Validate only if required fields are present
    if (!empty($email) && !empty($nid)) {
      if ($this->emailExistsForNid($email, $nid, $campaign_id)) {
        $form_state->setErrorByName('email', $this->t(
          'This email address has already been used for this reminder. Please use a different email address or contact support.'
        ));
      }
    }
  }

  /**
   * Check if email exists for a specific node ID.
   *
   * @param string $email
   *   The email address to check.
   * @param string $nid
   *   The node ID to check against.
   * @param string $campaign_id
   *   The campaign ID (optional).
   *
   * @return bool
   *   TRUE if email exists for this nid, FALSE otherwise.
   */
  protected function emailExistsForNid($email, $nid, $campaign_id = '') {
    $database = \Drupal::database();

    // Query to check existing submissions for this email + nid combination
    $query = $database->select('webform_submission_data', 'wsd')
      ->fields('wsd', ['sid'])
      ->condition('wsd.name', 'email')
      ->condition('wsd.value', $email, '=');

    // Join with webform_submission table
    $query->innerJoin(
      'webform_submission',
      'ws',
      'wsd.sid = ws.sid'
    );
    $query->condition('ws.webform_id', 'remind_me');

    // Join to get nid from submission data
    $query->innerJoin(
      'webform_submission_data',
      'wsd_nid',
      'ws.sid = wsd_nid.sid AND wsd_nid.name = :nid_field',
      [':nid_field' => 'nid']
    );
    $query->condition('wsd_nid.value', $nid, '=');

    // If campaign_id provided, also match on it
    if (!empty($campaign_id)) {
      $query->innerJoin(
        'webform_submission_data',
        'wsd_campaign',
        'ws.sid = wsd_campaign.sid AND wsd_campaign.name = :campaign_field',
        [':campaign_field' => 'campaign_id']
      );
      $query->condition('wsd_campaign.value', $campaign_id, '=');
    }

    // Execute query and check if any results found
    $result = $query->execute()->fetchField();

    return !empty($result);
  }

}
