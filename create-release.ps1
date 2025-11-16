# WordPress Plugin GitHub Release Creator (PowerShell)
# Usage: .\create-release.ps1
#
# This script uploads wp-staff-diary.zip from the dist folder to GitHub releases
# The ZIP should be created by build-release.ps1 using 7-Zip for Linux compatibility

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "GitHub Release Uploader" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Get version from wp-staff-diary.php
$versionLine = Get-Content "wp-staff-diary.php" | Select-String -Pattern "Version:" | Select-Object -First 1
$version = ($versionLine -replace '.*Version:\s*', '').Trim()

Write-Host "Preparing Release: v$version" -ForegroundColor Yellow
Write-Host ""

$zipName = "wp-staff-diary.zip"
$rootDir = Get-Location
$distDir = Join-Path $rootDir "dist"
$zipPath = Join-Path $distDir $zipName

# Check if ZIP exists in dist folder
Write-Host "Step 1: Checking for ZIP in dist folder..." -ForegroundColor Green

if (-Not (Test-Path $zipPath)) {
    Write-Host ""
    Write-Host "=========================================" -ForegroundColor Red
    Write-Host "ERROR: ZIP file not found!" -ForegroundColor Red
    Write-Host "=========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Expected location: $zipPath" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "The ZIP file should be created by build-release.ps1" -ForegroundColor Yellow
    Write-Host "Please run: .\build-release.ps1" -ForegroundColor White
    Write-Host "Then run this script again" -ForegroundColor White
    Write-Host ""
    exit 1
}

$zipSize = (Get-Item $zipPath).Length / 1KB
$zipSizeRounded = [math]::Round($zipSize, 2)
Write-Host "SUCCESS: Found ZIP - $zipName ($zipSizeRounded KB)" -ForegroundColor Green
Write-Host ""

Write-Host "Step 2: Creating GitHub release..." -ForegroundColor Green

# Create GitHub release
$releaseNotes = "Release v$version - Bug fixes and improvements"
gh release create "v$version" $zipPath --title "v$version" --notes $releaseNotes --repo Lylie87/TCS

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "=========================================" -ForegroundColor Green
    Write-Host "SUCCESS: Release v$version created!" -ForegroundColor Green
    Write-Host "=========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "View release: https://github.com/Lylie87/TCS/releases/tag/v$version" -ForegroundColor Cyan
    Write-Host ""

    # Note: We keep the ZIP file in dist folder for verification
    # The dist folder will be cleaned on next build
} else {
    Write-Host ""
    Write-Host "ERROR: Failed to create GitHub release" -ForegroundColor Red
    Write-Host "Make sure you're authenticated with: gh auth login" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Done!" -ForegroundColor Green
