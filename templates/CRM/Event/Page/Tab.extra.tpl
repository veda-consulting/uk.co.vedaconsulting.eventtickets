<!-- regionaleventtickets tab.extra.tpl -->
{if $showTicketButton}

{if $action eq 4}

	<div id="view_ticketing_details_div" style="display: none;">
		<a class="button" id="view_ticketing_details" href="{$viewURL}"><span><i class="crm-i fa-pencil"></i> View Ticket Details</span></a>
	</div>

{elseif $action eq 2}

	<div id="view_ticketing_details_div" style="display: none;">
		<a class="button" id="view_ticketing_details" href="{$editURL}"><span><i class="crm-i fa-pencil"></i> Edit Ticket Details</span></a>
	</div>

{/if}

{literal}
<script type="text/javascript">
	CRM.$(function($){
		if ($('.crm-submit-buttons #view_ticketing_details').length === 0) {
			$('#view_ticketing_details').appendTo('.crm-submit-buttons');
		}
	});
</script>
{/literal}
{/if}