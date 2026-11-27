@echo off
rem Monta a pasta php\ (PHP portatil) a partir de um PHP 8.2 para Windows (x64, Thread Safe).
rem Uso:  preparar-php.bat "C:\caminho\do\php"      (sem caminho, usa C:\xampp\php)
rem Download oficial: https://windows.php.net/download/
setlocal
cd /d "%~dp0"
set "ORIGEM=%~1"
if "%ORIGEM%"=="" set "ORIGEM=C:\xampp\php"
if not exist "%ORIGEM%\php.exe" (
  echo Nao achei o php.exe em "%ORIGEM%".
  echo Baixe o PHP 8.2 "x64 Thread Safe" em https://windows.php.net/download/ , extraia e rode:
  echo     preparar-php.bat "C:\pasta\onde\extraiu"
  exit /b 1
)
if not exist php\ext mkdir php\ext
for %%F in (php.exe php8ts.dll libsqlite3.dll libcrypto-3-x64.dll libssl-3-x64.dll libsodium.dll license.txt) do (
  if exist "%ORIGEM%\%%F" (copy /y "%ORIGEM%\%%F" php\ >nul) else (echo Aviso: %%F nao existe na origem.)
)
for %%F in (php_pdo_sqlite.dll php_mbstring.dll php_gd.dll php_fileinfo.dll php_openssl.dll) do (
  if exist "%ORIGEM%\ext\%%F" (copy /y "%ORIGEM%\ext\%%F" php\ext\ >nul) else (echo Aviso: ext\%%F nao existe na origem.)
)
rem bibliotecas do Visual C++, para rodar em computadores sem elas
for %%F in (vcruntime140.dll vcruntime140_1.dll msvcp140.dll) do (
  if exist "%SystemRoot%\System32\%%F" copy /y "%SystemRoot%\System32\%%F" php\ >nul
)
copy /y lancador\php.ini php\php.ini >nul
php\php.exe -c php\php.ini -d extension_dir="%~dp0php\ext" -r "foreach (['pdo_sqlite','mbstring','gd','fileinfo','openssl'] as $e) if (!extension_loaded($e)) { echo 'Falta a extensao ', $e, PHP_EOL; exit(1); } echo 'PHP ', PHP_VERSION, ' pronto.', PHP_EOL;"
if errorlevel 1 exit /b 1
echo Agora rode lancador\compilar.bat para criar o MyGameList.exe.
