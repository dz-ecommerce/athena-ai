# Release Checklist

## Pre-Release
- [ ] Update version number in `athena-ai.php`
- [ ] Update version constant in `athena-ai.php`
- [ ] Update CHANGELOG.md with latest changes
- [ ] Test all new features and fixes
- [ ] Run WordPress coding standards check
- [ ] Ensure all translations are up to date
- [ ] Commit all changes to main branch

## Creating the Release
1. Run the build script:
   ```powershell
   .\build.ps1
   ```

2. Create and push the tag:
   ```bash
   git tag -a v1.0.30 -m "Version 1.0.30 - Feed Categories and Menu Improvements"
   git push origin v1.0.30
   ```

3. The GitHub Action will:
   - Create a draft release
   - Build the plugin
   - Upload the ZIP file
   - Attach the changelog

4. On GitHub:
   - Go to Releases
   - Find the draft release
   - Review the changelog
   - Publish the release

## Post-Release
- [ ] Verify the update appears in WordPress
- [ ] Test the update process
- [ ] Update development version number
- [ ] Create new milestone for next version

## Testing the Update
1. Install the previous version on a test site
2. Configure the GitHub updater
3. Verify update notification appears
4. Test the update process
5. Verify all features work after update
