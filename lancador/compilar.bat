@echo off
rem Recria o MyGameList.exe com o compilador C# que ja vem no Windows.
cd /d "%~dp0"
if not exist icone.ico "%~dp0..\php\php.exe" -c "%~dp0..\php\php.ini" -d extension_dir="%~dp0..\php\ext" icone.php
"%WINDIR%\Microsoft.NET\Framework64\v4.0.30319\csc.exe" /nologo /target:winexe /optimize+ /codepage:65001 /win32icon:icone.ico /r:System.Windows.Forms.dll /r:System.Management.dll /out:..\MyGameList.exe Lancador.cs
if errorlevel 1 (echo Falhou.& exit /b 1)
echo MyGameList.exe criado.
