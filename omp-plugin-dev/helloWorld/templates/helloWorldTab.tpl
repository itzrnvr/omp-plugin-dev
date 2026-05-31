{**
 * templates/helloWorldTab.tpl
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Hello World tab content for settings pages.
 *
 * This template is injected via the call_hook mechanism and renders
 * as a tab inside the <tabs> block on the Website and Distribution
 * settings pages.
 *
 * The <tab> element integrates with PKP's Vue.js SettingsPage component.
 *}
<tab id="helloWorld" label="{translate key="plugins.generic.helloWorld.tabTitle"}">
	<p>{$helloWorldMessage|escape}</p>
</tab>
