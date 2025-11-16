# Build Release Script for Staff Daily Job Planner
# Creates a clean distribution folder and zip file ready for release

param(
    [string]$Version = $null
)

# Set error action preference
$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Staff Diary - Build Release Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Get version from plugin file if not provided
if (-not $Version) {
    $pluginFile = Get-Content "wp-staff-diary.php" -Raw
    if ($pluginFile -match 'Version:\s*(\d+\.\d+\.\d+)') {
        $Version = $Matches[1]
        Write-Host "Detected version: $Version" -ForegroundColor Green
    } else {
        Write-Host "Error: Could not detect version from wp-staff-diary.php" -ForegroundColor Red
        exit 1
    }
}

# Define paths
$rootDir = Get-Location
$distDir = Join-Path $rootDir "dist"
$pluginDir = Join-Path $distDir "wp-staff-diary"
$zipFile = Join-Path $distDir "wp-staff-diary-v$Version.zip"

# Clean up old dist folder if it exists
if (Test-Path $distDir) {
    Write-Host "Cleaning up old dist folder..." -ForegroundColor Yellow
    Remove-Item $distDir -Recurse -Force
}

# Create dist directory structure
Write-Host "Creating dist folder structure..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path $pluginDir -Force | Out-Null

# Define files/folders to exclude
$excludePatterns = @(
    '.git',
    '.gitignore',
    '.gitattributes',
    'dist',
    'build-release.ps1',
    'create-release.ps1',
    'DEVELOPER.md',
    'README.md',
    '.vscode',
    '.idea',
    'node_modules',
    '*.log',
    '.DS_Store',
    'Thumbs.db'
)

Write-Host "Copying plugin files (excluding development files)..." -ForegroundColor Yellow

# Get all items in root directory
$items = Get-ChildItem -Path $rootDir -Force

foreach ($item in $items) {
    # Check if item should be excluded
    $shouldExclude = $false
    foreach ($pattern in $excludePatterns) {
        if ($item.Name -like $pattern) {
            $shouldExclude = $true
            break
        }
    }

    if (-not $shouldExclude) {
        $destination = Join-Path $pluginDir $item.Name

        if ($item.PSIsContainer) {
            # Copy directory
            Write-Host "  Copying folder: $($item.Name)" -ForegroundColor Gray
            Copy-Item -Path $item.FullName -Destination $destination -Recurse -Force
        } else {
            # Copy file
            Write-Host "  Copying file: $($item.Name)" -ForegroundColor Gray
            Copy-Item -Path $item.FullName -Destination $destination -Force
        }
    } else {
        Write-Host "  Excluding: $($item.Name)" -ForegroundColor DarkGray
    }
}

Write-Host ""
Write-Host "Creating zip file..." -ForegroundColor Yellow
Write-Host "  Output: wp-staff-diary-v$Version.zip" -ForegroundColor Gray

# Remove old zip if exists
if (Test-Path $zipFile) {
    Remove-Item $zipFile -Force
}

# Create zip file
if (Get-Command Compress-Archive -ErrorAction SilentlyContinue) {
    # Use PowerShell's built-in Compress-Archive
    Compress-Archive -Path $pluginDir -DestinationPath $zipFile -Force
} else {
    # Fallback to .NET method
    Add-Type -Assembly System.IO.Compression.FileSystem
    [System.IO.Compression.ZipFile]::CreateFromDirectory($pluginDir, $zipFile)
}

# Get file size
$fileSize = (Get-Item $zipFile).Length
$fileSizeMB = [math]::Round($fileSize / 1MB, 2)

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Build Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Version: $Version" -ForegroundColor Cyan
Write-Host "Zip file: $zipFile" -ForegroundColor Cyan
Write-Host "File size: $fileSizeMB MB" -ForegroundColor Cyan
Write-Host ""
Write-Host "The release is ready in the 'dist' folder!" -ForegroundColor Green
Write-Host ""

# Show contents
Write-Host "Distribution folder contents:" -ForegroundColor Yellow
Get-ChildItem $distDir -Recurse | Select-Object FullName | Format-Table -AutoSize
