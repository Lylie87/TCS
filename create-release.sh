#!/bin/bash
#
# WordPress Plugin GitHub Release Creator
# Usage: ./create-release.sh [version] [release-notes]
# Example: ./create-release.sh 2.0.15 "Bug fixes and improvements"
#

set -e

# Get version from argument or from wp-staff-diary.php
if [ -z "$1" ]; then
    VERSION=$(grep "Version:" wp-staff-diary.php | head -1 | sed 's/.*Version: //' | tr -d ' \r\n')
else
    VERSION=$1
fi

# Get release notes from argument or use default
if [ -z "$2" ]; then
    NOTES="Release version ${VERSION}"
else
    NOTES="$2"
fi

echo "========================================="
echo "Creating Release: v${VERSION}"
echo "========================================="

# Create temporary directory for packaging
TEMP_DIR="/tmp/wp-staff-diary-release-$$"
ZIP_NAME="wp-staff-diary.zip"

echo ""
echo "Step 1: Creating clean copy of plugin files..."
mkdir -p "${TEMP_DIR}/wp-staff-diary"
cp -r ./* "${TEMP_DIR}/wp-staff-diary/" 2>/dev/null || true

# Remove files that shouldn't be in the release
cd "${TEMP_DIR}/wp-staff-diary"
rm -rf .git .gitignore node_modules .DS_Store *.sh 2>/dev/null || true

echo "Step 2: Creating zip archive..."
cd "${TEMP_DIR}"
zip -r "${ZIP_NAME}" wp-staff-diary -q

echo "Step 3: Uploading to GitHub..."
gh release create "v${VERSION}" \
    "${ZIP_NAME}" \
    --title "v${VERSION}" \
    --notes "${NOTES}" \
    --repo Lylie87/TCS

echo ""
echo "========================================="
echo "✅ Release v${VERSION} created successfully!"
echo "========================================="
echo ""
echo "Your WordPress plugin will now auto-update to this version."
echo "Users will see the update notification in their WordPress admin."
echo ""
echo "View release: https://github.com/Lylie87/TCS/releases/tag/v${VERSION}"
echo ""

# Cleanup
cd /
rm -rf "${TEMP_DIR}"

echo "✅ Done!"
