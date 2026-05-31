# HelloWorld Plugin for OMP

A basic OMP (Open Monograph Press) generic plugin that demonstrates correct PKP plugin structure by adding a "Hello World" tab to the settings pages.

## Features

- Adds a **"Hello World"** tab to the **Website Settings** and **Distribution Settings** pages
- Displays a configurable greeting message
- Includes a settings form in the Plugin Gallery for customising the message
- Full form validation (required field, CSRF protection, POST validation)
- Follows PKP plugin conventions: namespacing, locale files, version.xml, and backwards compatibility

## Requirements

- OMP 3.3+ / OJS 3.3+ / OPS 3.3+ (shared PKP framework)

## Installation

1. Copy the `helloWorld` directory into `<omp-root>/plugins/generic/`
2. Navigate to **Settings > Website > Plugins**
3. Find **"Hello World"** in the list of installed plugins and enable it
4. The "Hello World" tab will appear on the **Website Settings** and **Distribution Settings** pages

## Configuration

1. In the Plugin Gallery, click the **Settings** (gear) icon next to "Hello World"
2. Enter your desired greeting message (255 characters max)
3. Click **Save**

The customised message will display on all Hello World tabs.

## Plugin Structure

```
helloWorld/
├── classes/
│   └── Settings/
│       └── SettingsForm.php      # Form with validation for plugin settings
├── locale/
│   └── en/
│       └── locale.po             # English translations
├── templates/
│   ├── helloWorldTab.tpl         # Tab template injected into settings pages
│   └── settingsForm.tpl          # Settings modal form template
├── HelloWorldPlugin.php          # Main plugin class
├── version.xml                   # Plugin version metadata
└── README.md                     # This file
```

## Hooks Used

| Hook | Purpose |
|------|---------|
| `Template::Settings::website` | Injects the Hello World tab into the Website Settings page |
| `Template::Settings::distribution` | Injects the Hello World tab into the Distribution Settings page |

## Extending

To add the Hello World tab to other settings pages, register additional hooks in `HelloWorldPlugin::register()`:

```php
Hook::add('Template::Settings::workflow', [$this, 'showHelloWorldTab']);
```

## Validation

The plugin implements three layers of validation:

1. **Field required** — the greeting message field must not be empty
2. **POST validation** — form submission must be made via POST
3. **CSRF token** — requests must include a valid CSRF token

## License

This plugin is licensed under the GNU General Public License v3.
See the file `docs/COPYING` included with OMP for full terms.
