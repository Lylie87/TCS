# WordPress Plugin GitHub Release Creator (PowerShell)
# Usage: .\create-release.ps1
#
# This script uploads a manually-created wp-staff-diary.zip to GitHub releases
# The ZIP should be created manually to ensure correct structure

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
$zipPath = "C:\Users\alexl\TCS Git\TCS\$zipName"

# Check if manually-created ZIP exists
Write-Host "Step 1: Checking for manually-created ZIP..." -ForegroundColor Green

if (-Not (Test-Path $zipPath)) {
    Write-Host ""
    Write-Host "=========================================" -ForegroundColor Red
    Write-Host "ERROR: ZIP file not found!" -ForegroundColor Red
    Write-Host "=========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Expected location: $zipPath" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Please create wp-staff-diary.zip manually:" -ForegroundColor Yellow
    Write-Host "1. Select all plugin files and folders (NOT the parent folder)" -ForegroundColor White
    Write-Host "2. Right-click -> Send to -> Compressed (zipped) folder" -ForegroundColor White
    Write-Host "3. Name it: wp-staff-diary.zip" -ForegroundColor White
    Write-Host "4. Place it in: C:\Users\alexl\TCS Git\TCS\" -ForegroundColor White
    Write-Host "5. Run this script again" -ForegroundColor White
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
    Write-Host "The ZIP file was uploaded from:" -ForegroundColor Yellow
    Write-Host "$zipPath" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "You can safely delete the local ZIP after verifying the release." -ForegroundColor Gray
} else {
    Write-Host ""
    Write-Host "ERROR: Failed to create GitHub release" -ForegroundColor Red
    Write-Host "Make sure you're authenticated with: gh auth login" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Done!" -ForegroundColor Green
