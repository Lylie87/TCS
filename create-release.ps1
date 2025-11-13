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

# Create temporary directory
$tempDir = "temp-release-$(Get-Random)"
$zipName = "wp-staff-diary.zip"

Write-Host "Step 1: Creating clean copy of plugin files..." -ForegroundColor Green
New-Item -ItemType Directory -Path "$tempDir\wp-staff-diary" -Force | Out-Null

# Copy all files except exclusions
$excludePatterns = @('.git', '.gitignore', 'node_modules', '.DS_Store', '*.sh', '*.ps1', 'temp-release-*', '*.zip')
Get-ChildItem -Path "." -Recurse | Where-Object {
    $item = $_
    $shouldExclude = $false
    foreach ($pattern in $excludePatterns) {
        if ($item.Name -like $pattern -or $item.FullName -match [regex]::Escape($pattern)) {
            $shouldExclude = $true
            break
        }
    }
    -not $shouldExclude
} | ForEach-Object {
    $relativePath = $_.FullName.Substring((Get-Location).Path.Length + 1)
    $targetPath = Join-Path "$tempDir\wp-staff-diary" $relativePath

    if ($_.PSIsContainer) {
        New-Item -ItemType Directory -Path $targetPath -Force | Out-Null
    } else {
        $targetDir = Split-Path -Parent $targetPath
        if (-not (Test-Path $targetDir)) {
            New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
        }
        Copy-Item $_.FullName -Destination $targetPath -Force
    }
}

Write-Host "Step 2: Creating ZIP archive..." -ForegroundColor Green

# Remove existing zip if it exists
if (Test-Path $zipName) {
    Remove-Item $zipName -Force
}

# Create ZIP archive
Compress-Archive -Path "$tempDir\wp-staff-diary" -DestinationPath $zipName -Force

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
