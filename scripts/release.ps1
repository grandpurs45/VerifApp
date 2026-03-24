param(
    [Parameter(Mandatory = $true)]
    [string]$Version,
    [string]$Date = (Get-Date -Format "yyyy-MM-dd")
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

if ($Version -notmatch '^\d+\.\d+\.\d+$') {
    throw "Version invalide. Format attendu: X.Y.Z"
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$changelogPath = Join-Path $repoRoot "CHANGELOG.md"
$versionPath = Join-Path $repoRoot "VERSION"

if (-not (Test-Path $changelogPath)) {
    throw "CHANGELOG.md introuvable."
}

$content = Get-Content -Path $changelogPath -Raw

$unreleasedHeader = "## [Unreleased]"
$unreleasedIndex = $content.IndexOf($unreleasedHeader)
if ($unreleasedIndex -lt 0) {
    throw "Section '## [Unreleased]' introuvable dans CHANGELOG.md"
}

$afterUnreleased = $unreleasedIndex + $unreleasedHeader.Length
$nextSectionIndex = $content.IndexOf("`n## [", $afterUnreleased)
if ($nextSectionIndex -lt 0) {
    $nextSectionIndex = $content.Length
}

$unreleasedBody = $content.Substring($afterUnreleased, $nextSectionIndex - $afterUnreleased)
$trimmedBody = $unreleasedBody.Trim()

if ([string]::IsNullOrWhiteSpace($trimmedBody) -or $trimmedBody -eq "### Added`n- Rien pour le moment.") {
    throw "La section Unreleased est vide. Rien a releaser."
}

$releasedSection = "## [$Version] - $Date`n`n$trimmedBody`n`n"
$newUnreleasedBody = "`n`n### Added`n- Rien pour le moment.`n`n"

$prefix = $content.Substring(0, $unreleasedIndex)
$suffix = $content.Substring($nextSectionIndex)

$newContent = $prefix + $unreleasedHeader + $newUnreleasedBody + $releasedSection + $suffix.TrimStart("`r","`n")
Set-Content -Path $changelogPath -Value $newContent -NoNewline

Set-Content -Path $versionPath -Value $Version -NoNewline

Write-Host "Release preparee: v$Version ($Date)"
Write-Host "Fichiers mis a jour:"
Write-Host " - VERSION"
Write-Host " - CHANGELOG.md"
Write-Host ""
Write-Host "Prochaines commandes:"
Write-Host "git add VERSION CHANGELOG.md"
Write-Host "git commit -m ""chore(release): $Version"""
Write-Host "git tag -a v$Version -m ""Release $Version - $Date"""
Write-Host "git push origin main --tags"
