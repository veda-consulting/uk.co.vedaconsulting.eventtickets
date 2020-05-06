<?php

use CRM_Eventtickets_ExtensionUtil as E;
require_once 'CRM/Eventtickets/Utils.php';

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Eventtickets_Form_TicketContactDetails extends CRM_Core_Form {
  public $_participantId;

  public $_contactId;

  public $_numberOfTickets;

  public $_existingTicketDetails;

  public function preProcess() {
    $this->_participantId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, NULL, 'REQUEST');
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, NULL, 'REQUEST');
    $cs = CRM_Utils_Request::retrieve('cs', 'String', $this, FALSE);

    // check if this is of the format cs=XXX
    if (!CRM_Contact_BAO_Contact_Utils::validChecksum($this->_contactId, $cs)
      && !CRM_Core_Permission::check('administer CiviCRM')
    ) {
      // also set a message in the UF framework
      $message = ts('You do not have permission to edit this contact record. Contact the site administrator if you need assistance.');
      CRM_Utils_System::setUFMessage($message);

      $config = CRM_Core_Config::singleton();
      CRM_Core_Error::statusBounce($message,
        $config->userFrameworkBaseURL
      );
    }

    $session = CRM_Core_Session::singleton();
    if (CRM_Core_Permission::check('administer CiviCRM')){
      $session->pushUserContext(CRM_Utils_System::url(CRM_Eventtickets_Constants::EVENT_TICKET_URL_PATH.'/view'
        , 'reset=1&id='.$this->_participantId)
      );
    }
    else{
      $config = CRM_Core_Config::singleton();
      $session->pushUserContext($config->userFrameworkBaseURL);
    }

    CRM_Utils_System::setTitle(ts('Ticket Contact Details'));

    parent::preProcess();
  }

  public function buildQuickForm() {
    $defaults = array();
    $this->_is_test_case == FALSE;
    $this->_numberOfTickets = CRM_Eventtickets_Utils::getNumberOfTicketsByParticipantID($this->_participantId);
    list($memberTicketCount, $nonMemberTicketCount) = CRM_Eventtickets_Utils::getMemberNonMemberTicketCounts($this->_participantId);

    // get existing ticket contact details if already exists.
    $this->_existingTicketDetails = CRM_Eventtickets_Utils::getTicketContactDetails($this->_participantId);

    // Build custom fields limit to number of tickets purchased.
    $cGroupName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
    $cgDetails  = CRM_Eventtickets_Utils::getCustomGroupAndFieldDetailsByGroupName($cGroupName);

    $customFieldSets = $memberTicketFields = array();
    for ($i=1; $i <= $this->_numberOfTickets; $i++) {
      $customFieldSet = array();
      foreach ($cgDetails['fields'] as $key => $fields) {
        //Build form fields using core method
        $cgCount = !empty($this->_existingTicketDetails[$i]) ? $this->_existingTicketDetails[$i]['id'] : $i * -1;
        $fieldName = "custom_".$fields['id']."_".$cgCount;
        $ticketNumber = CRM_Eventtickets_Utils::formatTicketNumber($this->_participantId, $i);
        if ($fields['is_view']) {
          $this->add('hidden', $fieldName, $ticketNumber);
        }
        // display is member ticket as checkbox rather than radio buttons
        elseif ($fields['name'] == CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_IS_MEMBER_TICKET) {
          if (!empty($memberTicketCount) && $i <= $memberTicketCount) {
            $this->add('hidden', $fieldName, 1);
            $memberTicketFields[$i] = $fieldName;
          }
          else{
            $this->add('hidden', $fieldName, 0);
          }
        }
        else{
          CRM_Core_BAO_CustomField::addQuickFormElement($this, $fieldName, $fields['id'], FALSE);
        }
        $customFieldSet[$fields['id']] = $fieldName;
        // build default values if any.
        if (!empty($this->_existingTicketDetails[$i]) && !empty($this->_existingTicketDetails[$i][$fields['name']])) {
          $defaults[$fieldName] = $this->_existingTicketDetails[$i][$fields['name']];
        }
      }
      $customFieldSets[$i] = $customFieldSet;
    }

    $buttons = array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      )
    );

    $loggedInUser = CRM_Core_Session::getLoggedInContactID();
    if (!empty($loggedInUser)) {
      $buttons[] = array(
        'type' => 'cancel',
        'name' => E::ts('Canel'),
      );
    }
    // Form buttons
    $this->addButtons($buttons);
    $this->setDefaults($defaults);

    // assign reuired values to Tpl
    $this->assign('customFieldSets', $customFieldSets);
    $this->assign('memberTicketFields', $memberTicketFields);
    $this->assign('numberOfTickets', $this->_numberOfTickets);
    $this->assign('memberTicketCount', $memberTicketCount);
    $this->assign('nonMemberTicketCount', $nonMemberTicketCount);
    $this->assign('memberTicketField', $cgDetails['fields'][CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_IS_MEMBER_TICKET]['id']);
    $this->assign('membershipNoField', $cgDetails['fields'][CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_MEMBERSHIP_NUMBER]['id']);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    $this->addFormRule(array('CRM_Eventtickets_Form_TicketContactDetails', 'formRule'), $this);
    parent::buildQuickForm();
  }

  public static function formRule($fields, $files, $self){
    $errors = array();

    // Build custom fields limit to number of tickets purchased.
    $cGroupName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
    $cgDetails  = CRM_Eventtickets_Utils::getCustomGroupAndFieldDetailsByGroupName($cGroupName);

    $memberTicketField = $cgDetails['fields'][CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_IS_MEMBER_TICKET]['id'];
    $membershipNoField = $cgDetails['fields'][CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_MEMBERSHIP_NUMBER]['id'];
    $submittedMembershipNo = array();
    for ($i=1; $i <= $self->_numberOfTickets; $i++) {
      $cgCount = !empty($self->_existingTicketDetails[$i]) ? $self->_existingTicketDetails[$i]['id'] : $i * -1;

      if (!empty($fields["custom_{$memberTicketField}_{$cgCount}"])) {
        if (empty($fields["custom_{$membershipNoField}_{$cgCount}"])) {
          $errors["custom_{$membershipNoField}_{$cgCount}"] = "Please enter the Membership Number for Ticket contact ({$i})";
        }
      }

      if (!empty($fields["custom_{$membershipNoField}_{$cgCount}"])) {
        $submittedMembershipNo["custom_{$membershipNoField}_{$cgCount}"] = $fields["custom_{$membershipNoField}_{$cgCount}"];
      }
    }

    // Verify duplicate membership number.
    $duplicate = array();
    if (!empty($submittedMembershipNo)) {
      $membershipNoCount = array_count_values($submittedMembershipNo);
      foreach ($submittedMembershipNo as $key => $value) {
        // Check is valid membership number
        $validMembershipNo = CRM_Eventtickets_Utils::checkValidMemberByMembershipNumber($value);
        if (!$validMembershipNo) {
          $errors[$key] = "{$value} is not valid membership number";
        }

        $isAvailable = CRM_Eventtickets_Utils::existingMembershipNumberRecord($value, $self->_participantId);
        if (!$isAvailable) {
          $errors[$key] = "Invalid Membership Number, {$value} is already used. Please make sure you are entering right membership number.";
        }
        // Make sure membership numbers are not duplicated.
        if ($membershipNoCount[$value] > 1) {
          $errors[$key] = "Membership Number should be unique. Looks like {$value} has been used more than once.";
        }
      }
    }


    return empty($errors) ? TRUE : $errors;
  }

  public function postProcess() {
    $submittedValues = $this->exportValues();

    $cGroupName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
    $cgDetails  = CRM_Eventtickets_Utils::getCustomGroupAndFieldDetailsByGroupName($cGroupName);
    $tableName  = $cgDetails['table_name'];
    $tnFieldID  = $cgDetails['fields'][CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_TICKET_NUMBER]['id'];
    $memberTicketFieldID  = $cgDetails['fields'][CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_IS_MEMBER_TICKET]['id'];

    $updateColumns = $insertColumns = array();
    $updateValues  = $insertValues  = array();


    // Format the array by number of rows entered.
    foreach ($submittedValues as $key => $value) {
      $explodedKey = explode('_', $key);
      if ($explodedKey[0] == 'custom') {
        $submittedFieldId = $explodedKey[1];
        $cgCount = $explodedKey[2];

        $tempArray[$cgCount][$submittedFieldId] = $value;
      }
    }
    // $cfID = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_CustomField', ET_TICKET_NUMBER_CF_NAME, 'id', 'name');
    foreach ($tempArray as $rowCount => $rowValues) {
      // if the any rows have not entered at all then skip updating to databases.
      // in some cases one or two values been entered in some rows (like only first name / last name entered.) just update empty value for those no value columns.
      // set default value for member ticket column, because form submission will not return any value if its unchecked.
      $rowValues[$memberTicketFieldID] = CRM_Utils_Array::value($memberTicketFieldID, $rowValues, '0');
      $valueExists = $rowValues;
      $valueExists = array_filter($valueExists);
      // if (!empty($valueExists)) {
        foreach ($rowValues as $submittedFieldId => $submittedValue) {
          //using custom fields api result to finding out the field column names.
          foreach ($cgDetails['fields'] as $fields) {
            if ($fields['id'] == $submittedFieldId && $rowCount < 0) {
              $insertColumns[$submittedFieldId] = $fields['column_name'];
              if($memberTicketFieldID==$submittedFieldId){
                $insertValues[$rowCount][$submittedFieldId] = empty($submittedValue) ? 0 : $submittedValue;
              }
              else{
                $insertValues[$rowCount][$submittedFieldId] = empty($submittedValue) ? '' : $submittedValue;
              }
            }
            elseif ($fields['id'] == $submittedFieldId && $rowCount > 0) {
              $updateColumns[$submittedFieldId] = $fields['column_name'];
              $updateValues[$rowCount][] = $fields['column_name']." = '".$submittedValue."'";
            }
          }
        }
      // }
    }

    // Insert new values.
    if (!empty($insertColumns) && !empty($insertValues)) {
      $insertColumns['entity_id'] = 'entity_id';
      $insertColumnStr = implode(', ', $insertColumns);
      $rows = array();
      foreach ($insertValues as $key => $submittedValue) {
        $submittedValue['entity_id'] = $this->_participantId;
        $rows[] = '("'. implode('", "', $submittedValue).'")';
      }
      $insertValueStr = implode(', ', $rows);

      $sql = "INSERT INTO {$tableName} ($insertColumnStr) VALUES {$insertValueStr}";
      CRM_Core_DAO::executeQuery($sql);
    }

    // Update Existing values
    if (!empty($updateValues)) {
      $rows = array();
      foreach ($updateValues as $tableId => $value) {
        $updateValueStr = implode(', ', $value);
        $sql = "UPDATE {$tableName} SET {$updateValueStr} WHERE id = {$tableId} and entity_id = {$this->_participantId}";
        CRM_Core_DAO::executeQuery($sql);
      }
    }

    //Trigger event email
    // Might need to check setting before send email out
    $ticket_settings = CRM_Eventtickets_Utils::getTicketSettings();
    if($ticket_settings['is_resend_confirm_email']){
      CRM_Eventtickets_Utils::resendConfirmation($this->_participantId);
    }

    $statusMsg = ts("Your Ticketing Details been successfully updated.");
    if($this->_is_test_case == FALSE){
      if (CRM_Core_Permission::check('administer CiviCRM')) {
        CRM_Core_Session::setStatus($statusMsg, 'Ticket Details' ,'success');
        CRM_Utils_System::redirect(CRM_Utils_System::url(CRM_Eventtickets_Constants::EVENT_TICKET_URL_PATH.'/view'
        , 'reset=1&id='.$this->_participantId));
      }
      else{
        CRM_Utils_System::setUFMessage($statusMsg);
        $config = CRM_Core_Config::singleton();
        CRM_Utils_System::redirect($config->userFrameworkBaseURL);
      }
    }

    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
