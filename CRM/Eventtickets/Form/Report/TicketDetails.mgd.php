<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 =>
  array (
    'name' => 'CRM_Eventtickets_Form_Report_TicketDetails',
    'entity' => 'ReportTemplate',
    'params' =>
    array (
      'version' => 3,
      'label' => 'Ticket Details',
      'description' => 'Ticket Details (uk.co.vedaconsulting.eventtickets)',
      'class_name' => 'CRM_Eventtickets_Form_Report_TicketDetails',
      'report_url' => 'uk.co.vedaconsulting.eventtickets/ticketdetails',
      'component' => 'CiviEvent',
    ),
  ),
);
