
<div>
	<div class="help help-text">
		You have purchased {$numberOfTickets} tickets, Please click 'Edit Ticket Details' button to update details.
	</div>
	<table id="ticketingContact" class="table table-bordered">
		<thead>
			<tr>
				<th>#</th>
				{foreach from=$headers item=header}
				<th>{$header}</th>
				{/foreach}
			</tr>
		</thead>
		<tbody>
			{foreach from=$ticketingContacts item=ticketingContact key=ticket_count}
			<tr>
				<td>{$ticket_count}</td>
				{foreach from=$headers item=header key=headerKey}
					{if $headerKey eq $isMemberTicketFieldName}
						<td align="center">
						{if $ticketingContact.$headerKey eq 1}
							<img src="/sites/all/modules/civicrm/i/check.gif" alt="Default">
						{/if}
						</td>
					{else}
						<td>{$ticketingContact.$headerKey}</td>
					{/if}
				{/foreach}
			</tr>
			{/foreach}
		</tbody>
	</table>

	<div class="crm-submit-buttons">
		<span class="crm-button crm-button-type-cancel crm-i-button">
      <i class="crm-i fa-times"></i>
      <input class="crm-form-submit default cancel" crm-icon="fa-times" name="_qf_ParticipantView_cancel" value="Done" type="submit" id="_qf_ParticipantView_cancel-top">
    </span>
		<a class="button" href="{$editURL}"><span><i class="crm-i fa-pencil"></i> Edit Ticket Details</span></a>
	</div>
</div>

{literal}
<script type="text/javascript">
	CRM.$(function($){
		$('#ticketingContact').dataTable();
	});
</script>
{/literal}