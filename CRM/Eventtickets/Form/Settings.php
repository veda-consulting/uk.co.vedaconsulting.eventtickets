<?php

require_once 'CRM/Core/Form.php';
use CRM_Eventtickets_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Eventtickets_Form_Settings extends CRM_Core_Form {

  public function buildQuickForm() {

    // add form elements
    $this->add(
      'select', // field type
      'event_types', // field name
      'Event Types', // field label
      $this->getEventTypes(), // list of options
      FALSE, // is required
      ['class' => 'crm-select2 huge',  //attributes
      'multiple' => TRUE,
      'placeholder' => ts('- select -')]
    );

    $this->addYesNo('is_resend_confirm_email', ts('Resend Confirmation Email?'), NULL, NULL);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames',$this->getRenderableElementNames());
    //Set default Values
    $defaults = CRM_Eventtickets_Utils::getTicketSettings();
    $this->setDefaults($defaults);
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $options = $this->getEventTypes();
    $ticket_settings['event_types'] = $values['event_types'];
    $ticket_settings['is_resend_confirm_email'] = (empty($values['is_resend_confirm_email'])) ? 0 : $values['is_resend_confirm_email'] ;
    CRM_Core_BAO_Setting::setItem($ticket_settings, CRM_Eventtickets_Constants::TICKET_SETTINGS, 'ticket_settings');
    CRM_Core_Session::setStatus(ts('Ticket Settings have been saved'), '' , 'success');
    parent::postProcess();
  }

  public function getEventTypes() {
    $eventTypeDetails = civicrm_api3('Event', 'getoptions', ['field' => "event_type_id",]);
    return $eventTypeDetails['values'];
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
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
