{* dbdSmarty *}
{if is_array($errors) && count($errors) > 0}
	<ul class="errorMsgs">
	{foreach from=$errors item=e}
		<li class="errorMsg">{$e}</li>
	{/foreach}
	</ul>
{/if}