{* dbdSmarty *}
	</div>
	<div id="pageFoot">
		<p class="legal">&copy; {$smarty.now|date:'Y'} Don't Blink Design, Inc. All Rights Reserved</p>
	{if $this.controller == "index"}
		<p class="dbdFoot">Web Presence Developed By<br />:: <a href="http://www.dontblinkdesign.com" title="Don't Blink Design - Web Development &amp; Graphic Design - Santa Monica" target="dbd">Don't Blink Design</a> ::</p>
	{/if}
	</div>
</div>
{*include file="global/_googleAnalytics.tpl"*}
</body>
</html>