<?php
/**
 * Create WordPress-compatible plugin ZIP
 * Using PHP's ZipArchive for maximum compatibility
 */

// Get version from wp-staff-diary.php
$content = file_get_contents('wp-staff-diary.php');
preg_match('/Version:\s*(.+)/', $content, $matches);
$version = trim($matches[1]);

echo "=========================================\n";
echo "Creating WordPress-Compatible Release\n";
echo "Version: $version\n";
echo "=========================================\n\n";

$zipFile = 'wp-staff-diary.zip';
$exclude = ['.git', '.gitignore', 'node_modules', '.DS_Store', 'create-release-simple.sh',
            'create-release.ps1', 'create-zip.ps1', 'create-zip-php.php', 'cleanup-git-history.ps1',
            'wp-staff-diary.zip', 'temp-release-*'];

// Remove existing ZIP
if (file_exists($zipFile)) {
    unlink($zipFile);
}

// Create ZIP
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
    die("Cannot create ZIP file\n");
}

echo "Step 1: Scanning files...\n";

// Recursively add files
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$count = 0;
foreach ($files as $file) {
    $filePath = $file->getPathname();
    $relativePath = substr($filePath, 2); // Remove './'

    // Check exclusions
    $skip = false;
    foreach ($exclude as $pattern) {
        if (strpos($relativePath, $pattern) === 0 ||
            preg_match('/' . preg_quote($pattern, '/') . '/', $relativePath)) {
            $skip = true;
            break;
        }
    }

    if (!$skip && $file->isFile()) {
        $zip->addFile($filePath, $relativePath);
        $count++;
    }
}

echo "Step 2: Added $count files to ZIP\n";

// Close ZIP
$zip->close();

$size = round(filesize($zipFile) / 1024, 2);
echo "\n✅ ZIP created: $zipFile ({$size}KB)\n";
echo "=========================================\n";
echo "This ZIP is optimized for WordPress compatibility\n";
echo "and should extract properly on your server.\n";
echo "=========================================\n";
