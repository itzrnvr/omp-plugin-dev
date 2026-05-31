{**
 * templates/settingsForm.tpl
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Settings form for the Hello World plugin.
 *
 * This form allows administrators to customise the greeting message
 * displayed on the Hello World tab via the Plugin Gallery settings button.
 *}
<script>
	$(function() {ldelim}
		$('#helloWorldSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form
	class="pkp_form"
	id="helloWorldSettingsForm"
	method="POST"
	action="{url router=\PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}"
>
	{csrf}

	{fbvFormSection
		title="plugins.generic.helloWorld.settingsTitle"
		list=true
	}
		{fbvElement
			type="text"
			id="greetingMessage"
			value=$greetingMessage
			label="plugins.generic.helloWorld.settings.greetingMessage"
			description="plugins.generic.helloWorld.settings.greetingMessage.description"
			required=true
			maxlength="255"
		}
	{/fbvFormSection}

	{fbvFormButtons submitText="common.save"}
</form>
