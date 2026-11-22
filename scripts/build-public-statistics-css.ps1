$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Push-Location $root
try {
    $npm = if (Get-Command npm.cmd -ErrorAction SilentlyContinue) { 'npm.cmd' } else { 'npm' }
    & $npm exec --yes --package=tailwindcss@3.4.17 -- tailwindcss --input=resources/styles/public-statistics.css --output=public/assets/public-statistics.css --content=public/views/statistics_public.php,public/views/partials/verification_calendar.php,public/views/partials/verification_annual_chart.php --minify
    if ($LASTEXITCODE -ne 0) { throw 'Echec de compilation du suivi public.' }
} finally { Pop-Location }
