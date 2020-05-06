<?php
/**
 * File to manage, all constants and defined variables in one place
 *
 * @package CiviCRM
 */
class CRM_Eventtickets_Constants {
  CONST EVENT_TYPE_TICKET_EVENT                 = 'Ticket Event'
    // Ticketing contact details custom group
    , CG_TICKETING_CONTACT_DETAILS              = 'Ticket_Contact_Details'
    , CF_TICKETING_CONTACT_FIRST_NAME           = 'First_Name'
    , CF_TICKETING_CONTACT_LAST_NAME            = 'Last_Name'
    , CF_TICKETING_CONTACT_EMAIL                = 'Email'
    , CF_TICKETING_CONTACT_PHONE                = 'Phone'
    , CF_TICKETING_CONTACT_COMPANY              = 'Company'
    , CF_TICKETING_CONTACT_SPECIAL_REQUIREMENTS = 'Special_Requirements'
    , CF_TICKETING_CONTACT_TICKET_NUMBER        = 'Ticket_Number'
    , CF_TICKETING_CONTACT_IS_MEMBER_TICKET     = 'Is_Member_Ticket'
    , CF_TICKETING_CONTACT_MEMBERSHIP_NUMBER    = 'Membership_Number'

    , TICKETING_CONTACT_SCHEDULED_ACTIVITY_STATUS_ID = 1
    , TICKETING_CONTACT_COMPLETED_ACTIVITY_STATUS_ID = 2

    , EVENT_TICKET_URL_PATH     = 'civicrm/event/ticket'
    , TICKET_SETTINGS   = 'Ticket_Settings'
  ;
}