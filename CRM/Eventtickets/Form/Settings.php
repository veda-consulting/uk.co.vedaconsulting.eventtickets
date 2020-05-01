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
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames',$this->getRenderableElementNames());
    $defaults = CRM_Eventtickets_Utils::getTicketSettings();
    //Set default values if its not empty
    if(!empty($defaults['event_types'])){
      foreach ($this->_elements as $eid => &$element) {
        $values = $element;
        if(array_key_exists('_options', $values)){
          foreach ($defaults['event_types'] as $id => $default) {
            foreach ($element->_options as $key => $option) {
              if($option['text'] == $default){
                $element->_options[$key]['attr']['selected']='selected';
              }
            }
          }
        }
      }
    }
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $options = $this->getEventTypes();
    $eventTypes=array();
    if(!empty($values['event_types'])){
      foreach ($values['event_types'] as $key => $value) {
        $eventTypes['event_types'][$value] = $options[$value];
      }
    }
    CRM_Core_BAO_Setting::setItem($eventTypes, CRM_Eventtickets_Constants::TICKET_SETTINGS, 'ticket_settings');
    CRM_Core_Session::setStatus(ts('Event types have been saved'));
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
