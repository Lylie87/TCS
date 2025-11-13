#!/bin/bash
#
# Simple WordPress Plugin GitHub Release Creator
# Creates ZIP in current directory for visibility
#

set -e

# Get version from wp-staff-diary.php
VERSION=$(grep "Version:" wp-staff-diary.php | head -1 | sed 's/.*Version: //' | tr -d ' \r\n')

echo "========================================="
echo "Creating Release: v${VERSION}"
echo "========================================="

# Create temporary directory for packaging
TEMP_DIR="./temp-release-$$"
ZIP_NAME="wp-staff-diary.zip"

echo ""
echo "Step 1: Creating clean copy of plugin files..."
mkdir -p "${TEMP_DIR}/wp-staff-diary"
cp -r ./* "${TEMP_DIR}/wp-staff-diary/" 2>/dev/null || true

# Remove files that shouldn't be in the release
echo "Step 2: Removing dev files..."
cd "${TEMP_DIR}/wp-staff-diary"
rm -rf .git .gitignore node_modules .DS_Store *.sh temp-release-* wp-staff-diary.zip 2>/dev/null || true

echo "Step 3: Creating zip archive in your directory..."
cd ..
zip -r "../${ZIP_NAME}" wp-staff-diary -q

# Go back to original directory
cd ..

# Cleanup temp directory
rm -rf "${TEMP_DIR}"

echo ""
echo "✅ ZIP created: ${ZIP_NAME} ($(du -h ${ZIP_NAME} | cut -f1))"
echo ""
echo "Step 4: Creating GitHub release..."
gh release create "v${VERSION}" \
    "${ZIP_NAME}" \
    --title "v${VERSION}" \
    --notes "Release v${VERSION} - Bug fixes and improvements" \
    --repo Lylie87/TCS

echo ""
echo "========================================="
echo "✅ Release v${VERSION} created successfully!"
echo "========================================="
echo ""
echo "View release: https://github.com/Lylie87/TCS/releases/tag/v${VERSION}"
echo ""
echo "The ZIP file '${ZIP_NAME}' is in your current directory."
echo "You can delete it after verifying the release, or keep it."
echo ""
echo "✅ Done!"
