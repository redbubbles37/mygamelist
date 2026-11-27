@echo off
rem Copia os seus dados do site antigo (XAMPP) para o programa. So le o site antigo.
rem Antes: ligue o MySQL no painel do XAMPP e feche o MyGameList.
chcp 65001 >nul
cd /d "%~dp0"
if not exist C:\xampp\php\php.exe (echo Nao achei o XAMPP em C:\xampp.& pause & exit /b 1)
C:\xampp\php\php.exe importar_do_xampp.php %*
echo.
pause
