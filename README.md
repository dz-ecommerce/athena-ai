# Athena AI

A powerful AI integration plugin for WordPress that provides advanced AI features and capabilities.

## Features

- Overview dashboard with quick stats and recent activity
- Feed page for displaying AI-generated content
- Settings page for managing API keys and plugin options
- Fully translatable (includes German translation)
- Modern and responsive admin interface

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Valid Athena AI API key

## Installation

1. Upload the `athena-ai` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Athena AI > Settings' and enter your API key
4. Enable the plugin features

## Usage

### Overview Page

The overview page provides quick access to important statistics and recent activity.
testtt 

### Feed Page

The feed page displays AI-generated content and updates.

### Settings Page

Configure your API key and enable/disable plugin features.

## Translation

The plugin includes German translations by default. Additional translations can be added by creating new language files in the `languages` directory.

## Development

### Directory Structure

```
athena-ai/
├── assets/
│   ├── css/
│   └── js/
├── includes/
│   ├── Admin/
│   └── Core/
├── languages/
├── templates/
│   └── admin/
└── athena-ai.php
```

### Adding New Features

1. Create new classes in the appropriate directory under `includes/`
2. Add templates in the `templates/` directory
3. Add translations to the language files
4. Update the main plugin class to initialize new features

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please create an issue in the plugin's repository or contact the plugin author.
