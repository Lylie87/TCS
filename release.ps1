# Complete Release Script for Staff Daily Job Planner
# This script handles the entire release process:
# 1. Clean up old dist folder
# 2. Pull latest changes from GitHub
# 3. Build the distribution package
# 4. Upload to GitHub releases

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Staff Diary - Complete Release Process" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Step 1: Clean up dist folder
Write-Host "Step 1: Cleaning up old dist folder..." -ForegroundColor Yellow
$distDir = Join-Path (Get-Location) "dist"
if (Test-Path $distDir) {
    Remove-Item $distDir -Recurse -Force
    Write-Host "  ✓ Dist folder cleaned" -ForegroundColor Green
} else {
    Write-Host "  ✓ No dist folder to clean" -ForegroundColor Green
}
Write-Host ""

# Step 2: Pull latest changes
Write-Host "Step 2: Pulling latest changes from GitHub..." -ForegroundColor Yellow
try {
    git pull origin claude/work-on-master-01NamkLhSA2hUnsVz57p5SaM
    if ($LASTEXITCODE -ne 0) {
        throw "Git pull failed"
    }
    Write-Host "  ✓ Latest changes pulled successfully" -ForegroundColor Green
} catch {
    Write-Host "  ✗ Failed to pull changes: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please resolve git issues and try again." -ForegroundColor Yellow
    exit 1
}
Write-Host ""

# Step 3: Build distribution package
Write-Host "Step 3: Building distribution package..." -ForegroundColor Yellow
try {
    & .\build-release.ps1
    if ($LASTEXITCODE -ne 0) {
        throw "Build failed"
    }
    Write-Host "  ✓ Build completed successfully" -ForegroundColor Green
} catch {
    Write-Host "  ✗ Build failed: $_" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 4: Upload to GitHub releases
Write-Host "Step 4: Uploading to GitHub releases..." -ForegroundColor Yellow
try {
    & .\create-release.ps1
    if ($LASTEXITCODE -ne 0) {
        throw "Release upload failed"
    }
    Write-Host "  ✓ Release uploaded successfully" -ForegroundColor Green
} catch {
    Write-Host "  ✗ Release upload failed: $_" -ForegroundColor Red
    exit 1
}
Write-Host ""

Write-Host "========================================" -ForegroundColor Green
Write-Host "Release Process Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Your plugin is now live on GitHub!" -ForegroundColor Cyan
Write-Host "You can update it in WordPress by:" -ForegroundColor Cyan
Write-Host "  1. Go to Plugins page" -ForegroundColor White
Write-Host "  2. Click Clear Cache & Refresh" -ForegroundColor White
Write-Host "  3. Click Update Now" -ForegroundColor White
Write-Host ""
