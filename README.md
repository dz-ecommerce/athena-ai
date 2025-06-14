# Athena AI

A powerful AI integration plugin for WordPress that provides advanced AI features and capabilities.

## Features

- Feed management system with categories
- Advanced feed parsing technology for all feed formats (RSS, Atom, RDF)
- Domain-based dynamic handling for problematic feeds
- Overview dashboard with quick stats and recent activity
- Feed page for displaying AI-generated content
- Settings page for managing API keys and plugin options
- Fully translatable (includes German translation)
- Modern and responsive admin interface
- Automatic updates via GitHub

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

### Feed Management

Access the feed management system through the 'Athena AI' menu in WordPress admin:

- View all feeds
- Add new feeds
- Organize feeds into categories
- Configure feed settings

### Feed Parser Features

The plugin includes a sophisticated feed parsing system that:

- Supports multiple feed formats (RSS, Atom, RDF)
- Automatically extracts content from problematic feeds
- Uses domain-based dynamic handling for special cases
- Provides fallback mechanisms for optimal content retrieval

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
│   ├── Core/
│   └── Services/
│       ├── FeedParser/
│       └── FeedProcessor/
├── languages/
├── templates/
│   └── admin/
└── athena-ai.php
```

### Release Process

The plugin uses GitHub releases for updates. To create a new release:

1. Update version numbers:

   - In `athena-ai.php`: Update both the version comment and `ATHENA_AI_VERSION` constant
   - Add new section in `CHANGELOG.md` with the changes

2. Commit changes:

   ```bash
   git add athena-ai.php CHANGELOG.md
   git commit -m "Bump version to X.Y.Z"
   ```

3. Create and push a new tag:

   ```bash
   git tag -a vX.Y.Z -m "Version X.Y.Z"
   git push origin main vX.Y.Z
   ```

4. On GitHub:
   - Go to Releases
   - Click "Draft a new release"
   - Choose the tag you just created
   - Title: "Version X.Y.Z"
   - Description: Copy the changelog entry
   - Click "Publish release"

The plugin's update checker will automatically detect the new release and notify WordPress admins.

### Version Numbering

We use [Semantic Versioning](https://semver.org/):

- MAJOR version for incompatible API changes (X.0.0)
- MINOR version for added functionality (X.Y.0)
- PATCH version for bug fixes (X.Y.Z)

### Adding New Features

1. Create new classes in the appropriate directory under `includes/`
2. Add templates in the `templates/` directory
3. Add translations to the language files
4. Update the main plugin class to initialize new features

## Updates

The plugin supports automatic updates through GitHub releases. When a new version is released:

1. You'll see the update notification in WordPress admin
2. Review the changelog
3. Click "Update Now" to install the latest version

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed list of changes.

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please create an issue in the plugin's repository or contact the plugin author.
