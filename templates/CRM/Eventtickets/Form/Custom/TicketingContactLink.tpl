<div id="ticketingContactLink_div" class="help">
	<a href="{$ticketingContactLink}" title="Add Ticket Contacts" class="btn btn-default"> Add Ticket Contact Details </a>
</div>

{literal}
<style type="text/css">
	#ticketingContactLink_div {
		margin-bottom: 2.5em;
    padding: 20px 35%;
    border-radius: 3px;
	}

	#ticketingContactLink_div a {
    float: unset;
    width: auto;
	}
</style>
<script type="text/javascript">
	CRM.$(function($){
		$('#ticketingContactLink_div').insertBefore($('div.event_info-group'));
	});
</script>
{/literal}