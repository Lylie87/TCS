# Create proper ZIP with forward slashes for Linux compatibility
Add-Type -Assembly System.IO.Compression.FileSystem
Add-Type -Assembly System.IO.Compression

$sourceDir = "C:\Users\alexl\TCS"
$zipPath = "C:\Users\alexl\TCS\wp-staff-diary.zip"

# Remove existing zip
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

# Create new zip
$zip = [System.IO.Compression.ZipFile]::Open($zipPath, 'Create')

# Function to add files with forward slashes
function Add-FileToZip {
    param($FilePath, $ZipPath, $ZipArchive)

    # Convert backslashes to forward slashes
    $entryName = $ZipPath -replace '\\', '/'

    Write-Host "Adding: $entryName"

    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
        $ZipArchive,
        $FilePath,
        $entryName,
        [System.IO.Compression.CompressionLevel]::Optimal
    ) | Out-Null
}

# Add all files with proper paths
$files = @(
    @{Source="wp-staff-diary.php"; Zip="wp-staff-diary/wp-staff-diary.php"},
    @{Source="uninstall.php"; Zip="wp-staff-diary/uninstall.php"},
    @{Source="README.md"; Zip="wp-staff-diary/README.md"},

    @{Source="admin\class-admin.php"; Zip="wp-staff-diary/admin/class-admin.php"},
    @{Source="admin\views\calendar-view.php"; Zip="wp-staff-diary/admin/views/calendar-view.php"},
    @{Source="admin\views\dashboard-widget.php"; Zip="wp-staff-diary/admin/views/dashboard-widget.php"},
    @{Source="admin\views\my-diary.php"; Zip="wp-staff-diary/admin/views/my-diary.php"},
    @{Source="admin\views\settings.php"; Zip="wp-staff-diary/admin/views/settings.php"},
    @{Source="admin\views\staff-overview.php"; Zip="wp-staff-diary/admin/views/staff-overview.php"},

    @{Source="assets\css\admin.css"; Zip="wp-staff-diary/assets/css/admin.css"},
    @{Source="assets\js\admin.js"; Zip="wp-staff-diary/assets/js/admin.js"},
    @{Source="assets\images\staff-daily-job-planner-logo.svg"; Zip="wp-staff-diary/assets/images/staff-daily-job-planner-logo.svg"},

    @{Source="includes\class-activator.php"; Zip="wp-staff-diary/includes/class-activator.php"},
    @{Source="includes\class-database.php"; Zip="wp-staff-diary/includes/class-database.php"},
    @{Source="includes\class-deactivator.php"; Zip="wp-staff-diary/includes/class-deactivator.php"},
    @{Source="includes\class-loader.php"; Zip="wp-staff-diary/includes/class-loader.php"},
    @{Source="includes\class-upgrade.php"; Zip="wp-staff-diary/includes/class-upgrade.php"},
    @{Source="includes\class-wp-staff-diary.php"; Zip="wp-staff-diary/includes/class-wp-staff-diary.php"},

    @{Source="public\class-public.php"; Zip="wp-staff-diary/public/class-public.php"}
)

foreach ($file in $files) {
    $fullPath = Join-Path $sourceDir $file.Source
    if (Test-Path $fullPath) {
        Add-FileToZip -FilePath $fullPath -ZipPath $file.Zip -ZipArchive $zip
    } else {
        Write-Host "WARNING: File not found: $fullPath" -ForegroundColor Yellow
    }
}

$zip.Dispose()
Write-Host "`nZIP created successfully: $zipPath" -ForegroundColor Green
