<?php

/**
 * Ticketing Reminder, Send reminder to participant to update ticketing contact details.
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_ticketingreminder_sendreminder($params) {

  // Prepare reminder table with all participant
  CRM_Regionaleventtickets_Utils::prepareReminderTable();
  CRM_Regionaleventtickets_Utils::createReminderActivities(1);

  // Get membership renewal settings
  $settings = CRM_Regionaleventtickets_Utils::getTicketingReminderSettings();

  // Create 2nd renewal reminder activities
  if (isset($settings['enable_second_reminder']) && $settings['enable_second_reminder'] == 1) {
		CRM_Regionaleventtickets_Utils::createReminderActivities(2);
	}

  // Create 3rd renewal reminder activities
  if (isset($settings['enable_third_reminder']) && $settings['enable_third_reminder'] == 1) {
		CRM_Regionaleventtickets_Utils::createReminderActivities(3);
	}

  // Return success
  return civicrm_api3_create_success($returnValues, $params, 'Ticketingreminder', 'Sendreminder');
}