# Session Restoration Protocol

## Critical Build & Release Information

### Release Script Architecture

The project uses a **three-script release system** that must run sequentially:

1. **build-release.ps1** - Builds the distribution package
2. **create-release.ps1** - Uploads to GitHub releases
3. **release.ps1** - Orchestrator that runs both scripts in sequence

### Windows → Linux Compatibility Issue

**CRITICAL**: ZIP files created with native PowerShell compression (`Compress-Archive`) cause file corruption when deployed to Linux servers. Specifically:

- **Problem**: Folders extracted on CloudLinux v9.6.0 are interpreted as text files instead of directories
- **Root Cause**: PowerShell creates ZIP archives with Windows-specific metadata that Linux misinterprets
- **Solution**: Use **7-Zip** for all ZIP file creation

### Build Script Requirements (`build-release.ps1`)

**Must use 7-Zip for compression:**

```powershell
# Find 7-Zip executable
$7zipPaths = @(
    "C:\Program Files\7-Zip\7z.exe",
    "C:\Program Files (x86)\7-Zip\7z.exe",
    "$env:ProgramFiles\7-Zip\7z.exe",
    "${env:ProgramFiles(x86)}\7-Zip\7z.exe"
)

# Create Linux-compatible ZIP
& $7zipExe a -tzip "$zipFile" "wp-staff-diary" -mx=9
```

**Output Requirements:**
- Filename: `wp-staff-diary.zip` (NOT versioned like `wp-staff-diary-v2.6.2.zip`)
- Location: `dist/wp-staff-diary.zip`
- Structure: Must contain `wp-staff-diary/` folder, not loose files

### Upload Script Requirements (`create-release.ps1`)

**Must look for ZIP in dist folder:**

```powershell
$zipName = "wp-staff-diary.zip"
$rootDir = Get-Location
$distDir = Join-Path $rootDir "dist"
$zipPath = Join-Path $distDir $zipName
```

### Environment Details

- **Local Development**: Windows (PowerShell)
- **Deployment Target**: CloudLinux v9.6.0
- **Repository**: https://github.com/Lylie87/TCS
- **Branch Pattern**: `claude/*` (Claude-created branches)

### WordPress Plugin Structure

When extracted on the server, the ZIP must create:
```
wp-content/plugins/wp-staff-diary/
├── wp-staff-diary.php
├── includes/
├── admin/
├── assets/
└── ...
```

NOT:
```
wp-content/plugins/
├── wp-staff-diary.php  ← WRONG!
├── includes/           ← WRONG!
└── ...
```

### Release Process Flow

1. **Version Bump**: Update version in `wp-staff-diary.php` (header + constant)
2. **Build**: Run `.\build-release.ps1` (uses 7-Zip, creates dist/wp-staff-diary.zip)
3. **Upload**: Run `.\create-release.ps1` (reads from dist/, uploads to GitHub)
4. **Or use orchestrator**: Run `.\release.ps1` (does all steps automatically)

### Historical Issues Fixed

1. ❌ **PowerShell Compress-Archive** → ✅ **7-Zip compression**
2. ❌ **Versioned ZIP name** → ✅ **Fixed wp-staff-diary.zip name**
3. ❌ **ZIP in root directory** → ✅ **ZIP in dist/ folder**
4. ❌ **Loose files in ZIP** → ✅ **Proper wp-staff-diary/ folder structure**

### Verification Checklist

Before releasing, verify:

- [ ] 7-Zip is installed on Windows machine
- [ ] `build-release.ps1` uses 7-Zip (not Compress-Archive)
- [ ] Output file is `dist/wp-staff-diary.zip` (not versioned)
- [ ] `create-release.ps1` reads from `dist/` folder
- [ ] ZIP contains `wp-staff-diary/` folder at root
- [ ] Version number matches in plugin header and constant

### Dependencies

**Required Software:**
- 7-Zip (https://www.7-zip.org/)
- GitHub CLI (`gh`) for release uploads
- PowerShell 5.1+ on Windows

**Installation Check:**
```powershell
# Verify 7-Zip
Test-Path "C:\Program Files\7-Zip\7z.exe"

# Verify GitHub CLI
gh --version
```

### Common Errors

**Error**: "Folders appear as text files on Linux server"
- **Cause**: Used PowerShell Compress-Archive instead of 7-Zip
- **Fix**: Update build script to use 7-Zip

**Error**: "ZIP file not found in root directory"
- **Cause**: `create-release.ps1` looking in wrong location
- **Fix**: Update to look in `dist/` folder

**Error**: "WordPress can't find plugin after extraction"
- **Cause**: ZIP contains loose files instead of wp-staff-diary/ folder
- **Fix**: Ensure 7-Zip command zips the folder, not just its contents

**Error**: "Virus detected: Sanesecurity.Foxhole.JS_Zip_23.UNOFFICIAL"
- **Cause**: False positive from overly aggressive Sanesecurity heuristic scanner
- **Issue**: Heuristic flags any ZIP containing JavaScript files (normal for WordPress plugins)
- **Verification**: Plugin contains only clean, unobfuscated JS in `assets/js/admin.js`
- **Fix Options**:
  1. Upload via FTP/SFTP instead of web uploader (bypasses scanner)
  2. Extract locally and upload the `wp-staff-diary/` folder directly
  3. Use WordPress Dashboard → Plugins → Add New → Upload Plugin
  4. Contact host support to whitelist (provide GitHub URL as proof: https://github.com/Lylie87/TCS)
  5. Temporarily disable malware scanner in cPanel if available

## Last Updated

Version: 2.6.2
Date: 2025-11-16
