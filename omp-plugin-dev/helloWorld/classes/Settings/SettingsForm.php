<?php
/**
 * @file classes/Settings/SettingsForm.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SettingsForm
 *
 * @brief Form for configuring the HelloWorld plugin's greeting message.
 * Implements PKP form validation patterns for data integrity.
 */

namespace APP\plugins\generic\helloWorld\classes\Settings;

use APP\plugins\generic\helloWorld\HelloWorldPlugin;
use PKP\form\Form;

class SettingsForm extends Form
{
    /** @var HelloWorldPlugin The plugin instance. */
    protected HelloWorldPlugin $plugin;

    /**
     * Constructor.
     *
     * @param HelloWorldPlugin $plugin The plugin instance.
     */
    public function __construct(HelloWorldPlugin &$plugin)
    {
        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->plugin = &$plugin;

        // Add form validation checks
        $this->addCheck(
            new \PKP\form\validation\FormValidator(
                $this,
                'greetingMessage',
                'required',
                'plugins.generic.helloWorld.settings.greetingMessage.required'
            )
        );

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    /**
     * Initialize form data from plugin settings.
     */
    public function initData(): void
    {
        $contextId = $this->getContextId();
        $this->setData(
            'greetingMessage',
            $this->plugin->getSetting($contextId, 'greetingMessage')
        );
        parent::initData();
    }

    /**
     * Assign form template variables.
     *
     * @param \PKP\template\PKPTemplateManager $templateMgr
     */
    public function display($request = null, $template = null): void
    {
        $templateMgr = \PKP\template\TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        parent::display($request, $template);
    }

    /**
     * Execute the form, saving the plugin settings.
     */
    public function execute(...$functionArgs): void
    {
        $contextId = $this->getContextId();
        $this->plugin->updateSetting(
            $contextId,
            'greetingMessage',
            $this->getData('greetingMessage'),
            'string'
        );
        parent::execute(...$functionArgs);
    }

    /**
     * Get the context ID, defaulting to 0 for site-wide settings.
     */
    protected function getContextId(): int
    {
        $request = \PKP\core\PKPApplication::get()->getRequest();
        return $request->getContext()?->getId() ?? 0;
    }
}
