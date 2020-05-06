<?php
use CRM_Eventtickets_ExtensionUtil as E;

class CRM_Eventtickets_Form_Report_TicketDetails extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL;

  protected $_ticketingDetailsTableName = NULL;
  protected $_filterByEventId = NULL;
  protected $_ticketingCustomFields = array();

  protected $_customGroupExtends = array('Participant');
  protected $_customGroupGroupBy = FALSE; function __construct() {

    $this->_filterByEventId = CRM_Utils_Request::retrieve('eid', 'Positive', CRM_Core_DAO::$_nullArray);

    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'sort_name' => array(
            'title' => E::ts('Participant Name'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'display_name' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'first_name' => array(
            'title' => E::ts('First Name'),
            'no_repeat' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'last_name' => array(
            'title' => E::ts('Last Name'),
            'no_repeat' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' => array(
          'sort_name' => array(
            'title' => E::ts('Contact Name'),
            'operator' => 'like',
          ),
          'id' => array(
            'no_display' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_participant' => array(
        'dao' => 'CRM_Event_DAO_Participant',
        'fields' => array(
          'participant_id' => array(
            'title' => ts('Participant ID'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
          'participant_record' => array(
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          // 'event_id' => array(
          //   'default' => TRUE,
          //   'type' => CRM_Utils_Type::T_STRING,
          // ),
          'status_id' => array(
            'title' => ts('Status'),
            // 'default' => TRUE,
          ),
          'role_id' => array(
            'title' => ts('Role'),
            // 'default' => TRUE,
          ),
          'fee_currency' => array(
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'registered_by_id' => array(
            'title' => ts('Registered by Participant ID'),
          ),
          'source' => array(
            'title' => ts('Source'),
          ),
          'participant_fee_level' => NULL,
          'participant_fee_amount' => array('title' => ts('Participant Fee')),
          'participant_register_date' => array('title' => ts('Registration Date')),
          'total_paid' => array(
            'title' => ts('Total Paid'),
            'dbAlias' => 'SUM(ft.total_amount)',
            'type' => 1024,
          ),
          'balance' => array(
            'title' => ts('Balance'),
            'dbAlias' => 'participant_civireport.fee_amount - SUM(ft.total_amount)',
            'type' => 1024,
          ),
        ),
        'grouping' => 'event-fields',
        'filters' => array(
          'event_id' => array(
            'name' => 'event_id',
            'title' => ts('Event'),
            'operatorType' => CRM_Report_Form::OP_ENTITYREF,
            'type' => CRM_Utils_Type::T_INT,
            'default' => empty($this->_filterByEventId) ? NULL : $this->_filterByEventId,
            'attributes' => array(
              'entity' => 'event',
              // 'api' => array('event_type_id' =>  22),
              'select' => array('minimumInputLength' => 0),
            ),
          ),
          'sid' => array(
            'name' => 'status_id',
            'title' => ts('Participant Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label'),
          ),
          'rid' => array(
            'name' => 'role_id',
            'title' => ts('Participant Role'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantRole(),
          ),
          'participant_register_date' => array(
            'title' => ts('Registration Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
        ),
        'order_bys' => array(
          'participant_register_date' => array(
            'title' => ts('Registration Date'),
            'default_weight' => '1',
            'default_order' => 'ASC',
          ),
          'event_id' => array(
            'title' => ts('Event'),
            'default_weight' => '1',
            'default_order' => 'ASC',
          ),
        ),
      ),
      'civicrm_event' => array(
        'dao' => 'CRM_Event_DAO_Event',
        'fields' => array(
          'event_id' => array(
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
            // 'title' => ts('Event Type'),
          ),
          'title' => array(
            'default' => TRUE,
            'title' => ts('Event'),
          ),
          'event_type_id' => array(
            'title' => ts('Event Type'),
          ),
          'event_start_date' => array(
            'title' => ts('Event Start Date'),
          ),
          'event_end_date' => array(
            'title' => ts('Event End Date'),
          ),
        ),
        'grouping' => 'event-fields',
        'filters' => array(
          'event_start_date' => array(
            'title' => ts('Event Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'event_end_date' => array(
            'title' => ts('Event End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
        ),
        'order_bys' => array(
          'event_type_id' => array(
            'title' => ts('Event Type'),
            'default_weight' => '2',
            'default_order' => 'ASC',
          ),
          'event_start_date' => array(
            'title' => ts('Event Start Date'),
          ),
        ),
      ),
      'civicrm_line_item' => array(
        'dao' => 'CRM_Price_DAO_LineItem',
        // 'grouping' => 'priceset-fields',
        'fields' => array(
          'qty' => array(
            'title' => ts('Number of Tickets'),
            'dbAlias' => "SUM(line_item_civireport.qty)",
            // 'default' => TRUE,
            // 'no_repeat' => TRUE,
          ),
          'ticket_count' => array(
            'name' => 'qty',
            'dbAlias' => "SUM(line_item_civireport.qty)",
            'default' => TRUE,
            'required' => TRUE,
            'no_display' => TRUE,
          ),
        ),
      ),
    );

    parent::__construct();

    $cGroupName = CRM_Eventtickets_Constants::CG_TICKETING_CONTACT_DETAILS;
    $cgDetails  = CRM_Eventtickets_Utils::getCustomGroupAndFieldDetailsByGroupName($cGroupName);
    $tableName = $cgDetails['table_name'];

    $this->_ticketingDetailsTableName = $cgDetails['table_name'];
    foreach ($cgDetails['fields'] as $key => $fields) {
      $this->_ticketingCustomFields[$fields['id']] = 'custom_'.$fields['id'];
    }

    if (!empty($this->_columns[$this->_ticketingDetailsTableName])) {
      $ticketingCustomSet =& $this->_columns[$this->_ticketingDetailsTableName];
      $ticketingCustomSet['fields']['ticketing_id'] = array(
        'name' => 'id',
        'no_display' => TRUE,
        'required' => TRUE,
      );
      foreach ($cgDetails['fields'] as $key => $ticketingField) {
        $ticketingCustomSet['fields']['custom_'.$ticketingField['id']]['default'] = TRUE;
      }
    }
    else{
      $this->_columns[$this->_ticketingDetailsTableName] = array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'extends' => 'Participant',
        'grouping' => $this->_ticketingDetailsTableName,
        'fields' => array(
          'ticketing_id' => array(
            'name'       => 'id',
            'no_display' => TRUE,
            'required'    => TRUE,
          ),
        ),
      );
    }
  }

  function preProcess() {
    $this->assign('reportTitle', E::ts('Membership Detail Report'));
    $this->_instanceValues['permission'] = "access RegionalDashboard";
    parent::preProcess();
  }

  function select() {
    $select = $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
      FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}

      INNER JOIN civicrm_participant {$this->_aliases['civicrm_participant']}
        ON ({$this->_aliases['civicrm_contact']}.id =
          {$this->_aliases['civicrm_participant']}.contact_id AND {$this->_aliases['civicrm_participant']}.is_test = 0)

      INNER JOIN civicrm_event {$this->_aliases['civicrm_event']}
        ON ({$this->_aliases['civicrm_participant']}.event_id = {$this->_aliases['civicrm_event']}.id)

      LEFT JOIN civicrm_line_item {$this->_aliases['civicrm_line_item']}
        ON ( {$this->_aliases['civicrm_line_item']}.entity_id = {$this->_aliases['civicrm_participant']}.id
          AND {$this->_aliases['civicrm_line_item']}.entity_table = 'civicrm_participant' )
   ";

    // if the user already selected, means left join of custom table would automatically added to from clause
    // Otherwise, add Left join of ticketing custom table.
    $includeTicketingTable = TRUE;
    foreach ($this->_ticketingCustomFields as $ticketingFieldId) {
      if (array_key_exists($ticketingFieldId, $this->_params['fields'])) {
        $includeTicketingTable = FALSE;
        break;
      }
    }

    if ($includeTicketingTable) {
      $this->_from .= "LEFT JOIN {$this->_ticketingDetailsTableName} {$this->_aliases[$this->_ticketingDetailsTableName]}
        ON {$this->_aliases[$this->_ticketingDetailsTableName]}.entity_id = {$this->_aliases['civicrm_participant']}.id";
    }
  }

  function where() {
    $ticketingEventTypeID = CRM_Eventtickets_Utils::getTicketEventTypeID();
    $clauses = array();
    $clauses[] = "({$this->_aliases['civicrm_event']}.event_type_id = {$ticketingEventTypeID})";
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_participant']}.id, {$this->_aliases[$this->_ticketingDetailsTableName]}.id";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_participant']}.id";
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery(TRUE);

    // Create temp table
    // $this->_tempTableName = CRM_Core_DAO::createTempTableName('veda_ticketing_details');
    $this->_tempTableName = 'veda_ticketing_details';

    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS {$this->_tempTableName}");

    $tempQuery = "CREATE TABLE {$this->_tempTableName} AS {$sql}";
    CRM_Core_DAO::executeQuery($tempQuery);

    $participantRecordCol = 'civicrm_participant_participant_record';
    $dao  = CRM_Core_DAO::executeQuery("SELECT * FROM {$this->_tempTableName} GROUP BY {$participantRecordCol}");
    $insertColumns = array('id' => 'civicrm_contact_id'
      , 'participant_record' => 'civicrm_participant_participant_record'
      , 'ticketing_id' => 'civicrm_value_ticketing_contact_details_74_ticketing_id'
    );
    $selectedFields = $this->_params['fields'];

    $defaultFields = array(
      'sort_name'   => 'civicrm_contact_sort_name',
      'display_name'=> 'civicrm_contact_display_name',
      'id'          => 'civicrm_contact_id',
      'event_id'    => 'civicrm_event_event_id',
      'event_title' => 'civicrm_event_title',
      'participant_id'=> 'civicrm_participant_participant_id',
      'qty'         => 'civicrm_line_item_qty',
    );

    foreach ($this->_ticketingCustomFields as $key => $value) {
      $defaultFields[$value] = $this->_ticketingDetailsTableName.'_'.$value;
    }

    foreach ($defaultFields as $key => $value) {
      if (array_key_exists($key, $selectedFields)) {
        $insertColumns[$key] = $value;
      }
    }

    while ($dao->fetch()) {
      $insertValues   = array();
      $participantId  = $dao->civicrm_participant_participant_record;
      $noOfTickets    = ($dao->civicrm_line_item_ticket_count * 100 / 100);
      $existingTickets= CRM_Eventtickets_Utils::getTicketContactDetails($participantId);
      $ticketingCount = count($existingTickets);
      if ($ticketingCount < $noOfTickets) {
        for ($i = $ticketingCount; $i < $noOfTickets; $i++) {
          $insertValueArray = array();
          foreach ($insertColumns as $key => $fields) {
            $insertValueArray[$key] = 'NULL';
            if (in_array($key, array('id', 'participant_record', 'event_id', 'event_title', 'sort_name', 'display_name', 'qty', 'participant_id'))) {
              $insertValueArray[$key] = "'".$dao->$fields."'";
            }

            if (in_array($key, $this->_ticketingCustomFields)) {
              $insertValueArray[$key] = "' Registered by ".$dao->civicrm_contact_display_name."'";
            }
          }

          $insertValues[] = "(".implode(', ', $insertValueArray).")";
        }
        $insertSQl = "INSERT INTO {$this->_tempTableName} (".implode(', ', $insertColumns).") VALUES ".implode(', ', $insertValues);
        CRM_Core_DAO::executeQuery($insertSQl);
      }
    }

    $rows = array();
    $newSQL = "SELECT * FROM {$this->_tempTableName} ORDER BY civicrm_contact_sort_name, civicrm_participant_participant_record";
    $this->buildRows($newSQL, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();

    foreach ($rows as $rowNum => $row) {

      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row
        $repeatFound = FALSE;
        foreach ($row as $colName => $colVal) {
          if (CRM_Utils_Array::value($colName, $checkList) &&
            is_array($checkList[$colName]) &&
            in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
            $repeatFound = TRUE;
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      if (array_key_exists('civicrm_membership_membership_type_id', $row)) {
        if ($value = $row['civicrm_membership_membership_type_id']) {
          $rows[$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = E::ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_participant_event_id', $row) && array_key_exists('civicrm_event_title', $row)) {
        if ($value = $row['civicrm_event_title']) {
          $rows[$rowNum]['civicrm_participant_event_id'] = $value;
        }
        $entryFound = TRUE;
      }

      $qtyEnabled = FALSE;
      if (array_key_exists('civicrm_line_item_qty', $row)) {
        if ($value = $row['civicrm_line_item_qty']) {
          $rows[$rowNum]['civicrm_line_item_qty'] = ($value * 100) / 100;
        }
        $qtyEnabled = TRUE;
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_participant_participant_id', $row)) {
        if ($value = $row['civicrm_participant_participant_id']) {
          $urlParams = array(
            'reset' => 1,
            'id'    => $value,
            'cid'   => $row['civicrm_contact_id'],
            'action'=> 'view',
            'context'=> 'participant',
            'selectedChild'=> 'event',
          );
          $noOfTickets = CRM_Eventtickets_Utils::getNumberOfTicketsByParticipantID($value);
          if (!$qtyEnabled) {
            $value = $value. " ($noOfTickets)";
          }
          // $rows[$rowNum]['civicrm_participant_participant_id'] = CRM_Utils_System::href($value, 'civicrm/contact/view/participant', $urlParams);
          $rows[$rowNum]['civicrm_participant_participant_id'] = $value;
        }
        $entryFound = TRUE;
      }

      if (!$entryFound) {
        break;
      }
    }
  }

}
