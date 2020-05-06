<?php
/**
 *  @file
 *  File for the BtoUnitTestCase class
 *
 */

/**
 *  Include class definitions
 */
require_once 'api/api.php';
require_once 'api/v3/utils.php';
require_once 'CRM/Eventtickets/Constants.php';

/**
 *  Base class for Regional event tickets unit tests
 *
 *  Common functions for unit tests
 * @package CiviCRM
 */
class CRM_Eventtickets_TestCase_Utils extends \PHPUnit\Framework\TestCase {

  /**
   * Api version - easier to override than just a define
   */
  protected $_apiversion = 3;
  public $_ids;
  public $_eventID;
  public $_contactID;
  public $_participantID;
  public $_paymentProcessorID;
  public $_priceSetID;

  /**
   * wrap api functions.
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   *
   * @param string $entity
   * @param string $action
   * @param array $params
   * @param mixed $checkAgainst
   *   Optional value to check result against, implemented for getvalue,.
   *   getcount, getsingle. Note that for getvalue the type is checked rather than the value
   *   for getsingle the array is compared against an array passed in - the id is not compared (for
   *   better or worse )
   *
   * @return array|int
   */
  public function callAPISuccess($entity, $action, $params, $checkAgainst = NULL) {
    $params = array_merge(array(
        'version' => $this->_apiversion,
        'debug' => 1,
      ),
      $params
    );
    switch (strtolower($action)) {
      case 'getvalue':
        return $this->callAPISuccessGetValue($entity, $params, $checkAgainst);

      case 'getsingle':
        return $this->callAPISuccessGetSingle($entity, $params, $checkAgainst);

      case 'getcount':
        return $this->callAPISuccessGetCount($entity, $params, $checkAgainst);
    }
    $result = $this->civicrm_api($entity, $action, $params);
    $this->assertAPISuccess($result, "Failure in api call for $entity $action");
    return $result;
  }

  /**
   * This function exists to wrap api getValue function & check the result
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   * There is a type check in this
   *
   * @param string $entity
   * @param array $params
   * @param string $type
   *   Per http://php.net/manual/en/function.gettype.php possible types.
   *   - boolean
   *   - integer
   *   - double
   *   - string
   *   - array
   *   - object
   *
   * @return array|int
   */
  public function callAPISuccessGetValue($entity, $params, $type = NULL) {
    $params += array(
      'version' => $this->_apiversion,
      'debug' => 1,
    );
    $result = $this->civicrm_api($entity, 'getvalue', $params);
    if ($type) {
      if ($type == 'integer') {
        // api seems to return integers as strings
        $this->assertTrue(is_numeric($result), "expected a numeric value but got " . print_r($result, 1));
      }
      else {
        $this->assertType($type, $result, "returned result should have been of type $type but was ");
      }
    }
    return $result;
  }

  /**
   * This function exists to wrap api getsingle function & check the result
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   *
   * @param string $entity
   * @param array $params
   * @param array $checkAgainst
   *   Array to compare result against.
   *   - boolean
   *   - integer
   *   - double
   *   - string
   *   - array
   *   - object
   *
   * @throws Exception
   * @return array|int
   */
  public function callAPISuccessGetSingle($entity, $params, $checkAgainst = NULL) {
    $params += array(
      'version' => $this->_apiversion,
      'debug' => 1,
    );
    $result = $this->civicrm_api($entity, 'getsingle', $params);
    if (!is_array($result) || !empty($result['is_error']) || isset($result['values'])) {
      throw new Exception('Invalid getsingle result' . print_r($result, TRUE));
    }
    if ($checkAgainst) {
      // @todo - have gone with the fn that unsets id? should we check id?
      $this->checkArrayEquals($result, $checkAgainst);
    }
    return $result;
  }

  /**
   * This function exists to wrap api getValue function & check the result
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   * There is a type check in this
   * @param string $entity
   * @param array $params
   * @param null $count
   * @throws Exception
   * @return array|int
   */
  public function callAPISuccessGetCount($entity, $params, $count = NULL) {
    $params += array(
      'version' => $this->_apiversion,
      'debug' => 1,
    );
    $result = $this->civicrm_api($entity, 'getcount', $params);
    if (!is_int($result) || !empty($result['is_error']) || isset($result['values'])) {
      throw new Exception('Invalid getcount result : ' . print_r($result, TRUE) . " type :" . gettype($result));
    }
    if (is_int($count)) {
      $this->assertEquals($count, $result, "incorrect count returned from $entity getcount");
    }
    return $result;
  }

  /**
   * A stub for the API interface. This can be overriden by subclasses to change how the API is called.
   *
   * @param $entity
   * @param $action
   * @param array $params
   * @return array|int
   */
  public function civicrm_api($entity, $action, $params) {
    return civicrm_api3($entity, $action, $params);
  }

  /**
   * Check that api returned 'is_error' => 0.
   *
   * @param array $apiResult
   *   Api result.
   * @param string $prefix
   *   Extra test to add to message.
   */
  public function assertAPISuccess($apiResult, $prefix = '') {
    if (!empty($prefix)) {
      $prefix .= ': ';
    }
    $errorMessage = empty($apiResult['error_message']) ? '' : " " . $apiResult['error_message'];

    if (!empty($apiResult['debug_information'])) {
      $errorMessage .= "\n " . print_r($apiResult['debug_information'], TRUE);
    }
    if (!empty($apiResult['trace'])) {
      $errorMessage .= "\n" . print_r($apiResult['trace'], TRUE);
    }
    $this->assertEquals(0, $apiResult['is_error'], $prefix . $errorMessage);
  }

  /**
   * Assert that a SQL query returns a given value.
   *
   * The first argument is an expected value. The remaining arguments are passed
   * to CRM_Core_DAO::singleValueQuery
   *
   * Example: $this->assertSql(2, 'select count(*) from foo where foo.bar like "%1"',
   * array(1 => array("Whiz", "String")));
   * @param $expected
   * @param $query
   * @param array $params
   * @param string $message
   */
  public function assertDBQuery($expected, $query, $params = array(), $message = '') {
    if ($message) {
      $message .= ': ';
    }
    $actual = CRM_Core_DAO::singleValueQuery($query, $params);
    $this->assertEquals($expected, $actual,
      sprintf('%sexpected=[%s] actual=[%s] query=[%s]',
        $message, $expected, $actual, CRM_Core_DAO::composeQuery($query, $params, FALSE)
      )
    );
  }

  /**
   * Request a record from the DB by seachColumn+searchValue. Success if a record is found.
   * @param string $daoName
   * @param $searchValue
   * @param $returnColumn
   * @param $searchColumn
   * @param $message
   *
   * @return null|string
   * @throws PHPUnit_Framework_AssertionFailedError
   */
  public function assertDBNotNull($daoName, $searchValue, $returnColumn, $searchColumn, $message) {
    if (empty($searchValue)) {
      $this->fail("empty value passed to assertDBNotNull");
    }
    $value = CRM_Core_DAO::getFieldValue($daoName, $searchValue, $returnColumn, $searchColumn, TRUE);
    $this->assertNotNull($value, $message);

    return $value;
  }

  /**
   * Request a record from the DB by seachColumn+searchValue. Success if returnColumn value is NULL.
   * @param string $daoName
   * @param $searchValue
   * @param $returnColumn
   * @param $searchColumn
   * @param $message
   */
  public function assertDBNull($daoName, $searchValue, $returnColumn, $searchColumn, $message) {
    $value = CRM_Core_DAO::getFieldValue($daoName, $searchValue, $returnColumn, $searchColumn, TRUE);
    $this->assertNull($value, $message);
  }

  /**
   * Get Custom group Id and custom field details by custom group Name.
   * @param $customGroupName
   *
   * @return array() of custom group details and custom fields details , so we can use to get field id by Name.
   */
  public function getCustomGroupAndFieldDetailsByGroupName($customGroupName, $searchValue = 'name') {
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
   * Generic function to create Household, to be used in test cases
   *
   * @param array $params
   *   parameters for civicrm_contact_add api function call
   * @param int $seq
   *   sequence number if creating multiple households
   *
   * @return int
   *   id of Household created
   */
  public function contactCreate($params = array(), $type = 'Individual') {
    $params['contact_type'] = $type;
    $result = $this->callAPISuccess('contact', 'create', $params);
    if (!empty($result['is_error']) || empty($result['id'])) {
      throw new Exception('Could not create test contact, with message: ' . CRM_Utils_Array::value('error_message', $result) . "\nBacktrace:" . CRM_Utils_Array::value('trace', $result));
    }
    $this->_ids['Contact'][$result['id']] = $result['id'];
    return $result['id'];
  }

  public function individualCreate($params = array()) {
    $params = array(
      'first_name' => CRM_Utils_Array::value('first_name', $params, 'TestContact'),
      'last_name' => CRM_Utils_Array::value('last_name', $params, 'DELETEME'.date('YmdHis')),
    );
    $params['email'] = $params['first_name']."_".$params['last_name']."@testcase.com";
    return $this->contactCreate($params);
  }

  public function eventPriceSetCreate($feeTotal, $minAmt = 1, $type = 'Text') {
    // creating price set, price field
    $paramsSet['title'] = 'Test Ticketing Price Set';
    $paramsSet['name'] = CRM_Utils_String::titleToVar('Test Ticketing Price Set');
    $paramsSet['is_active'] = TRUE;
    $paramsSet['extends'] = 1;
    $paramsSet['financial_type_id'] = $this->getFinancialTypeId('Event Fee');
    $priceSet = $this->callAPISuccess('PriceSet', 'get', $paramsSet);
    if (!empty($priceSet['id'])) {
      $this->_ids['price_set'] = $priceSet['id'];
    }
    else{

      $priceSet = CRM_Price_BAO_PriceSet::create($paramsSet);
      $this->_ids['price_set'] = $priceSet->id;

      $paramsField = array(
        'label' => 'Test Ticketing Price Field',
        'name' => CRM_Utils_String::titleToVar('Test Ticketing Price Field'),
        'html_type' => $type,
        'price' => $feeTotal,
        'option_label' => array('1' => 'Ticketing Price'),
        'option_value' => array('1' => $feeTotal),
        'option_name' => array('1' => $feeTotal),
        'option_weight' => array('1' => 1),
        'option_amount' => array('1' => 2),
        'is_display_amounts' => 1,
        'weight' => 1,
        'options_per_line' => 1,
        'is_active' => array('1' => 1),
        'price_set_id' => $this->_ids['price_set'],
        'is_enter_qty' => 1,
        'financial_type_id' => $this->getFinancialTypeId('Event Fee'),
      );

      CRM_Price_BAO_PriceField::create($paramsField);
    }
    $fields = $this->callAPISuccess('PriceField', 'get', array('price_set_id' => $this->_ids['price_set']));
    $this->_ids['price_field'] = array_keys($fields['values']);
    $fieldValues = $this->callAPISuccess('PriceFieldValue', 'get', array('price_field_id' => $this->_ids['price_field'][0]));
    $this->_ids['price_field_value'] = array_keys($fieldValues['values']);
    $this->callAPISuccess('PriceFieldValue', 'create', array('id' => $fieldValues['id'], 'count' => 1, 'amount' => $feeTotal));
    $this->_priceSetID = $this->_ids['price_set'];
    return $this->_ids['price_set'];
  }

  public function eventTypeCreate(){
    //create test eventtype
    $eventTypeName = 'Test Event Type';
    $eventTypeDetails = civicrm_api3('OptionValue', 'create', [
      'sequential' => 1,
      'option_group_id' => "event_type",
      'label' => $eventTypeName,
    ]);
    return $eventTypeDetails;
  }

  public function eventTypeDelete($optionId){
    // delete the test event type
    civicrm_api3('OptionValue', 'delete', ['id' => $optionId,]);
  }

  /**
   * Create an Event.
   *
   * @param array $params
   *   Name-value pair for an event.
   *
   * @return array
   */
  public function eventCreate($eventTypeId = 8, $params = array()) {
    // if no contact was passed, make up a dummy event creator
    if (!isset($params['contact_id'])) {
      $params['contact_id'] = $this->individualCreate(array(
        'first_name' => 'Event',
        'last_name' => 'Creator',
      ));
    }

    //Event Start and End Date
    $today = new DateTime();
    $eventStartDate = $today->format('Ymd');
    $eventEndDate   = $today->modify('+1 month');
    $eventEndDate   = $eventEndDate->format('Ymd');
    $defaultFinancialType = $this->getFinancialTypeId('Event Fee');
    //set defaults for missing params
    $params = array_merge(array(
      'title' => 'Test Ticketing Event',
      'summary' => 'DELETE_ME, This is test event create from Eventtickets module test cases',
      'description' => 'This is test event for ticketing event related test cases created from uk.co.vedaconsulting.module.Eventtickets',
      'event_type_id' => $eventTypeId,
      'is_public' => 1,
      'start_date' => $eventStartDate,
      'end_date' => $eventEndDate,
      'is_online_registration' => 1,
      'registration_start_date' => $eventStartDate,
      'registration_end_date' => $eventEndDate,
      'financial_type_id' => $defaultFinancialType,
      'contribution_type_id' => $defaultFinancialType,
      'max_participants' => 10,
      'event_full_text' => 'Sorry! We are already full',
      'fee_label' => 'Event Fee(s)',
      'is_monetary' => 1,
      'is_active' => 1,
      'is_show_location' => 0,
    ), $params);

    $result = $this->callAPISuccess('Event', 'create', $params);

    //Event Invoice Settings
    $this->_eventID = $result['id'];

    return $result;
  }

  /**
   * Create contribution.
   *
   * @param array $params
   *   Array of parameters.
   *
   * @return int
   *   id of created contribution
   */
  public function contributionCreate($params) {
    $defaultFinancialType = $this->getFinancialTypeId('Event Fee');
    $params = array_merge(array(
      'domain_id' => 1,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      // 'fee_amount' => 5.00,
      'financial_type_id' => $defaultFinancialType,
      'payment_instrument_id' => 4,
      // 'non_deductible_amount' => 10.00,
      'source' => 'Test case',
      'contribution_status_id' => 1,
    ), $params);

    $result = $this->callAPISuccess('contribution', 'create', $params);
    return $result['id'];
  }

  /**
   * Create Payment Processor.
   *
   * @return int
   *   Id Payment Processor
   */
  public function processorCreate($params = array()) {
    $processorParams = array(
      'domain_id' => 1,
      'name' => 'Dummy'.date('YmdHis'),
      'payment_processor_type_id' => 'Dummy',
      // 'financial_account_id' => 4,
      'is_test' => TRUE,
      'is_active' => 1,
      'user_name' => '',
      'url_site' => 'http://dummy.com',
      'url_recur' => 'http://dummy.com',
      'billing_mode' => 1,
      'sequential' => 1,
      'payment_instrument_id' => 'Debit Card',
    );
    $processorParams = array_merge($processorParams, $params);
    $processor = $this->callAPISuccess('PaymentProcessor', 'create', $processorParams);
    $this->_paymentProcessorID = $processor['id'];
    return $processor['id'];
  }


  /**
   * Create Participant.
   *
   * @param array $params
   *   Array of contact id and event id values.
   *
   * @return int
   *   $id of participant created
   */
  public function participantCreate($params = array()) {
    if (empty($params['contact_id'])) {
      $params['contact_id'] = $this->individualCreate();
    }
    if (empty($params['event_id'])) {
      $event = $this->eventCreate();
      $params['event_id'] = $event['id'];
    }

    $today = new DateTime();
    $registerDate = $today->format('Ymd');

    $defaults = array(
      'status_id' => 2,
      'role_id' => 1,
      'register_date' => $registerDate,
      'source' => 'TestCase',
      'event_level' => 'Payment',
      'debug' => 1,
    );

    $params = array_merge($defaults, $params);
    $result = $this->callAPISuccess('Participant', 'create', $params);
    return $result['id'];
  }

  /**
   * Create participant payment.
   *
   * @param int $participantID
   * @param int $contributionID
   * @return int
   *   $id of created payment
   */
  public function participantPaymentCreate($participantID, $contributionID = NULL) {
    //Create Participant Payment record With Values
    $params = array(
      'participant_id' => $participantID,
      'contribution_id' => $contributionID,
    );

    $result = $this->callAPISuccess('participant_payment', 'create', $params);
    return $result;
  }

  public function getContactIdFromParticipant($participantID) {
    if (empty($participantID)) {
      return NULL;
    }

    return CRM_Core_DAO::getFieldValue('CRM_Event_BAO_Participant', $participantID, 'contact_id');
  }

  /**
   * Return financial type id on basis of name
   *
   * @param string $name Financial type m/c name
   *
   * @return int
   */
  public function getFinancialTypeId($name) {
    $financialType = CRM_Contribute_PseudoConstant::financialType();
    return array_search($name, $financialType);
  }

  /**
   * Delete contact, ensuring it is not the domain contact
   *
   * @param int $contactID
   *   Contact ID to delete
   */
  public function contactDelete($contactID) {
    if ($contactID) {
      $this->callAPISuccess('Contact', 'delete', array(
        'id' => $contactID,
        'skip_undelete' => 1,
      ));
    }
  }

  /**
   * Delete event.
   *
   * @param int $id
   *   ID of the event.
   *
   * @return array|int
   */
  public function eventDelete($id) {
    $params = array(
      'event_id' => $id,
    );
    return $this->callAPISuccess('event', 'delete', $params);
  }

  /**
   * Delete participant.
   *
   * @param int $participantID
   *
   * @return array|int
   */
  public function participantDelete($participantID) {
    $params = array(
      'id' => $participantID,
    );
    $check = $this->callAPISuccess('Participant', 'get', $params);
    if ($check['count'] > 0) {
      return $this->callAPISuccess('Participant', 'delete', $params);
    }
  }

  /**
   * Delete participant.
   *
   * @param int $participantID
   *
   * @return array|int
   */
  public function contributionDelete($id) {
    $params = array(
      'id' => $id,
    );
    $check = $this->callAPISuccess('Contribution', 'get', $params);
    if ($check['count'] > 0) {
      return $this->callAPISuccess('Contribution', 'delete', $params);
    }
  }

  public function priceSetDelete($priceSetId) {
    if (empty($priceSetId)) {
      return NULL;
    }

    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_price_field_value
      WHERE price_field_id IN (
        SELECT id FROM civicrm_price_field WHERE price_set_id = {$priceSetId}
      )"
    );
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_price_field WHERE price_set_id = {$priceSetId}");
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_price_set WHERE id = {$priceSetId}");
  }

  /**
   * Initial test of submit function for paid event.
   */
  public function _regsitrationFormSubmit($contactID, $amount = 10.00, $qty = 5) {
    $contact = $this->callAPISuccess('Contact', 'getsingle', array('id' => $contactID));

    $cardExpDate = new DateTime('now');
    $cardExpDate->modify("+3 month");
    $expYear = $cardExpDate->format('Y');
    $expMonth= $cardExpDate->format('m');
    $totalAmount = $qty * $amount;

    ########################### Test Form submit Event Registration ################################

    $params = array(
      'id' => $this->_eventID,
      'contributeMode' => 'direct',
      'registerByID' => $this->_contactID,
      'paymentProcessorObj' => CRM_Financial_BAO_PaymentProcessor::getPayment($this->_paymentProcessorID),
      'totalAmount' => $totalAmount,
      'params' => array(
        array(
          'qfKey' => 'e6eb2903eae63d4c5c6cc70bfdda8741_2801',
          'entryURL' => CRM_Utils_System::url('civicrm/event/register', 'id='.$this->_eventID.'&reset=1'),
          'contact_id' => $contact['id'],
          'first_name' => $contact['first_name'],
          'last_name' => $contact['last_name'],
          'email-Primary' => CRM_Utils_Array::value('email', $contact, 'testcasecontact@example.com'),
          'hidden_processor' => '1',
          'credit_card_number' => '4111111111111111',
          'cvv2' => '123',
          'credit_card_exp_date' => array(
            'M' => $expMonth,
            'Y' => $expYear,
          ),
          'credit_card_type' => 'Visa',
          'billing_first_name' => $contact['first_name'],
          'billing_middle_name' => '',
          'billing_last_name' => $contact['last_name'],
          'billing_street_address-5' => 'dummy street address',
          'billing_city-5' => 'dummy city',
          'billing_state_province_id-5' => '1061',
          'billing_postal_code-5' => 'Dum My',
          'billing_country_id-5' => '1228',
          'scriptFee' => '',
          'scriptArray' => '',
          'priceSetId' => $this->_priceSetID,
          'price_'.$this->_ids['price_field'][0] => $qty,
          'payment_processor_id' => $this->_paymentProcessorID,
          'bypass_payment' => '',
          'MAX_FILE_SIZE' => '33554432',
          'is_primary' => 1,
          'is_pay_later' => 1,
          'campaign_id' => NULL,
          'defaultRole' => 1,
          'participant_role_id' => '1',
          'currencyID' => 'GBP',
          'amount_level' => 'Test Paymemt',
          'amount' => $totalAmount,
          'total_amount' => $totalAmount,
          'tax_amount' => NULL,
          'year' => $expYear,
          'month' => $expMonth,
          'ip_address' => '127.0.0.1',
          'invoiceID' => '57adc34957a29171948e8643ce906332',
          'button' => '_qf_Register_upload',
          'billing_state_province-5' => 'AP',
          'billing_country-5' => 'UK',
        ),
      ),
    );

    //Init form Event Confirm.
    $form = new CRM_Event_Form_Registration_Confirm();
    // This way the mocked up controller ignores the session stuff.
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_REQUEST['id']            = $form->_eventId = $params['id'];
    $_REQUEST['cid']           = $form->_cid = $contactID;
    $form->controller          = new CRM_Event_Controller_Registration();

    require_once 'CRM/Price/BAO/PriceSet.php';
    //Init Price set into form
    CRM_Price_BAO_PriceSet::initSet($form, 'civicrm_event', FALSE, $this->_priceSetID);

    //Init Line Item
    $lineItem = array();
    CRM_Price_BAO_PriceSet::processAmount($form->_values['fee'], $params['params'][0], $lineItem, $this->_priceSetID);
    $form->set('lineItem', array($lineItem));
    $form->_lineItem           = array($lineItem);
    $form->_amount             = $form->_totalAmount = CRM_Utils_Array::value('totalAmount', $params);
    $form->_priceSetId         = $this->_priceSetID;
    $form->set('params', $params['params']);

    $form->_values['custom_pre_id']   = array();
    $form->_values['custom_post_id']  = array();
    $form->_values['event']           = CRM_Utils_Array::value('event', $params);
    $form->_contributeMode            = $params['contributeMode'];

    //To have event details in form object
    $eventParams = array('id' => $params['id']);
    CRM_Event_BAO_Event::retrieve($eventParams, $form->_values['event']);
    $form->set('registerByID', $params['registerByID']);
    if (!empty($params['paymentProcessorObj'])) {
      $form->_paymentProcessor = $params['paymentProcessorObj'];
    }

    //do Confirm Registration Post process and postProcessHook.
    $form->mainProcess();

    //######################### End Form Submit ############################################

    require_once 'CRM/Core/Session.php';
    $session = CRM_Core_Session::singleton();
    $contributionID = $session->get('CreatedContributionId');

    // NEW FUNCTIONALITY - can fetch from $form->_values
    if (empty($contributionID)) {
      $contributionID = $form->_values['contributionId'];
    }

    $participantID = $form->_values['participant']['id'];

    return array($participantID, $contributionID);
  }

  public function getTicketGroupFieldNames(){
    $fieldNames = array(
      CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_FIRST_NAME,
      CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_LAST_NAME,
      CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_EMAIL,
      CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_PHONE,
      CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_COMPANY,
      CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_SPECIAL_REQUIREMENTS,
      CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_TICKET_NUMBER,
      CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_IS_MEMBER_TICKET,
      CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_MEMBERSHIP_NUMBER
    );
    return $fieldNames;
  }

  public function getTicketGroupColumnNames(){
    $fieldNames = self::getTicketGroupFieldNames();
    $cgName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
    $cgDetails = CRM_Eventtickets_Utils::getCustomGroupAndFieldDetailsByGroupName($cgName);
    $columnNames = array();
    foreach ($fieldNames as $key => $fieldName) {
      $columnNames[$fieldName] = $cgDetails['fields'][$fieldName]['column_name'];
    }
    return $columnNames;
  }

  public function updateTicketDetails($participantID, $count=1){
    $tnFName = CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_TICKET_NUMBER;
    $isMemberFName = CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_IS_MEMBER_TICKET;
    $mnFName = CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_MEMBERSHIP_NUMBER;
    $columnNames = self::getTicketGroupColumnNames();
    $cgName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
    $cgDetails = CRM_Eventtickets_Utils::getCustomGroupAndFieldDetailsByGroupName($cgName);
    $tableName = $cgDetails['table_name'];
    $ticketDetails = CRM_Eventtickets_Utils::getTicketContactDetails($participantID);
    $updatedTicketDetails = $ticketDetails;
    //Updating the ticket details
    for ($i=1; $i<= $count; $i++) {
      foreach ($cgDetails['fields'] as $fieldName => $details) {
        if($fieldName == $tnFName || $fieldName == $mnFName || $fieldName == $isMemberFName){
          continue;
        }
        else{
          $columnName = $columnNames[$fieldName];
          $updatedTicketDetails[$i][$fieldName] = $columnValue = 'Test value '.$i;
          $id = $ticketDetails[$i]['id'];
          $updateQuery = "UPDATE {$tableName} SET {$columnName} = %1 WHERE id = %2";
          $queryParams = array(
            1 => array($columnValue, 'String'),
            2 => array($id, 'Integer')
          );
          CRM_Core_DAO::executeQuery($updateQuery, $queryParams);
        }
      }
    }
    return $updatedTicketDetails;
  }

  /**
   * Initial test of submit function for updating the ticketdetails.
   */
  public function _ticketFormSubmit($participantID, $contactId, $submitValues=array()){
    ########################### Test Form submit Event Registration ################################

    $form = new CRM_Eventtickets_Form_TicketContactDetails;
    $ticketCount = CRM_Eventtickets_Utils::getNumberOfTicketsByParticipantID($participantID);
    $ticketDetails = CRM_Eventtickets_Utils::getTicketContactDetails($participantID);
    $form->_participantId = $participantID;
    $form->_contactId = $contactId;
    $form->buildQuickForm();
    $form->controller = new CRM_Core_Controller_Simple('CRM_Eventtickets_Form_TicketContactDetails', 'Ticket Contact Details');
    $cgName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
    $cgDetails = CRM_Eventtickets_Utils::getCustomGroupAndFieldDetailsByGroupName($cgName);
    $tnFName = CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_IS_MEMBER_TICKET;
    $ismemFname = CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_IS_MEMBER_TICKET;
    $mNFName = CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_MEMBERSHIP_NUMBER;
    $submitValues = array();
    for ($i=1; $i <= $ticketCount; $i++) {
      foreach ($cgDetails['fields'] as $key => $fields) {
        //Build form fields using core method
        $cgCount = !empty($ticketDetails[$i]) ? $ticketDetails[$i]['id'] : $i * -1;
        $fieldName = "custom_".$fields['id']."_".$cgCount;
        $fieldValue = $ticketDetails[$i][$fields['name']];
        if($fields['name']==$tnFName || $fields['name']==$ismemFname || $fields['name']==$mNFName){
          $form->_submitValues[$fieldName] = $fieldValue;
        }
        else{
          $form->_submitValues[$fieldName] = !empty($fieldValue) ? $fieldValue : 'Test '.$i;
        }
        $submitValues[$i]['id'] = $ticketDetails[$i]['id'];
        $submitValues[$i]['entity_id'] = $participantID;
        $submitValues[$i][$fields['name']] = $form->_submitValues[$fieldName];
      }
    }

    $form->_is_test_case = TRUE;

    $form->mainProcess();
    //Return the form submitted values.
    return array($submitValues);
  }
}
