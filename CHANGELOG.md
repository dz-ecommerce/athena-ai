# Changelog

All notable changes to the Athena AI plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-05-01

### Added

- Complete plugin architecture overhaul with modern OOP structure
- New Profile management system for storing company and product information
- AI Service abstraction layer for multiple AI providers (OpenAI, Gemini, etc.)
- Modular admin interface with separate components
- Internationalization support with .pot file
- Composer and npm for dependency management
- Unit testing infrastructure
- Comprehensive documentation

### Changed

- Refactored codebase to follow PSR-4 autoloading standards
- Improved error handling and logging
- Enhanced security with proper nonce and capability checks
- Modernized admin interface with responsive design
- Streamlined plugin initialization process

### Removed

- Legacy feed parsing functionality (to be reimplemented as a module)
- Deprecated functions and classes
- Unused assets and dependencies

## [1.0.31] - 2025-04-30

### Changed

- Refactored feed parsing system to use a more generic approach
- Removed tagesschau.de-specific code in favor of domain-based dynamic handling
- Improved feed error handling and fallback mechanisms
- Enhanced feed content extraction for problematic feeds

## [1.0.30] - 2025-04-06

### Added

- Feed categories taxonomy for better organization
- Unified admin menu structure
- Proper capability management for feed editing

### Fixed

- Menu structure and hierarchy
- Feed editing permissions
- Update checker configuration

### Changed

- Improved menu organization under single Athena AI menu
- Enhanced feed management interface
- Updated capability mapping for better security

## [1.0.29] - 2025-04-06

### Changed

- Version bump for testing updates

## [1.0.28] - 2025-04-06

### Changed

- Version bump for testing updates

## [1.0.27] - 2025-04-06

### Changed

- Version bump for testing updates

## [1.0.26] - 2025-04-06

### Added

- Initial release
- Feed management system
- Settings page
- GitHub-based update system
