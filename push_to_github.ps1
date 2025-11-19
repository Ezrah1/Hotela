# GitHub Push Helper Script
# Replace YOUR_USERNAME with your actual GitHub username

$username = Read-Host "Enter your GitHub username"
if ([string]::IsNullOrWhiteSpace($username)) {
    Write-Host "Username is required. Exiting."
    exit 1
}

$repoName = "Hotela"
$remoteUrl = "https://github.com/$username/$repoName.git"

Write-Host "`nSetting up remote repository..."
Write-Host "Remote URL: $remoteUrl`n"

# Check if remote already exists
$existingRemote = git remote get-url origin 2>$null
if ($existingRemote) {
    Write-Host "Remote 'origin' already exists: $existingRemote"
    $update = Read-Host "Do you want to update it? (y/n)"
    if ($update -eq "y" -or $update -eq "Y") {
        git remote set-url origin $remoteUrl
        Write-Host "Remote updated successfully."
    } else {
        Write-Host "Keeping existing remote."
    }
} else {
    git remote add origin $remoteUrl
    Write-Host "Remote 'origin' added successfully."
}

Write-Host "`nVerifying remote..."
git remote -v

Write-Host "`nReady to push! Make sure you've created the repository on GitHub first."
Write-Host "Repository URL: https://github.com/$username/$repoName"
Write-Host "`nTo push, run:"
Write-Host "  git push -u origin master"
Write-Host "`nOr if your default branch is 'main':"
Write-Host "  git branch -M main"
Write-Host "  git push -u origin main"

