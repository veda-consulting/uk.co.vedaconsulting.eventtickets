<?php

require_once 'eventtickets.civix.php';
use CRM_eventtickets_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function eventtickets_civicrm_config(&$config) {
  _eventtickets_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function eventtickets_civicrm_xmlMenu(&$files) {
  _eventtickets_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function eventtickets_civicrm_install() {
  _eventtickets_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function eventtickets_civicrm_postInstall() {
  //Including the Created 'Ticket Event' type option as a default ticketed event type to the  settings
  $result = civicrm_api3('OptionValue', 'get', [
    'sequential' => 1,
    'name' => CRM_Eventtickets_Constants::EVENT_TYPE_TICKET_EVENT,
  ]);
  if(!empty($result['values'])){
    $eventTypeId = $result['values'][0]['value'];
    $eventTypeName = $result['values'][0]['label'];
    $ticket_settings['event_types'][$eventTypeId] = $eventTypeName;
    CRM_Core_BAO_Setting::setItem($ticket_settings, CRM_Eventtickets_Constants::TICKET_SETTINGS, 'ticket_settings');
  }
  _eventtickets_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function eventtickets_civicrm_uninstall() {
  _eventtickets_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function eventtickets_civicrm_enable() {
  _eventtickets_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function eventtickets_civicrm_disable() {
  _eventtickets_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function eventtickets_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _eventtickets_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function eventtickets_civicrm_managed(&$entities) {
  _eventtickets_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function eventtickets_civicrm_caseTypes(&$caseTypes) {
  _eventtickets_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function eventtickets_civicrm_angularModules(&$angularModules) {
  _eventtickets_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function eventtickets_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _eventtickets_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function eventtickets_civicrm_entityTypes(&$entityTypes) {
  _eventtickets_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function eventtickets_civicrm_themes(&$themes) {
  _eventtickets_civix_civicrm_themes($themes);
}

/**
 * Implements hook_civicrm_navigationMenu().
 */
function eventtickets_civicrm_navigationMenu(&$params){
  $parentId             = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Events', 'id', 'name');

  $maxId                = max(array_keys($params[$parentId]['child']));
  $ticketEventSettingsMaxId     = $maxId+1;

  $params[$parentId]['child'][$ticketingEventSettingsMaxId] = array(
        'attributes' => array(
          'label'     => ts('Ticket Event Type Settings'),
          'name'      => 'Ticket_Event_Type_Settings',
          'url'       => 'civicrm/eventtickets/settings?reset=1',
          'active'    => 1,
          'parentID'  => $parentId,
          'operator'  => NULL,
          'navID'     => $ticketEventSettingsMaxId,
          'permission'=> 'administer CiviCRM',
        ),
  );

}

/**
 * Implements hook_civicrm_buildForm().
 *
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm
 */
function eventtickets_civicrm_buildForm($formName, &$form) {

  if ($formName == 'CRM_Custom_Form_Group') {

    // Get contact types which are already assigned to smarty.
    $smarty = CRM_Core_Smarty::singleton();

    // This is variable which is used to decide show/hide is_multiple_row field.
    // expecting Json rather than array, since we retireve from smart variable not from $form.
    $contactTypes = $smarty->get_template_vars('contactTypes');
    $contactTypes = CRM_Eventtickets_Utils::allowMultisetDataForParticipantByEventtype($contactTypes);

    //then push back to smarty variables
    $smarty->assign('contactTypes', $contactTypes);
  }

  if ($formName == 'CRM_Event_Form_Registration_Register') {
    $evenTypeId  = $form->_values['event']['event_type_id'];
    // $eventTypes     = CRM_Event_PseudoConstant::eventType();
    // $ticketedEventTypeId = array_search(CRM_eventtickets_Constants::EVENT_TYPE_TICKET_EVENT, $eventTypes);
    $isTicketingEvent = CRM_Eventtickets_Utils::isEventTypeTicketBased($evenTypeId);
    if ($isTicketingEvent) {

      if ($priceSetId = $form->getVar('_priceSetId')) {
        // set 0 as default quantity for each ticket of a ticketed event
        if (isset($form->_priceSet) && !empty($form->_priceSet)) {
          $defaults = array();
          foreach ($form->_priceSet['fields'] as $priceFieldId => $priceField) {
            $defaults['price_' . $priceFieldId] = 0;
          }
          $form->setDefaults($defaults);
        }
      }
    }
  }

  if ($formName == 'CRM_Event_Form_Registration_ThankYou') {
    $event_type_id  = $form->_values['event']['event_type_id'];
    $isTicketingEvent = CRM_Eventtickets_Utils::isEventTypeTicketBased($event_type_id);
    if ($isTicketingEvent) {
      $templatePath = realpath(dirname(__FILE__).'/templates');
      CRM_Core_Region::instance('page-body')->add(
        array(
          'template' => "{$templatePath}/CRM/Eventtickets/Form/Custom/TicketingContactLink.tpl"
        )
      );

      $participantID = $form->getVar('_participantId');
      $ticketingContactLink = CRM_Eventtickets_Utils::getTicketingDetailsURL($participantID, 'edit');
      $form->assign('ticketingContactLink', $ticketingContactLink);
    }
  }

  if ($formName == 'CRM_Event_Form_ParticipantView') {
    $participantID = CRM_Utils_Request::retrieve('id', 'Positive', CRM_Core_DAO::$_nullObject);
    if ($participantID) {
      $viewURL = CRM_Eventtickets_Utils::getTicketingDetailsURL($participantID, 'view');
      $editURL = CRM_Eventtickets_Utils::getTicketingDetailsURL($participantID, 'edit');
      $smarty = CRM_Core_Smarty::singleton();
      $form->assign('viewURL', $viewURL);
      $form->assign('editURL', $editURL);

      $eventID   = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $participantID, 'event_id');
      $showButton = CRM_Eventtickets_Utils::isEventTicketBased($eventID);
      $form->assign('showTicketButton', $showButton);
    }
  }
}


/*
 * Implementation of hook_civicrm_alterMailParams
 * To insert QR codes in event registration email
 */
function eventtickets_civicrm_alterMailParams(&$params, $context) {

  // Scheduled Reminder Sender
  // Get Participant ID from scheduled reminder
  if ($params['groupName'] == 'Scheduled Reminder Sender') {
    if ($params['entity'] == 'action_schedule' && !empty($params['entity_id'])) {
      // $participantID = CRM_Civiqrcode_Utils::getParticipantIdFromScheduledReminder($params['entity_id']);
    }
  }

  // Check if this online/offline event registration receipt
  // and get Participant ID
  if ($params['groupName'] == 'msg_tpl_workflow_event'
    && ($params['valueName'] == 'event_online_receipt' || $params['valueName'] == 'event_offline_receipt') && isset($params['tplParams']['participantID'])
    ) {
    $participantID = $params['tplParams']['participantID'];
  }

  // We need to attach QR code for event registration
  // and Scheduled reminder
  if ($params['groupName'] == 'Scheduled Reminder Sender' || $params['groupName'] == 'msg_tpl_workflow_event') {
    if (!empty($participantID)) {
      $tplParams =& $params['tplParams'];
      $ticketingContactLink =  CRM_Eventtickets_Utils::getTicketingDetailsURL($participantID, 'edit');

      if (!empty($ticketingContactLink)) {
        $tplParams['ticketingContactLink'] = $ticketingContactLink;
      }
    }
  }
}

function eventtickets_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($op == 'create' && $objectName == 'LineItem') {
    if ($objectRef->entity_table == 'civicrm_participant' && $objectRef->entity_id) {
      $eventId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $objectRef->entity_id, 'event_id', 'id');
      if (CRM_Eventtickets_Utils::isEventTicketBased($eventId)) {
        $cGroupName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
        $cgDetails  = CRM_Eventtickets_Utils::getCustomGroupAndFieldDetailsByGroupName($cGroupName);
        $cfID       = $cgDetails['fields'][CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_TICKET_NUMBER]['id'];
        if (!empty($cfID)) {
          for ($i = 1; $i <= (int) $objectRef->qty; $i++) {
            $result = civicrm_api3('CustomValue', 'create', array(
              'sequential' => 1,
              'entity_id'  => $objectRef->entity_id,
              "custom_{$cfID}" => CRM_Eventtickets_Utils::generateTicketNumber($objectRef->entity_id, $i),
            ));
          }
        }
      }
    }
  }
}