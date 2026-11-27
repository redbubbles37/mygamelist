@echo off
rem Cria um atalho do MyGameList na area de trabalho.
powershell -NoProfile -Command "$s=(New-Object -ComObject WScript.Shell).CreateShortcut([Environment]::GetFolderPath('Desktop')+'\MyGameList.lnk'); $s.TargetPath='%~dp0..\MyGameList.exe'; $s.WorkingDirectory='%~dp0..'; $s.Save()"
echo Atalho criado na area de trabalho.
pause
