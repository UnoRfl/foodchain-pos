@echo off
title FoodChain POS - Installer
setlocal
set "HERE=%~dp0"
set "XAMPP="
for %%D in (C D E F G H) do if not defined XAMPP if exist "%%D:\xampp\htdocs" set "XAMPP=%%D:\xampp"
if not defined XAMPP set "XAMPP=C:\xampp"

echo.
echo  ==========================================
echo    FoodChain POS - one-click installer
echo  ==========================================
echo.

if not exist "%HERE%foodchain_pos\config.php" (
  echo  [!] Please EXTRACT the zip first ^(right-click the zip ^> Extract All^),
  echo      then double-click INSTALL.bat from the extracted folder.
  goto :end
)

if not exist "%XAMPP%\htdocs" (
  echo  [!] XAMPP was not found on this PC
  echo      Install XAMPP first: https://www.apachefriends.org
  echo      ^(keep the default folder C:\xampp^), then run this again.
  goto :end
)

echo  [1/4] Copying foodchain_pos into %XAMPP%\htdocs ...
xcopy "%HERE%foodchain_pos" "%XAMPP%\htdocs\foodchain_pos\" /E /I /Y /Q >nul
echo        Done.
echo.

echo  [2/4] Checking that MySQL is running ...
:checkmysql
"%XAMPP%\mysql\bin\mysql.exe" -u root -e "SELECT 1" >nul 2>&1
if errorlevel 1 (
  echo        MySQL is not running. Opening the XAMPP Control Panel...
  start "" "%XAMPP%\xampp-control.exe"
  echo        Click START next to Apache AND next to MySQL, wait until both turn green,
  echo        then come back here and
  pause
  goto :checkmysql
)
echo        MySQL is running.
echo.

echo  [3/4] Creating the database ...
"%XAMPP%\mysql\bin\mysql.exe" -u root -e "USE foodchain_pos" >nul 2>&1
if not errorlevel 1 (
  echo        The foodchain_pos database already exists - skipping, nothing changed.
) else (
  "%XAMPP%\mysql\bin\mysql.exe" -u root < "%XAMPP%\htdocs\foodchain_pos\database.sql"
  if errorlevel 1 ( echo  [!] Database import failed. See SETUP-INSTRUCTIONS.txt, Option B. & goto :end )
  echo        Database created.
)
echo.

echo  [4/4] Opening setup.php in your browser ...
echo        ^(Make sure Apache is green in the XAMPP Control Panel.^)
start "" "http://localhost/foodchain_pos/setup.php"
echo.
echo  ------------------------------------------
echo   NEXT: after the page says "Administrator account created",
echo   delete %XAMPP%\htdocs\foodchain_pos\setup.php
echo   then go to http://localhost/foodchain_pos/pos.php
echo.
echo   Username: admin
echo   Password: ChangeMe123!
echo  ------------------------------------------

:end
echo.
pause
