<?php

require_once 'CRM/Eventtickets/Constants.php';

/**
 *  Regional event tickets utils functions
 *
 * @package CiviCRM
 */
class CRM_Eventtickets_Utils {

  /**
   * CiviCRM API wrapper
   *
   * @param string $entity
   * @param string $action
   * @param string $params
   *
   * @return array of API results
   */
  public static function CiviCRMAPIWrapper($entity, $action, $params) {

    if (empty($entity) || empty($action) || empty($params)) {
      return;
    }

    try {
      $result = civicrm_api3($entity, $action, $params);
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('CiviCRM API Call Failed');
      CRM_Core_Error::debug_var('CiviCRM API Call Error', $e);
      return;
    }

    return $result;
  }

  /**
   * Get Custom group Id and custom field details by custom group Name.
   * @param $customGroupName
   *
   * @return array() of custom group details and custom fields details , so we can use to get field id by Name.
   *
   * sample array format
   * array(
      'id' => custom group id
      'name' => custom group name
      'table_name' => custom group table name
      .
      .
      'fields' => array(
        'field_name' => array( field details )
       )
     )
   */
  public static function getCustomGroupAndFieldDetailsByGroupName($customGroupName, $searchValue = 'name') {
    $returnResult = array();
    if (empty($customGroupName)) {
      return $returnResult;
    }

    //get Custom Group details
    $cGroup = civicrm_api3('CustomGroup', 'get', array($searchValue => $customGroupName));
    if (!empty($cGroup['id'])) {
      $returnResult = $cGroup['values'][$cGroup['id']];
      $cFields = civicrm_api3('CustomField', 'get', array('custom_group_id' => $cGroup['id'], 'options' => array('sort' => "weight"), 'is_active' => 1));
      $returnResult['fields'] = array();
      foreach ($cFields['values'] as $key => $value) {
        $returnResult['fields'][$value['name']] = $value;
      }
    }

    return $returnResult;
  }

  /**
   * Amend smarty variable called 'contactTypes' in new custom group form, which used to decide show/hide multirow field.
   *
   * smarty return contact types which allow mutiset, we are amend array with 'PartcipantEventType'
   * so multiset will enable for participants as well.
   */
  public static function allowMultisetDataForParticipantByEventtype($contactTypes, $returnAsArray = FALSE) {
    // Expecting smarty should return json encoded array for contact type list
    if (!empty($contactTypes)) {
      $contactTypes = json_decode($contactTypes);
    }

    $newMultiRowKey = 'ParticipantEventType';

    //and here we are going to include the participant event type, to allow multirow
    if (!in_array($newMultiRowKey, $contactTypes)) {
      array_push($contactTypes, $newMultiRowKey);
    }

    return $returnAsArray ? $contactTypes : json_encode($contactTypes);
  }

  public static function isEventTicketBased($eventID) {
    if ($eventID) {
      $eventType = self::getEventTypeID($eventID);
      return self::isEventTypeTicketBased($eventType);
    }
    return FALSE;
  }

  public static function formatTicketNumber($participantID, $count){
    return str_pad($participantID, 6, "0", STR_PAD_LEFT) . '-'. str_pad($count, 3, "0", STR_PAD_LEFT);
  }

  public static function generateTicketNumber($participantID, $count) {
    $ticketNumber =  self::formatTicketNumber($participantID, $count);
    //do increment if the ticket number is the already exists.
    $cGroupName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
    $cgDetails  = self::getCustomGroupAndFieldDetailsByGroupName($cGroupName);
    $tableName  = $cgDetails['table_name'];
    $columnName = $cgDetails['fields'][CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_TICKET_NUMBER]['column_name'];

    $sqlParams = array(1 =>  array($ticketNumber, 'String'));
    $isTicketNumberExist = CRM_Core_DAO::singleValueQuery("SELECT id FROM {$tableName} WHERE {$columnName} = %1", $sqlParams);
    if ($isTicketNumberExist) {
      return self::generateTicketNumber($participantID, $count + 1);
    }

    return $ticketNumber;
  }

  // To get contact valid checksum
  public static function getContactChecksum($contactID, $validity = 'inf') {
    return CRM_Contact_BAO_Contact_Utils::generateChecksum($contactID, NULL, $validity);
  }

  // To get number of tickets purchased at the time of registration
  public static function getNumberOfTicketsByParticipantID($participantID) {
    if (empty($participantID)) {
      return NULL;
    }

    $lnItems = civicrm_api3('LineItem', 'get', array(
      'sequential' => 1,
      'entity_id' => $participantID,
      'entity_table' => "civicrm_participant",
    ));
    $noOfTickets = 0;
    foreach ($lnItems['values'] as $lnItem) {
      $noOfTickets = $noOfTickets + $lnItem['qty'];
    }

    $noOfTickets = ($noOfTickets * 100) / 100; //remove decimals

    return $noOfTickets;
  }

  // To get number of tickets purchased at the time of registration
  public static function getMemberNonMemberTicketCounts($participantID) {
    if (empty($participantID)) {
      return NULL;
    }

    $lnItems = civicrm_api3('LineItem', 'get', array(
      'sequential' => 1,
      'entity_id' => $participantID,
      'entity_table' => "civicrm_participant",
      'participant_count' => array('>' => 0), // ignoring discount line items
    ));
    $memberCount = $nonMemberCount = 0;
    foreach ($lnItems['values'] as $lnItem) {
      // $noOfTickets = $noOfTickets + $lnItem['qty'];
      if (!empty($lnItem['price_field_value_id'])) {
        // $memberPrice = memberbasedpricing_getMemberPrice($lnItem['price_field_value_id']);
        // if (isset($memberPrice)) {
        //   $memberCount = $memberCount + $lnItem['qty'];
        // }
        // else{
          $nonMemberCount = $nonMemberCount + $lnItem['qty'];
        // }
      }
      else{
        $nonMemberCount = $nonMemberCount + $lnItem['qty'];
      }
    }

    $memberCount = ($memberCount * 100) / 100; //remove decimals
    $nonMemberCount = ($nonMemberCount * 100) / 100; //remove decimals

    return array($memberCount, $nonMemberCount);
  }

  // To get number of tickets purchased at the time of registration
  public static function getTicketContactDetails($participantID, $action = 'view') {
    if (empty($participantID)) {
      return NULL;
    }

    $cGroupName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
    $cgDetails  = self::getCustomGroupAndFieldDetailsByGroupName($cGroupName);
    $tableName = $cgDetails['table_name'];

    $sql = "SELECT * FROM {$tableName} WHERE entity_id = %1";
    $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($participantID, 'Integer')));
    $retunResult = array();
    $i = 1;
    while ($dao->fetch()) {
      $row = array();
      $row['id'] = $dao->id;
      $row['entity_id'] = $dao->entity_id;
      foreach ($cgDetails['fields'] as $fieldName => $field) {
        $columnName = $field['column_name'];
        $row[$fieldName] = $dao->$columnName;
      }
      $retunResult[$i] = $row;
      $i++;
    }

    return $retunResult;
  }

  // To get View / Edit Url with checksum.
  public static function getTicketingDetailsURL($participantID, $action = 'view',$is_test=FALSE) {
    if (empty($participantID)) {
      return NULL;
    }

    $participantDetails = self::CiviCRMAPIWrapper('Participant', 'get', array('id' => $participantID));
    if (!empty($participantDetails['id'])) {
      // Generate this link only for ticketing event type.
      $eventID   = $participantDetails['values'][$participantID]['event_id'];
      $isEventTicketBased = self::isEventTicketBased($eventID);
      if (!$isEventTicketBased) {
        return NULL;
      }
      $contactID = $participantDetails['values'][$participantID]['contact_id'];
      $checksum  = self::getContactChecksum($contactID);

      $urlParams = array(
        'id' => $participantID,
        'cid' => $contactID,
        'cs' => $checksum,
        'reset' => 1,
        'context' => 'participant',
      );
      if($is_test == TRUE){
        return $urlParams;
      }

      return CRM_Utils_System::url('civicrm/event/ticket/'.strtolower($action), $urlParams, TRUE);
    }

    return NULL;
  }

  public static function getTicketingDetailsTableHeader() {
    $cGroupName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
    $cgDetails  = CRM_Eventtickets_Utils::getCustomGroupAndFieldDetailsByGroupName($cGroupName);

    $headers = array();
    foreach ($cgDetails['fields'] as $key => $value) {
      $headers[$key] = $value['label'];
    }

    return $headers;
  }

  // Get Ticketing Event Type ID
  public static function getTicketEventTypeID() {
    $eventTypes     = CRM_Event_PseudoConstant::eventType();
    return array_search(CRM_Eventtickets_Constants::EVENT_TYPE_TICKET_EVENT, $eventTypes);
  }

  // Get selected ticket type from line item table.
  public static function getTicketTypeByParticipantID($participantID) {
    if (empty($participantID)) {
      return NULL;
    }

    $returnResult = CRM_Core_DAO::singleValueQuery("
      SELECT GROUP_CONCAT(t2.label SEPARATOR ', ')
      FROM civicrm_line_item t1
      JOIN civicrm_price_field t2 ON ( t1.price_field_id = t2.id )
      where t1.entity_table = 'civicrm_participant' and t1.entity_id = %1 AND t1.participant_count > 0"
      , array(1 => array($participantID, 'Integer'))
    );

    return $returnResult;
  }

  // Get event type id for event.
  public static function getEventTypeID($eventID) {
    if (empty($eventID)) {
      return NULL;
    }
    return CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $eventID, 'event_type_id');
  }

  // get activity type id by name
  public static function getActivityTypeID($name) {
    return CRM_Core_OptionGroup::getValue('activity_type', $name, 'name');
  }

  public static function resendConfirmation($participantId) {
    if (empty($participantId)) {
      return FALSE;
    }

    $params = array(
      'id' => $participantId,
      'sequential' => 1
    );
    // Get participant details
    $participantDetails = self::CiviCRMAPIWrapper('Participant', 'get', $params);
    $participant = $participantDetails['values'][0];
    $contactID   = $participant['contact_id'];

    // Get event details
    $eventID     = $participant['event_id'];
    $params = array(
      'id' => $eventID,
      'sequential' => 1
    );
    $eventDetails = self::CiviCRMAPIWrapper('Event', 'get', $params);
    $event = $eventDetails['values'][0];

    if (!$event['is_email_confirm']) {
      return FALSE;
    }

    $isTicketEvent = self::isEventTypeTicketBased($event['event_type_id']);

    // gather all information need to send as like event registration send email out.
    $location = array();
    if (CRM_Utils_Array::value('is_show_location', $event) == 1) {
      $locationParams = array(
        'entity_id' => $eventID,
        'entity_table' => 'civicrm_event',
      );
      $location = CRM_Core_BAO_Location::getValues($locationParams, TRUE);
      CRM_Core_BAO_Address::fixAddress($location['address'][1]);
    }
    // profile ids
    list($pre_id, $post_id) = CRM_Event_Cart_Form_MerParticipant::get_profile_groups($eventID);
    $payer_values = array(
      'email' => '',
      'name' => '',
    );

    // if in case register by id exists
    if ($participant['registered_by_id']) {
      $payer_contact_details = CRM_Contact_BAO_Contact::getContactDetails($participant['registered_by_id']);
      $payer_values = array(
        'email' => $payer_contact_details[1],
        'name' => $payer_contact_details[0],
      );
    }

    $locationParams = array(
      'entity_id' => $eventID,
      'entity_table' => 'civicrm_event',
    );
    $location = CRM_Core_BAO_Location::getValues($locationParams, TRUE);
    $lnItem = CRM_Price_BAO_LineItem::getLineItems($participantId);
    $priceSetID = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $eventID);
    $pricesetFieldsCount = CRM_Price_BAO_PriceSet::getPricesetCount($priceSetID);
    $contributionId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $participantId, 'contribution_id', 'participant_id');
    if ($contributionId) {
      $contributionAmt = CRM_Core_DAO::getFieldValue('CRM_Contribute_BAO_Contribution', $contributionId, 'total_amount');
    }
    $totalTaxAmount = 0;
    if (!empty($lnItem)) {
      foreach ($lnItem as $key => $value) {
        $totalTaxAmount = $value['tax_amount'] + $totalTaxAmount;
      }
    }

    // required Values to tplParams
    $values = array(
      'params' => array($participantId => $participant),
      'event' => $event,
      'location' => $location,
      'custom_pre_id' => $pre_id,
      'custom_post_id' => $post_id,
      'payer' => $payer_values,
      'title' => $event['title'],
      'lineItem' => $lnItem,
      'pricesetFieldsCount' => $pricesetFieldsCount,
      'isPrimary' => 1,
      'totalTaxAmount' => $totalTaxAmount,
      'location' => $location,
    );

    if ($isTicketEvent) {
      $values['regional_ticketing_event'] = TRUE;
    }

    if ($contributionAmt) {
      $values['totalAmount'] = $contributionAmt;
    }

    // Call event BAO to send mail out.
    CRM_Event_BAO_Event::sendMail($contactID, $values, $participantId);

    return TRUE;
  }

  // Get Ticket settings
  public static function getTicketSettings() {
    $settings = CRM_Core_BAO_Setting::getItem(CRM_Eventtickets_Constants::TICKET_SETTINGS,'ticket_settings');
    return $settings;
  }

  public static function isEventTypeTicketBased($eventTypeID){
    $settings = self::getTicketSettings();
    if(!empty($settings['event_types'])){
      foreach($settings['event_types'] as $id => $name){
        if($name == $eventTypeID){
          return TRUE;
        }
      }
    }
    return FALSE;
  }


  /**
   * Get batch activities
   *
   * @return array $batchList
   */
  public static function getActivitiesDetails($activityId) {

    if (empty($activityId)) {
      return;
    }

    $whereClauses = array();
    $whereClauses[] = " WHERE (1)";
    if (!empty($activityId)) {
      $whereClauses[] = "a.id = {$activityId}";
    }

    $whereClause = implode(' AND ', $whereClauses);

    $sql = "
      SELECT a.activity_type_id, a.subject, a.status_id,
      a.activity_date_time, c.display_name, ac.contact_id, a.source_record_id as participant_id
      FROM civicrm_activity a
      LEFT JOIN civicrm_activity_contact ac ON ac.activity_id = a.id AND ac.record_type_id = 3
      LEFT JOIN civicrm_contact c ON c.id = ac.contact_id
      {$whereClause}
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    $activityDetails = $dao->toArray();
    return $activityDetails;
  }

  /*
   * Get tickets for a participant
   */
  public static function getTicketsDetails($participantID) {
    $response  = array();

    if (empty($participantID)) {
      return NULL;
    }

    $cGroupName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
    $cgDetails  = self::getCustomGroupAndFieldDetailsByGroupName($cGroupName);
    $tableName = $cgDetails['table_name'];
    $fnColName = $cgDetails['fields'][CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_FIRST_NAME]['column_name'];
    $lnColName = $cgDetails['fields'][CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_LAST_NAME]['column_name'];
    $tnColName = $cgDetails['fields'][CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_TICKET_NUMBER]['column_name'];

    $sql = "SELECT * FROM {$tableName} WHERE entity_id = %1
      AND {$fnColName} IS NOT NULL AND {$fnColName} != ''
      AND {$lnColName} IS NOT NULL AND {$lnColName} != ''
    ";
    $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($participantID, 'Integer')));
    while ($dao->fetch()) {
      $row = array();
      foreach ($cgDetails['fields'] as $fieldName => $fields) {
        $colName = $fields['column_name'];
        $row[strtolower($fieldName)] = $dao->$colName;
      }
      $response[] = $row;
    }

    return $response;
  }

  public static function checkValidMemberByMembershipNumber($membershipNumber, $eventID = NULL) {

    if (!empty($membershipNumber)) {
      $eContactId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $membershipNumber, 'id', 'external_identifier');

      //is Right contact found ?
      if (empty($eContactId)) {
        return FALSE;
      }

      $memParams = array(
      'version'         => 3,
      'contact_id'      => $eContactId,
      'is_test'         => 0,
      );
      $membership = civicrm_api('Membership' , 'get' , $memParams);
      $memberships = $membership['values'];

      // have valid membership record ?
      if (empty($memberships)) {
        return FALSE;
      }

      $validMember = FALSE;
      foreach ($memberships as $membership_id => $membership_value) {
        $status_id = $membership_value['status_id'];

        // API to get membership status
        $params = array(
          'version' => 3,
          'id'      => $status_id,
        );
        $membership_status = civicrm_api('MembershipStatus', 'get', $params);

        // Check is current member?
        if (($membership_status['values'][$membership_status['id']]['is_current_member'] == 1))  {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  public static function existingMembershipNumberRecord($membershipNumber, $participantID) {
    if (empty($membershipNumber) OR empty($participantID)) {
      return FALSE;
    }

    $eventID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $participantID, 'event_id');
    // and also make sure same membership number not been used before.
    if (!empty($eventID)) {
      $cGroupName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
      $cgDetails  = CRM_Eventtickets_Utils::getCustomGroupAndFieldDetailsByGroupName($cGroupName);
      $tableName  = $cgDetails['table_name'];
      $membershipNoColName  = $cgDetails['fields'][CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_MEMBERSHIP_NUMBER]['column_name'];
      $sql = "SELECT t1.id FROM {$tableName} t1
        JOIN civicrm_participant t2 ON (t1.entity_id = t2.id)
        WHERE t2.event_id = %1
        AND {$membershipNoColName} = %2
        AND entity_id != %3
      ";
      $sqlParams = array(
        1 => array($eventID, 'Integer'),
        2 => array($membershipNumber, 'String'),
        3 => array($participantID, 'Integer'),
      );
      $alreadyUsed = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);

      if (!$alreadyUsed) {
        return TRUE;
      }
    }

    return FALSE;
  }
} //End Class