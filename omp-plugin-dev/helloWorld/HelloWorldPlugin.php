<?php
/**
 * @file HelloWorldPlugin.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class HelloWorldPlugin
 *
 * @brief A simple OMP plugin that adds a Hello World tab to the Website Settings page.
 */

namespace APP\plugins\generic\helloWorld;

use APP\core\Request;
use APP\plugins\generic\helloWorld\classes\Settings\SettingsForm;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class HelloWorldPlugin extends GenericPlugin
{
    /**
     * @copydoc GenericPlugin::register()
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
            // Add a "Hello World" tab to the Website Settings page
            Hook::add('Template::Settings::website', [$this, 'showHelloWorldTab']);

            // Also add the tab to the Distribution settings page as a second example
            Hook::add('Template::Settings::distribution', [$this, 'showHelloWorldTab']);
        }

        return $success;
    }

    /**
     * Provide a display name for this plugin.
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.helloWorld.displayName');
    }

    /**
     * Provide a description for this plugin.
     */
    public function getDescription(): string
    {
        return __('plugins.generic.helloWorld.description');
    }

    /**
     * Add a settings action to the plugin's entry in the Plugin Gallery.
     *
     * @param Request $request
     * @param array $actionArgs
     */
    public function getActions($request, $actionArgs): array
    {
        $actions = parent::getActions($request, $actionArgs);

        if (!$this->getEnabled()) {
            return $actions;
        }

        $router = $request->getRouter();

        $linkAction = new LinkAction(
            'settings',
            new AjaxModal(
                $router->url(
                    $request,
                    null,
                    null,
                    'manage',
                    null,
                    [
                        'verb' => 'settings',
                        'plugin' => $this->getName(),
                        'category' => 'generic',
                    ]
                ),
                $this->getDisplayName()
            ),
            __('manager.plugins.settings'),
            null
        );

        array_unshift($actions, $linkAction);

        return $actions;
    }

    /**
     * Route plugin management requests.
     *
     * @param array $args
     * @param Request $request
     */
    public function manage($args, $request): JSONMessage
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $form = new SettingsForm($this);

                if (!$request->getUserVar('save')) {
                    $form->initData();
                    return new JSONMessage(true, $form->fetch($request));
                }

                $form->readInputData();
                if ($form->validate()) {
                    $form->execute();
                    return new JSONMessage(true);
                }

                return new JSONMessage(false, __('plugins.generic.helloWorld.settings.validationError'));
        }

        return new JSONMessage(false);
    }

    /**
     * Display the Hello World tab on settings pages.
     *
     * Hook callback for `Template::Settings::website` and `Template::Settings::distribution`.
     * Adds a new tab containing a configurable greeting message.
     *
     * @param string $hookName The name of the invoked hook.
     * @param array $args [&$params, $templateMgr, &$output]
     */
    public function showHelloWorldTab(string $hookName, array $args): bool
    {
        $templateMgr = &$args[1];
        $output = &$args[2];

        $templateMgr->assign([
            'helloWorldMessage' => $this->getSetting(
                $templateMgr->getRequest()->getContext()?->getId() ?? 0,
                'greetingMessage'
            ) ?: __('plugins.generic.helloWorld.defaultMessage'),
        ]);

        $output .= $templateMgr->fetch($this->getTemplateResource('helloWorldTab.tpl'));

        return false;
    }
}

// Backwards compatibility for PKP < 3.5
if (!PKP_STRICT_MODE) {
    class_alias(
        '\APP\plugins\generic\helloWorld\HelloWorldPlugin',
        '\HelloWorldPlugin'
    );
}
