# Styling colors & helper
function Write-Header {
    param([string]$text)
    Write-Host "====================================================" -ForegroundColor Cyan -BackgroundColor Black
    Write-Host "          $text" -ForegroundColor Cyan -BackgroundColor Black
    Write-Host "====================================================" -ForegroundColor Cyan -BackgroundColor Black
}

Write-Header "GIT BACKDATED COMMIT UTILITY"

# Define dates for February 2026
$DATES = @(
  "2026-02-01T12:00:00",
  "2026-02-02T12:00:00",
  "2026-02-06T12:00:00",
  "2026-02-07T12:00:00"
)

$DATE_LABELS = @(
  "Feb 1st, 2026 (Sunday)",
  "Feb 2nd, 2026 (Monday)",
  "Feb 6th, 2026 (Friday)",
  "Feb 7th, 2026 (Saturday)"
)

# Check git status
$hasStaged = $false
$gitDiff = git diff --cached --quiet
if ($LASTEXITCODE -ne 0) {
    $hasStaged = $true
}

if ($hasStaged) {
    Write-Host "✓ Detected staged changes ready to be committed!" -ForegroundColor Green
} else {
    Write-Host "⚠ No staged changes detected." -ForegroundColor Yellow
    Write-Host "Commits will be created using the --allow-empty flag." -ForegroundColor White
    Write-Host "This is perfect for creating contribution history without modifying files." -ForegroundColor White
}

Write-Host "`nSelect a date option:" -ForegroundColor White
for ($i = 0; $i -lt $DATE_LABELS.Length; $i++) {
    Write-Host "  $($i + 1)) $($DATE_LABELS[$i]) ($($DATES[$i]))" -ForegroundColor Cyan
}
Write-Host "  5) All of the above (creates 4 commits, one for each date)" -ForegroundColor Cyan
Write-Host "  6) Exit" -ForegroundColor Cyan

$option = Read-Host "`nEnter option (1-6)"
$optionVal = 0
if (![int]::TryParse($option, [ref]$optionVal) -or $optionVal -lt 1 -or $optionVal -gt 6) {
    Write-Host "Invalid option. Exiting." -ForegroundColor Red
    exit
}

if ($optionVal -eq 6) {
    Write-Host "Exiting. No commits made."
    exit
}

$msg = Read-Host "Enter commit message [Default: 'backdate-commit']"
if ([string]::IsNullOrWhiteSpace($msg)) {
    $msg = "backdate-commit"
}

function Make-Commit {
    param(
        [string]$commitDate,
        [string]$commitMsg,
        [bool]$isStaged
    )
    
    Write-Host "`nCreating commit for date: $commitDate..." -ForegroundColor Yellow
    
    # Set Env Vars in PowerShell
    $env:GIT_AUTHOR_DATE = $commitDate
    $env:GIT_COMMITTER_DATE = $commitDate
    
    if ($isStaged) {
        git commit -m $commitMsg
    } else {
        git commit --allow-empty -m $commitMsg
    }
    
    # Clear Env Vars
    Remove-Item Env:\GIT_AUTHOR_DATE -ErrorAction SilentlyContinue
    Remove-Item Env:\GIT_COMMITTER_DATE -ErrorAction SilentlyContinue
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Successfully created commit!" -ForegroundColor Green
    } else {
        Write-Host "✗ Failed to create commit." -ForegroundColor Red
    }
}

if ($optionVal -eq 5) {
    Write-Host "`nPreparing to create 4 backdated commits..." -ForegroundColor Yellow
    for ($i = 0; $i -lt $DATES.Length; $i++) {
        # Check staged changes again before each commit
        git diff --cached --quiet
        $currentStaged = ($LASTEXITCODE -ne 0)
        Make-Commit -commitDate $DATES[$i] -commitMsg $msg -isStaged $currentStaged
    }
} else {
    $index = $optionVal -1
    Make-Commit -commitDate $DATES[$index] -commitMsg $msg -isStaged $hasStaged
}

Write-Host "`nDone! Remember to push your changes using: git push" -ForegroundColor Green
