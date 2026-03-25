param(
    [string]$Version = "",
    [string]$OutputDir = "dist"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$repoRoot = Split-Path -Parent $PSScriptRoot
$versionFile = Join-Path $repoRoot "VERSION"

if ($Version -eq "") {
    if (-not (Test-Path $versionFile)) {
        throw "Fichier VERSION introuvable."
    }
    $Version = (Get-Content -Path $versionFile -Raw).Trim()
}

if ($Version -notmatch '^\d+\.\d+\.\d+$') {
    throw "Version invalide. Format attendu: X.Y.Z"
}

$tag = "v$Version"
$distPath = Join-Path $repoRoot $OutputDir
if (-not (Test-Path $distPath)) {
    New-Item -ItemType Directory -Path $distPath | Out-Null
}

$archivePath = Join-Path $distPath "verifapp-$tag.zip"
$shaPath = "$archivePath.sha256"

# Verifie que le tag existe localement, sinon fallback sur HEAD.
$tagExists = $false
try {
    git rev-parse --verify --quiet $tag | Out-Null
    if ($LASTEXITCODE -eq 0) {
        $tagExists = $true
    }
} catch {
    $tagExists = $false
}

if ($tagExists) {
    git archive --format=zip --output=$archivePath $tag
    $sourceRef = $tag
} else {
    git archive --format=zip --output=$archivePath HEAD
    $sourceRef = "HEAD"
}

$hash = Get-FileHash -Path $archivePath -Algorithm SHA256
"$($hash.Hash)  $(Split-Path -Leaf $archivePath)" | Set-Content -Path $shaPath

Write-Host "Package genere: $archivePath"
Write-Host "Checksum: $shaPath"
Write-Host "Source: $sourceRef"
