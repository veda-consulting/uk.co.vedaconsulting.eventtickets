<?php

use CRM_Eventtickets_ExtensionUtil as E;
use Civi\Test\EndToEndInterface;
// require_once 'PHPUnit/Autoload.php';
// require_once ('\PHPUnit/Framework/TestCase.php');

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - The global variable $_CV has some properties which may be useful, such as:
 *    CMS_URL, ADMIN_USER, ADMIN_PASS, ADMIN_EMAIL, DEMO_USER, DEMO_PASS, DEMO_EMAIL.
 *  - To spawn a new CiviCRM thread and execute an API call or PHP code, use cv(), e.g.
 *      cv('api system.flush');
 *      $data = cv('eval "return Civi::settings()->get(\'foobar\')"');
 *      $dashboardUrl = cv('url civicrm/dashboard');
 *  - This template uses the most generic base-class, but you may want to use a more
 *    powerful base class, such as \PHPUnit_Extensions_SeleniumTestCase or
 *    \PHPUnit_Extensions_Selenium2TestCase.
 *    See also: https://phpunit.de/manual/4.8/en/selenium.html
 *
 * @group e2e
 * @see cv
 */
class CRM_Eventtickets_TicketingTest extends CRM_Eventtickets_TestCase_Utils implements EndToEndInterface {

  public static function setUpBeforeClass() {
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md

    // Example: Install this extension. Don't care about anything else.
    \Civi\Test::e2e()->installMe(__DIR__)->apply();

    // Example: Uninstall all extensions except this one.
    // \Civi\Test::e2e()->uninstall('*')->installMe(__DIR__)->apply();

    // Example: Install only core civicrm extensions.
    // \Civi\Test::e2e()->uninstall('*')->install('org.civicrm.*')->apply();
  }

  public function setUp() {
    parent::setUp();
  }

  ////Test case001: Test the custom fields are generated or not.
  public function testTicketEvent001(){
    $cgName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
    $cFNames = $this->getTicketGroupFieldNames();
    $cgDetails = CRM_Eventtickets_Utils::getCustomGroupAndFieldDetailsByGroupName($cgName);
    $this->assertEquals($cgName, $cgDetails['name']);
    $this->assertNotNull($cgDetails['id']);
    foreach ($cFNames as $key => $value) {
      $this->assertEquals(1,array_key_exists($value, $cgDetails['fields']));
      $this->assertNotNull($cgDetails['fields'][$value]['id']);
    }
  }

  public function testTicketEvent002(){
    $fee = 10.00;
    $qty = 2;
    $processorId = $this->processorCreate();
    $contactId = $this->individualCreate();
    $priceSetId = $this->eventPriceSetCreate($fee);
    $eventCreateParams = array('contact_id'=> $contactId);

    //Creating the test Event types and will be deleting it after the test.
    //Retrive and store the default settings.
    $defaultSettings = CRM_Eventtickets_Utils::getTicketSettings();

    $testEventTypeDetails = $this->eventTypeCreate();
    $testEventTypeId = $testEventTypeDetails['values'][0]['value'];
    $testSettings = $defaultSettings;
    $testSettings['event_types'] = array($testEventTypeId);
    //Storing  the latest eventtypes to the settings.
    CRM_Core_BAO_Setting::setItem($testSettings, CRM_Eventtickets_Constants::TICKET_SETTINGS, 'ticket_settings');
    $newSettings = CRM_Eventtickets_Utils::getTicketSettings();

    //Test case002: Check the settings are saved
    $this->assertEquals($testSettings, $newSettings);
    $eventDetails = $this->eventCreate($testEventTypeId , $eventCreateParams);

    //Registering the contact id for the event created
    list($participantID, $contributionID) = $this->_regsitrationFormSubmit($contactId, $fee, $qty);

    $ticketCount = CRM_Eventtickets_Utils::getNumberOfTicketsByParticipantID($participantID);
    //Ticket count is not empty
    $this->assertNotEmpty($ticketCount);
    //Test case004: Ticket count is equal to the number of tickets purchased
    $this->assertEquals($qty, $ticketCount);
    $ticketDetails = CRM_Eventtickets_Utils::getTicketContactDetails($participantID);
    $this->assertNotEmpty($ticketDetails);
    //Test case004: Ticket count equal to the number of ticket records generated.
    $this->assertEquals($qty,count($ticketDetails));

    $tnFName = CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_TICKET_NUMBER;
    $isMemberFName = CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_IS_MEMBER_TICKET;
    $mnFName = CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_MEMBERSHIP_NUMBER;
    $cgName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
    $cgDetails = CRM_Eventtickets_Utils::getCustomGroupAndFieldDetailsByGroupName($cgName);
    $tableName = $cgDetails['table_name'];
    $tNColumn = $cgDetails['fields'][$tnFName]['column_name'];
    foreach ($ticketDetails as $id => $value) {
      //Test case005: Ticket Number is automatically generated
      $this->assertNotEmpty($value[$tnFName]);
      $tNumber = $value[$tnFName];
      $getQuery = "SELECT id FROM {$tableName} WHERE {$tNColumn} = %1";
      $queryParam = array(1 => array($tNumber,'String'));
      $dao = CRM_Core_DAO::executeQuery($getQuery, $queryParam);
      while($dao->fetch()){
        $field = $dao->toArray();
        //Ticket number is unique
        $this->assertEquals($value['id'],$field['id']);
      }
    }

    //Submiting the form edit ticket  details form and receiving the form submitted values.
    $submitValues = $this->_ticketFormSubmit($participantID, $contactId);
    $submitValues = $submitValues[0];

    // Retrieving new ticket details
    $ticketDetails = CRM_Eventtickets_Utils::getTicketContactDetails($participantID);

    // Test case 007 & 006: Check whether Given details are stored in the table exactly and able to retrieve them
    //Asserting the newer ticket values to the form submitted values
    $this->assertEquals($submitValues, $ticketDetails);

    $ticketUrlParams = CRM_Eventtickets_Utils::getTicketingDetailsURL($participantID, 'view', TRUE);
    $checkSum = CRM_Eventtickets_Utils::getContactChecksum($contactId);

    //Test case 008: verifying the checksum generated for the participantId
    $this->assertNotEmpty($contactId);
    $this->assertNotEmpty($checkSum);
    $this->assertEquals($contactId, $ticketUrlParams['cid']);
    $this->assertEquals($checkSum, $ticketUrlParams['cs']);
    //End  of Test cases.

    //deleting the test ticket details from the table.
    $deleteQuery = "DELETE FROM {$tableName} WHERE entity_id ={$participantID} ";
    CRM_Core_DAO::executeQuery($deleteQuery);
    $this->contributionDelete($contributionID);
    $this->participantDelete($participantID);
    $this->eventDelete($eventDetails['id']);
    $this->priceSetDelete($priceSetId);
    $this->contactDelete($contactId);
    // Restoring the default ticket settings
    CRM_Core_BAO_Setting::setItem($defaultSettings, CRM_Eventtickets_Constants::TICKET_SETTINGS, 'ticket_settings');
    $this->eventTypeDelete($testEventTypeDetails['id']);
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Example: Test that a version is returned.
   */
  public function testWellFormedVersion() {
    $this->assertRegExp('/^([0-9\.]|alpha|beta)*$/', \CRM_Utils_System::version());
  }

  /**
   * Example: Test that we're using a real CMS (Drupal, WordPress, etc).
   */
  public function testWellFormedUF() {
    $this->assertRegExp('/^(Drupal|Backdrop|WordPress|Joomla)/', CIVICRM_UF);
  }

}
