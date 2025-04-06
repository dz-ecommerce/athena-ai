# Release Checklist

Use this checklist when preparing a new release of the Athena AI plugin.

## Pre-Release Checks

### Code Quality
- [ ] All PHP files follow WordPress coding standards
- [ ] No debug code or console.log statements left
- [ ] All functions are properly documented
- [ ] No hardcoded paths or URLs
- [ ] All text is translatable

### Testing
- [ ] Test all new features and changes
- [ ] Test on a clean WordPress installation
- [ ] Test update process from previous version
- [ ] Test with both default and custom theme
- [ ] Verify all AJAX calls work
- [ ] Check for JavaScript errors in console
- [ ] Test with debug mode enabled

### Documentation
- [ ] Update version number in `athena-ai.php`
- [ ] Update `ATHENA_AI_VERSION` constant
- [ ] Update CHANGELOG.md with all changes
- [ ] Verify README.md is current
- [ ] Check all inline documentation is up to date

## Release Process

1. **Final Testing**
   - [ ] Run on a fresh WordPress install
   - [ ] Verify update checker configuration
   - [ ] Test automatic update detection

2. **Version Update**
   ```php
   * Version: X.Y.Z
   define('ATHENA_AI_VERSION', 'X.Y.Z');
   ```

3. **Update Changelog**
   - [ ] Add new version section
   - [ ] List all changes under appropriate categories:
     - Added (new features)
     - Changed (changes in existing functionality)
     - Deprecated (soon-to-be removed features)
     - Removed (now removed features)
     - Fixed (any bug fixes)
     - Security (in case of vulnerabilities)

4. **Create Release**
   ```bash
   # Commit version changes
   git add athena-ai.php CHANGELOG.md
   git commit -m "Bump version to X.Y.Z"

   # Create and push tag
   git tag -a vX.Y.Z -m "Version X.Y.Z"
   git push origin main vX.Y.Z
   ```

5. **On GitHub**
   - [ ] Go to Releases page
   - [ ] Click "Draft a new release"
   - [ ] Choose the new tag
   - [ ] Title: "Version X.Y.Z"
   - [ ] Copy changelog entry to description
   - [ ] Publish release

## Post-Release

1. **Verify Release**
   - [ ] Check GitHub release page
   - [ ] Verify ZIP file is downloadable
   - [ ] Test update notification in WordPress

2. **Test Update Process**
   - [ ] Install previous version on test site
   - [ ] Verify update notification appears
   - [ ] Test update process
   - [ ] Verify all features work after update

3. **Communication**
   - [ ] Notify team of new release
   - [ ] Update any relevant documentation
   - [ ] Respond to any initial feedback

## Emergency Hotfix Process

If a critical bug is found in a release:

1. **Create Hotfix**
   - [ ] Create hotfix branch from tag
   - [ ] Fix the critical issue
   - [ ] Bump patch version
   - [ ] Update changelog

2. **Release Hotfix**
   - [ ] Follow normal release process
   - [ ] Mark previous release as deprecated
   - [ ] Add note to changelog about hotfix

3. **Post-Hotfix**
   - [ ] Notify users of critical update
   - [ ] Document issue and solution
   - [ ] Review what caused the issue
