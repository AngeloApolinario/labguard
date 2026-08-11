@echo off
echo ===================================================
echo  1. Compiling terminal_lock.py with PyInstaller
echo ===================================================
pyinstaller --onedir --windowed --noconfirm --name labguard terminal_lock.py

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] PyInstaller build failed!
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo ===================================================
echo  2. Building Inno Setup Installer
echo ===================================================
"C:\Program Files (x86)\Inno Setup 6\ISCC.exe" LabGuard_Setup.iss

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Inno Setup compilation failed!
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo ===================================================
echo  3. Cleaning up build clutter...
echo ===================================================
:: Delete temporary PyInstaller build folder
if exist "build" rmdir /s /q "build"

:: Delete PyInstaller output folder (since Inno already packaged it)
if exist "dist" rmdir /s /q "dist"

:: Delete generated spec file
if exist "labguard.spec" del /q "labguard.spec"

echo ===================================================
echo  SUCCESS! Clean build complete.
echo  Installer ready in: Output\LabGuard_Client_Setup.exe
echo ===================================================
pause