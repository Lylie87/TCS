# Git History Cleanup Script
# This script creates a clean branch without AI references in commit messages
#
# Usage: .\cleanup-git-history.ps1

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "Git History Cleanup - Remove AI References" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Get current branch
$currentBranch = git branch --show-current
Write-Host "Current branch: $currentBranch" -ForegroundColor Yellow

# Create a new clean branch from master
Write-Host ""
Write-Host "Step 1: Fetching latest from remote..." -ForegroundColor Green
git fetch origin master

Write-Host "Step 2: Creating new clean branch 'development' from master..." -ForegroundColor Green
git checkout -b development origin/master

# Get all changed files from the claude branch
Write-Host "Step 3: Getting all changes from $currentBranch..." -ForegroundColor Green
git checkout $currentBranch -- .

# Check if there are changes
$status = git status --porcelain
if ($status) {
    Write-Host "Step 4: Committing all changes with clean message..." -ForegroundColor Green

    # Create a professional commit message
    $commitMessage = @"
Release v2.0.23: Complete job management system upgrade

Major Features Added:
- Customer database with UK address format
- Order numbering system with customizable prefix
- Comprehensive financial tracking and payment management
- Accessories system with pricing
- Fitting cost field for separate labor charges
- VAT calculations with configurable rate
- PDF job sheet generation (TCPDF)
- Fitter assignment and management
- GitHub auto-update support for private repositories
- Calendar and list view interfaces
- Advanced filtering and search capabilities
- Image upload and management per job
- Real-time financial calculations
- Company branding and customization

Technical Improvements:
- Complete database schema with 6 custom tables
- Automated database migrations
- RESTful AJAX API
- WordPress media library integration
- Responsive admin interface
- Comprehensive settings management
- Enhanced security with nonce verification
- Output buffer management for clean AJAX responses

Bug Fixes:
- Fixed PDF download nonce verification
- Fixed database table creation issues
- Fixed fitting cost calculations in totals
- Removed dangerous table dropping code
- Fixed duplicate UNIQUE constraints
- Enhanced error handling and logging

Files: 50+ files modified/created
Author: Alex Lyle
"@

    git add -A
    git commit -m $commitMessage

    Write-Host ""
    Write-Host "================================================" -ForegroundColor Green
    Write-Host "SUCCESS: Clean branch 'development' created!" -ForegroundColor Green
    Write-Host "================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "1. Review the commit with: git log -1" -ForegroundColor White
    Write-Host "2. Push to GitHub: git push -u origin development" -ForegroundColor White
    Write-Host "3. Set 'development' as default branch on GitHub" -ForegroundColor White
    Write-Host "4. Delete the old claude branch (optional)" -ForegroundColor White
    Write-Host ""
    Write-Host "To delete the old branch:" -ForegroundColor Yellow
    Write-Host "  git branch -D $currentBranch" -ForegroundColor White
    Write-Host "  git push origin --delete $currentBranch" -ForegroundColor White
    Write-Host ""
} else {
    Write-Host "No changes detected. All files are already on master." -ForegroundColor Yellow
    Write-Host "The development branch has been created from master." -ForegroundColor Yellow
}

Write-Host "Current branch: $(git branch --show-current)" -ForegroundColor Cyan
