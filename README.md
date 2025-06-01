# Athena AI

A powerful AI assistant for WordPress that helps you create and manage content with the help of artificial intelligence.

## Features

- **AI-Powered Content Generation**: Generate high-quality content using advanced AI models
- **Profile Management**: Store and manage company and product information
- **Smart Content Analysis**: Extract key information from existing content
- **Multiple AI Providers**: Support for various AI providers (OpenAI, Gemini, etc.)
- **Modern Admin Interface**: Intuitive and responsive design
- **Fully Extensible**: Built with modular architecture for easy extension
- **Multilingual Support**: Ready for translation with included .pot file
- **Secure**: Follows WordPress coding standards and security best practices

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- An API key from a supported AI provider (OpenAI, Gemini, etc.)

## Installation

1. Upload the `athena-ai` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Athena AI > Profile' to set up your company and product information
4. Configure your AI provider settings in the plugin settings

## Getting Started

### Setting Up Your Profile

1. Navigate to 'Athena AI > Profile' in your WordPress admin
2. Fill in your company details, products, and services
3. Save your profile information

### Generating Content

1. Go to any post or page editor
2. Look for the 'Athena AI' meta box
3. Enter a prompt or select a template
4. Click 'Generate' to create content
5. Review and insert the generated content into your editor

## Advanced Usage

### Content Generation

Athena AI provides several ways to generate content:

1. **Quick Generate**: Generate content directly from the post editor
2. **Templates**: Use predefined templates for common content types
3. **Custom Prompts**: Create and save your own custom prompts

### API Integration

Connect with multiple AI providers:

1. OpenAI (GPT models)
2. Google Gemini
3. And more coming soon...

### Customization

- Create custom content templates
- Define your brand voice and style
- Set content generation rules and guidelines

## Translation

The plugin is translation-ready and includes a `.pot` file in the `languages` directory. To add a new translation:

1. Copy the `.pot` file and rename it to `athena-ai-de_DE.po` (for German)
2. Translate the strings using a PO editor like Poedit
3. Save the file and compile it to a `.mo` file
4. Place both files in the `languages` directory

## Contributing

We welcome contributions! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## Support

For support, please open an issue on the [GitHub repository](https://github.com/yourusername/athena-ai).

## License

This plugin is licensed under the GPL-2.0+.

## Development

### Directory Structure

```
athena-ai/
├── assets/                  # Frontend assets (CSS, JS, images)
│   ├── css/
│   └── js/
├── includes/                # Core plugin files
│   ├── Admin/               # Admin-specific functionality
│   │   ├── Controllers/     # Request handlers
│   │   ├── Models/          # Data models
│   │   ├── Services/        # Business logic
│   │   └── Views/           # View templates
│   └── class-athena-*.php   # Core plugin classes
├── languages/               # Translation files
├── templates/               # Template files
│   └── admin/               # Admin templates
├── athena-ai.php            # Main plugin file
└── README.md                # This file
```

### Building Assets

1. Install dependencies:
   ```bash
   npm install
   ```

2. Build for development:
   ```bash
   npm run dev
   ```

3. Build for production:
   ```bash
   npm run build
   ```

### Coding Standards

This plugin follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).

### Testing

1. Run PHPUnit tests:
   ```bash
   composer test
   ```

2. Run PHP_CodeSniffer:
   ```bash
   composer lint
   ```

## Changelog

### 1.0.0
* Initial release with content generation and profile management
* Support for multiple AI providers
* Modern, responsive admin interface
* Translation-ready with .pot file

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
