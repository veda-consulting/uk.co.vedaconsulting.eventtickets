<?php
use CRM_Eventtickets_ExtensionUtil as E;

class CRM_Eventtickets_Page_TicketContactDetails extends CRM_Core_Page {

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(E::ts('Ticket Contact Details'));

    $participantId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, NULL, 'REQUEST');
    $existingTicketingDetails = CRM_Eventtickets_Utils::getTicketContactDetails($participantId);
    $numberOfTickets = CRM_Eventtickets_Utils::getNumberOfTicketsByParticipantID($participantId);

    $headers = CRM_Eventtickets_Utils::getTicketingDetailsTableHeader();
    $editURL = CRM_Eventtickets_Utils::getTicketingDetailsURL($participantId, 'edit');
    $eventId = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_Participant', $participantId, 'event_id');

    // Changes related to regional dashboard display

    $this->assign('headers', $headers);
    $this->assign('ticketingContacts', $existingTicketingDetails);
    $this->assign('numberOfTickets', $numberOfTickets);
    $this->assign('existingCount', count($existingTicketingDetails));
    $this->assign('event_id', $eventId);
    $this->assign('editURL', $editURL);
    $this->assign('isMemberTicketFieldName', CRM_Eventtickets_Constants::CF_TICKETING_CONTACT_IS_MEMBER_TICKET);

    parent::run();
  }

}
