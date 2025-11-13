# GitHub Auto-Update Guide

This plugin now supports automatic updates from GitHub! Here's how it works:

## How It Works

The plugin checks GitHub for new releases and displays update notifications in WordPress, just like official WordPress.org plugins.

## Quick Release (Automated) ⚡

After making changes and committing them, simply run:

```bash
./create-release.sh
```

**That's it!** The script automatically:
- ✅ Reads the version from `wp-staff-diary.php`
- ✅ Creates a clean ZIP file (excludes .git, node_modules, etc.)
- ✅ Creates a GitHub release with proper tag
- ✅ Uploads the ZIP to GitHub
- ✅ WordPress users get update notifications instantly!

### With Custom Release Notes:

```bash
./create-release.sh 2.0.15 "Fixed payment section and WordPress 6.7 compatibility"
```

---

## Manual Release Process

If you prefer manual control or the script isn't available:

### 1. Update the Version Number

Edit `wp-staff-diary.php` and bump the version:
```php
* Version: 2.0.15  // Increment this
define('WP_STAFF_DIARY_VERSION', '2.0.15');  // And this
```

### 2. Commit and Push
```bash
git add -A
git commit -m "Version 2.0.15: Description of changes"
git push origin main
```

### 3. Run the Release Script
```bash
./create-release.sh
```

Or manually via GitHub Web Interface:
1. Go to https://github.com/Lylie87/TCS/releases
2. Click **"Draft a new release"**
3. Tag: `v2.0.15` (must start with 'v')
4. Title: `Version 2.0.15`
5. Upload `wp-staff-diary.zip` (create manually)
6. Add release notes
7. Click **"Publish release"**

### 4. WordPress Detects the Update!

Within minutes, users see:
- **"Update Available"** notification in Plugins page
- One-click update button
- Changelog from release notes

## Important Notes

- ✅ **Tag format**: Always use `v` prefix (e.g., `v2.0.13`)
- ✅ **Version consistency**: Tag version must match plugin version
- ✅ **Public repo**: Repository must be public for auto-updates
- ✅ **Release notes**: These appear as changelog in WordPress

## Testing Updates

To test without releasing:
1. Create a draft release (don't publish)
2. WordPress won't detect it until published
3. When ready, publish the release

## Forcing Update Check

If WordPress hasn't detected the update yet:
1. Go to **Dashboard → Updates**
2. Click **"Check Again"** button
3. The update should appear

## Configuration

The auto-updater is configured in `wp-staff-diary.php`:
```php
new WP_Staff_Diary_GitHub_Updater(
    __FILE__,
    'Lylie87',  // Your GitHub username
    'TCS'       // Your repository name
);
```

If you change the repository, update these values.

## Troubleshooting

### Update not showing?
- Check tag format has 'v' prefix
- Ensure release is published (not draft)
- Version in tag matches version in plugin file
- Repository is public

### Update fails?
- Check GitHub is accessible
- Verify repository URL is correct
- Ensure user has write permissions to wp-content/plugins

---

**That's it!** Every time you create a GitHub release, WordPress users will automatically get update notifications! 🎉
