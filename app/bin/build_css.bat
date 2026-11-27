@echo off
rem Gera o CSS do Tailwind (public\assets\css\app.css). So e preciso se voce mudar classes nas telas.
rem   bin\build_css.bat          -> compila e minifica uma vez
rem   bin\build_css.bat watch    -> fica recompilando enquanto voce edita
cd /d "%~dp0.."
set "TW=tools\tailwindcss.exe"
if not exist "%TW%" set "TW=C:\xampp\htdocs\mygamelist\tools\tailwindcss.exe"
if not exist "%TW%" (
  echo tailwindcss.exe nao encontrado. Baixe a versao 3.4.16 em https://github.com/tailwindlabs/tailwindcss/releases e coloque em tools\
  exit /b 1
)
if "%1"=="watch" (
  "%TW%" -c tailwind.config.js -i resources/css/app.css -o public/assets/css/app.css --watch
) else (
  "%TW%" -c tailwind.config.js -i resources/css/app.css -o public/assets/css/app.css --minify
)
