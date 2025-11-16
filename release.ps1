# Complete Release Orchestrator
# Handles the full release process: clean, pull, build, and upload

param(
    [string]$Version = $null
)

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Staff Diary - Complete Release Process" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Get version from plugin file if not provided
if (-not $Version) {
    $pluginFile = Get-Content "wp-staff-diary.php" -Raw
    if ($pluginFile -match 'Version:\s*(\d+\.\d+\.\d+)') {
        $Version = $Matches[1]
        Write-Host "Detected version: $Version" -ForegroundColor Green
    } else {
        Write-Host "ERROR: Could not detect version from wp-staff-diary.php" -ForegroundColor Red
        exit 1
    }
}

Write-Host ""

# Step 1: Clean up dist folder
Write-Host "Step 1: Cleaning dist folder..." -ForegroundColor Yellow
if (Test-Path "dist") {
    Remove-Item -Path "dist" -Recurse -Force
    Write-Host "[OK] Dist folder cleaned" -ForegroundColor Green
} else {
    Write-Host "[OK] Dist folder doesn't exist (nothing to clean)" -ForegroundColor Green
}
Write-Host ""

# Step 2: Pull latest changes
Write-Host "Step 2: Pulling latest changes from branch..." -ForegroundColor Yellow
$currentBranch = git branch --show-current
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to get current branch" -ForegroundColor Red
    exit 1
}

Write-Host "Current branch: $currentBranch" -ForegroundColor Cyan
git pull origin $currentBranch
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to pull latest changes" -ForegroundColor Red
    exit 1
}
Write-Host "[OK] Latest changes pulled" -ForegroundColor Green
Write-Host ""

# Step 3: Build distribution package
Write-Host "Step 3: Building distribution package..." -ForegroundColor Yellow
& .\build-release.ps1 -Version $Version
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Build failed" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 4: Upload to GitHub releases
Write-Host "Step 4: Uploading to GitHub releases..." -ForegroundColor Yellow
& .\create-release.ps1
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Upload to GitHub failed" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Success!
Write-Host "========================================" -ForegroundColor Green
Write-Host "Release Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Version $Version has been:" -ForegroundColor Green
Write-Host "  [OK] Built and packaged" -ForegroundColor Green
Write-Host "  [OK] Uploaded to GitHub releases" -ForegroundColor Green
Write-Host ""
Write-Host "You can view the release at:" -ForegroundColor Cyan
$releaseUrl = "https://github.com/Lylie87/TCS/releases/tag/v$Version"
Write-Host $releaseUrl -ForegroundColor Cyan
