<div class="crm-form-block">
	<div class="help">
		You have purchased {$numberOfTickets} tickets. Please view/update the ticket contact details below.
	</div>
	<div class="crm-submit-buttons">
	{include file="CRM/common/formButtons.tpl" location="top"}
	</div>

	<div>
		{foreach from=$customFieldSets item=fields key=i}
		{assign var=memberTicket value=$i|array_key_exists:$memberTicketFields}
	  <div class="custom-group custom-group-Ticket_Contact_Details crm-accordion-wrapper crm-custom-accordion collapsed {if $memberTicket}member_ticket_accordion{/if}">
	  	<div class="crm-accordion-header">{if $memberTicket}Member Ticket : {/if} Ticket Contact ({$i})</div>
	  	<div class="crm-accordion-body">
				<table class="form-layout-compressed">
	  		{foreach from=$fields item=field key=fieldID}
	  			<tr class="custom_field-row {$field}_-row {if $memberTicket}member_ticket_number{/if}">
			    	<td class="label">{$form.$field.label}
			    		{if $fieldID eq $membershipNoField}
			    			<span class="crm-marker" title="This field is required.">*</span>
			    		{/if}
			    	</td>
			    	<td class="html-adjust">
			    		{$form.$field.html}
			    		{if $fieldID eq $membershipNoField}
			    			<div class="description">Please enter the membership number</div>
			    		{/if}
			    	</td>
			  	</tr>
	  		{/foreach}
				</table>
	  	</div>
	  </div>
		{/foreach}
	</div>

	<div class="crm-submit-buttons">
	{include file="CRM/common/formButtons.tpl" location="bottom"}
	</div>
</div>

{literal}
<style type="text/css">
	.member_ticket_accordion div.crm-accordion-header{
		background-color: #178358;
	}
</style>
<script type="text/javascript">
	CRM.$(function($){
		var memberTicketField = "{/literal}{$memberTicketField}{literal}";
		var membershipNoField = "{/literal}{$membershipNoField}{literal}";

		$( "input[name^='custom_"+membershipNoField+"']" ).each(function(){
			var elemID = $(this).attr('id');
			$('tr.'+elemID+'_-row').hide();
		});

		$(".member_ticket_number").show();
	});
</script>
{/literal}