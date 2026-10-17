@echo off
setlocal
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0preparar-php.ps1" -ProjectRoot "%~dp0."
if errorlevel 1 (
    echo Preparacao nao concluida. Confira a conexao com a internet e tente novamente.
    pause
    exit /b 1
)
echo PHP portatil preparado no pendrive.
pause
endlocal