param([switch]$Browser)
$ErrorActionPreference = 'Stop'
$taskRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
Push-Location $taskRoot
try {
    npm run build
    if ($LASTEXITCODE -ne 0) { throw 'Frontend build failed.' }
    npm audit --audit-level=low
    if ($LASTEXITCODE -ne 0) { throw 'Frontend dependency audit failed.' }
    composer --working-dir=backend validate --strict
    if ($LASTEXITCODE -ne 0) { throw 'Composer manifest invalid.' }
    composer --working-dir=backend audit
    if ($LASTEXITCODE -ne 0) { throw 'Backend dependency audit failed.' }
    php backend/vendor/bin/pint --test backend/app backend/bootstrap/app.php backend/bootstrap/providers.php backend/database backend/routes backend/tests backend/scripts
    if ($LASTEXITCODE -ne 0) { throw 'PHP formatting check failed.' }
    composer --working-dir=backend test
    if ($LASTEXITCODE -ne 0) { throw 'Backend tests failed.' }
    if ($Browser) {
        npm run test:browser
        if ($LASTEXITCODE -ne 0) { throw 'Browser tests failed.' }
    }
    Write-Output 'All requested checks passed.'
} finally { Pop-Location }
