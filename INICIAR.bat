@echo off
setlocal
set "ROOT=%~dp0"
set "PHP_HOME=%ROOT%runtime\php"
set "PHP_EXE=%PHP_HOME%\php.exe"
set "PORTABLE=1"

if not exist "%PHP_EXE%" (
    where php >nul 2>nul
    if errorlevel 1 (
        echo PHP nao encontrado. Execute PREPARAR_PENDRIVE.bat com internet primeiro.
        pause
        exit /b 1
    )
    set "PHP_EXE=php"
    set "PORTABLE=0"
)

if "%PORTABLE%"=="1" (
    "%PHP_EXE%" -c "%PHP_HOME%\php.ini" -d extension_dir="%PHP_HOME%\ext" -d extension=pdo_sqlite -d extension=sqlite3 -r "if (!extension_loaded('pdo_sqlite')) exit(1);"
) else (
    "%PHP_EXE%" -r "if (!extension_loaded('pdo_sqlite')) exit(1);"
)
if errorlevel 1 (
    echo PHP encontrado, mas a extensao PDO SQLite nao esta disponivel.
    echo Execute PREPARAR_PENDRIVE.bat ou habilite pdo_sqlite no PHP instalado.
    pause
    exit /b 1
)

if not exist "%ROOT%data" mkdir "%ROOT%data"
if "%PORTABLE%"=="1" (
    start "Oficina - servidor local" /D "%ROOT%" "%PHP_EXE%" -c "%PHP_HOME%\php.ini" -d extension_dir="%PHP_HOME%\ext" -d extension=pdo_sqlite -d extension=sqlite3 -S 127.0.0.1:8000 -t "%ROOT%public"
) else (
    start "Oficina - servidor local" /D "%ROOT%" "%PHP_EXE%" -S 127.0.0.1:8000 -t "%ROOT%public"
)
"%SystemRoot%\System32\timeout.exe" /t 2 /nobreak >nul
start "" "http://127.0.0.1:8000"
echo Para encerrar, feche a janela "Oficina - servidor local" com Ctrl+C.
endlocal