param([Parameter(Mandatory = $true)][string]$ProjectRoot)

$ErrorActionPreference = 'Stop'
$runtimePath = Join-Path $ProjectRoot 'runtime\php'
$phpPath = Join-Path $runtimePath 'php.exe'

if (Test-Path $phpPath) {
    Write-Host 'PHP portatil ja esta instalado.'
    exit 0
}

[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$downloadPage = Invoke-WebRequest -Uri 'https://windows.php.net/download/' -UseBasicParsing
$versionMatches = [regex]::Matches($downloadPage.Content, 'php-(8\.\d+\.\d+)-nts-Win32-vs17-x64\.zip')
if ($versionMatches.Count -eq 0) {
    throw 'Nao encontrei uma versao PHP 8 x64 NTS no site oficial.'
}

$version = $versionMatches | ForEach-Object { [version]$_.Groups[1].Value } | Sort-Object -Descending | Select-Object -First 1
$archiveName = "php-$version-nts-Win32-vs17-x64.zip"
$archivePath = Join-Path $ProjectRoot $archiveName
$downloadUrl = "https://windows.php.net/downloads/releases/$archiveName"
$runtimeParent = Split-Path $runtimePath -Parent
New-Item -ItemType Directory -Path $runtimeParent -Force | Out-Null

Write-Host "Baixando PHP $version para o pendrive..."
Invoke-WebRequest -Uri $downloadUrl -OutFile $archivePath -UseBasicParsing
New-Item -ItemType Directory -Path $runtimePath -Force | Out-Null
Expand-Archive -Path $archivePath -DestinationPath $runtimePath -Force
Remove-Item $archivePath -Force

$developmentIni = Join-Path $runtimePath 'php.ini-development'
$phpIni = Join-Path $runtimePath 'php.ini'
if (Test-Path $developmentIni) {
    Copy-Item $developmentIni $phpIni -Force
}
if (-not (Test-Path $phpPath)) {
    throw 'O arquivo php.exe nao apareceu apos extrair o pacote oficial.'
}

Write-Host "PHP $version instalado em runtime\php. Agora execute INICIAR.bat."
Write-Host 'Se o Windows pedir, instale o Microsoft Visual C++ Redistributable 2015-2022 x64.'