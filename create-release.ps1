# WordPress Plugin GitHub Release Creator (PowerShell)
# Usage: .\create-release.ps1

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "WordPress Plugin GitHub Release Creator" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Get version from wp-staff-diary.php
$versionLine = Get-Content "wp-staff-diary.php" | Select-String -Pattern "Version:" | Select-Object -First 1
$version = ($versionLine -replace '.*Version:\s*', '').Trim()

Write-Host "Creating Release: v$version" -ForegroundColor Yellow
Write-Host ""

# Create temporary directory to stage files
$tempDir = Join-Path $env:TEMP "wp-staff-diary-release-$(Get-Random)"
$zipName = "wp-staff-diary.zip"

Write-Host "Step 1: Creating clean copy of plugin files..." -ForegroundColor Green
New-Item -ItemType Directory -Path "$tempDir" -Force | Out-Null

# Copy all files except exclusions (directly to temp dir, not nested folder)
$excludeFiles = @('.git', '.gitignore', 'node_modules', '.DS_Store', '*.sh', '*.ps1', 'temp-release-*', '*.zip')
Get-ChildItem -Path "." -Exclude $excludeFiles | ForEach-Object {
    Copy-Item $_.FullName -Destination "$tempDir\" -Recurse -Force -Exclude $excludeFiles
}

Write-Host "Step 2: Creating ZIP archive (files at root for WordPress)..." -ForegroundColor Green

# Remove existing zip if it exists
if (Test-Path $zipName) {
    Remove-Item $zipName -Force
}

# Create ZIP archive with files at root (use \* to compress contents, not the folder)
Compress-Archive -Path "$tempDir\*" -DestinationPath $zipName -Force

$zipSize = (Get-Item $zipName).Length / 1KB
$zipSizeRounded = [math]::Round($zipSize, 2)
Write-Host "SUCCESS: ZIP created - $zipName ($zipSizeRounded KB)" -ForegroundColor Green
Write-Host ""

Write-Host "Step 3: Creating GitHub release..." -ForegroundColor Green

# Create GitHub release
$releaseNotes = "Release v$version - Bug fixes and improvements"
gh release create "v$version" $zipName --title "v$version" --notes $releaseNotes --repo Lylie87/TCS

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "=========================================" -ForegroundColor Green
    Write-Host "SUCCESS: Release v$version created!" -ForegroundColor Green
    Write-Host "=========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "View release: https://github.com/Lylie87/TCS/releases/tag/v$version" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "The ZIP file '$zipName' is in your current directory." -ForegroundColor Yellow
    Write-Host "You can delete it after verifying the release." -ForegroundColor Yellow
} else {
    Write-Host ""
    Write-Host "ERROR: Failed to create GitHub release" -ForegroundColor Red
    Write-Host "Make sure you're authenticated with: gh auth login" -ForegroundColor Yellow
}

Write-Host ""

# Cleanup temp directory
Write-Host "Cleaning up temporary files..." -ForegroundColor Gray
Remove-Item -Recurse -Force $tempDir

Write-Host "Done!" -ForegroundColor Green
